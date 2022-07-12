const mix = require('laravel-mix');

const distDirectory = 'public';
const srcDirectory = 'assets';
const isProd = mix.inProduction();

mix.setPublicPath(distDirectory);

mix.options({
    processCssUrls: false,
});

const directoriesToCopy = [
    // 'fonts',
    // 'images'
];
directoriesToCopy.forEach(d => mix.copyDirectory(srcDirectory + '/' + d, distDirectory + '/' + d));

const cssEntryPoints = [
    // 'public.css',
    // 'admin.css',
];
cssEntryPoints.forEach(file => mix.postCss(srcDirectory + '/css/' + file, '/css', [
    require("tailwindcss"),
]))

const jsEntryPoints = [
    'admin.js',
];
jsEntryPoints.forEach(file => mix.js(srcDirectory + '/js/' + file, 'js'));


if (isProd) {
    mix.sourceMaps();
    mix.version();
}
