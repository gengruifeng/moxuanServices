<?php
namespace Logic\Common;


use Hprose\Http\Client;

class ServiceUtils {
    protected static $headerKeys = array(
        //平台名, 可能为视频, 课堂, 校管等等, 特殊情况下会用到
        'APP_NAME',
        //机构的APP_KEY,用于接口信息获取
        'APP_KEY',
        //请求时间,存在接口调用接口的情况,会直接通过当前HEADER调用, 可以通过设置一个requestHeader有效期时长
        //来让请求不过期
        'REQUEST_TIME',
        //只有当用户登录了以后才会产生ACCESS_TOKEN, 服务端通过ACCESS_TOKEN获取用户信息
        'ACCESS_TOKEN',
        //签名值,签名计算方法查看ServiceUtils类
        'SIGNATURE',
        //浏览器UA, 特殊情况会用到
    );

    /**
     * @param Client $rpcClient
     * @param string $appKey
     * @param string $appSecret
     * @param string $accessToken
     */
    public static function setClientHeader($rpcClient, $appKey='', $appSecret='', $accessToken='') {
        self::clearHeaders($rpcClient);
        if($appKey) {
            $requestTime = time();
            $args = array('appKey'=>$appKey, 'requestTime'=>$requestTime, 'accessToken'=>'');
            $rpcClient->setHeader('APP_KEY', $appKey);
            $rpcClient->setHeader('REQUEST_TIME', $requestTime);
            if($accessToken) {
                $rpcClient->setHeader('ACCESS_TOKEN', $accessToken);
                $args['accessToken'] = $accessToken;
            }
            $signature = self::getSignature($appKey,  $appSecret, $requestTime, $args['accessToken']);
            $rpcClient->setHeader('SIGNATURE', $signature);
        }

        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $rpcClient->setHeader('USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
        }

        $remoteAddr = self::getRemoteAddr();
        if($remoteAddr) {
            $rpcClient->setHeader('REMOTE_ADDR', $remoteAddr);
        }
    }

    public static function getSignature($appKey, $appSecret, $requestTime, $accessToken) {
        $args = array($appKey, strval($requestTime)); //不加strval的话排序会不一致
        if($accessToken) $args[] = strval($accessToken);
        sort($args);
        return strtolower(md5($appSecret . '^&*' . implode('^' , $args)));
    }

    protected static function getRemoteAddr() {
        //todo:对设置了CNAME解析的域名进行特殊处理,要看一下CNAME解析时的$_SERVER变量
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @param Client $rpcClient
     * @param ServiceContext $serviceContext
     */
    public static function setHeadersByContext($rpcClient, $serviceContext) {
        self::clearHeaders($rpcClient);
        $contextHeaders = $serviceContext->getRequestHeaders();
        foreach ($contextHeaders as $key=>$value) {
            $rpcClient->setHeader($key, $value);
        }
    }

    /**
     * @param Client $rpcClient
     */
    public static function clearHeaders($rpcClient) {
        foreach (self::$headerKeys as $key) {
            $rpcClient->setHeader($key, '');
        }
    }
}