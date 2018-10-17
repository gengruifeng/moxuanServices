<?php
namespace MicroService;

class Redis {
    protected static $redisConns = array();
    protected $redis;

    /**
     * @param string $connName
     * @param bool   $isSubscribe
     * @return \Redis
     */
    public static function getRedis($connName='', $isSubscribe=false) {
        $pid = posix_getpid();
        defined('DEFAULT_REDIS') || define('DEFAULT_REDIS', 'DEFAULT_REDIS');
        if(false == $connName) {
            $connName = DEFAULT_REDIS;
        }
        $connName = strtoupper($connName);
        $key = md5($pid . ':' . $connName . ':' . abs($isSubscribe));
        if(false == isset(self::$redisConns[$key])) {
            $redis = new self($connName);
            self::$redisConns[$key] = $redis;
        }

        return self::$redisConns[$key];
    }

    protected function __construct($connName) {
        $redisConfigs = Config::get('REDIS_CONFIGS');
        if(false == isset($redisConfigs[$connName])) {
            throw new \RuntimeException('');
        }
        $redisConfig = $redisConfigs[$connName];
        $host = $redisConfig['host'];
        $port = $redisConfig['port'];
        $db = $redisConfig['db'];
        $prefix = $redisConfig['prefix'];
        $password = $redisConfig['password'];
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
        if($password) {
            $this->redis->auth($password);
        }
        if($prefix) {
            $this->redis->setOption(\Redis::OPT_PREFIX, trim($prefix, ':') . ':');
        }
        $this->redis->select($db);
    }

    public function __call($name, $arguments) {
        return call_user_func_array(array($this->redis, $name), $arguments);
    }
}