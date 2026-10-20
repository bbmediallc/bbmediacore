# BB Media Core

The WordPress plugin behind every site B&B Media manages, including the
sites it white-labels for KNC Marketing and Pace Marketing. Each site is set
to one company, and that company's name, logo, colors, login page, support
email and blog feed are used everywhere.

## Folder layout

```
plugin/                      Everything that ships. Becomes bbmediacore/ in the zip.
  custom-dashboard-plugin.php  Main file: version number and loader. Never rename.
  readme.txt                   WordPress readme (keep "Stable tag" in sync).
  includes/                    The PHP, one file per feature.
  brands/<id>/                 One folder per company: brand.json, logo, icon.
  assets/                      CSS, JavaScript and the shared login photo.
scripts/build.sh             Builds dist/bbmediacore.zip.
.github/workflows/           Checks the code and publishes releases on GitHub.
CHANGELOG.md                 What changed in each version (becomes the release notes).
```

## How a site gets its brand

- **New install:** activate the plugin while logged in as support@bandbmedia.com.
  A screen asks which company the site is for.
- **Updating from 1.6.x B&B Media Core:** set to B&B Media automatically.
- **Replacing an old KNC Core / Pace Marketing Core:** upload and activate
  `bbmediacore.zip`. The old plugin is turned off and its company is
  pre-selected. Confirm it, then delete the old plugin.
- **Later:** Settings > Brand Settings (only visible to support@bandbmedia.com).

To allow another account on one site, add to that site's `wp-config.php`:

```php
define('BBCORE_ADMIN_EMAILS', 'support@bandbmedia.com, someone@example.com');
```

## Tutorials

Off by default. Turn on per site at Settings > Brand Settings > Features.
Add, reorder or remove videos at Settings > Tutorial Videos. Clients see them
at Dashboard > Tutorials. Both settings screens are visible only to
support@bandbmedia.com.

A site that ran the older ACF-based tutorials version has its videos copied
in automatically on update. After checking them, the ACF "tutorial_links"
field group (and ACF itself, if nothing else uses it) can be removed.

## Releasing a change

1. Make the change in `plugin/`.
2. Pick the new version number (1.7.0 → 1.7.1 for fixes, 1.8.0 for features)
   and set it in all three places:
   - `Version:` in `plugin/custom-dashboard-plugin.php`
   - `BBCORE_VERSION` in `plugin/custom-dashboard-plugin.php`
   - `Stable tag:` in `plugin/readme.txt`
3. Add a `## [1.7.1]` section to `CHANGELOG.md`.
4. Commit, then tag and push:
   ```
   git tag v1.7.1
   git push origin main v1.7.1
   ```
5. GitHub checks the code, builds `bbmediacore.zip` and publishes the
   release. Sites see the update within about a day. WordPress checks
   twice a day, and the plugin asks GitHub at most every 6 hours.

To build the zip yourself: `bash scripts/build.sh` (output in `dist/`).

## Adding a company

On one site: Settings > Brand Settings > Add a company.

For every site: open that company in Brand Settings, copy the text at the
bottom, save it as `plugin/brands/<id>/brand.json`, add its `logo` and
`menu_icon` files to the same folder (file names only in the JSON, e.g.
`"logo": "logo.svg"`), and release an update.

## Things that must not change

- The zip folder name `bbmediacore` and main file name
  `custom-dashboard-plugin.php`. WordPress tracks the plugin by
  `bbmediacore/custom-dashboard-plugin.php`. Changing either one makes
  updates install a second copy, or deactivates the plugin.
- The GitHub repository `bbmediallc/bbmediacore` must stay **public**, and
  each release must have a `bbmediacore.zip` file. Sites check
  `releases/latest` without logging in.
- `Requires PHP: 5.6`: some sites may run old PHP. GitHub checks every
  change against PHP 5.6, 7.4 and 8.3.
