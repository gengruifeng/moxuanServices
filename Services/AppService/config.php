<?php
$appConfig = require CONFIG_DIR . '/Config.php';

$appConfig['AUTOLOAD_CONFIGS'] = array(
    array('Com\\', __DIR__ . '/Libs'),
);


return $appConfig;
