<?php
namespace MicroService;

use Swoole\Process;

class Server {
    /**
     * @var \Swoole\Websocket\Server
     */
    protected static $swServer;
    protected static $logger = null;
    protected $baseService;
    protected static $services = array();
    protected $classLoader;
    protected static $timeZone = 'asia/shanghai';
    protected static $crontabFile = '';
    protected static $listenHost = '';
    protected static $listenPort = 0;

    /**
     * @var callable
     */
    protected $beforeInvoke = null;
    protected $afterInvoke = null;
    protected $errorHandler = null;
    public function __construct($listen, $services, $settings) {
        self::$swServer = new \Swoole\Websocket\Server($listen[0], $listen[1]);
        self::$listenHost = $listen[0];
        self::$listenPort = $listen[1];
        $settings = array_merge(array(
            'daemonize'=>false,
            'worker_num'=>2,
            'task_worker_num'=>2
        ), $settings);
        self::$swServer->set($settings);

        self::$services = $services;
        $this->setTaskHandler(self::$swServer);
        $this->setMessageHandler(self::$swServer);
    }

    public function setTimezone($timezone) {
        self::$timeZone = $timezone;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $classLoader
     */
    public function setClassLoader($classLoader) {
        $autoLoadConfig = Config::get('AUTOLOAD_CONFIGS');
        foreach ($autoLoadConfig as $autoloadItem) {
            $namespace = trim($autoloadItem[0], '\\') . '\\';
            $classDir = $autoloadItem[1];
            $classLoader->addPsr4($namespace, $classDir);
        }
    }

    protected function setTaskHandler(\Swoole\Server $server) {
        $appServer = $this;
        $server->on('task', function (\Swoole\Server $server, $taskId, $srcWorkerId, $data) use ($appServer) {
            defined('IN_TASK') || define('IN_TASK', 1);
            $callback = $data['callback'];
            $args = $data['args'];


            try {
                if(false == is_callable($callback)) return null;
                DataPool::initPool();
                $result = call_user_func_array($callback, $args);
                return $result;
            } catch (\Exception $e) { #异步任务中可能会抛出任意异常
                $logger = $appServer->getLogger();
                $logger->addError($e->getMessage());
            } finally {
                DataPool::freePool();
            }
        });

        $server->on('finish', function($server, $taskId, $data){});
    }

    protected function setMessageHandler(\Swoole\Server $server) {
        $appServer = $this;
        $server->on('pipeMessage', function(\Swoole\Server $server, $fromWorkerId, $message) use ($appServer) {
            $data = unserialize($message);
            if(false == isset($data['callback'])) {
                return;
            }
            $callback = $data['callback'];
            $args = isset($data['args']) ? $data['args'] : array();

            try {
                DataPool::initPool();
                call_user_func_array($callback, $args);
            } catch (\Exception $e) {   #异步任务中可能会抛出任意异常
                $logger = $appServer->getLogger();
                $logger->addError($e->getMessage());
            } finally {
                DataPool::freePool();
            }
        });
    }

    public static function swooleServer() {
        return self::$swServer;
    }

    protected function setServiceHandler() {
        Register::setServer($this);

        $this->baseService = new Service();
        $this->baseService->addFunction(array($this,'getServices'));
        $this->baseService->addFunction(array($this, 'getMethods'));
        $this->baseService->addFunction(array($this, 'getMethodInfo'));

        foreach (self::$services as $service) {
            $title = $service['title'];
            $register = $service['register'];
            $uriInfo = explode('/', trim($service['uri'], '/'));
            $uri = $uriInfo[sizeof($uriInfo)-1];
            $service = new Service();
            try {
                call_user_func_array(array($register, 'register'), array($service, $uri));
            } catch (\Exception $e) {
                die($title . '发布失败：' . $e->getMessage() . PHP_EOL);
            }
        }

        #这里设置通过swoole server处理请求，但具体请求的服务由， 并不是只有baseService
        $this->baseService->httpHandle(self::$swServer);
        $this->baseService->wsHandle(self::$swServer);

        CrontabService::setProcess(self::$swServer, self::$crontabFile);
        self::$swServer->on('workerStart', function($server, $workerId){
            if($workerId < $server->setting['worker_num']) {
                Process::signal(SIGCHLD, function (){
                    while($ret = Process::wait(false)) {}
                });
            }

            if($workerId == 0) {
                self::$swServer->tick(1000, function () {
                    CrontabService::tick();
                });
            }
        });
    }

    public function start() {
        date_default_timezone_set(self::$timeZone);
        echo 'Server is running on ' . self::$listenHost . ':' . self::$listenPort . PHP_EOL;
        $this->setServiceHandler();
        self::$swServer->start();
    }

    /**
     * @return Service
     */
    public function getBaseService() {
        return $this->baseService;
    }

    public function setBeforeInvoke(callable $beforeInvoke) {
        $this->beforeInvoke = $beforeInvoke;
    }

    public function setAfterInvoke(callable $afterInvoke) {
        $this->afterInvoke = $afterInvoke;
    }

    public function setErrorHandler(callable $errorHandler) {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return callable
     */
    public function getBeforeInvoke() {
        return $this->beforeInvoke;
    }

    public function getAfterInvoke() {
        return $this->afterInvoke;
    }

    public function getErrorHandler() {
        return $this->errorHandler;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $logger->setSwServer(self::$swServer);
        self::$logger = $logger;
    }

    /**
     * @return \Monolog\Logger
     */
    public static function getLogger() {
        if(defined('IN_TASK')) return null;
        return self::$logger;
    }


    public function getServices() {
        return array_values(self::$services);
    }

    protected static function getServiceInfo($serviceName) {
        $upperServiceName = strtoupper($serviceName);
        if(false == self::$services) self::$services = array();
        foreach (self::$services as $serviceName=>$serviceInfo) {
            if(strtoupper($serviceName) == $upperServiceName) {
                return $serviceInfo;
            }
        }
        throw new \RuntimeException('服务不存在！');
    }

    public function getMethods($serviceName='') {
        if(false == $serviceName) {
            throw new \RuntimeException('缺少服务名称！');
        }
        $serviceInfo  = self::getServiceInfo($serviceName);
        if(false == $serviceInfo) return array();
        $registerClass = $serviceInfo['register'];

        $methodList = call_user_func(array($registerClass, 'getMethods'));
        $methods = array();
        if($methodList) {
            foreach ($methodList as $method) {
                $methods[] = $method[1];
            }
        }
        return $methods;
    }

    public function getMethodInfo($serviceName, $methodName) {
        $serviceInfo  = self::getServiceInfo($serviceName);
        $registerClass = $serviceInfo['register'];

        $methodInfo = call_user_func_array(array($registerClass, 'getMethodInfo'), array($methodName));
        return $methodInfo;
    }

    public function setCrontabService($crontabFile) {
        self::$crontabFile = $crontabFile;
    }

    public static function setServerStats($serverStats) {
        $redis = Redis::getRedis();
        $expire = 300;
        $identifier = self::getIdentifier();
        $key = $identifier . ':ServerStats';
        $serverStats = serialize($serverStats);
        $redis->set($key, $serverStats, $expire);
    }

    public static function getIdentifier() {
        if(defined('APP_NAME')) {
            $identifier = APP_NAME;
        } else {
            $identifier = 'MSF-' . substr(md5(realpath($_SERVER['SCRIPT_FILENAME'])), 0, 4);
        }

        $identifier .= '：port->' . self::$listenPort;
        return $identifier;
    }

    public static function handleDocument($reqeust, $response, $baseUri) {
        $baseUri = '/' . trim($baseUri, '/');
        $requestUri = $reqeust->server['request_uri'];
        $uri = preg_replace('#^' . $baseUri . '#i', '', $requestUri);
        $uri = trim($uri, '/');
        if(false == $uri) {
            $services = self::$services;
            require __DIR__ . '/html/services.html';
            return;
        }
        $uriInfo = explode('/', $uri);
        $upperServiceName = strtoupper($uriInfo[0]);
        foreach(self::$services as $serviceName=>$serviceInfo) {
            if(strtoupper($serviceName) == $upperServiceName) {
                $originServiceName = $serviceName;
                $register = $serviceInfo['register'];
            }
        }
        if(false == $register) {
            echo "服务不存在！";
            return;
        }
        $serviceName = $originServiceName;
        $methods = call_user_func(array($register, 'getMethods'));

        $methodList = array();
        foreach ($methods as $methodInfo) {
            $methodList[] = array(
                'methodInfo'=>$methodInfo,
                'document'=>call_user_func_array(array($register, 'getMethodInfo'), array($methodInfo[1])),
            );
        }

        require __DIR__ . '/html/methods.html';

    }
}