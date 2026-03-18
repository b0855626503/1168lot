const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

// flag ดูว่า prod หรือ dev
const isProd = mix.inProduction();

// 1) set publicPath
// - dev: build ใส่ใน src/Publishable/assets (ใช้ตอนพัฒนาแพ็กเกจ)
// - prod: build ลง public/ ของ Laravel หลัก (ให้เว็บใช้จริง)
if (isProd) {
    mix.setPublicPath('../../../public');
} else {
    mix.setPublicPath('src/Publishable/assets');
}

// 2) main js
// - dev: แค่วางไว้เป็น js/lineoa.js ใน publishable
// - prod: วางเป็น assets/lineoa/js/lineoa.js ใน public
mix.js(
    __dirname + '/src/Resources/assets/js/app.js',
    isProd ? 'assets/lineoa/js/lineoa.js' : 'js/lineoa.js'
).options({
    processCssUrls: false,
});

// 3) dev options
if (!isProd) {
    mix.sourceMaps();
}

// 4) prod options
if (isProd) {
    mix.version();
}

// 5) merge manifest
// - dev: merge ที่ src/Publishable/assets/mix-manifest.json (เอาไว้ publish ไปก็ได้)
// - prod: merge ที่ public/mix-manifest.json (ตัวนี้ Laravel ใช้)
mix.mergeManifest();

// 6) ปิด notification
mix.disableNotifications();
