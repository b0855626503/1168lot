const mix = require("laravel-mix");

if (mix === 'undefined') {
    const {mix} = require("laravel-mix");
}

require("laravel-mix-merge-manifest");

// if (mix.inProduction()) {
//     var publicPath = 'publishable/assets';
// } else {
//     var publicPath = '../../../public/assets/admin';
// }
var publicPath = '../../../public';
// var publicPath = '../../../public/assets/admin';

mix.setPublicPath(publicPath).mergeManifest();
mix.disableNotifications();

mix.inProduction()

mix.js([__dirname + '/src/Resources/assets/js/app.js'], 'assets/admin/js/app.js').extract(['vue'])
    // .sass(__dirname + '/src/Resources/assets/sass/app.scss', 'css/web.css')
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
