const mix = require('laravel-mix');
require('laravel-mix-valet');

const distDirectory = 'dist';
const resourcesDirectory = 'resources';
const domain = 'snicco-enterprise.test'

const isProd = mix.inProduction();
const usingLaravelValet = true;


mix.setPublicPath(distDirectory);
mix.options({
    processCssUrls: false,
})

const directoriesToCopy = [
    'fonts',
    'images'
];
directoriesToCopy.forEach(d => mix.copyDirectory(resourcesDirectory + '/' + d, distDirectory + '/' + d));

const cssEntryPoints = [
    'public.css',
    'admin.css',
];
cssEntryPoints.forEach(file => mix.postCss(resourcesDirectory + '/css/' + file, '/css', [
    require("tailwindcss"),
]))

const jsEntryPoints = [
    'frontend.js',
    'admin.js',
];
jsEntryPoints.forEach(file => mix.js(resourcesDirectory + '/js/' + file, 'js'))

if (isProd) {
    mix.version();
    mix.sourceMaps();
}

if (!isProd) {

    if (usingLaravelValet) {
        // Hot module replacement for laravel valet.
        mix.valet({
            host: domain,
            port: 8080,
            https: true,
        })
    }

    browserSyncConfig = {
            files: [
                'src/**/*.php',
                'src/**/*.js',
                'src/**/*.css',
            ],
            open: false,
            proxy: domain
    };

    if (usingLaravelValet) {

        homeDir = process.env.HOME;

        browserSyncConfig.proxy = 'https://' + domain
        browserSyncConfig.host = domain
        browserSyncConfig.https = true
        // browserSyncConfig.open = 'external' // This needs to "external" not "local" for valet.
        browserSyncConfig.https = {
            key: homeDir + '/.config/valet/Certificates/' + domain + '.key',
            cert: homeDir + '/.config/valet/Certificates/' + domain + '.crt',
        }

        mix.browserSync(browserSyncConfig);

    }
}

