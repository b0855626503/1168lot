<?php
$basePath = __DIR__;

// โหลดไฟล์สำคัญสำหรับเริ่มระบบ Laravel
opcache_compile_file($basePath . '/bootstrap/app.php');
opcache_compile_file($basePath . '/vendor/autoload.php');