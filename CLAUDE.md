# BB Media Core: notes for Claude

Read README.md first. It is the source of truth for layout and releases.

- Installed on 200+ live client sites. Treat every change as a production release.
- Never rename `plugin/custom-dashboard-plugin.php` or the `bbmediacore` zip folder.
- Never change the menu slug `custom_dashboard` or the shortcodes `[latest_blog_posts]` and `[bbyear]`. Sites and CSS depend on them.
- PHP must run on 5.6: no `??`, no scalar or return type hints, no arrow functions, no `match`. CI lints on 5.6, 7.4 and 8.3.
- Prefix every function, option and transient with `bbcore_`. The old knccore/pacecore plugins can be active at the same time during a swap, and they use the old unprefixed names.
- Anything that differs between companies belongs in `bbcore_brand_fields()` (includes/brands.php) and each `brands/*/brand.json`, never hard-coded.
- A release means bumping the version in three places (see README), adding a CHANGELOG section, then tagging `vX.Y.Z`. `scripts/build.sh` enforces that the versions match.
- PHP is not installed on Austin's Mac. Rely on CI for linting, and ask before testing on a live site.
- Releases reach sites only while the GitHub repo is public; Austin manages its visibility by hand. Never change it yourself. Test releases use a `-beta.N` tag (published as a pre-release) and reach only sites with `BBCORE_UPDATE_CHANNEL` set to 'beta'.
