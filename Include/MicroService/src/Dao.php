<?php
namespace MicroService;

use \RuntimeException;

abstract class Dao {
    private static $daoArray = array();
    private static $connArray = array();
    protected $timers = array();


    protected $connName = '';
    protected $tablePrefix = '';
    protected $dbType  = '';

    protected $writeConfig = array();
    protected $readConfig = array();

    protected $async = true;

    protected $affectRows = 0;
    protected $lastInsertId = 0;
    protected $lastQuery = '';
    protected $lastException = null;

    /**
     * 读写分离时为true
     * @var bool
     */
    protected $isMulti = false;

    protected static $isTrans = false;
    protected static $transConnArray = array();

    const FETCH_TYPE_ALL = 'ALL';
    const FETCH_TYPE_ROW = 'ROW';


    const TABLE_NOT_EXISTS = 0;
    const TABLE_IS_TABLE = 1;
    const TABLE_IS_VIEW = 2;

    const COLUMN_TYPE_INT = 'INT';
    const COLUMN_TYPE_DECIMAL = 'DECIMAL';
    const COLUMN_TYPE_STRING = 'STRING';
    const COLUMN_TYPE_DATE   = 'DATE';
    const COLUMN_TYPE_TIME   = 'TIME';
    const COLUMN_TYPE_DATETIME= 'DATETIME';
    const COLUMN_TYPE_TIMESTAMP= 'TIMESTAMP';


    /**
     * @param string $connName
     * @return Dao
     * @throws RuntimeException
     */
    public static function getDao($connName) {
        if(false == $connName) {
            $connName = Config::get('DEFAULT_CONN');
        }
        if(false == $connName) {
            throw new RuntimeException('没有设置默认数据库连接名"DEFAULT_CONN"！');
        }
        $connName = strtoupper($connName);

        if(false == is_array($connName)) {
            $dbConfig = self::getDbConfig($connName);
        } else {
            $dbConfig = $connName;
        }

        $pid = posix_getpid();
        $daoKey = md5(serialize(array($pid, $dbConfig)));
        if(false == isset(self::$daoArray[$daoKey])) {
            if(isset($dbConfig['DB_TYPE'])) {
                $dbType = $dbConfig['DB_TYPE'];
            } else {
                $dbType = $dbConfig[0]['DB_TYPE'];
            }
            $dbType = strtoupper($dbType);
            $daoClassArray = array(
                'MYSQL'=>'MysqlDao',
                'MSSQL'=>'MssqlDao',
                'MONGO'=>'MongoDao',
            );
            if(isset($daoClassArray[$dbType])) {
                $className = '\\MicroService\\Dao\\Adapter\\' . $daoClassArray[$dbType];
                $dao = new $className($connName);
                self::$daoArray[$daoKey] = $dao;
            } else {
                throw new RuntimeException('不支持的数据库类型＜' . $dbType . '＞！');
            }
        }

        return self::$daoArray[$daoKey];
    }

    /**
     * 仅仅根据连接名称返回配置， 如果有主从分离的设置，返回全部连接
     * @param $connName
     * @return array
     * @throws RuntimeException
     */
    protected static function getDbConfig($connName) {
        static $allConfigArray = array();
        if(false == $allConfigArray) {
            $dbConfigs = Config::get('DB_CONFIGS');
            foreach ($dbConfigs as $key=>$config) {
                $key = strtoupper($key);
                $allConfigArray[$key] = $config;
            }
        }

        if(false == isset($allConfigArray[$connName])) {
            throw new RuntimeException('数据库连接"' . $connName . '"不存在，请先配置！');
        }

        return $allConfigArray[$connName];
    }

    protected function __construct($connName) {
        $this->connName = $connName;
        $dbConfig = self::getDbConfig($connName);
        #是否有主从分离设置
        if(isset($dbConfig['DB_TYPE'])) {
            $this->tablePrefix = $dbConfig['TABLE_PREFIX'];
            $this->dbType = strtoupper($dbConfig['DB_TYPE']);
            $this->isMulti = false;
        } else {
            $this->tablePrefix = $dbConfig[0]['TABLE_PREFIX'] ?: '';
            $this->dbType = strtoupper($dbConfig[0]['DB_TYPE']) ?: '';
            $this->isMulti = true;
        }
        if(false == $this->dbType) {
            throw new RuntimeException('缺少数据库类型！');
        }
    }

    public function getConnName() {
        return $this->connName;
    }

    public function getDbType() {
        return $this->dbType;
    }

    /**
     * 这里的$dbConfig已经具体到了某一个连接
     * @param $dbConfig
     * @return \PDO|\Mongo
     * @throws \Exception
     */
    protected function connect($dbConfig) {
        $pid = posix_getpid();
        #不同的对象， 使用不同的PDO对象， 配置信息一样也要连接多次
        $connKey = md5(serialize($dbConfig));
        if(isset(self::$connArray[$pid][$connKey]) && false == $this->ping(self::$connArray[$pid][$connKey])) {
            unset(self::$connArray[$pid][$connKey]);
        }

        if(false == isset(self::$connArray[$pid][$connKey])) {
            try {
                $conn = $this->_connect($dbConfig);
                self::$connArray[$pid][$connKey] = $conn;
            } catch (\Exception $e) {
                throw $e;  #异常原样抛出， 能不能捕获就看具体代码了
            }
        }

        return self::$connArray[$pid][$connKey];
    }

    /**
     * @param $dbConfig
     * @return \PDO|\MongoClient
     */
    abstract protected function _connect($dbConfig);

    /**
     * @param \PDO|\MongoClient $dbConn
     * @return mixed
     */
    abstract protected function ping($dbConn);


