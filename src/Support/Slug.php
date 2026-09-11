<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared\Support;

final class Slug
{
    public static function fromTitle(string $title): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($title)) ?? '');

        return trim($slug, '-');
    }

    public static function sanitize(string $slug): string
    {
        $slug = sanitize_title($slug);

        if ($slug === '' || str_contains($slug, '..')) {
            \WP_CLI::error('Invalid slug.');
        }

        return $slug;
    }
}
