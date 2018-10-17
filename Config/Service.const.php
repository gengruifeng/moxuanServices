<?php
#服务名称及端口定义
defined('APP_SERVICE_NAME') || define('APP_SERVICE_NAME', 'MX_APP_SERVICE');
defined('APP_SERVICE_PORT') || define('APP_SERVICE_PORT', 60001);




#微服务尽量通过supervisor统一启动, 每个微服务的daemon模式都要设置为false
#微服务URL地址定义
if(false == in_array(APP_ENV, array('production', 'testing', 'demo'))) {
    defined('MSF_BASE_URL') || define('MSF_BASE_URL', 'http://localhost');

    defined('APP_SERVICE_URL') || define('APP_SERVICE_URL', MSF_BASE_URL . ':' . APP_SERVICE_PORT);
} else {
    #线上地址通过nginx反向代理
    defined('MSF_BASE_URL') || define('MSF_BASE_URL', 'http://service.aitifen.com/' . APP_ENV);

    defined('APP_SERVICE_URL') || define('APP_SERVICE_URL', MSF_BASE_URL . '/appService');
}