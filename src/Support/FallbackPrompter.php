<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared\Support;

final class FallbackPrompter
{
    public static function askText(string $label, string $default = '', bool $required = true): string
    {
        $value = self::readLine($label, $default);

        if ($required && $value === '') {
            \WP_CLI::error('Value is required.');
        }

        return $value;
    }

    public static function askConfirm(string $label, bool $default = false): bool
    {
        $suffix = $default ? ' [Y/n]' : ' [y/N]';
        $value = strtolower(self::readLine($label . $suffix, ''));

        if ($value === '') {
            return $default;
        }

        return in_array($value, ['y', 'yes'], true);
    }

    /**
     * @param  array<int, string>  $choices
     */
    public static function askChoice(string $label, array $choices, ?string $default = null): string
    {
        if (function_exists('cli\\menu')) {
            return (string) \cli\menu($choices, $default, $label);
        }

        foreach ($choices as $index => $choice) {
            \WP_CLI::log(sprintf('  [%d] %s', $index + 1, $choice));
        }

        $defaultChoice = $default ?? ($choices[0] ?? '1');
        $choice = self::readLine('Choose a number', $defaultChoice);
        $index = (int) $choice - 1;

        return $choices[$index] ?? (string) $defaultChoice;
    }

    /**
     * @param  array<int, string>  $choices
     * @return array<int, string>
     */
    public static function askMultiChoice(string $label, array $choices): array
    {
        $selected = [];

        \WP_CLI::log($label);

        while (true) {
            $pick = self::askChoice('Select an item', $choices);

            if ($pick !== '' && ! in_array($pick, $selected, true)) {
                $selected[] = $pick;
            }

            if (! self::askConfirm('Add another?', false)) {
                break;
            }
        }

        return $selected;
    }

    private static function readLine(string $label, string $default = ''): string
    {
        $prompt = $label;

        if ($default !== '') {
            $prompt .= ' [' . $default . ']';
        }

        $prompt .= ': ';
        fwrite(STDOUT, $prompt);

        $input = fgets(STDIN);

        if ($input === false) {
            return $default;
        }

        $value = trim($input);

        return $value !== '' ? $value : $default;
    }
}
