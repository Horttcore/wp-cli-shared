<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared;

final class Bootstrap
{
    public static function registerPromptFallback(): void
    {
        // No-op: prompts use WP-CLI-compatible stdin helpers in PromptHelper.
    }
}
