<?php
namespace MicroService;

final class Cache {
    protected $redis;
    protected static $cache = null;
    protected $keyPrefix = '';
    protected $defCacheLife = 600;

    protected static function getCache() {
        if(null === self::$cache) {
            self::$cache = new self();
        }

        return self::$cache;
    }

    protected function __construct() {
        $cacheConfig = Config::get('CACHE_CONFIG');
        $redisConn = $cacheConfig['redis_conn'];
        $this->defCacheLife = abs($cacheConfig['cache_lifetime']) ?: 600;
        $this->redis = Redis::getRedis($redisConn);
        $cachePrefix = $cacheConfig['cache_prefix'] ?: Server::getIdentifier();
        $this->keyPrefix = $cachePrefix . ':Cache';
    }

    protected function getNsKey($ns) {
        return $this->keyPrefix . ':' . $ns;
    }

    protected function getCacheKey($ns, $key) {
        return $this->getNsKey($ns) . ':' . $key;
    }

    protected function checkNsVersion($ns, $cacheTime) {
        $nsKey = $this->getNsKey($ns);
        $nsVer = $this->redis->get($nsKey);
        if(false == $nsVer) return true;
        return $nsVer < $cacheTime;
    }

    public static function get($ns, $key) {
        $cache = self::getCache();
        $cacheKey = $cache->getCacheKey($ns, $key);
        $data = $cache->redis->get($cacheKey);
        if(null === $data) return null;
        $data = unserialize($data);
        $cacheTime = $data['cacheTime'];
        $cacheData = $data['cacheData'];

        if($cache->checkNsVersion($ns, $cacheTime)) {
            return $cacheData;
        }
        return null;
    }

    public static function set($ns, $key, $data, $expire=-1) {
        $cache = self::getCache();
        $cacheKey = $cache->getCacheKey($ns, $key);
        $now = time();
        if($expire == 0) {
            $ttl = 0;
        } else {
            if ($expire == -1) {
                $expire = $cache->defCacheLife;
            } else if($expire > $now) {
                $expire = $expire - $now;
            }
            if(false == $expire) {
                $expire = $cache->defCacheLife;
            }
            $ttl = $expire;
        }
        $cacheData = array(
            'cacheTime'=>$now,
            'cacheData'=>$data,
        );
        if($ttl) {
            return $cache->redis->setex($cacheKey, $ttl, serialize($cacheData));
        } else {
            return $cache->redis->set($cacheKey, serialize($cacheData));
        }
    }

    public static function delete($ns, $key='') {
        $cache = self::getCache();
        if($key) {
            $cacheKey = $cache->getCacheKey($ns, $key);
            $cache->redis->delete($cacheKey);
        } else {
            $cacheKey = $cache->getNsKey($ns);
            $cache->redis->set($cacheKey, time());
        }
    }
}