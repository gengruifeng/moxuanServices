<?php
namespace Logic\Common;

use MicroService\Cache;
use MicroService\Config;
use MicroService\HttpClient;

class ApiClient {
    protected static $cacheData = array();
    protected static $cacheLifeTime = 300; //缓存5分钟,但服务重启会全部失效

    /**
     * 在类中设置缓存方法的目的是为了重启服务器立即会使缓存失效
     * @param $agencyId
     * @param $cacheKey
     * @param $cacheValue
     */
    protected static function cacheSet($agencyId, $cacheKey, $cacheValue) {
        $cacheKey = strtoupper($cacheKey);
        $cacheData = array(
            'cacheTime'=>time(),
            'cacheValue'=>$cacheValue
        );
        self::$cacheData[$agencyId][$cacheKey] = $cacheData;
    }

    /**
     * 获取缓存值
     * @param $agencyId
     * @param $cacheKey
     * @return null
     */
    protected static function cacheGet($agencyId, $cacheKey) {
        $cacheKey = strtoupper('API:' . $cacheKey);
        if(isset(self::$cacheData[$agencyId][$cacheKey])) {
            $cacheData = self::$cacheData[$cacheKey];
            if(time() - $cacheData['cacheTime'] <= self::$cacheLifeTime) {
                return $cacheData['cacheValue'];
            }
        }

        return null;
    }

    /**
     * 第三方机构的接口调用并返回数据
     * @param $agencyId
     * @param $userId
     * @param $apiKey
     * @param array $params
     * @return \Generator
     * @throws \Exception
     */
    public static function asyncGetResult($agencyId, $userId, $apiKey, $params=array()) {
        $apiHeaderCallback = self::getHeaderCallback($agencyId);
        $apiUrlConfig = self::getApiConfig($agencyId, $apiKey);

        if(is_callable($apiHeaderCallback)) {
            $headers = call_user_func_array($apiHeaderCallback, array($agencyId,$userId));
        } else {
            throw new \RuntimeException('接口HEADER生成回调函数没有设置或设置错误！');
        }
        foreach ($headers as $key=>$value) {
            if(is_null($value)) unset($headers[$key]);
        }
        $method = strtoupper($apiUrlConfig['api_method']);
        $apiUrl = $apiUrlConfig['api_url'];
        $paramConverter = $apiUrlConfig['param_converter'];
        $resultConverter = $apiUrlConfig['result_converter'];
        $params = call_user_func_array($paramConverter, array($agencyId, $params));

        $result = null;
        switch ($method) {
            case 'GET':
                $apiUrl = self::buildGetUrl($apiUrl, $params);
                $result = (yield HttpClient::get($apiUrl, $headers));
                break;
            case 'POST':
                $result = (yield HttpClient::post($apiUrl, $params, $headers));
                break;
        }

        if(false == $result || false == json_decode($result, true)) {
            throw new \RuntimeException('接口"' . $apiKey . '(' . $apiUrl . ')"数据获取失败,HEADER:' . json_encode($headers, JSON_UNESCAPED_UNICODE) . '参数:' . json_encode($params, JSON_UNESCAPED_UNICODE));
        } else {
            $result = json_decode($result, true);
            yield call_user_func_array($resultConverter, array($agencyId, $result));
        }
    }

    protected static function buildGetUrl($url, $params) {
        $urlInfo = parse_url($url);
        $url = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $urlInfo['path'];
        if($urlInfo['query']) {
            $queryArgs = parse_str($urlInfo['query']);
            $params = is_array($params) ? $params : parse_str($params);
            foreach ($params as $key=>$value) {
                $queryArgs[$key] = $value;
            }
            $url .= '?' . http_build_query($queryArgs);
        }
        return $url;
    }

    protected static function getHeaderCallback($agencyId) {
        $cacheKey = 'HEADER_CALLBACK';
        $headerCallback = self::cacheGet($agencyId, $cacheKey);
        if(false == $headerCallback) {
            $apiConfigTable = Tables::apiConfigTable();
            $condition = array(
                'agency_id'=>$agencyId
            );
            $apiHeaderConfig = $apiConfigTable->find($condition);
            if(false == $apiHeaderConfig) {
                $headerCallback = self::defaultHeaderCallback();
            } else {
                $headerCallback = $apiHeaderConfig['header_callback'];
                if(false == strstr($headerCallback, '::')) {
                    $headerCallback = json_decode($headerCallback, true) ?: '';
                }
            }
            if(false == is_callable($headerCallback)) {
                throw new \RuntimeException('无法获取机构接口HEADER生成回调方法!');
            }
            self::cacheSet($agencyId, $cacheKey, $headerCallback);
        }
        return $headerCallback;
    }

