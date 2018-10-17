<?php
namespace Logic\Common;

use Logic\Common\Exceptions\TokenExpireException;
use Logic\Common\Structs\AccessTokenStruct;
use Logic\Common\Structs\AgencyInfoStruct;
use MicroService\Server;
use MicroService\Util;

class ServiceContext {
    /**
     * @var ServiceContext
     */
    protected static $instance = null;
    protected $requestHeaders = array();
    protected $agencyId = 0;
    /**
     * @var AgencyInfoStruct
     */
    protected $agencyInfo = null;
    protected $agencyAppInfo = array();
    protected $userInfo = null;
    protected $requestId = '';

    protected static $apiRequestLogs = array();

    protected static $refreshInterval = 1800; //间隔30分钟刷新token

    protected function __construct() {
        $this->requestId = APP_NAME . '-' . date('YmdHis') . '-' . md5(uniqid(microtime(true)));
    }

    /**
     * 每次调用接口都会先调用这个方法,
     * @param $name
     * @param $args
     * @param $byRef
     * @param $context
     * @throws \Exception
     */
    public static function beforeInvoke($name, &$args, $byRef, $context) {
        self::clearContext();
        $request = $context->request;
        $headers = $request->header;
        $requestUri = $request->server['request_uri'];

        self::$instance = new self();
        self::$instance->setHeaders($headers);
        self::startApiLog($requestUri, $name, $args, self::$instance->requestHeaders);
        self::$instance->validToken();
    }

    public function getRequestId() {
        return $this->requestId;
    }


    public static function afterInvoke($name, &$args, $byRef, &$result, $context) {
        self::endApiLog();
    }

    public static function onSendError(\Exception &$error, $context) {
        $errorMsg = $error->getMessage();
        $errorDetail = $error->getTraceAsString();
        $traceItems = explode("\n", $errorDetail);
        $errTraceArray = array();
        foreach ($traceItems as $msg) {
            $errTraceArray[] = $msg;
            if(strstr($msg, '[internal function]')) {
                break;
            }
        }
        $errTraceStr = implode("\n", $errTraceArray);
        self::endApiLog(array(
            'message'=>$errorMsg,
            'detail'=>$errTraceStr
        ));
    }


    public static function startApiLog($requestUri, $apiName, $params, $headers) {
        $requestId = self::$instance->getRequestId();
        $startTime = intval(microtime(true) * 10000);
        $appName = APP_NAME;
        //todo:做异步日志
        self::$apiRequestLogs[$requestId] = compact(
            'requestId',
            'appName',
            'requestUri',
            'apiName',
            'params',
            'headers',
            'startTime'
        );
    }

