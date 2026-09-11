<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared;

use RalfHortt\WpCliShared\Support\Slug;

final class PluginResolver
{
    public function getPluginsPath(): string
    {
        return WP_PLUGIN_DIR;
    }

    public function getPluginPath(string $slug): string
    {
        $slug = Slug::sanitize($slug);
        $path = $this->getPluginsPath() . '/' . $slug;

        if (! is_dir($path)) {
            \WP_CLI::error(sprintf('Plugin "%s" does not exist at %s.', $slug, $path));
        }

        return $path;
    }

    public function getPluginTextDomain(string $slug): string
    {
        $slug = Slug::sanitize($slug);
        $plugins = $this->getPluginData();

        if (! isset($plugins[$slug])) {
            return $slug;
        }

        $textdomain = (string) ($plugins[$slug]['TextDomain'] ?? '');

        return $textdomain !== '' ? $textdomain : $slug;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getPluginData(): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all = get_plugins();
        $bySlug = [];

        foreach ($all as $file => $data) {
            $slug = dirname($file);

            if ($slug === '.') {
                $slug = basename($file, '.php');
            }

            $bySlug[$slug] = $data;
        }

        return $bySlug;
    }
}
