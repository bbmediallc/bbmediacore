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
- **Later:** Dashboard > Brand Settings (only visible to support@bandbmedia.com).

To allow another account on one site, add to that site's `wp-config.php`:

```php
define('BBCORE_ADMIN_EMAILS', 'support@bandbmedia.com, someone@example.com');
```

## Tutorials

Off by default. Turn on per site at Dashboard > Brand Settings > Features.
Add, reorder or remove videos at Dashboard > Tutorial Videos. Clients see them
at Dashboard > Tutorials. Both settings screens are visible only to
support@bandbmedia.com.

A site that ran the older ACF-based tutorials version has its videos copied
in automatically on update. After checking them, the ACF "tutorial_links"
field group (and ACF itself, if nothing else uses it) can be removed.

## Releasing a change

1. Make the change in `plugin/`.
2. Pick the new version number (2.0.0 → 2.0.1 for fixes, 2.1.0 for features)
   and set it in all three places:
   - `Version:` in `plugin/custom-dashboard-plugin.php`
   - `BBCORE_VERSION` in `plugin/custom-dashboard-plugin.php`
   - `Stable tag:` in `plugin/readme.txt`
3. Add a `## [2.0.1]` section to `CHANGELOG.md`.
4. Commit, then tag and push:
   ```
   git tag v2.0.1
   git push origin main v2.0.1
   ```
5. GitHub checks the code, builds `bbmediacore.zip` and publishes the
   release. This works while the repository is private.
6. Sites can only see the release while the repository is public. Use
   Dashboard > Updates > **Check again** (or your site manager's update
   check) to have a site look right away. Otherwise sites notice on their
   own within about a day. WordPress downloads the zip when it installs the
   update, so a site that hasn't finished updating before the repository is
   private again will fail to update.

### Testing a change on one site first

Add this line to the test site's `wp-config.php`:

```php
define('BBCORE_UPDATE_CHANNEL', 'beta');
```

Release a test version, e.g. version `2.0.1-beta.1`, tagged `v2.0.1-beta.1`.
Any tag with a dash becomes a GitHub pre-release, and only sites with the
line above are offered it. When it looks good, release `2.0.1` normally.

To build the zip yourself: `bash scripts/build.sh` (output in `dist/`).

## Adding a company

On one site: Dashboard > Brand Settings > Add a company.

For every site: open that company in Brand Settings, copy the text at the
bottom, save it as `plugin/brands/<id>/brand.json`, add its `logo` and
`menu_icon` files to the same folder (file names only in the JSON, e.g.
`"logo": "logo.svg"`), and release an update.

## Things that must not change

- The zip folder name `bbmediacore` and main file name
  `custom-dashboard-plugin.php`. WordPress tracks the plugin by
  `bbmediacore/custom-dashboard-plugin.php`. Changing either one makes
  updates install a second copy, or deactivates the plugin.
- The GitHub repository name `bbmediallc/bbmediacore`, and a
  `bbmediacore.zip` file on each release. Sites check it without logging in.
- `Requires PHP: 5.6`: some sites may run old PHP. GitHub checks every
  change against PHP 5.6, 7.4 and 8.3.
