<?php
#这里定义需要覆盖的常量值,后续这里的值可能会通过搭建配置中心服务,从配置服务器中获取
#例如: defined('XXX') || define('XXX', ConfCenter::getConfig('XXX', APP_ENV));
#目前采用常量形式配置,开发阶段这里的常量要求可以被覆盖,所以定义形式要采用 defined('XXX') || define('XXX', 'YYY');的形式



