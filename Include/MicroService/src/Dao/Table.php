<?php
namespace MicroService\Dao;

use MicroService\Dao;
use MicroService\Util;
use \RuntimeException;

class Table implements ITable {
    protected static $tableArray = array();
    /**
     * @var Dao
     */
    protected $dao = null;
    protected $tableName = '';
    protected $sequenceName = '';
    protected $schema = array();

    protected $queryColumns = '*';

    protected $isExists = -1;
    protected $isView = false;
    protected $colMaps = array();
    protected $lowerColNameMaps = array();

    protected $tableKeys = null;

    protected $insertDefaultTimeCols = array('create_at', 'create_time', 'created_time', 'created_at');
    protected $updateDefaultTimeCols = array('update_at', 'update_time', 'updated_time', 'updated_at');

    /**
     * @param string     $tableName
     * @param string|Dao $conn
     * @return Table
     */
    public static function getTable($tableName, $conn=null) {
        if(is_object($conn)) {
            $connName = $conn->getConnName();
        } else {
            $connName = $conn;
        }
        $dao = Dao::getDao($connName);
        $tableKey = md5(serialize(array($connName, $tableName)));
        if (false == isset(self::$tableArray[$tableKey])) {
            if($dao->getDbType() == 'MONGODB') {
                $table = new MongoTable($tableName, $connName);
            } else {
                $table = new Table($tableName, $connName);
            }
            self::$tableArray[$tableKey] = $table;
        }

        return self::$tableArray[$tableKey];
    }

    /**
     * Table constructor.
     *
     * @param $tableName
     * @param $connName
     */
    protected function __construct($tableName, $connName) {
        $this->tableName = $tableName;
        $this->dao = Dao::getDao($connName);
    }

    /**
     * @return string
     */
    protected function realTableName() {
        return $this->dao->realTableName($this->tableName);
    }

    /**
     * @return Dao|RdbmsTrait
     */
    public function getDao() {
        return $this->dao;
    }


    public function getTableKeys() {
        if(null === $this->tableKeys) {
            try {
                $schema = $this->getSchema(false, true);
            } catch (\Exception $e) {
                $schema = $this->getSchema(true, false);
            }
            $keys = $schema['keys'] ?: array();
            if (false == $keys) {
                $this->tableKeys = array();
                return $this->tableKeys;
            }
            $returnKeys = array();
            foreach ($keys as $key) {
                if ($key['type'] == 'primary' || $key['type'] == 'unique') {
                    $type = $key['type'];
                    if ($type == 'unique') {
                        $type = implode(',', $key['columns']);
                    }
                    $returnKeys[$type] = $key['columns'];
                }
            }
            $this->tableKeys = $returnKeys;
        }
        return $this->tableKeys;
    }

    /**
     * @param bool $reload
     * @param $throw
     * @return array
     * @throws RuntimeException
     */
    public function getSchema($reload=false, $throw=false) {
        if(false == $reload) {
            if(false == $this->schema && $throw) {
                throw new RuntimeException('数据表' . $this->realTableName() . ' 没有初始化结构数据！');
            }
            return $this->schema;
        }
        if(false == $this->schema && $reload) {
            if($this->isExists == Dao::TABLE_NOT_EXISTS) {
                throw new RuntimeException('数据表' . $this->realTableName() . '不存在！');
            }
            $this->schema = $this->dao->getTableSchema($this->tableName);
        }

        return $this->schema;
    }

    /**
     * @param $schema
     * @return bool
     */
    public function setSchema($schema) {
        if(false == $this->schema) {
            $this->schema = $schema;
            if(-1 == $this->isExists) {
                $this->isExists = $this->isExists();
            }
            if (Dao::TABLE_NOT_EXISTS == $this->isExists) {
                $this->create($schema);
            }
        }
        return true;
    }

