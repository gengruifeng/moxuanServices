<?php
error_reporting(E_ALL ^ E_NOTICE);
$localEnvFile = __DIR__ . '/Env/env.php';
if(file_exists($localEnvFile)) {
    require($localEnvFile);
}
defined('APP_ENV') || define('APP_ENV', 'production');
$localConstFile = __DIR__ . '/Env/' . APP_ENV . '.env.php';

#当运行环境为production,demo,testing三个值时,项目目录中的$localConstFile尽量不要定义过多的常量,统一通过Config目录中的env文件设置,
#开发环境中的env文件可以设置较多的内容
if(file_exists($localConstFile)) {
    require($localConstFile);
}

#PHP类库包含文件目录,
defined('COMMON_INCLUDE_DIR') || define('COMMON_INCLUDE_DIR', dirname(dirname(__DIR__)) . '/Include');

#通用配置文件所在目录
define('CONFIG_DIR', dirname(COMMON_INCLUDE_DIR) . '/Config');
#加载通用常量配置
require_once CONFIG_DIR . '/Const.php';
#从MicroService框架中加载composer加载类
$classLoader = require_once COMMON_INCLUDE_DIR . '/MicroService/vendor/autoload.php';

#注册基础通用类库的命名空间
$classLoader->addPsr4('Logic\\', dirname(COMMON_INCLUDE_DIR) . '/Logic');
#加载公共方法
require_once dirname(COMMON_INCLUDE_DIR) . '/Logic/Common/ServiceDef.php';

