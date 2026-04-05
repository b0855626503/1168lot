<?php

return [
    'enabled' => env('SELF_UPDATER_ENABLED', false),
    'version_installed' => env('SELF_UPDATER_VERSION_INSTALLED', env('APP_VERSION', '4.1.0')),
    'decommissioned_message' => env(
        'SELF_UPDATER_DECOMMISSIONED_MESSAGE',
        'ระบบ self-update ถูกถอดออกจากแอปแล้ว ให้ใช้ deployment flow ภายนอกแทน'
    ),

];