    /**
     * @param bool $recheck
     * @return int
     */
    public function isExists($recheck=false) {
        if(-1 == $this->isExists || $recheck) {
            $tableName = $this->tableName;
            $this->isExists = $this->dao->isTableExists($tableName, false);
            if($this->isExists && $this->isExists == Dao::TABLE_IS_VIEW) {
                $this->isView = true;
            }
        }
        return $this->isExists;
    }

    /**
     * @param $schema
     * @return bool
     * @throws \RuntimeException
     */
    public function create($schema) {
        if(Dao::TABLE_NOT_EXISTS == $this->isExists) {
            $tableName = $this->tableName;
            if($this->dao->createTable($tableName, $schema)) {
                $this->schema = $schema;
                $this->isExists = Dao::TABLE_IS_TABLE;
                return true;
            } else {
                throw new RuntimeException('create exception');
            }
        }
        return true;
    }

    /**
     * 把各种样式的字段别名转换为标准字段名，返回的就是数据库里的字段名样式
     * @param $colName
     * @return mixed|string
     * @throws RuntimeException
     */
    public function toColumnName($colName) {
        $colName = trim($colName);
        if(preg_match('#\s+#', $colName)) return $colName;
        $schema = $this->getSchema(false, true);
        if(false == $this->lowerColNameMaps) {
            foreach ($this->schema['columns'] as $col=>$colInfo) {
                $this->lowerColNameMaps[strtolower($col)] = $col;
            }
        }

        $columns = $schema['columns'];
        if(isset($columns[$colName])) {
            return $colName;
        }
        if(false == $colName) {
            throw new RuntimeException('字段名称为空，无法获取字段名称！');
        }
        $lowerColName = strtolower($colName);
        if(isset($this->lowerColNameMaps[$lowerColName])) {
            return $this->lowerColNameMaps[$lowerColName];
        }
        $colName = Util::lowerFirst($colName);
        if(isset($this->colMaps[$colName])) {
            return $this->colMaps[$colName];
        }
        $upperCharArray = range('A', 'Z');
        $lowerCharArray = range('a', 'z');
        foreach ($lowerCharArray as $k=>$v) {
            $lowerCharArray[$k] = '_' . $v;
        }
        $lowerColName = str_replace($upperCharArray, $lowerCharArray, $colName);
        if(isset($columns[$lowerColName])) {
            $this->colMaps[$colName] = $lowerColName;
            return $lowerColName;
        }

        throw new RuntimeException('数据表"' . $this->realTableName() . '"中不存在字段"' . $colName . '"');
    }

