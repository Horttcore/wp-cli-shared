<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared;

final class ThemeResolver
{
    private ?string $themeSlug;

    public function __construct(?string $themeSlug = null)
    {
        $this->themeSlug = $themeSlug;
    }

    public function getTheme(): \WP_Theme
    {
        if ($this->themeSlug !== null && $this->themeSlug !== '') {
            $theme = wp_get_theme($this->themeSlug);

            if (! $theme->exists()) {
                \WP_CLI::error(sprintf('Theme "%s" does not exist.', $this->themeSlug));
            }

            return $theme;
        }

        return wp_get_theme();
    }

    public function getThemePath(): string
    {
        return $this->getTheme()->get_stylesheet_directory();
    }

    public function getTextDomain(): string
    {
        $theme = $this->getTheme();
        $textdomain = (string) $theme->get('TextDomain');

        if ($textdomain === '' || strtoupper($textdomain) === 'TEXTDOMAIN') {
            $textdomain = $theme->get_stylesheet();
        }

        return $textdomain;
    }

    public function isBlockTheme(): bool
    {
        return $this->getTheme()->is_block_theme();
    }

    public function warnIfNotBlockTheme(): void
    {
        if (! $this->isBlockTheme()) {
            \WP_CLI::warning(
                'The active theme is not a block theme. PHP files in patterns/ are only auto-discovered for block themes.'
            );
        }
    }

    public function getBlocksPath(): string
    {
        $path = $this->getThemePath() . '/blocks';

        if (! is_dir($path)) {
            wp_mkdir_p($path);
        }

        return $path;
    }

    public function getPatternsPath(): string
    {
        $path = $this->getThemePath() . '/patterns';

        if (! is_dir($path)) {
            wp_mkdir_p($path);
        }

        return $path;
    }

    public function getTemplatesPath(): string
    {
        $path = $this->getThemePath() . '/templates';

        if (! is_dir($path)) {
            wp_mkdir_p($path);
        }

        return $path;
    }

    public function getPartsPath(): string
    {
        $path = $this->getThemePath() . '/parts';

        if (! is_dir($path)) {
            wp_mkdir_p($path);
        }

        return $path;
    }

    public function getStylesPath(): string
    {
        $path = $this->getThemePath() . '/styles';

        if (! is_dir($path)) {
            wp_mkdir_p($path);
        }

        return $path;
    }

    public function getThemeJsonPath(): string
    {
        return $this->getThemePath() . '/theme.json';
    }
}
