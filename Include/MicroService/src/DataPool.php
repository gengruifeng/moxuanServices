<?php
namespace MicroService;

use MicroService\Dao\Table;
use Swoole\Coroutine;

/**
 * Class DataPool
 * 这个对象的目的是根据表主键ID批量查询数据信息并缓存， 主要用于一些关联表信息的查询， 例如创建人等，
 * 可以批量的把创建人ID加入数据池， 之后统一获取数据信息，并且在多个方法中只获取一次，减少数据库查询次数
 *
 * @package MicroService
 */
class DataPool {
    protected static $poolInstanceArray = array();
    protected $dataInfoArray = array();
    protected $tableColsArray = array();
    protected $colValues = array();
    protected $coroutineId = 0;


    protected function __construct() {
        $this->coroutineId = Coroutine::getuid();
    }

    /**
     * 这个方法要在入口函数中定义，比如说一个微服务的方法
     */
    public static function initPool() {
        $poolKey = self::getPoolKey();
        self::$poolInstanceArray[$poolKey] = new self();
        return self::$poolInstanceArray[$poolKey];
    }

    public static function freePool() {
        $poolKey = self::getPoolKey();
        unset(self::$poolInstanceArray[$poolKey]);
    }

    public static function getDataPool() {
        $poolKey = self::getPoolKey();
        if(false == isset(self::$poolInstanceArray[$poolKey])) {
            return self::initPool();
        }
        $coroutineId = Coroutine::getuid();
        $dataPool = self::$poolInstanceArray[$poolKey];
        #这里只判断普通请求的协程ID， 对于task等进程， 需要在swoole的回调方法中进行初始化！
        if($coroutineId && $dataPool->coroutineId != $coroutineId) {
            return self::initPool();
        }
        return self::$poolInstanceArray[$poolKey];
    }

    protected static function getPoolKey() {
        $pid = posix_getpid();
        $coroutineId = Coroutine::getuid();
        if($coroutineId > 0) {
            return 'dataPool-' . $pid . '-' . $coroutineId;
        }
        return 'dataPool-' . $pid . '-0';
    }

    public function getTable($tableName, $connName=null) {
        return Table::getTable($tableName, $connName);
    }

    protected function getPrimaryKey(Table $table) {
        $tableKeys = $table->getTableKeys();
        if(isset($tableKeys['primary'])) {
            if(is_array($tableKeys['primary']) && sizeof($tableKeys['primary']) == 1) {
                return $tableKeys['primary'][0];
            }
        }
        return false;
    }

    protected function getValueGroupKey(Table $table) {
        static $groupKeys = array();
        $connName = $table->getDao()->getConnName();
        $tableName = $table . '';
        $key = $connName . ':' . $tableName;
        if(false == isset($groupKeys[$key])) {
            $primaryCol = $this->getPrimaryKey($table);
            if (is_array($primaryCol)) {
                throw new \RuntimeException('数据表"' . $tableName . '"没有设置唯一主键，不能通过DataPool对象获取数据！');
            }
            $groupKeys[$key] =  $key. ':' . $primaryCol;
        }

        return $groupKeys[$key];
    }

    public function addColValue($table, $colValue, $reload=false) {
        $valueGroupKey = $this->getValueGroupKey($table);
        if(false == isset($this->colValues[$valueGroupKey])) {
            $this->colValues[$valueGroupKey] = array();
        }
        $infoKey = $valueGroupKey . ':' . $colValue;
        if($reload || (false == isset($this->dataInfoArray[$infoKey]) && false == isset($this->$colValues[$valueGroupKey][$colValue]))) {
            $this->colValues[$valueGroupKey][$infoKey] = $colValue;
        }
    }

    public function getInfo($table, $colValue) {
        $valueGroupKey = $this->getValueGroupKey($table);
        $infoKey = $valueGroupKey . ':' . $colValue;
        $groupKeyInfo = explode(':', $valueGroupKey);
        $primaryCol = array_pop($groupKeyInfo);
        if(false == isset($this->dataInfoArray[$infoKey])) {
            if(false == isset($this->colValues[$valueGroupKey][$infoKey])) {
                throw new ServiceException('数据表"' . $table . '"的主键ID"' . $colValue . '"没有添加到数据池，请先添加！');
            }
            if($this->colValues[$valueGroupKey]) {
                $infoList = $this->getInfoArray($table, $primaryCol, $this->colValues[$valueGroupKey]);
                foreach ($infoList as $row) {
                    $value = $row[$primaryCol];
                    $dataKey = $valueGroupKey . ':' . $value;
                    $this->dataInfoArray[$dataKey] = $row;
                }
                $this->colValues[$valueGroupKey] = array();
            }
        }
        return $this->dataInfoArray[$infoKey] ?: array();
    }


    protected function getInfoArray($table, $colName, $colValues) {
        $condition = array(
            $colName=>array('$in'=>$colValues)
        );
        $infoList = $table->findAll($condition);
        return $infoList;
    }


    public function refreshInfo($table, $colValue) {
        $valueGroupKey = $this->getValueGroupKey($table);
        $infoKey = $valueGroupKey . ':' . $colValue;
        if(isset($this->dataInfoArray[$infoKey])) {
            unset($this->dataInfoArray[$infoKey]);
        }
        $this->addColValue($table, $colValue, true);
        return $this->getInfo($table, $colValue);
    }


}