    /**
     * @param $condition
     * @return string
     * @throws RuntimeException
     */
    public function condition($condition) {
        try {
            $schema = $this->getSchema(false, true);
        } catch (\Exception $e) {
            $schema = $this->getSchema(true, false);
        }

        $columns = $schema['columns'];

        if(is_string($condition) && preg_match('#[\s=<>%_]+#', $condition)) {
            return $condition;
        }
        if(is_integer($condition) || is_string($condition)) {
            $keys = $schema['keys'];
            foreach ($keys as $key) {
                $keyType = strtolower($key['type']);
                if($keyType == 'primary') {
                    $keyCols = $key['columns'];
                    if(sizeof($keyCols) == 1) {
                        $pkCol = $keyCols[0];
                        $colInfo = $columns[$pkCol];
                        $colType = $this->dao->getColumnType($colInfo);
                        if($colType == Dao::COLUMN_TYPE_INT) {
                            return $pkCol . '=' . abs($condition);
                        } else if($colType == Dao::COLUMN_TYPE_STRING) {
                            return $pkCol . '=' . $this->dao->quote($condition);
                        }
                    }
                }
            }
            throw new RuntimeException('1查询条件设置错误！' . var_export($condition, true));
        }

        if(false == is_array($condition)) {
            throw new RuntimeException('2查询条件设置错误' . var_export($condition, true));
        }

        $condArr = array();
        foreach ($condition as $colName=>$colValue) {
            try {
                $colName = $this->toColumnName($colName);
            } catch (\Exception $e){}
            if($colName == '$and' || $colName == '$or') {
                if (false == is_array($colValue)) {
                    throw new RuntimeException('$and与$or为键时， 值必须为数组！');
                }
                $subConds = array();
                foreach ($colValue as $subCond) {
                    $subConds[] = $this->condition($subCond);
                }
                if ($colName == '$and') {
                    $condArr[] = '(' . implode(' AND ', $subConds) . ')';
                } else {
                    #$or操作符
                    $condArr[] = '(' . implode(' OR ', $subConds) . ')';
                }
            } else if ($colName  == '$expr') {
                #表达式， 直接返回
                $condArr[] = $colValue;
            } else {
                #标准字段名
                if(isset($columns[$colName])) {
                    $colInfo = $columns[$colName];
                    if (is_array($colValue)) {
                        $k = $v = '';
                        foreach ($colValue as $k => $v) break;
                        $k = strtolower($k);
                        switch ($k) {
                            case '$in':
                                if(false == is_array($v)) {
                                    throw new RuntimeException('$in操作符的值数据必须为数组！');
                                }
                                if(false == $v) {
                                    throw new RuntimeException('$in操作符的值数据不能为空！');
                                }
                                $vals = array();
                                foreach ($v as $val) {
                                    $vals[] = $this->renderColumnValue($colName, $colInfo, $val);
                                }
                                $condArr[] = $colName . ' IN (' . implode(',', $vals) . ')';
                            break;
                            case '$between':
                                if (false == is_array($v) || sizeof($v) != 2) {
                                    throw new RuntimeException('$between操作符的值数据必须为长度为2的数组！');
                                }
                                sort($v);
                                $condArr[] = $colName . ' BETWEEN ' . $this->renderColumnValue($colName, $colInfo, $v[0]) . ' AND ' . $this->renderColumnValue($colName, $colInfo, $v[1]);
                            break;
                            case '$gt':
                            case '$lt':
                            case '$gte':
                            case '$lte':
                            case '$ne':
                                if(is_array($v)) {
                                    throw new RuntimeException($k . '操作符的值不能为数组！');
                                }
                                $opArr = array(
                                    '$gt'=>'>', '$gte'=>'>=', '$lt'=>'<', '$lte'=>'<=','$ne'=>'!='
                                );

                                $condArr[] = $colName . $opArr[$k] . $this->renderColumnValue($colName, $colInfo, $v);
                            break;
                            case '$null':
                                $v = !!$v;
                                if ($v) {
                                    $condArr[] = $colName . ' IS NULL';
                                } else {
                                    $condArr[] = $colName . ' IS NOT NULL';
                                }
                            break;
                            case '$blank':
                                $v = !!$v;
                                if($v) {
                                    $condArr[] = '(' . $colName . ' IS NULL OR ' . $colName . '= \'\')';
                                } else {
                                    $condArr[] = '(' . $colName . ' IS NOT NULL AND ' . $colName . ' != \'\')';
                                }
                            break;

                            case '$like':
                                $condArr[] = $colName . ' LIKE ' . $this->dao->quote($v);
                            break;
                            default:
                                throw new RuntimeException('不支持的操作符！');
                            break;
                        }
                    } else {
                        $condArr[] = $colName . '=' . $this->renderColumnValue($colName, $colInfo, $colValue);
                    }
                }
            }
        }
        return '(' . implode(' AND ', $condArr) . ')';
    }

    /**
     * @param $data
     * @return array
     * @throws RuntimeException
     */
    public function idCondition($data) {
        $schema = $this->getSchema(false, true);
        $keys = $schema['keys'];

        foreach ($data as $key=>$val) {
            $key = $this->toColumnName($key);
            $data[$key] = $val;
        }
        foreach ($keys as $key) {
            $keyType = strtolower($key['type']);
            if($keyType == 'primary' || $keyType == 'unique') {
                $keyCols = $key['columns'];
                $cond = array();
                foreach ($keyCols as $col) {
                    if(false == isset($data[$col])) {
                        $cond = array();
                        break;
                    } else {
                        $cond[$col] = $data[$col];
                    }
                }
                if($cond) {
                    return $cond;
                }
            }
        }

        throw new RuntimeException('传递的数组无法确定唯一记录行！');
    }

