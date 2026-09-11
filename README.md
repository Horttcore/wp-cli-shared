# ralfhortt/wp-cli-shared

Shared support classes for Ralf Hortt WP-CLI packages.

## Contents

- `WordPressData` — live post types, taxonomies, blocks, pattern categories, theme.json tokens
- `ThemeResolver` — active theme paths, textdomain, block theme checks
- `PluginResolver` — plugin paths and textdomains
- `PromptHelper` — Laravel Prompts with WP-CLI flag fallbacks
- `FileGuard` — overwrite protection
- `Slug` — slug sanitization helpers

This package is a Composer library dependency used by `ralfhortt/wp-cli-css` and `ralfhortt/wp-cli-scaffold`.

Install consumers as WP-CLI packages and Composer will install this dependency automatically.

For local development with path repositories, install in this order:

```bash
wp package install /path/to/packages/wp-cli-shared
wp package install /path/to/packages/wp-cli-css
wp package install /path/to/packages/wp-cli-scaffold
```
