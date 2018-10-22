<?php
/*
|--------------------------------------------------------------------------
| init rpc Framework
|--------------------------------------------------------------------------
|
*/
$localEnvFile = dirname(__DIR__) . '/Env/env.php';
if(file_exists($localEnvFile)) {
    require($localEnvFile);
}
defined('APP_ENV') || define('APP_ENV', 'production');
$localConstFile = dirname(__DIR__) . '/Env/' . APP_ENV . '.env.php';

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

#加载公共方法
require_once dirname(COMMON_INCLUDE_DIR) . '/Logic/Common/ServiceDef.php';

#注册基础通用类库的命名空间
$classLoader->addPsr4('Logic\\', dirname(COMMON_INCLUDE_DIR) . '/Logic');

define ('STORAGE_PATH', dirname (__DIR__).'/storage');

define ('PUBLIC_PATH', __DIR__);
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and bootstraps the application so it
| is ready to receive HTTP / Console requests from the environment.
|
*/

$app = require __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app->run();
