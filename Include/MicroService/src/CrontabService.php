<?php
namespace MicroService;

use Swoole\Process;

class CrontabService {
    /**
     * @var Process
     */
    protected static $process = null;
    protected function __construct() {
    }

    /**
     * @param \Swoole\Server $swServer
     * @param string $crontabFile
     * @return Process
     */
    public static function setProcess($swServer, $crontabFile) {
        if($crontabFile && file_exists($crontabFile)) {
            $rules = require $crontabFile;
        }
        if (false == $rules) $rules = array();

        self::$process = new Process(function(Process $process) use ($swServer, $rules) {
            if(PHP_OS == 'Linux') swoole_set_process_name('crontabServer');

            while(true) {
                $time = $process->read();
                /**
                 * 缓存服务器运行状态
                 */
                $serverStats = $swServer->stats();
                $startTime = $serverStats['start_time'];
                $serverStats['start_time'] = date('Y-m-d H:i:s', $startTime);
                $diffSeconds = $time - $startTime;
                $days = intval($diffSeconds / 86400);
                $hours = intval(($diffSeconds % 86400) / 3600);
                $minutes = intval(($diffSeconds % 3600) / 60);
                $seconds = $diffSeconds % 60;

                $alreadyRunning = $days . '天' . $hours . '小时' . $minutes . '分' . $seconds . '秒';
                $serverStats['already_running'] = $alreadyRunning;
                $serverStats['script_filename'] = realpath($_SERVER['SCRIPT_FILENAME']);

                $workerNum = $swServer->setting['worker_num'];

                $swServer->sendMessage(serialize(array(
                    'callback'=>array(Server::class, 'setServerStats'),
                    'args'=>array(
                        'serverStats'=>$serverStats
                    )
                )), mt_rand(0, $workerNum + $swServer->setting['task_worker_num'] - 1)); #状态缓存的消息可以发送到任意worker

                $second = date('s', $time);

                if($second != '00') continue;

                foreach ($rules as $ruleInfo) {
                    if($ruleInfo['timer'] && isset($ruleInfo['callback']) && self::ifCrontabMatch($time, $ruleInfo)) {
                        $swServer->sendMessage(serialize(array(
                            'callback' => array(self::class, 'execute'),
                            'args'=>array(
                                'timestamp' => $time,
                                'ruleInfo' => $ruleInfo
                            )
                        )), mt_rand(0, $workerNum - 1)); #crontab因为需要创建process并wait，只能在eventWorker中执行
                    }
                }
            }
        });

        $swServer->addProcess(self::$process);
    }

    public static function tick() {
        if(self::$process) {
            self::$process->write(time());
        }
    }

    /**
     * @param $time
     * @param $ruleInfo
     * @return bool
     */
    public static function ifCrontabMatch($time, $ruleInfo) {
        if(false == $ruleInfo['timer']) return false;
        $timer = $ruleInfo['timer'];
        list($minute, $hours, $day, $month, $week) = preg_split('#\s+#', $timer);

        #分-时(24小时制)-日-月-周
        $formatDate = date('i-G-j-n-w', $time);
        list($timeMinute, $timeHours, $timeDay, $timeMonth, $timeWeek) = explode('-', $formatDate);
        $timeMinute = abs(preg_replace('#^0#', '', $timeMinute));

        #前边是规则设置，后边是当前时间
        $mapping = array(
            array($minute, $timeMinute),
            array($hours, $timeHours),
            array($day, $timeDay),
            array($month, $timeMonth),
            array($week, $timeWeek)
        );

        foreach ($mapping as $item) {
            $rule = trim($item[0]);
            $now = abs($item[1]);
            if($rule != '*') {
                if(false !== strpos($rule, '/')) {
                    $ar = explode('/', $rule);
                    $value = abs($ar[0]);
                    if($value == 0) return false;
                    if($now % $value != 0) return false;
                } else {
                    if($rule != $now) return false;
                }
            }
        }
        return true;
    }

    public static function execute($timestamp, $ruleInfo) {
        static $waiting = false;
        $callback = $ruleInfo['callback'];
        $process = new Process(function() use ($callback, $timestamp) {
            DataPool::initPool();
            call_user_func_array($callback, array($timestamp));
            DataPool::freePool();
        });
        $process->start();
    }
}