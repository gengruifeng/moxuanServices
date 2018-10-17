<?php
namespace MicroService;

class Config {
    protected static $uConfDir = '';
    protected static $confArray = array();
    protected static $uConfArray = array();
    protected static $initialized = false;

    public static function setUConfDir($uConfDir) {
        self::$uConfDir = $uConfDir;
    }

    protected static function getUConfDir() {
        return self::$uConfDir ?: dirname($_SERVER['SCRIPT_FILENAME']) . '/UserConfig';
    }

    public static function initialize() {
        if(self::$initialized) return;
        $confFile = defined('APP_CONFIG_FILE') ? APP_CONFIG_FILE : dirname($_SERVER['SCRIPT_FILENAME']) . '/config.php';
        if(false == file_exists($confFile)) {
            throw new \RuntimeException('配置文件config.php不存在！');
        }
        $confArray = require_once($confFile);

        foreach ($confArray as $key=>$value) {
            $key = strtoupper($key);
            self::$confArray[$key] = $value;
        }
        self::$initialized = true;
    }

    public static function get($key) {
        self::initialize();
        $key = strtoupper($key);
        if(false == isset(self::$confArray[$key])) {
            throw new \RuntimeException('配置项[' . $key . ']不存在！');
        }
        return self::$confArray[$key];
    }

    public static function loadUserConf($confName) {
        $nameParts = preg_split('#[\.\/]#', $confName);
        array_walk($nameParts, function (&$value){
            $value = ucfirst($value);
        });
        $filePath = implode(DIRECTORY_SEPARATOR,  $nameParts);
        $pathMd5 = md5($filePath);
        if(false == isset(self::$uConfArray[$pathMd5])) {
            $filePath = self::getUConfDir() . '/' . $filePath . '.conf.php';
            if(false == file_exists($filePath)) {
                throw new \RuntimeException('用户配置文件"' . $filePath . '"不存在！');
            }

            $value = require_once($filePath);
            self::$uConfArray[$pathMd5] = $value;
        }

        $value = self::$uConfArray[$pathMd5];
        if(is_callable($value)) {
            return call_user_func($value);
        }
        return $value;
    }


    public static function loadRootPathConf($confName) {
        $filePath = $confName;
        $pathMd5 = md5($filePath);
        if(false == isset(self::$uConfArray[$pathMd5])) {
            $filePath = $filePath . '.conf.php';
            if(false == file_exists($filePath)) {
                throw new \RuntimeException('用户配置文件"' . $filePath . '"不存在！');
            }
            $value = require_once($filePath);
            self::$uConfArray[$pathMd5] = $value;
        }

        $value = self::$uConfArray[$pathMd5];
        if(is_callable($value)) {
            return call_user_func($value);
        }
        return $value;
    }
}