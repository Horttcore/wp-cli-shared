<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared;

final class WordPressData
{
    private ?string $themeSlug;

    public function __construct(?string $themeSlug = null)
    {
        $this->themeSlug = $themeSlug;
    }

    /**
     * @return array<string, string> slug => "Label (slug)"
     */
    public function getPostTypes(?array $args = null): array
    {
        $args ??= ['public' => true];
        $objects = get_post_types($args, 'objects');
        $result = [];

        foreach ($objects as $slug => $object) {
            $result[$slug] = sprintf('%s (%s)', $object->labels->name ?? $slug, $slug);
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function getPostTypesForPatterns(): array
    {
        return $this->getPostTypes([
            'public' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getQueryLoopPostTypes(): array
    {
        $types = get_post_types(['public' => true], 'objects');
        $result = [];

        foreach ($types as $slug => $object) {
            if ($slug === 'attachment') {
                continue;
            }

            $result[$slug] = sprintf('%s (%s)', $object->labels->name ?? $slug, $slug);
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function getTaxonomies(?array $args = null): array
    {
        $args ??= ['public' => true, 'show_ui' => true];
        $objects = get_taxonomies($args, 'objects');
        $result = [];

        foreach ($objects as $slug => $object) {
            $result[$slug] = sprintf('%s (%s)', $object->labels->name ?? $slug, $slug);
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, string> block name => block name
     */
    public function getRegisteredBlocks(?callable $filter = null): array
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $blocks = array_keys($registry->get_all_registered());
        sort($blocks);

        if ($filter !== null) {
            $blocks = array_values(array_filter($blocks, $filter));
        }

        return array_combine($blocks, $blocks) ?: [];
    }

    /**
     * @return array<string, string>
     */
    public function getBlocksForPatterns(): array
    {
        return $this->getRegisteredBlocks(function (string $blockType): bool {
            $excludePatterns = [
                '/^core\/template/',
                '/^core\/post-/',
                '/^core\/site-/',
                '/^core\/query-/',
                '/^core\/avatar/',
                '/^core\/loginout/',
            ];

            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $blockType)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @return array<string, string> slug => "Title (slug)"
     */
    public function getBlockCategories(): array
    {
        $categories = get_default_block_categories();
        $result = [];

        foreach ($categories as $category) {
            $slug = $category['slug'] ?? '';
            $title = $category['title'] ?? $slug;

            if ($slug !== '') {
                $result[$slug] = sprintf('%s (%s)', $title, $slug);
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function getPatternCategories(): array
    {
        $registry = \WP_Block_Pattern_Categories_Registry::get_instance();
        $categories = $registry->get_all_registered();
        $result = [];

        foreach ($categories as $category) {
            $slug = $category['name'] ?? '';

            if ($slug === '') {
                continue;
            }

            $label = $category['label'] ?? $slug;
            $result[$slug] = sprintf('%s (%s)', $label, $slug);
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function getTemplateTypes(): array
    {
        $types = get_default_block_template_types();
        $result = [];

        foreach ($types as $type) {
            $slug = $type['slug'] ?? '';
            $title = $type['title'] ?? $slug;

            if ($slug !== '') {
                $result[$slug] = sprintf('%s (%s)', $title, $slug);
            }
        }

        return $result;
    }

    /**
     * @return array<string, string> area => "Label (area)"
     */
    public function getTemplatePartAreas(): array
    {
        $result = [];

        if (class_exists('\WP_Block_Template_Part_Area_Registry')) {
            $registry = \WP_Block_Template_Part_Area_Registry::get_instance();
            $areas = $registry->get_all_registered();

            foreach ($areas as $key => $area) {
                if (! is_array($area)) {
                    continue;
                }

                $slug = isset($area['area']) && is_string($area['area'])
                    ? $area['area']
                    : (is_string($key) ? $key : '');

                if ($slug === '') {
                    continue;
                }

                $label = isset($area['label']) && is_string($area['label'])
                    ? $area['label']
                    : $slug;

                $result[$slug] = sprintf('%s (%s)', $label, $slug);
            }
        }

        if ($result === []) {
            $result = [
                'header' => 'Header (header)',
                'footer' => 'Footer (footer)',
                'sidebar' => 'Sidebar (sidebar)',
                'uncategorized' => 'Uncategorized (uncategorized)',
            ];
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, string> folder slug => "Plugin Name (slug)"
     */
    public function getInstalledPlugins(): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all = get_plugins();
        $result = [];

        foreach ($all as $file => $data) {
            $slug = dirname($file);

            if ($slug === '.') {
                $slug = basename($file, '.php');
            }

            $name = $data['Name'] ?? $slug;
            $result[$slug] = sprintf('%s (%s)', $name, $slug);
        }

        ksort($result);

        return $result;
    }

    public function getPostsPerPage(): int
    {
        return (int) get_option('posts_per_page', 10);
    }

    /**
     * @return array<string, string> slug => resolved size (e.g. "640px")
     */
    public function getViewportWidths(): array
    {
        $settings = $this->getMergedThemeJson()->get_data()['settings'] ?? [];
        $widths = [];

        foreach ($this->extractViewportSettings($settings) as $slug => $raw) {
            $resolved = $this->resolveValue((string) $raw, $settings, 0, [], $settings);

            if ($resolved !== '') {
                $widths[$slug] = $resolved;
            }
        }

        uasort(
            $widths,
            fn (string $a, string $b): int => ($this->parsePixelValue($a) ?? 0) <=> ($this->parsePixelValue($b) ?? 0)
        );

        return $widths;
    }

    /**
     * @return array<string, string> slug => "Mobile (640px)"
     */
    public function getViewportWidthsLabeled(): array
    {
        $result = [];

        foreach ($this->getViewportWidths() as $slug => $value) {
            $label = ucwords(str_replace(['-', '_'], ' ', $slug));
            $result[$slug] = sprintf('%s (%s)', $label, $value);
        }

        return $result;
    }

    public function parsePixelValue(string $value): ?float
    {
        $value = trim($value);

        if (preg_match('/^(-?\d+(?:\.\d+)?)(?:px)?$/i', $value, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    public function getMergedThemeJson(): \WP_Theme_JSON
    {
        if ($this->themeSlug !== null && $this->themeSlug !== '') {
            return \WP_Theme_JSON_Resolver::get_theme_data($this->themeSlug);
        }

        return \WP_Theme_JSON_Resolver::get_merged_data();
    }

    /**
     * @return array<int, ThemeToken>
     */
    public function getTokensByType(string $type): array
    {
        return array_values(array_filter(
            $this->getAllTokens(),
            fn (ThemeToken $token): bool => $token->type === $type
        ));
    }

    /**
     * @return array<int, ThemeToken>
     */
    public function getAllTokens(): array
    {
        $data = $this->getMergedThemeJson()->get_data();
        $settings = $data['settings'] ?? [];
        $tokens = [];

        $this->collectPresetTokens($settings, $tokens);
        $this->collectCustomTokens($settings['custom'] ?? [], '', $settings, $tokens);
        $this->collectLayoutTokens($settings['layout'] ?? [], $settings, $tokens);

        return $tokens;
    }

    /**
     * @param  array<int, ThemeToken>  $tokens
     */
    private function collectPresetTokens(array $settings, array &$tokens): void
    {
        $presetMap = [
            'color.palette' => 'color',
            'color.gradients' => 'gradient',
            'color.duotone' => 'duotone',
            'spacing.spacingSizes' => 'size',
            'typography.fontSizes' => 'size',
            'typography.fontFamilies' => 'font-family',
        ];

        foreach ($presetMap as $path => $type) {
            [$group, $key] = explode('.', $path);
            $items = $settings[$group][$key] ?? [];

            foreach ($items as $item) {
                $slug = $item['slug'] ?? '';
                $raw = $item['color'] ?? $item['gradient'] ?? $item['name'] ?? $item['size'] ?? $item['fontFamily'] ?? '';

                if (is_array($raw)) {
                    $raw = implode(' ', $raw);
                }

                if ($slug === '' || $raw === '') {
                    continue;
                }

                $resolved = $this->resolveValue((string) $raw, $settings, 0, [], $settings);
                $inferred = ThemeToken::inferValueType($resolved) ?: $type;

                $tokens[] = new ThemeToken(
                    slug: $slug,
                    type: $inferred,
                    raw: (string) $raw,
                    resolved: $resolved,
                    forms: $this->buildPresetForms($group, $key, $slug),
                );
            }
        }
    }

    /**
     * @param  array<int, ThemeToken>  $tokens
     */
    private function collectCustomTokens(array $custom, string $prefix, array $settings, array &$tokens): void
    {
        foreach ($custom as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $this->collectCustomTokens($value, $path, $settings, $tokens);

                continue;
            }

            $raw = (string) $value;
            $resolved = $this->resolveValue($raw, $settings, 0, [], $settings);
            $type = ThemeToken::inferValueType($resolved) ?? 'unknown';

            $tokens[] = new ThemeToken(
                slug: $path,
                type: $type,
                raw: $raw,
                resolved: $resolved,
                forms: $this->buildCustomForms($path),
            );
        }
    }

    /**
     * @param  array<int, ThemeToken>  $tokens
     */
    private function collectLayoutTokens(array $layout, array $settings, array &$tokens): void
    {
        foreach (['contentSize', 'wideSize'] as $key) {
            if (empty($layout[$key])) {
                continue;
            }

            $raw = (string) $layout[$key];
            $resolved = $this->resolveValue($raw, $settings, 0, [], $settings);
            $type = ThemeToken::inferValueType($resolved) ?? 'size';

            $tokens[] = new ThemeToken(
                slug: 'layout.' . $key,
                type: $type,
                raw: $raw,
                resolved: $resolved,
                forms: [$raw, $resolved],
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function buildPresetForms(string $group, string $key, string $slug): array
    {
        $cssVar = sprintf('var(--wp--preset--%s--%s)', $this->kebab($key), $this->kebab($slug));
        $presetRef = sprintf('var:preset|%s|%s', $key, $slug);

        return [$slug, $cssVar, $presetRef];
    }

    /**
     * @return array<int, string>
     */
    private function buildCustomForms(string $path): array
    {
        $cssPath = str_replace('.', '--', $path);
        $cssVar = 'var(--wp--custom--' . $cssPath . ')';
        $customRef = 'var:custom|' . str_replace('.', '|', $path);

        return [$path, $cssVar, $customRef];
    }

    private function resolveValue(string $value, array $settings, int $depth = 0, array $seen = [], ?array $rootSettings = null): string
    {
        $rootSettings ??= $settings;

        if ($depth > 10) {
            return $value;
        }

        $trimmed = trim($value);

        if (isset($seen[$trimmed])) {
            return $value;
        }

        $seen[$trimmed] = true;

        if (preg_match('/^var:preset\|([^|]+)\|(.+)$/', $trimmed, $matches)) {
            $presetKey = $matches[1];
            $slug = $matches[2];
            $group = $this->presetGroupForKey($presetKey);

            if ($group !== null) {
                $settingsKey = $this->presetSettingsKeyForKey($presetKey);
                $items = $rootSettings[$group][$settingsKey] ?? [];

                foreach ($items as $item) {
                    if (($item['slug'] ?? '') === $slug) {
                        $raw = $item['color'] ?? $item['gradient'] ?? $item['size'] ?? $item['fontFamily'] ?? '';

                        if (is_array($raw)) {
                            $raw = implode(' ', $raw);
                        }

                        return $this->resolveValue((string) $raw, $rootSettings, $depth + 1, $seen, $rootSettings);
                    }
                }
            }
        }

        if (preg_match('/^var:custom\|(.+)$/', $trimmed, $matches)) {
            $path = str_replace('|', '.', $matches[1]);
            $custom = $rootSettings['custom'] ?? [];
            $resolved = $this->getNestedValue($custom, $path);

            if ($resolved !== null) {
                return $this->resolveValue((string) $resolved, $rootSettings, $depth + 1, $seen, $rootSettings);
            }
        }

        if (preg_match('/^var\(--wp--custom--(.+)\)$/', $trimmed, $matches)) {
            $path = str_replace('--', '.', $matches[1]);
            $custom = $rootSettings['custom'] ?? [];
            $resolved = $this->getNestedValue($custom, $path);

            if ($resolved !== null) {
                return $this->resolveValue((string) $resolved, $rootSettings, $depth + 1, $seen, $rootSettings);
            }
        }

        return $trimmed;
    }

    private function getNestedValue(array $data, string $path): mixed
    {
        $parts = explode('.', $path);
        $current = $data;

        foreach ($parts as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return null;
            }

            $current = $current[$part];
        }

        return $current;
    }

    private function presetGroupForKey(string $key): ?string
    {
        return match ($key) {
            'color', 'gradient', 'duotone' => 'color',
            'spacing' => 'spacing',
            'font-size', 'fontSizes' => 'typography',
            default => null,
        };
    }

    private function presetSettingsKeyForKey(string $key): string
    {
        return match ($key) {
            'color' => 'palette',
            'gradient' => 'gradients',
            'duotone' => 'duotone',
            'spacing' => 'spacingSizes',
            'font-size', 'fontSizes' => 'fontSizes',
            default => $key,
        };
    }

    private function kebab(string $value): string
    {
        return str_replace('_', '-', $value);
    }

    /**
     * @return array<string, string>
     */
    private function extractViewportSettings(array $settings): array
    {
        $viewports = [];

        if (isset($settings['viewport']) && is_array($settings['viewport'])) {
            foreach ($settings['viewport'] as $slug => $value) {
                if (is_string($value)) {
                    $viewports[(string) $slug] = $value;
                }
            }
        }

        $customViewport = $settings['custom']['viewport'] ?? [];

        if (is_array($customViewport)) {
            foreach ($customViewport as $slug => $value) {
                if (is_string($value) && ! isset($viewports[$slug])) {
                    $viewports[(string) $slug] = $value;
                }
            }
        }

        return $viewports;
    }
}