    protected function order($sortOrder) {
        if($sortOrder && is_string($sortOrder)) {
            if(preg_match('#rand\(\s*\)#i', $sortOrder)) {
                return $this->dao->randOrder();
            }

            $sortOrder = preg_replace('#^order by#', '', $sortOrder);

            $orderArr = explode(',', $sortOrder);
            $sortOrder = array();
            foreach ($orderArr as $order) {
                $orderInfo = preg_split('#\s+#', $order);
                $col = $orderInfo[0];
                $order = isset($orderInfo[1]) ? $orderInfo[1] : 'asc';
                $sortOrder[$col] = $order;
            }
        }

        if(is_array($sortOrder)) {
            $orderArr = array();
            foreach ($sortOrder as $col=>$order) {
                $order = strtolower($order);
                try {
                    $col = $this->toColumnName($col);
                    if(in_array($order,array('-1', 'desc'))) {
                        $orderArr[] = $col . ' DESC';
                    } else {
                        $orderArr[] = $col . ' ASC';
                    }
                } catch (\Exception $e) {}
            }
            if(false == $sortOrder) {
                return '';
            }
            $sortOrder = implode(',', $orderArr);
        }

        return $sortOrder;
    }

    public function getQuery($condition, $order='', $columns='*') {
        try {
            $this->getSchema(false, true);
        } catch (\Exception $e) {
            $this->getSchema(true, false);
        }
        $columns = $columns ?: '*';
        if($columns != '*') {
            $columns = $this->columnNames($columns);
        }
        $condition = $this->condition($condition);
        $strQuery = 'SELECT ' . $columns . ' FROM ' . $this->realTableName() . '
                     WHERE ' . $condition;
        if($order) {
            $order = $this->order($order);
            if($order) $strQuery .= ' ORDER BY ' . $order;
        }

        return $strQuery;
    }

    protected function columnNames($columns) {
        if($columns == '*') return $columns;
        if(is_string($columns)) {
            $originColumns = $columns;
            $columns = explode(',', $columns);
            #如果列表中包含了DISTINCT AS 或者不包含AS的别名（即当前字段中包含了空格）,直接返回原字段列表
            foreach ($columns as $colName) {
                $colName = trim($colName);
                if(preg_match('#\s+#', $colName)) {
                    return $originColumns;
                }
            }
        }
        $colNames = array();
        foreach ($columns as $colName) {
            try {
                $colName = $this->toColumnName($colName);
                $colNames[] = $colName;
            } catch (RuntimeException $e){}
        }
        $columns = implode(',', $colNames);
        if(false == $columns) $columns = '*';
        return $columns;
    }

    public function find($condition, $columns='*', $order='') {
        #$strQuery = $this->getQuery($condition, $order, $columns);
        #return $this->dao->getRow($strQuery);
        $rows = $this->findLimit($condition, $order, 1, 1, $columns);
        if(false == $rows) return array();
        return $rows[0];
    }


    public function findAll($condition, $order='', $columns='*') {
        $strQuery = $this->getQuery($condition, $order, $columns);
        $order = $this->order($order);
        return $this->dao->getAll($strQuery, $order);
    }

    /**
     * @param        $condition
     * @param string $order
     * @param int    $currentPage
     * @param int    $pageSize
     * @param string $columns
     * @return array
     */
    public function findLimit($condition, $order='', $currentPage=1, $pageSize=20, $columns='*') {
        $strQuery = $this->getQuery($condition, $order, $columns);
        $order = $this->order($order);
        return $this->dao->getLimit($strQuery, $order, $currentPage, $pageSize);
    }

    public function findMapArray($condition, $keyCol, $valCol='', $order='', $columns='*') {
        $strQuery = $this->getQuery($condition, $order, $columns);
        return $this->dao->getMapArray($strQuery, $keyCol, $valCol);
    }


