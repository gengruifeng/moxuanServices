<?php
namespace MicroService\Dao;

use MicroService\Server;
use MicroService\ServiceException;
use \RuntimeException;

trait RdbmsTrait  {
    protected $sequenceName = '';
    protected $lastPing = 0;
    protected function isPdo($conn) {
        return is_a($conn, '\\PDO');
    }

    /**
     * @param $strQuery
     * @param \PDO $conn
     * @return \PDOStatement
     */
    public function getStmt($strQuery, $conn=null) {
        $conn = $conn ?: $this->getConn($strQuery);
        $error = $conn->errorInfo ();

        $pds = $conn->prepare($strQuery);
        if(false == $pds) {
            throw new ServiceException('SQL语句错误，无法获取PDOStatment对象！' . PHP_EOL . $strQuery.print_r ($error, true));
        }
        if(strstr($strQuery, '?')) {
            $logger = Server::getLogger();
            $logger && $logger->debug('[SQL]: ' . $strQuery);
        }
        return $pds;
    }

    protected function ping($dbConn) {
        if(PHP_SAPI != 'cli') return true;
        if(time() - $this->lastPing < 2) {
            return true;
        }
        try{
            $errorLevel = error_reporting();
            error_reporting($errorLevel ^ E_WARNING);
            $serverInfo = $dbConn->getAttribute(\PDO::ATTR_SERVER_INFO);
            error_reporting($errorLevel);
            if($serverInfo) {
                $this->lastPing = time();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @param $strQuery
     * @return \PDO
     */
    protected function getConn($strQuery) {
        $queryInfo = preg_split('#\s+#', $strQuery);
        $firstWord = strtolower($queryInfo[0]);
        $words = array('insert', 'update', 'delete', 'replace', 'begin', 'commit', 'rollback', 'create', 'drop', 'show', 'desc', 'description', 'call');
        if(in_array($firstWord, $words)) {
            $conn = $this->writeConn();
        } else {
            $conn = $this->readConn();
        }
        return $conn;
    }

    /**
     * @return bool
     */
    protected function begin() {
        $conn = $this->writeConn();
        return $conn->beginTransaction();
    }

    /**
     * @return bool
     */
    protected function commit() {
        $conn = $this->writeConn();
        return $conn->commit();
    }

    /**
     * @return bool
     */
    protected function rollback() {
        $conn = $this->writeConn();
        return $conn->rollback();
    }

    /**
     * @param      $strQuery
     * @param null $conn
     * @param      $fetchType
     * @return array
     * @throws RuntimeException
     */
    public function fetchResult($strQuery, $conn=null, $fetchType=self::FETCH_TYPE_ALL) {
        if (null === $conn) {
            $conn = $this->getConn($strQuery);
        }
        if(false == $this->isPdo($conn)) {
            throw new RuntimeException('该方法仅对PDO连接有效！');
        }
        $stmt = $this->getStmt($strQuery, $conn);
        $queryStart = microtime(true) * 1000;
        $result = $stmt->execute();
        $queryEnd = microtime(true) * 1000;
        $this->lastQuery = $strQuery;
        $logger = Server::getLogger();
        if($result) {
            $logger && $logger->debug(' [SQL in ' . sprintf('%.3f', $queryEnd - $queryStart) . 'ms]: ' . $strQuery);

            switch ($fetchType) {
                case self::FETCH_TYPE_ROW:
                    return $stmt->fetch(\PDO::FETCH_ASSOC);
                default:
                    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            if($logger) {
                $logger->error(json_encode(array(
                    'strQuery' => $strQuery,
                    'errorInfo' => $errorInfo
                )));
            } else {
                print_r(array(
                    'strQuery' => $strQuery,
                    'errorInfo' => $errorInfo
                ));
            }
            return false;
        }

    }

    /**
     * @param      $strQuery
     * @param null $conn
     * @param      $fetchType
     * @return array
     */
    protected function query($strQuery, $conn=null, $fetchType = self::FETCH_TYPE_ALL) {
        return $this->fetchResult($strQuery, $conn, $fetchType);
    }

    /**
     * @param \PDO $pdo
     */
    protected function setPdoAttr($pdo) {
        $pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * @return string
     */
    public function getLastQuery() {
        return $this->lastQuery;
    }

    /**
     * @param string $sequenceName
     * @return mixed
     */
    public function lastInsertId($sequenceName='') {
        $conn = $this->writeConn();
        $sequenceName = $sequenceName ?: $this->sequenceName;
        return $conn->lastInsertId($sequenceName);
    }

    public function affectRows() {
        return $this->affectRows;
    }

    /**
     * @param $strQuery
     * @return  bool
     */
    public function execute($strQuery) {
        $conn = $this->writeConn();
        $stmt = $this->getStmt($strQuery, $conn);
        $queryStart = microtime(true) * 1000;
        $result = $stmt->execute();
        $queryEnd = microtime(true) * 1000;
        $this->lastQuery = $strQuery;
        $logger = Server::getLogger();
        if($result) {
            $logger && $logger->debug('[SQL in ' . sprintf('%.3f', $queryEnd - $queryStart) . 'ms]: ' . $strQuery);
            $this->affectRows = $stmt->rowCount();
            return true;
        }
        $errorInfo = $stmt->errorInfo();
        $msg = json_encode(array(
            'strQuery'=>$strQuery,
            'errorInfo'=>$errorInfo
        ), JSON_UNESCAPED_UNICODE);
        $this->setLastException(new RuntimeException($msg));
        if($logger) {
            $logger->error($msg);
        } else {
            print_r(array(
                'strQuery' => $strQuery,
                'errorInfo' => $errorInfo
            ));
        }
        return false;
    }

    /**
     * @param $strQuery
     * @param $order
     * @return string
     */
    protected function getOrderQuery($strQuery, $order) {
        $order = trim($order);
        $order = preg_replace('#^ORDER\s+BY\s+#i', '', $order);
        $strQuery = preg_replace('#ORDER\s+BY\s+' . $order . '$#i', '', $strQuery);
        $strQuery .= ' ORDER BY ' . $order;

        return $strQuery;
    }

    public function getOne($strQuery) {
        $row = $this->getRow($strQuery);
        if(false == $row) return null;
        $arr = array_values($row);
        return $arr[0];
    }

    /**
     * @param $strQuery
     * @return array
     */
    public function getRow($strQuery) {
        $conn = $this->readConn();
        return $this->query($strQuery, $conn, self::FETCH_TYPE_ROW);
    }

    /**
     * @param $strQuery
     * @param $order
     * @return array
     */
    public function getAll($strQuery, $order='') {
        $conn = $this->readConn();
        if($order) {
            $strQuery = $this->getOrderQuery($strQuery, $order);
        }
        return $this->query($strQuery, $conn, self::FETCH_TYPE_ALL);
    }

    /**
     * @param        $strQuery
     * @param        $mapCol
     * @param string $valueCol
     * @return array
     */
    public function getMapArray($strQuery, $mapCol, $valueCol='', $order='') {
        $rows = $this->getAll($strQuery, $order);

        $mapCol = strtolower($mapCol);
        $valueCol = strtolower($valueCol);
        $mapArray = array();
        foreach ($rows as $row) {
            $value = $valueCol ? $row[$valueCol] : $row;
            $key = $row[$mapCol];
            $mapArray[$key] = $value;
        }
        return $mapArray;
    }

    /**
     * @param string $strQuery
     * * @param string $order
     * @param int    $currentPage
     * @param int    $pageSize
     * @return array
     */
    public function getLimit($strQuery, $order='', $currentPage=1, $pageSize=20) {
        $limitQuery = $this->limitQuery($strQuery, $order, $currentPage, $pageSize);
        return $this->getAll($limitQuery);
    }

    /**
     * @param $string
     * @return string
     */
    public function quote($string) {
        $conn = $this->readConn();
        return $conn->quote($string);
    }

    /**
     * @param $datetime
     * @return int
     */
    public function strtotime($datetime) {
        $datetime = preg_replace('#:\d{3}#', '', $datetime);
        return strtotime($datetime);
    }

    /**
     * @param $timestamp
     * @return string|null
     */
    public function timestamp($timestamp) {
        return $timestamp;
    }

    /**
     * @param        $datetime
     * @param string $format
     * @return string
     */
    public function datetime($datetime, $format='Y-m-d H:i:s') {
        if(false == $datetime) return null;
        return date($format, $this->strtotime($datetime));
    }

    /**
     * @param        $strQuery
     * @param string $order
     * @param int    $currentPage
     * @param int    $pageSize
     * @return string
     */
    abstract protected function limitQuery($strQuery, $order='', $currentPage=1, $pageSize=20);
}