<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared;

final class ThemeToken
{
    /** @var array<int, string> */
    private static array $namedColors = [
        'red', 'green', 'blue', 'white', 'black', 'transparent',
        'currentcolor', 'inherit', 'initial', 'unset',
    ];

    /**
     * @param  array<int, string>  $forms
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $type,
        public readonly string $raw,
        public readonly string $resolved,
        public readonly array $forms,
        public readonly ?string $unit = null,
        public readonly ?float $numeric = null,
    ) {}

    public static function inferValueType(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $value)) {
            return 'color';
        }

        if (preg_match('/^(rgb|hsl|oklch|lab|lch)\(/i', $value)) {
            return 'color';
        }

        if (in_array(strtolower($value), self::$namedColors, true)) {
            return 'color';
        }

        if (str_contains($value, 'gradient(')) {
            return 'gradient';
        }

        if (preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vw|vh|ch|ex|svw|lvw|dvw)$/i', $value)) {
            return 'size';
        }

        if (str_starts_with($value, 'clamp(')) {
            return 'size';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function suggestionForms(): array
    {
        return array_values(array_unique(array_merge($this->forms, [$this->resolved, $this->slug])));
    }
}
