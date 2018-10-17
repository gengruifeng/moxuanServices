<?php
namespace MicroService;

use Monolog\Handler\HandlerInterface;

class Logger {
    protected $logger;
    /**
     * @var \Swoole\Server
     */
    protected $swServer;

    public function __construct() {
        $this->logger = new \Monolog\Logger('ServiceLogger');
    }

    /**
     * @param \Swoole\Server $server
     */
    public function setSwServer($server) {
        $this->swServer = $server;
    }

    public function addLogHandler(HandlerInterface $handler) {
        $this->logger->pushHandler($handler);
    }

    public function __call($name, $arguments) {
        if(in_array($name, array(
            'log', 'debug', 'info', 'notice', 'warn', 'warning', 'err', 'error',
            'crit', 'critical', 'alert', 'ererg', 'emergency',
            'addRecord', 'addDebug', 'addInfo', 'addNotice', 'addWarning', 'addError',
            'addCritical', 'addAlert', 'addEmergency',
        ))) {
            $this->swServer->task(array(
                'callback' => array($this->logger, $name),
                'args' => $arguments
            ));
        } else {
            return call_user_func_array(array($this->logger, $name), $arguments);
        }
    }


}