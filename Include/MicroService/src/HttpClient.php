<?php
namespace MicroService;

use Hprose\Future;
use Swoole\Http\Client;

class HttpClient {
    protected static $clients = array();
    protected function __construct() {}

    protected static function getHttpClient($urlInfo) {
        $client = new Client($urlInfo['host'], $urlInfo['port'], $urlInfo['ssl']);
        $client->set(['timeout'=>-1]);
        return $client;
    }

    /**
     * @param Client $httpClient
     * @param $urlInfo
     * @param $method
     * @param $data
     * @param $headers
     * @param $sslOptions
     * @return Future
     */
    protected static function getContents($httpClient, $urlInfo, $method, $data, $headers, $sslOptions) {
        $path = $urlInfo['path'];
        $methods = array('PUT', 'HEAD', 'PATCH', 'GET', 'POST');
        $method = strtoupper($method);

        if(false == in_array($method, $methods)) {
            throw new \RuntimeException('不支持的HTTP请求！');
        }
        if($data) {
            if(false == is_string($data)) {
                $data = http_build_query($data);
                $headers['content-type'] = 'application/x-www-form-urlencoded';
            } else {
                if(json_decode($data)) {
                    $headers['content-type'] = 'application/json';
                }
            }
            $httpClient->setData($data);
        }

        $headers = self::buildHeaders($urlInfo, $headers, $sslOptions);
        $httpClient->setMethod($method);
        $httpClient->setHeaders($headers);

        $future = new Future();
        $httpClient->execute($path, function ($httpClient) use ($future) {
            $future->resolve($httpClient->body);
        });
        return $future;
    }

    public static function get($url, $headers=array(), $sslOptions=array()) {
        $urlInfo = self::getUrlInfo($url);
        $httpClient = self::getHttpClient($urlInfo);

        return self::getContents($httpClient, $urlInfo, 'GET', array(), $headers, $sslOptions);
    }

    public static function post($url, $data=array(), $headers=array(), $sslOptions=array()) {
        $urlInfo = self::getUrlInfo($url);
        $httpClient = self::getHttpClient($urlInfo);
        return self::getContents($httpClient, $urlInfo, 'POST', $data, $headers, $sslOptions);
    }

    protected static function getUrlInfo($url) {
        $urlInfo = parse_url($url);
        $ssl = $urlInfo['schema'] == 'https' ? true : false;
        $path = $urlInfo['path'] ?: '/';
        if($urlInfo['query']) {
            $path .= '?' . $urlInfo['query'];
        }
        return array(
            'url'=>$url,
            'host'=>$urlInfo['host'],
            'port'=>$urlInfo['port'] ?: ($ssl ? 443 : 80),
            'path'=>$path,
            'query'=>$urlInfo['query'] ?: '',
        );
    }

    protected static function buildHeaders($urlInfo, $headers, $sslOptions) {
        $host = $urlInfo['host'];
        if($urlInfo['port'] != 80) {
            $host .= ':' . $urlInfo['port'];
        }
        $defaultHeaders = array(
            'Host'=>$host,
            'Accept' => 'text/html,application/json,text/plain',
            'Accept-Encoding' => 'gzip',
        );

        if($sslOptions) {
            //todo:xxx
        }

        if($headers) {
            foreach ($headers as $key=>$value) {
                if(is_numeric($key)) {
                    $itemInfo = explode('=', $key);
                    $key = array_shift($itemInfo);
                    $key = ucfirst($key);
                    $value = implode('=', $itemInfo);
                }
                $defaultHeaders[$key] = $value;
            }
        }

        return $defaultHeaders;
    }
}