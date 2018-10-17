<?php
require_once __DIR__ . '/init.php';

#把服务注册类的命名空间注册到composer中
$classLoader->addPsr4('Registers\\', __DIR__ . '/Registers');

#这个常量会设置REDIS中缓存的前缀，全局redis前缀相同的项目缓存会根据这个常量进行隔离
#否则可能会出现A项目修改B项目的缓存值的问题
define('APP_NAME', APP_SERVICE_NAME);
#LOG日志等级可以在项目目录的env配置文件中定义,但是因为加载env文件时还没有加载composer加载器, 所以日志等级只能写数字常量
defined('APP_LOG_LEVEL')  || define('APP_LOG_LEVEL', \Monolog\Logger::WARNING);


#加载子服务注册文件
$services = require_once __DIR__ . '/services.php';
$listenHost = '0.0.0.0';

$settings = array(
    'daemonize'=>false
);

$listen = array($listenHost, APP_SERVICE_PORT);
$server = new \MicroService\Server($listen, $services, $settings);
$logger = new \MicroService\Logger();
#日志直接输出,可以通过supervisor抓取到日志内容
$logger->addLogHandler(new \Monolog\Handler\StreamHandler('php://output', APP_LOG_LEVEL));

$server->setLogger($logger);
$server->setClassLoader($classLoader);

#根据情况启用吧, 这个还没有经过测试
#$server->setCrontabService(__DIR__ . '/crontab.php');

#单文件配置目录,可以简单的通过 return array();做一些简单的配置
\MicroService\Config::setUConfDir(CONFIG_DIR . '/UserConfig');

$server->setBeforeInvoke(array(Logic\Common\ServiceContext::class, 'beforeInvoke'));
$server->setAfterInvoke(array(Logic\Common\ServiceContext::class, 'afterInvoke'));
$server->setErrorHandler(array(Logic\Common\ServiceContext::class, 'onSendError'));
$server->start();