    /**
     * @param $agencyId
     * @param $apiKey
     * @return array
     * @throws \Exception
     */
    protected static function getApiConfig($agencyId, $apiKey) {
        $apiKey = strtoupper($apiKey);
        $cacheKey = 'API_INFO:' . $apiKey;
        $apiConfig = self::cacheGet($agencyId, $cacheKey);
        if(false == $apiConfig) {
            $apiUrlsTable = Tables::apiUrlsTable();
            $condition = array(
                'agency_id'=>$agencyId,
                'api_key'=>$apiKey,
            );
            $apiConfig = $apiUrlsTable->find($condition);
            if(false == $apiConfig) {
                throw new \RuntimeException('接口"' . $apiKey . '"的配置记录不存在!');
            }
            if(false == $apiConfig['param_converter']) {
                $paramConverter = self::defaultparamConverter();
            } else {
                $paramConverter = $apiConfig['param_converter'];
                if(false == strstr($paramConverter, '::')) {
                    $paramConverter = json_decode($paramConverter, true) ?: '';
                }
            }

            if(false == is_callable($paramConverter)) {
                throw new \RuntimeException('无法设置接口参数转换器回调方法!');
            }

            if(false == $apiConfig['result_converter']) {
                $resultConverter = self::defaultResultConverter();
            } else {
                $resultConverter = $apiConfig['result_converter'];
                if(false == strstr($resultConverter, '::')) {
                    $resultConverter = json_decode($resultConverter, true) ?: '';
                }
            }

            if(false == is_callable($resultConverter)) {
                throw new \RuntimeException('无法设置接口结果转换器回调方法!');
            }

            $apiConfig['param_converter'] = $paramConverter;
            $apiConfig['result_converter'] = $resultConverter;
            self::cacheSet($agencyId, $cacheKey, $apiConfig);
        }

        return $apiConfig;
    }

    /**
     * @return callable
     */
    protected static function defaultHeaderCallback() {
        return array(self::class, 'getApiHeaders');
    }

    /**
     * @return callable
     */
    protected static function defaultparamConverter() {
        return array(self::class, 'paramConverter');
    }

    /**
     * @return callable
     */
    protected static function defaultResultConverter() {
        return array(self::class, 'resultConverter');
    }

    /**
     * 如果设置了独立的回调方法, 就不会执行这个方法,否则默认为
     * @param int $agencyId
     * @param mixed $userId [default:null]
     * @return array
     * @throws \Exception
     */
    public static function getApiHeaders($agencyId, $userId=null) {
        try {
            $agencyConfig = Config::loadUserConf('ApiConfigs/' . $agencyId);
            if ($agencyConfig) {
                $callback = $agencyConfig['headerCallback'] ?: '';
                if(false == is_callable($callback)) {
                    throw new \RuntimeException('机构没有设置接口HEADER回调函数!');
                }
                return call_user_func_array($callback, array($userId)) ?: array();
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('无法获取机构接口HEADER数组!');
        }
    }

    public static function paramConverter($agencyId, $params) {
        try {
            $agencyConfig = Config::loadUserConf('ApiConfigs/' . $agencyId);
            if($agencyConfig) {
                $callback = $agencyConfig['paramConverter'] ?: '';
                if(false == is_callable($callback)) {
                    return $params;
                }
                return call_user_func_array($callback, array($params)) ?: array();
            }
        } catch (\Exception $e) {
            return $params;
        }
    }

    public static function resultConverter($agencyId, $result) {
        try {
            $agencyConfig = Config::loadUserConf('ApiConfigs/' . $agencyId);
            if($agencyConfig) {
                $callback = $agencyConfig['resultConverter'] ?: '';
                if(false == is_callable($callback)) {
                    return $result;
                }
                return call_user_func_array($callback, array($result)) ?: array();
            }
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * 这个方法是在后台设置时调用清空缓存, 但是因为在swoole中运行,所以只会清空一个worker的缓存数据,
     * 所以运行可能会存在问题,这里要进行特殊设置, 比如在redis时间戳, 并在cacheGet方法中进行时间戳校验,
     * 目前这里不实现
     * @param $agencyId
     */
    public static function clearAgencyData($agencyId) {

    }

}