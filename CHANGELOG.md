# Changelog

Each `## [version]` section becomes the release notes on GitHub.

## [2.0.3] - 2026-10-20

### Fixed
- Screens other plugins open at `/wp-admin/?...` (WooCommerce's email
  preview, Gravity Forms' entry column picker) were sent to the custom
  dashboard. Only a plain visit to `/wp-admin/` is redirected now.
- WooCommerce customers were offered the custom dashboard and support
  form. They now get neither, and the support form rejects their tickets.

### Security
- Company settings files are now `brands/<id>/brand.php` instead of
  `brand.json`, so visitors can't open them in a browser to read the
  support email addresses.

### Changed
- Tested up to WordPress 7.1.

## [2.0.2] - 2026-10-20

### Changed
- WordPress's update screens always show the B&B Media icon, to match the
  "BB Media Core" name WordPress shows there, whichever company is active.
- Only support@bandbmedia.com sees this plugin on the Plugins page, and its
  available updates on the Updates screen and in the update counts.
  Background auto-updates still run.

## [2.0.1] - 2026-10-20

### Fixed
- The Plugins page "Enable auto-updates" link now always shows for this
  plugin. WordPress hid it whenever GitHub couldn't be reached, such as
  while the repository is private.
- Added an `Update URI` header so WordPress.org never offers a different
  plugin with the same folder name as an update.

### Changed
- Brand Settings company picker: the radio button now sits next to the
  company name instead of above the logo.
- Brand Settings company list: the "Type" column is replaced by each
  company's support ticket recipients.

## [2.0.0] - 2026-10-20

The all-in-one release: one plugin for B&B Media, KNC Marketing and Pace
Marketing, with Brand Settings and Tutorials. See 1.7.0 and 1.7.1 below
for everything that changed since 1.6.6.

### Fixed
- KNC and Pace logos were invisible in Brand Settings (SVG logos had no size).

### Changed
- Dashboard > Tutorial Videos only appears while tutorials are enabled.

## [1.7.1] - 2026-10-20

### Changed
- Brand Settings and Tutorial Videos moved from the Settings menu to the
  Dashboard menu (still visible only to support@bandbmedia.com).
- KNC support tickets no longer go to chelsey@kncmarketingagency.com.

## [1.7.0] - 2026-10-20

One plugin now serves B&B Media, KNC Marketing and Pace Marketing (and any
company added later). It replaces the separate `knccore` and `pacecore` plugins.

### Added
- Brand choice on first activation, shown only to support@bandbmedia.com.
  Sites updating from BB Media Core 1.6.x are set to B&B Media automatically.
- Settings > Brand Settings panel (support@bandbmedia.com only): switch the
  active company, edit any company's name, logos, colors, support emails and
  blog feed, or add a new company.
- The Plugins page shows the active company's plugin name and description.
- Activating on a site that still runs the old `knccore` or `pacecore` plugin
  turns the old plugin off and pre-selects that company.
- Tutorials: an optional Dashboard > Tutorials page listing video links
  (e.g. Loom), turned on per site under Brand Settings > Features. The list is
  edited at Settings > Tutorial Videos (support@bandbmedia.com only), with
  drag-to-reorder. Needs no other plugin.
- Sites running the one-off ACF tutorials version have their videos copied
  over automatically, with tutorials turned on.
- New brand color "Accent" (tutorial icons).
- Test channel: sites with `define('BBCORE_UPDATE_CHANNEL', 'beta');` in
  wp-config.php are also offered pre-release versions (tags like
  `v1.7.0-beta.1`). All other sites ignore them.
- Support ticket emails include the site's address.
- The login logo links to the company's website instead of wordpress.org.

### Changed
- KNC's dashboard uses KNC navy and orange instead of B&B blue.
- Pace's login box uses Pace navy and green instead of KNC's navy and orange.
- KNC and Pace now update from GitHub like B&B Media, and get the B&B login
  button styling in their own colors.
- All three companies use the same login layout and fixes (B&B's centered
  box, plus KNC/Pace's form background, input focus and icon font fixes).

### Fixed
- Update check compared against a hard-coded "1.6.5" instead of the
  installed version.
- Update check called GitHub every time WordPress read its update list.
  It now runs only when WordPress refreshes updates, cached for 1 hour
  (10 minutes after a failed check). Dashboard > Updates > "Check again"
  always asks GitHub fresh.
- Removed the after-update "reactivate" step, which could never run: a
  deactivated plugin's code isn't loaded.
- Support form no longer accepts submissions from logged-out visitors.
- Dashboard paragraph spacing rule (`custom-dashboard-layout p`) was missing
  its leading dot.
- Release zip no longer contains `.DS_Store` / `__MACOSX` files.

## [1.6.6]

Last release before this repository. KNC Core and Pace Marketing Core were
maintained as separate copies.
