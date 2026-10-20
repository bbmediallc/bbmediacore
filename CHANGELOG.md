# Changelog

Each `## [version]` section becomes the release notes on GitHub.

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