    abstract public function isTableExists($tableName, $isRealTableName=false);

    abstract public function createTable($tableName, $schema, $isRealTableName = false);

    /**
     * @param      $tableName
     * @param bool $isRealTableName
     * @return array
     */
    abstract public function getTableSchema($tableName, $isRealTableName=false);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isIntColumn($column);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isDecimalColumn($column);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isStrColumn($column);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isDateColumn($column);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isTimeColumn($column);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isDatetimeColumn($column);

    /**
     * @param $column
     * @return bool
     */
    abstract protected function isTimestampColumn($column);

    /**
     * @param $column
     * @return string
     */
    abstract public function getColumnType($column);

    /**
     * @return mixed
     */
    abstract protected function begin();

    /**
     * @return mixed
     */
    abstract protected function commit();

    /**
     * @return mixed
     */
    abstract protected function rollback();

    /**
     * @return mixed
     */
    abstract public function getLastQuery();

    /**
     * @param string $sequenceName
     * @return mixed
     */
    abstract public function lastInsertId($sequenceName='');

    /**
     * @return int
     */
    abstract public function affectRows();

    /**
     * @param $conn
     * @return mixed
     */
    abstract protected function closeConn($conn);

    /**
     * @param $strQuery
     * @return bool
     */
    abstract public function execute($strQuery);

    /**
     * @param $strQuery
     * @param $order
     * @return array
     */
    abstract public function getAll($strQuery, $order='');

    /**
     * @param $strQuery
     * @return array
     */
    abstract public function getRow($strQuery);

    /**
     * @param $strQuery
     * @return array
     */
    abstract public function getOne($strQuery);

    /**
     * @param        $strQuery
     * @param string $order
     * @param int    $currentPage
     * @param int    $pageSize
     * @return array
     */
    abstract public function getLimit($strQuery, $order='', $currentPage=1, $pageSize=20);

    /**
     * @param        $strQuery
     * @param        $mapCol
     * @param string $valueCol
     * @param        $order
     * @return array
     */
    abstract public function getMapArray($strQuery, $mapCol, $valueCol='', $order='');

    /**
     * @return string
     */
    abstract public function randOrder();

    /**
     * @param $string
     * @return string
     */
    abstract public function quote($string);

    /**
     * @param $datetime
     * @return int
     */
    abstract public function strtotime($datetime);

    /**
     * @param        $datetime
     * @param string $format
     * @return string
     */
    abstract public function datetime($datetime, $format='Y-m-d H:i:s');

    /**
     * @param $timestamp
     * @return string|null
     */
    abstract public function timestamp($timestamp);

    /**
     * @param RuntimeException $exception
     */
    public function setLastException($exception) {
        $this->lastException = $exception;
    }

    /**
     * @return RuntimeException
     */
    public function getLastException() {
        return $this->lastException;
    }

    /**
     * @return \Mongo|\PDO
     */
    public function readConn() {
        if(false == $this->isMulti || self::isTrans()) {
            return $this->writeConn();
        }
        if(false == $this->readConfig) {
            $pid = posix_getpid();
            $dbConfigArray = self::getDbConfig($this->connName);
            $configCount = sizeof($dbConfigArray);
            $idx = $pid % $configCount;
            $dbConfig = $dbConfigArray[$idx];
            $this->readConfig = $dbConfig;
        } else {
            $dbConfig = $this->readConfig;
        }
        $conn = $this->connect($dbConfig);

        return $conn;
    }

    /**
     * @return \Mongo|\PDO
     */
    public function writeConn() {
        if(false == $this->writeConfig) {
            $dbConfig = self::getDbConfig($this->connName);
            if ($this->isMulti) {
                $dbConfig = $dbConfig[0];
            }
            $this->writeConfig = $dbConfig;
        } else {
            $dbConfig = $this->writeConfig;
        }
        $conn = $this->connect($dbConfig);

        if(self::isTrans()) {
            $connName = $this->connName;
            if(false == self::$transConnArray[$connName]) {
                self::$transConnArray[$connName] = $this;
                $this->begin();
            }
        }
        return $conn;
    }

    /**
     * @param $tableName
     * @return string
     */
    public function realTableName($tableName) {
        return $this->tablePrefix . $tableName;
    }

    /**
     * @return bool
     */
    protected static function isTrans() {
        return self::$isTrans;
    }

    public function beginTrans() {
        if(false == self::isTrans()) {
            self::$isTrans = true;
        }
        return true;
    }

    public function commitTrans() {
        if(self::isTrans()) {
            foreach (self::$transConnArray as $dao) {
                $dao->commit();
            }
            self::$isTrans = false;
            self::$transConnArray = array();
        }
        return true;
    }

    /**
     * @return bool
     */
    public function rollbackTrans() {
        if(self::isTrans()) {
            foreach (self::$transConnArray as $dao) {
                $dao->rollback();
            }
            self::$isTrans = false;
            self::$transConnArray = array();
        }
        return true;
    }

    /**
     * @return bool
     */
    public function close() {
        $pid = posix_getpid();
        $swServer = Server::swooleServer();
        foreach ($this->timers as $connKey=>$timer) {
            $swServer->clearTimer($timer);
            $conn = self::$connArray[$pid][$connKey];
            $this->closeConn($conn);
        }
        unset(self::$connArray[$pid]);
        unset($this->timers);
        return true;
    }

    /**
     * @return bool
     */
    public static function free() {
        $pid = posix_getpid();
        if(isset(self::$daoArray[$pid])) {
            foreach (self::$daoArray[$pid] as $dao) {
                $dao->close();
            }
            unset(self::$daoArray[$pid]);
        }
        return true;
    }

}