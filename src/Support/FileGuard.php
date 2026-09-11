<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared\Support;

final class FileGuard
{
    public static function assertCanWrite(string $path, array $assoc_args): void
    {
        if (! file_exists($path) && ! is_dir($path)) {
            return;
        }

        if (\WP_CLI\Utils\get_flag_value($assoc_args, 'force')) {
            return;
        }

        if (! PromptHelper::isInteractive($assoc_args)) {
            \WP_CLI::error(sprintf('"%s" already exists. Re-run with --force to overwrite.', $path));
        }

        $overwrite = PromptHelper::confirm(
            sprintf('"%s" already exists. Overwrite?', $path),
            default: false
        );

        if (! $overwrite) {
            \WP_CLI::log('Cancelled.');

            exit(0);
        }
    }

    public static function assertDirectoryWritable(string $directory, array $assoc_args): void
    {
        if (! is_dir($directory)) {
            return;
        }

        if (! self::directoryHasContents($directory)) {
            return;
        }

        self::assertCanWrite($directory, $assoc_args);
    }

    private static function directoryHasContents(string $directory): bool
    {
        $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

        return $iterator->valid();
    }
}
