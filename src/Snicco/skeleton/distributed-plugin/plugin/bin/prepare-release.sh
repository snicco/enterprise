# Anything that must be performed before an automatic release can be performed here.
function die { echo "$@" && exit 1; }

__dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

buildDir="plugin-build/PLUGIN_BASENAME"

sh "${__dir}"/scope-plugin.sh $buildDir || die "Failed to scope plugins"

# Building js/css assets
(export CI=true && npm install) || die "Could not install npm dependencies"
npm run prod || die "NPM production build failed"
mv dist $buildDir || die "Could not copy dist directory to build directory"

# Create cache and log dirs, alternatively we could also do cache warming if
# our configuration does not rely on values only available on the client WP install
mkdir -p "$buildDir/var/cache" || die "Could not create cache directory"
mkdir -p "$buildDir/var/log" || die "Could not create log directory"

## Copy resources and translations
mkdir -p "$buildDir/resources/views" || die "Could not create resources/views directory"
mkdir -p "$buildDir/resources/languages" || die "Could not create resources/languages directory"
cp -R resources/views "$buildDir/resources" || die "Could not copy views"
cp -R resources/languages "$buildDir/resources" || die "Could not copy languages"

