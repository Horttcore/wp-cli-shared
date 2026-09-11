<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared\Support;

final class PromptHelper
{
    public static function isInteractive(array $assoc_args = []): bool
    {
        if (\WP_CLI\Utils\get_flag_value($assoc_args, 'no-interaction')) {
            return false;
        }

        if (defined('WP_CLI') && class_exists('\WP_CLI')) {
            $runner = \WP_CLI::get_runner();

            if ($runner !== null && method_exists($runner, 'is_interactive') && ! $runner->is_interactive()) {
                return false;
            }
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty(STDIN);
        }

        if (function_exists('posix_isatty')) {
            return posix_isatty(STDIN);
        }

        return true;
    }

    public static function textOrFlag(
        array $assoc_args,
        string $flag,
        string $label,
        string $default = '',
        bool $required = true,
    ): string {
        $value = \WP_CLI\Utils\get_flag_value($assoc_args, $flag);

        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        if (! self::isInteractive($assoc_args)) {
            if ($default !== '' || ! $required) {
                return $default;
            }

            \WP_CLI::error(sprintf('Missing required --%s (non-interactive mode).', $flag));
        }

        return self::text($label, default: $default, required: $required);
    }

    public static function suggestOrFlag(
        array $assoc_args,
        string $flag,
        string $label,
        array|callable $options,
        string $default = '',
        bool $allowFreeText = false,
    ): string {
        $value = \WP_CLI\Utils\get_flag_value($assoc_args, $flag);

        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        if (! self::isInteractive($assoc_args)) {
            if ($default !== '') {
                return $default;
            }

            \WP_CLI::error(sprintf('Missing required --%s (non-interactive mode).', $flag));
        }

        return self::suggest($label, $options, default: $default, allowFreeText: $allowFreeText);
    }

    /**
     * @param  callable(string): array<int, string>|array<string, string>  $search
     * @return array<int, string>
     */
    public static function multisearchOrFlag(
        array $assoc_args,
        string $flag,
        string $label,
        callable $search,
        array $default = [],
    ): array {
        $value = \WP_CLI\Utils\get_flag_value($assoc_args, $flag);

        if ($value !== null && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
        }

        if (! self::isInteractive($assoc_args)) {
            return $default;
        }

        return self::multisearch($label, $search);
    }

    public static function confirmOrFlag(
        array $assoc_args,
        string $flag,
        string $label,
        bool $default = false,
    ): bool {
        $value = \WP_CLI\Utils\get_flag_value($assoc_args, $flag);

        if ($value !== null && $value !== '') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (! self::isInteractive($assoc_args)) {
            return $default;
        }

        return self::confirm($label, default: $default);
    }

    public static function text(string $label, string $default = '', bool $required = true): string
    {
        return SymfonyPrompter::askText($label, $default, $required);
    }

    public static function textarea(string $label, string $hint = ''): string
    {
        \WP_CLI::log($label);

        if ($hint !== '') {
            \WP_CLI::log($hint);
        }

        \WP_CLI::log('(Finish with an empty line)');

        $lines = [];

        while (true) {
            $line = fgets(STDIN);

            if ($line === false) {
                break;
            }

            $line = rtrim($line, "\r\n");

            if ($line === '' && $lines !== []) {
                break;
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    public static function confirm(string $label, bool $default = false): bool
    {
        return SymfonyPrompter::askConfirm($label, $default);
    }

    /**
     * @param  array<int, string>|array<string, string>|callable(string): array<int, string>|array<string, string>  $options
     */
    public static function suggest(
        string $label,
        array|callable $options,
        string $default = '',
        bool $allowFreeText = false,
    ): string {
        $resolved = self::resolveLabeledOptions($options, '');
        $choices = $resolved['choices'];
        $slugMap = $resolved['slugMap'];

        $defaultLabel = $default;

        if ($default !== '' && isset($slugMap[$default])) {
            $defaultLabel = $slugMap[$default];
        }

        $selection = SymfonyPrompter::askChoice(
            $label,
            $choices,
            $defaultLabel !== '' ? $defaultLabel : null,
            $allowFreeText
        );

        return self::resolveSlugFromLabel($selection, $slugMap);
    }

    /**
     * @param  callable(string): array<int, string>|array<string, string>  $search
     * @return array<int, string>
     */
    public static function multisearch(string $label, callable $search): array
    {
        $resolved = self::resolveLabeledOptions($search, '');
        $selections = SymfonyPrompter::askMultiChoice($label, $resolved['choices']);

        return array_values(array_map(
            function (string $selection) use ($resolved): string {
                if (ctype_digit($selection) && isset($resolved['choices'][(int) $selection])) {
                    $selection = $resolved['choices'][(int) $selection];
                }

                return self::resolveSlugFromLabel($selection, $resolved['slugMap']);
            },
            $selections
        ));
    }

    /**
     * @param  array<string, string>  $slugMap  label => slug
     */
    public static function resolveSlugFromLabel(string $selection, array $slugMap): string
    {
        if (isset($slugMap[$selection])) {
            return $slugMap[$selection];
        }

        $flipped = array_flip($slugMap);

        if (isset($flipped[$selection])) {
            return $selection;
        }

        return $selection;
    }

    /**
     * @param  array<int, string>|array<string, string>|callable(string): array<int, string>|array<string, string>  $options
     * @return array{choices: array<int, string>, slugMap: array<string, string>}
     */
    private static function resolveLabeledOptions(array|callable $options, string $search): array
    {
        $raw = is_callable($options) ? $options($search) : $options;

        if ($raw === []) {
            return ['choices' => [], 'slugMap' => []];
        }

        if (self::isAssociativeLabeledMap($raw)) {
            $choices = array_values($raw);
            $slugMap = [];

            foreach ($raw as $slug => $label) {
                $slugMap[(string) $label] = (string) $slug;
            }

            return ['choices' => $choices, 'slugMap' => $slugMap];
        }

        $choices = array_values(array_map('strval', $raw));
        $slugMap = [];

        foreach ($choices as $choice) {
            $slugMap[$choice] = $choice;
        }

        return ['choices' => $choices, 'slugMap' => $slugMap];
    }

    /**
     * @param  array<int|string, string>  $options
     */
    private static function isAssociativeLabeledMap(array $options): bool
    {
        if ($options === []) {
            return false;
        }

        return array_keys($options) !== range(0, count($options) - 1);
    }
}