    protected function renderDefaultValue($colName, $colInfo) {
        $colType = $this->dao->getColumnType($colInfo);
        if(false == isset($colInfo['default']) &&  false == in_array($colName, array_merge($this->insertDefaultTimeCols, $this->updateDefaultTimeCols))) {
            throw new RuntimeException('数据表' . $this->realTableName() . '的' . $colName . '字段没有设置默认值！');
        }
        switch ($colType) {
            case Dao::COLUMN_TYPE_INT:
            case Dao::COLUMN_TYPE_DECIMAL:
                return floatval($colInfo['default']);
            break;
            case Dao::COLUMN_TYPE_STRING:
                $defaultVal = $colInfo['default'];
                $upperDefaultVal = strtoupper($defaultVal);
                if(in_array($upperDefaultVal, array('UUID()', 'NOW()'))) {
                    switch ($upperDefaultVal) {
                        case 'UUID()';
                            return $this->dao->quote(Util::uuid());
                        break;
                        case 'NOW()':
                            return $this->dao->quote(date('Y-m-d H:i:s'));
                        break;
                        default:
                            return $this->dao->quote($defaultVal);
                        break;
                    }
                }

            break;
            case Dao::COLUMN_TYPE_DATE:
                return $this->dao->quote(date('Y-m-d'));
            break;
            case Dao::COLUMN_TYPE_TIME:
                return $this->dao->quote(date('H:i:s'));
            break;
            case Dao::COLUMN_TYPE_DATETIME:
                return $this->dao->quote(date('Y-m-d H:i:s'));
            break;
        }
    }

    /**
     * 根据字段类型格式化数值， 避免数值型的类型JSON化时都是字符串
     * @param $colName
     * @param $value
     * @return float|int|null|string
     */
    public function formatColumnValue($colName, $value) {
        $dao = $this->dao;
        if(false == isset($this->schema['columns'][$colName])) return $value;
        $colInfo = $this->schema['columns'][$colName];
        if(false == isset($colInfo['columnType'])) {
            $colType = $dao->getColumnType($colInfo);
            $this->schema['columns'][$colName]['columnType'] = $colType;
        }
        $colType = $this->schema['columns'][$colName]['columnType'];
        switch ($colType) {
            case Dao::COLUMN_TYPE_DATETIME:
                return $dao->datetime($value);
            case Dao::COLUMN_TYPE_DATE:
                return $dao->datetime($value, 'Y-m-d');
            case Dao::COLUMN_TYPE_TIME:
                return $dao->datetime($value, 'H:i:s');
            case Dao::COLUMN_TYPE_TIMESTAMP:
                return $dao->timestamp($value);
            case Dao::COLUMN_TYPE_INT:
                return intval($value);
            case Dao::COLUMN_TYPE_DECIMAL:
                return floatval($value);
            default:
                return $value;
        }
    }

    /**
     * @param $colName
     * @param $colInfo
     * @param $colValue
     * @return string|mixed
     * @throws RuntimeException
     */
    protected function renderColumnValue($colName, $colInfo, $colValue) {
        if(is_null($colValue)) {
            return 'NULL';
        }

        if(is_array($colValue)) {
            foreach ($colValue as $k=>$v) {
                $k = strtolower($k);
                if(in_array($k, array('$incre', '$decre'))) {
                    if(false == is_numeric($v)) {
                        throw new RuntimeException('$incre, $decre操作的值只允许为数值！');
                    }
                }
                switch ($k) {
                    case '$incre':
                        return $colName . '+' . $v;
                    break;
                    case '$decre':
                        return $colName . '-' . $v;
                    break;
                    case '$expr':
                        return $v;
                    break;
                    default:
                        throw new RuntimeException('不支持的运算符"' . $k . '",请通过$expr操作符实现！');
                }
                break;
            }
        }

        $colType = $this->dao->getColumnType($colInfo);
        switch ($colType) {
            case Dao::COLUMN_TYPE_INT:
            case Dao::COLUMN_TYPE_DECIMAL:
                #return floatval($colValue);
                if(floatval($colValue) < 0) {
                    return abs($colValue) * -1;
                }
                return abs($colValue);
            break;
            case Dao::COLUMN_TYPE_DATE:
            case Dao::COLUMN_TYPE_DATETIME:
            case Dao::COLUMN_TYPE_TIME:
                if(false == $colValue) {
                    return 'NULL';
                }
                return $this->dao->quote($colValue);
            break;
            default:
                return $this->dao->quote($colValue);
            break;

        }
    }

