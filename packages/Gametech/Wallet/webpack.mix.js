const mix = require("laravel-mix");

if (mix === 'undefined') {
    const {mix} = require("laravel-mix");
}

require("laravel-mix-merge-manifest");

// if (mix.inProduction()) {
//     var publicPath = 'publishable/assets';
//
// } else {
//     var publicPath = '../../../public/assets/main';
// }

var publicPath = '../../../public';

mix.setPublicPath(publicPath).mergeManifest();
mix.disableNotifications();

mix.inProduction();

mix
    .js([__dirname + '/src/Resources/assets/js/app.js'], 'js/app.js').extract(['vue'])
    // .js([__dirname + '/src/Resources/assets/js/web.js'], 'js/web.js').extract(['vue'])
    // .sass(__dirname + '/src/Resources/assets/sass/default.scss', 'css/default.css')
    .sass(__dirname + '/src/Resources/assets/sass/app.scss', 'css/web.css')
    .options({
        processCssUrls: false
    });

mix.autoload({
    jquery: ['$', 'jQuery', 'window.jQuery']
});


if (!mix.inProduction()) {
    mix.sourceMaps();
}

if (mix.inProduction()) {
    mix.version();
}
