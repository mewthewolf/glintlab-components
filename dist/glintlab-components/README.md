# GlintLab Components (WordPress plugin)

This plugin provides a couple of reusable frontend components as shortcodes:

- Team member card + modal: `[glintlab_team_member]`
- ABG feature grid + modal: `[glintlab_abg_features]`

## Demo data (Team Members)

This build of the plugin includes bundled demo Team Members and an importer.

- On plugin activation, if there are **no existing Team Members**, demo content is imported automatically.
- To re-run manually: in WordPress admin, go to `Tools` → `GlintLab Demo Import` and click `Import Demo Team Members`.

The importer will **skip** any Team Member post that already exists with the same title.

## GitHub auto-updates (private repo)

This plugin can update itself from a GitHub private repo release.

1) Ensure the repo has a release tagged like `v0.2.0` with an asset named `glintlab-components.zip`.
2) Configure the updater using either:
- WordPress admin: `Settings` → `GlintLab Components`, or
- Constants in `wp-config.php`:

```php
define('GLINTLAB_COMPONENTS_GITHUB_REPO', 'mewthewolf/glintlab-components');
define('GLINTLAB_COMPONENTS_GITHUB_TOKEN', 'YOUR_FINE_GRAINED_TOKEN_HERE');
```

The token needs access to that repository’s releases (repo read). Without the token, update checks are skipped.

## Shortcodes

### `[glintlab_team_member]`

Attributes:

- `name` (required)
- `role`
- `description` (supports basic HTML)
- `image_id` (preferred) or `image_url`
- `image_alt`
- `link_url` (optional; used for the name link)
- `max_width` (default: `520`)

Example:

```
[glintlab_team_member
  name="Misagh Naderi, PhD"
  role="Chief Executive Officer"
  image_url="https://example.com/avatar.png"
  description="Full bio here..."
  link_url="https://www.linkedin.com/in/..."
]
```

### `[glintlab_abg_features]`

Attributes:

- `heading` (default: `Capabilities`)
- `subheading` (default: `Tap a card to see details.`)
- `aria_label` (default: `ABG capabilities`)

Data can be customized via the `glintlab_components_abg_features_data` filter.