    /**
     * @param $data
     * @param $condition
     * @return bool|array
     * @throws \Exception
     */
    public function save($data, $condition=array()) {
        try {
            $schema = $this->getSchema(false, true);
        } catch (\Exception $e) {
            $schema = $this->getSchema(true, false);
        }

        $columns = $schema['columns'];
        if($condition) { //update
            $condition = $this->condition($condition);
            if(false == $condition) {
                throw new RuntimeException('数据表更新条件错误！' . json_encode($condition));
            }
            $saveData = array();
            foreach ($data as $col=>$value) {
                try {
                    $col = $this->toColumnName($col);
                } catch (\Exception $e) {
                    $col = '';
                }
                if($col) {
                    $colInfo = $columns[$col];
                    $colValue = $this->renderColumnValue($col, $colInfo, $value);
                    if ($colValue === 'NULL' && $colInfo['notnull']) {
                        #throw new RuntimeException('数据表' . $this->realTableName() . '的字段' . $col . '设置了非空， 不允许更新为NULL值');
                    } else {
                        $saveData[$col] = $colValue;
                    }
                }
            }
            if(false == $saveData) {
                throw new RuntimeException('数据表' . $this->realTableName() . '的SAVE操作传递的数组中不包含字段信息！');

            }
            foreach ($columns as $colName=>$colInfo) {
                if(in_array($colName, $this->updateDefaultTimeCols) && false == isset($saveData[$col])) {
                    $colValue = $this->renderDefaultValue($colName, $colInfo);
                    $saveData[$colName] = $colValue;
                }
            }

            $strQuery = 'UPDATE ' . $this->realTableName() . ' SET ';
            foreach ($saveData as $colName=>$colValue) {
                $strQuery .= $colName . '=' . $colValue . ',';
            }

            $strQuery = substr($strQuery, 0, -1);
            $strQuery .= ' WHERE ' . $condition;
            return $this->dao->execute($strQuery);
        } else {   //insert
            $saveData = array();
            $autoColName = '';
            foreach ($data as $colName=>$colValue) {
                try {
                    $colName = $this->toColumnName($colName);
                } catch (\Exception $e) {
                    $colName = '';
                }
                if($colName) {
                    $colInfo = $columns[$colName];
                    if(is_null($colValue)) {
                        if(isset($colInfo['default'])) {
                            $colValue = $this->renderDefaultValue($colName,$colInfo);
                        } else {
                            $colValue = 'NULL';
                        }
                    } else {
                        $colValue = $this->renderColumnValue($colName, $colInfo, $colValue);
                    }
                    $saveData[$colName] = $colValue;
                }
            }

            if(false == $saveData) {
                $e = new RuntimeException('数据写入失败，数组中不包含合法字段数据！');
                $this->dao->setLastException($e);
                return false;
            }
            $defTimeCols = array_merge($this->insertDefaultTimeCols, $this->updateDefaultTimeCols);

            foreach ($columns as $colName=>$colInfo) {
                if(false == isset($saveData[$colName]) && in_array($colName, $defTimeCols)) {
                    $saveData[$colName] = $this->renderDefaultValue($colName, $colInfo);
                } else {
                    #自增字段是否设置了数据
                    if (isset($colInfo['auto']) && $colInfo['auto'] && false == isset($saveData[$colName])) {
                        #如果没有设置，填充变量， 之后会为这个变量所指字段赋值
                        $autoColName = $colName;
                    }
                    if ((false == isset($colInfo['auto']) || false == $colInfo['auto'])
                        && (false == isset($saveData[$colName]) || $saveData[$colName] === 'NULL')) {
                        if (isset($colInfo['default'])) {
                            $colValue = $this->renderDefaultValue($colName, $colInfo);
                            if(null !== $colValue) {
                                $saveData[$colName] = $colValue;
                            }
                        } else {
                            #没有默认值的字段设置为NULL
                            $saveData[$colName] = 'NULL';
                        }
                    }
                }
            }

            $strQuery = 'INSERT INTO ' . $this->realTableName() . '
                        (' . implode(',', array_keys($saveData)) . ')
                        VALUES 
                        (' . implode(',', array_values($saveData)) . ')';

            if($this->dao->execute($strQuery)) {
                if($autoColName) $saveData[$autoColName] = $this->dao->lastInsertId($this->sequenceName);

                $idCondition = $this->idCondition($saveData);
                return $idCondition;
            }
            return false;
        }
    }

