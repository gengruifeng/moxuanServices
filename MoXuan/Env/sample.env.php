<?php
//这个文件不要加到git版本库中
//项目中的env配置文件只允许提交production,testing,demo三种,
//但这三个文件在项目中也可以不存在

defined('DEFAULT_REDIS_DB') || define('DEFAULT_REDIS_DB', '11');
defined('DEFAULT_REDIS_PORT') || define('DEFAULT_REDIS_PORT', '6379');