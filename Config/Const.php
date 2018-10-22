<?php
require_once __DIR__ . '/Env.php';
$envFile = __DIR__ . '/Env/' . APP_ENV . '.env.php';
if(file_exists($envFile)) {
    require_once $envFile;
}

require_once __DIR__ . '/Service.const.php';
require_once __DIR__ . '/Database.const.php';
require_once __DIR__ . '/Redis.const.php';
require_once __DIR__ . '/Url.const.php';


