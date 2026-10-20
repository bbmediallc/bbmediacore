#!/usr/bin/env bash
# Builds dist/bbmediacore.zip from plugin/.
#
#   scripts/build.sh            build
#   scripts/build.sh v1.7.0     build, and fail unless the version is 1.7.0
#
# The zip's top folder must be "bbmediacore": that is the folder installed
# on every site, and a different name would install a second copy instead
# of updating.
set -euo pipefail

cd "$(dirname "$0")/.."

header_version=$(sed -n 's/^Version:[[:space:]]*//p' plugin/custom-dashboard-plugin.php | tr -d '\r')
constant_version=$(sed -n "s/^define('BBCORE_VERSION', '\([^']*\)');/\1/p" plugin/custom-dashboard-plugin.php)
readme_version=$(sed -n 's/^Stable tag:[[:space:]]*//p' plugin/readme.txt | tr -d '\r')

if [ "$header_version" != "$constant_version" ] || [ "$header_version" != "$readme_version" ]; then
    echo "Version mismatch. Make all three the same:" >&2
    echo "  Version: header in custom-dashboard-plugin.php  $header_version" >&2
    echo "  BBCORE_VERSION in custom-dashboard-plugin.php   $constant_version" >&2
    echo "  Stable tag: in readme.txt                       $readme_version" >&2
    exit 1
fi

if [ $# -gt 0 ] && [ "${1#v}" != "$header_version" ]; then
    echo "Tag $1 does not match plugin version $header_version." >&2
    exit 1
fi

for brand_dir in plugin/brands/*/; do
    [ -f "$brand_dir/brand.php" ] || { echo "Missing $brand_dir/brand.php" >&2; exit 1; }
done

rm -rf dist
mkdir -p dist/build
cp -R plugin dist/build/bbmediacore
find dist/build -name '.DS_Store' -delete

(cd dist/build && zip -qrX ../bbmediacore.zip bbmediacore)
rm -rf dist/build

echo "Built dist/bbmediacore.zip (version $header_version)"