    public static function endApiLog($error=null) {
        $requestId = self::$instance->getRequestId();
        $endTime = intval(microtime(true) * 10000);

        $requestInfo = self::$apiRequestLogs[$requestId];
        $requestInfo['endTime'] = $endTime;
        unset(self::$apiRequestLogs[$requestId]);
        $timeCost = (($endTime - $requestInfo['startTime']) / 10) . 'ms';
        $timeCost = Util::cliColor($timeCost, 'green');
        //todo:做异步日志
        $fail = Util::cliColor('Fail', 'red');
        $succ = Util::cliColor('Succ', 'green');
        error_log('[' . ($error ? $fail: $succ) . '][time cost:' . $timeCost . ']' . json_encode($requestInfo, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取当前接口请求上下文对象
     * @return ServiceContext
     */
    public static function getContext() {
        return self::$instance;
    }

    /**
     * 清除上下文对象
     */
    protected static function clearContext() {
        self::$instance = null;
    }

    /**
     * 获取当前请求的HEADER头数据
     * @return array
     */
    public function getRequestHeaders() {
        return $this->requestHeaders;
    }

    /**
     * 从请求中提取需要的HEADER数据并保存
     * @param $headers
     */
    protected function setHeaders($headers) {
        $acceptHeaders = array(
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
            'USER_AGENT',
            //客户端IP, 特殊其情况会用到
            'REMOTE_ADDR',
        );

        foreach ($headers as $key=>$value) {
            $key = str_replace('-', '_', strtoupper($key));
            if(in_array($key, $acceptHeaders)) {
                $this->requestHeaders[$key] = $value;
            }
        }
    }

    /**
     * 校验请求头签名信息,并初始化用户与机构信息
     * @throws \Exception
     */
    protected function validToken() {
        if(isset($this->requestHeaders['SIGNATURE'])) {
            $agencyAppInfo = AgencyApp::getAppInfoByKey($this->requestHeaders['APP_KEY']);
            $this->agencyAppInfo = $agencyAppInfo;
            $agencyId = $agencyAppInfo['agency_id'];
            $appKey = $this->requestHeaders['APP_KEY'];
            $appSecret = $agencyAppInfo['app_secret'];
            $requestTime = $this->requestHeaders['REQUEST_TIME'];
            $accessToken = trim($this->requestHeaders['ACCESS_TOKEN']);
            $signature = $this->requestHeaders['SIGNATURE'];

            if($signature != ServiceUtils::getSignature($appKey, $appSecret, $requestTime, $accessToken)) {
                throw new \RuntimeException('接口签名校验失败!');
            }
            $this->agencyId = $agencyId;
            if($accessToken) {
                self::getUserInfo();
                $userInfo = $this->userInfo;
                if(time() - $userInfo['update_at'] >= self::$refreshInterval) {
                    self::touchToken($accessToken);
                }
            }
            self::getAgencyInfo();
        } else {
            #进行初始化设置, 后续调用获取机构信息与用户信息的方法会抛出异常
            $this->agencyInfo = new AgencyInfoStruct();
            $this->userInfo = new AccessTokenStruct();
        }
        //通过域名获取机构ID的接口不会有任何HEADER头
        return;
    }

    /**
     * 更新用户accessToken信息
     * @param $accessToken
     */
    public static function touchToken($accessToken) {
        $server = Server::swooleServer();
        $server->task(array(
            'callback'=>array(AccessToken::class, 'touch'),
            'args'=>array($accessToken),
        ));
    }

    /**
     * @return int
     */
    public static function getAgencyId() {
        $instance = self::$instance;
        return $instance->agencyId;
    }

    /**
     * @return AgencyInfoStruct
     * @throws \Exception
     */
    public static function getAgencyInfo() {
        $instance = self::$instance;
        if(null === $instance->agencyInfo) {
            $agencyAppInfo = $instance->agencyAppInfo;
            $agencyId = $agencyAppInfo['agency_id'];
            $instance->agencyInfo = self::getAgencyInfoById($agencyId, true);
            return $instance->agencyInfo;
        }

        if($instance->agencyInfo->isEmpty()) {
            //当实例属性不为null时调用, 如果无法获取值,抛出异常
            throw new \RuntimeException('无法获取用户机构信息!');
        }
        return $instance->agencyInfo;
    }

    /**
     * 特殊情况可能会需要设置机构信息,方法体暂时置空
     * @param $userInfo
     */
    public static function setAgencyInfo($userInfo) {

    }

    public static function getAgencyInfoById($agencyId, $requireAppKey=false) {
        return Agency::getAgencyInfoById($agencyId, $requireAppKey);
    }

    /**
     * 这个只是简单的返回AccessTokenStruct的结构体对象,通过这个对象可以获取机构ID,用户类型,用户ID等信息
     * @return  AccessTokenStruct
     */
    public static function getUserInfo() {
        $instance = self::$instance;
        if(null === $instance->userInfo) {
            $accessToken = $instance->requestHeaders['ACCESS_TOKEN'];
            $userInfo = AccessToken::getUserInfoByToken($accessToken);
            $instance->userInfo = $userInfo;
        }

        if($instance->userInfo->isEmpty()) {
            //当实例属性不为null时调用, 如果无法获取值,抛出异常
            throw new TokenExpireException('用户没有登录或登录已过期!');
        }

        return $instance->userInfo;
    }

    /**
     * 特殊情况可能会需要设置用户信息,方法体暂时置空
     * @param AccessTokenStruct $userInfo
     */
    public static function setUserInfo($userInfo) {

    }


}