    /**
     * @param $dataArray
     * @return  bool
     */
    public function saveAll($dataArray) {
        $this->dao->beginTrans();
        try {
            foreach ($dataArray as $data) {
                if(false == $this->save($data)) {
                    throw new RuntimeException('数据写入失败：' . $this->getLastQuery());
                }
            }
            $this->dao->commitTrans();
            return true;
        } catch (\Exception $e) {
            $this->dao->rollbackTrans();
            return false;
        }
    }

    public function delete($condition) {
        $condition = $this->condition($condition);

        $strQuery = 'DELETE FROM ' . $this->realTableName() . '
                     WHERE ' . $condition;
        return $this->dao->execute($strQuery);
    }

    public function count($condition) {
        $condition = $this->condition($condition);
        $strQuery = 'SELECT count(1) FROM ' . $this->realTableName() . '
                     WHERE ' . $condition;
        return abs($this->dao->getOne($strQuery));
    }

    public function getLastQuery() {
        return $this->dao->getLastQuery();
    }

    public function sqlfunc($funcName, $colName, $condition, $groupCol, $alias='', $order='') {
        $condition = $this->condition($condition);
        $colName = $this->toColumnName($colName);
        $funcName = strtoupper($funcName);

        if(false == in_array($funcName, array('SUM', 'MAX', 'MIN', 'COUNT', 'AVERAGE'))) {
            throw new RuntimeException('不支持SQL聚合函数' . $funcName);
        }

        $strQuery = 'SELECT ';
        if($groupCol) {
            $groupCol = $this->toColumnName($groupCol);
            $strQuery .= $groupCol . ',';
        }
        $strQuery .= $funcName . '(' . $colName . ')';
        if($alias) {
            $strQuery .= ' AS ' . $alias;
        }
        $strQuery .= ' FROM ' . $this->realTableName() . '
                      WHERE ' . $condition;

        if($groupCol) {
            $strQuery .= ' GROUP BY ' . $groupCol;
            if($order) {
                $order = $this->order($order);
                $strQuery .= ' ORDER BY ' . $order;
            }
        }

        if($groupCol) {
            $mapArray = $this->dao->getMapArray($strQuery, $groupCol);
            foreach ($mapArray as $col=>$value) {
                if(false == is_array($value) && is_numeric($value)) {
                    $mapArray[$col] = floatval($value);
                }
                if(is_array($value) && $alias && is_numeric($value[$alias])) {
                    $mapArray[$col][$alias] = floatval($value[$alias]);
                }
            }
            return $mapArray;
        } else {
            $value = $this->dao->getOne($strQuery);

            if(is_numeric($value)) {
                return floatval($value);
            }
            return $value;
        }
    }

    public function lastInsertId() {
        return $this->dao->lastInsertId($this->sequenceName);
    }


    public function __toString() {
        return $this->realTableName();
    }
}