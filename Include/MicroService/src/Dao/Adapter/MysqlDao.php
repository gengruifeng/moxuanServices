<?php
namespace MicroService\Dao\Adapter;

use MicroService\Dao;
use MicroService\ServiceException;

class MysqlDao extends Dao {
    use Dao\RdbmsTrait {
        Dao\RdbmsTrait::execute as executeQuery;
    }

    protected $intColTypes = array('bit'=>2, 'tinyint'=>4, 'smallint'=>6,
                                   'mediumint'=>9, 'int'=>11,  'bigint'=>20);
    protected $decColTypes = array('float'=>'', 'double'=>'', 'decimal'=>'', 'numeric'=>'');
    protected $strColTypes = array('char'=>'', 'varchar'=>'', 'text'=>'', 'tinytext'=>'', 'mediumtext'=>'', 'longtext'=>'',
                                   'blob'=>'', 'tinyblob'=>'', 'mediumblob'=>'', 'binary'=>'', 'varbinary'=>'');
    protected $dateColTypes = array('date'=>'');
    protected $timeColTypes = array('time'=>'');
    protected $datetimeColTypes = array('datetime'=>'');
    protected $timestampTypes = array('timestamp'=>'');

    protected function _connect($dbConfig) {
        $charset = $dbConfig['DB_CHARSET'] ?: 'utf8mb4';
        $connCfg = array(
            'host'=>$dbConfig['DB_HOST'],
            'user'=>$dbConfig['DB_USER'],
            'password'=>$dbConfig['DB_PASS'],
            'database'=>$dbConfig['DB_NAME'],
            'port'=>$dbConfig['DB_PORT'] ?: 3306,
            'timeout'=>10,
        );
        if($dbConfig['DB_DSN']) {
            $dsn = $dbConfig['DB_DSN'];
        } else {
            $dsn = 'mysql:host=' . $connCfg['host'] . ':' . $connCfg['port'] . ';dbname=' . $connCfg['database'] . ';charset=' . $charset;
        }
        try {
            $mysqlClient = new \PDO($dsn, $connCfg['user'], $connCfg['password']);
            $mysqlClient->exec('set names ' . $charset);
            $this->setPdoAttr($mysqlClient);
            $this->lastPing = time();
            return $mysqlClient;
        } catch (\Exception $e) {
            $msg = 'Database "' . $this->connName . '" connect failed on host "' . $dbConfig['DB_HOST'] . '"';
            throw new ServiceException($msg);
        }
    }

    /**
     * @param        $strQuery
     * @param null   $conn
     * @param string $fetchType
     * @return array
     */
    protected function query($strQuery, $conn=null, $fetchType=self::FETCH_TYPE_ALL) {
        if(null === $conn) {
            $conn = $this->getConn($strQuery);
        }
        if($this->isPdo($conn)) {
            return $this->fetchResult($strQuery, $conn, $fetchType);
        }
        $queryResult = $conn->query($strQuery);
        return $queryResult;
    }

    /**
     * @return mixed
     */
    protected function begin() {
        $conn = $this->writeConn();
        if($this->isPdo($conn)) {
            $conn->beginTransaction();
        } else {
            #如果使用的是swoole内置的mysql_client, 通过执行SQL方式启用事务
            $this->query('begin', $conn);
        }
    }

    protected function commit() {
        $conn = $this->writeConn();
        if($this->isPdo($conn)) {
            $conn->commit();
        } else {
            #如果使用的是swoole内置的mysql_client, 通过执行SQL方式提交事务
            $this->query('commit', $conn);
        }
    }

    protected function rollback() {
        $conn = $this->writeConn();
        if($this->isPdo($conn)) {
            $conn->rollBack();
        } else {
            #如果使用的是swoole内置的mysql_client, 通过执行SQL方式回滚事务
            $this->query('rollback', $conn);
        }
    }

    protected function isIntColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return isset($this->intColTypes[$type]);
    }

    protected function isDecimalColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return isset($this->decColTypes[$type]);
    }

    protected function isStrColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return isset($this->strColTypes[$type]);
    }

    protected function isDateColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return $type == 'date';
    }

    protected function isTimeColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return $type == 'time';
    }

    protected function isDatetimeColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return $type == 'datetime';
    }

    protected function isTimestampColumn($column) {
        $colType = $column['type'];
        $typeInfo = explode('(', $colType);
        $type = strtolower(trim($typeInfo[0]));
        return $type == 'timestamp';
    }

    public function getColumnType($column) {
        if($this->isIntColumn($column)) return Dao::COLUMN_TYPE_INT;
        if($this->isStrColumn($column)) return Dao::COLUMN_TYPE_STRING;
        if($this->isDecimalColumn($column)) return Dao::COLUMN_TYPE_DECIMAL;
        if($this->isDateColumn($column)) return Dao::COLUMN_TYPE_DATE;
        if($this->isTimeColumn($column)) return Dao::COLUMN_TYPE_TIME;
        if($this->isDatetimeColumn($column)) return Dao::COLUMN_TYPE_DATETIME;
        if($this->isTimestampColumn($column)) return Dao::COLUMN_TYPE_DATETIME;
    }

    /**
     * 这个方法用于把PHP中设置的字段类型转为建表语句中的字段类型
     * @param $column
     * @return int|mixed|string
     */
    protected function toColType($column) {
        $colTypeInfo = explode(' ', $column['column_type']);
        $isUnsigned = sizeof($colTypeInfo) == 2;
        if(isset($this->intColTypes[$column['data_type']])) {
            if ($isUnsigned) {
                //SQL SERVER没有unsigned类型， 为了兼容采用更大长度的整型
                $dataType = $column['data_type'];
                $length = $this->intColTypes[$dataType];
                $steps = array_values($this->intColTypes);
                foreach ($steps as $key => $len) {
                    if ($len == $length && isset($steps[$key + 1])) {
                        $length = $steps[$key + 1];
                    }
                }
                foreach ($this->intColTypes as $colType => $len) {
                    if ($len == $length) {
                        return $colType;
                    }
                }
            }
            return $column['data_type'];
        }
        if(in_array($column['data_type'], $this->decColTypes)) {
            return str_replace(array('float', 'double'), 'numeric', $column['column_type']);
        }
        return $column['column_type'];
    }

    /**
     * 获取表结构信息
     * @param      $tableName
     * @param bool $isRealTableName
     * @return array
     */
    public function getTableSchema($tableName, $isRealTableName=false) {
        if(false == $isRealTableName) {
            $tableName = $this->realTableName($tableName);
        }

        $strQuery = 'SELECT table_schema,table_name,column_name,column_default,is_nullable,
                            data_type, column_type,column_key,extra,column_comment
                     FROM information_schema.columns 
                     WHERE TABLE_SCHEMA=database() 
                       AND TABLE_NAME =' . $this->quote($tableName);
        $columnList = $this->getAll($strQuery);
        $columns = array();
        foreach ($columnList as $column) {
            $colName = $column['column_name'];
            $colType = $this->toColType($column);
            $notnull = $column['is_nullable'] == 'NO';
            $default = $column['column_default'];
            $auto = $column['extra'] == 'auto_increment';
            $comment = $column['column_comment'];

            $col = array(
                'type'=>$colType, 'notnull'=>$notnull
            );
            if(false == is_null($default)) {
                $col['default'] = $default;
                $col['notnull'] = true;
            }
            if($auto) $col['auto'] = true;
            if($comment) $col['comment'] = $comment;

            $columns[$colName] = $col;
        }
        $strQuery = 'SHOW KEYS FROM ' . $tableName;
        $keyList = $this->getAll($strQuery);

        $keys = array();
        foreach ($keyList as $key) {
            $keyName = strtolower($key['key_name']);
            if($keyName == 'primary') {
                $keyType = 'primary';
            } else {
                $keyType = $key['non_unique']  ? 'index' : 'unique';
            }
            $keys[$keyName]['type'] = $keyType;
            $keys[$keyName]['columns'][] = $key['column_name'];
        }

        return array('columns'=>$columns, 'keys'=>$keys);
    }

    public function execute($strQuery) {
        $conn = $this->writeConn();
        if($this->isPdo($conn)) {
            return $this->executeQuery($strQuery);
        }
        return $this->query($strQuery, $conn);
    }

    protected function limitQuery($strQuery, $order='', $currentPage=1, $pageSize=20) {
        if($order) {
            $order = trim($order);
            $order = preg_replace('#^ORDER\s+BY\s+#i', '', $order);
            $strQuery = preg_replace('#ORDER\s+BY\s+' . $order . '$#i', '', $strQuery);
            $strQuery .= ' ORDER BY ' . $order;
        }

        $limit = ' LIMIT ' . ($currentPage - 1) *  $pageSize . ',' . $pageSize;

        $strQuery .= $limit;
        return $strQuery;
    }

    public function quote($string) {
        $client = $this->writeConn();
        if($this->isPdo($client)) {
            return $client->quote($string);
        }
        return "'" . mysqli_escape_string($string) . "'";
    }

    public function isTableExists($tableName, $isRealTableName=false) {
        if(false == $isRealTableName) {
            $tableName = $this->realTableName($tableName);
        }
        $strQuery = 'SELECT * FROM information_schema.tables 
                     WHERE TABLE_SCHEMA=database()  
                       AND TABLE_NAME =' . $this->quote($tableName);
        $tableInfo = $this->getRow($strQuery);

        if(false == $tableInfo) {
            return self::TABLE_NOT_EXISTS;
        } else if($tableInfo['table_type'] == 'BASE TABLE') {
            return self::TABLE_IS_TABLE;
        } else {
            return self::TABLE_IS_VIEW;
        }
    }

    protected function closeConn($conn) {

    }

    public function createTable($tableName, $schema, $isRealTableName = false) {
        if(false == $schema
            || false == is_array($schema)
            || false == isset($schema['columns'])
            || false == $schema['columns']
            || false == is_array($schema['columns'])
            || false == $schema['columns']
        ) {

        }
        if(false == $isRealTableName) {
            $tableName = $this->realTableName($tableName);
        }

        $createSql = 'CREATE TABLE ' . $tableName . '(';
        $columns = array();

        foreach ($schema['columns'] as $colName=>$colCfg) {
            $colQuery = $colName . ' ' . $colCfg['type'];

            if($colCfg['notnull']) $colQuery .= ' NOT NULL';
            if(isset($colCfg['default']) && false == is_null($colCfg['default'])) {
                if(in_array($colCfg['type'], array('date', 'time', 'datetime', 'timestamp'))) {
                    //建表时不设置默认值， 默认值通过TABLE类实现
                } else {
                    $colQuery .= ' DEFAULT ' . $this->quote($colCfg['default']);
                }
            }

            if($this->isIntColumn($colCfg) && $colCfg['auto']) {
                $colQuery .= ' AUTO_INCREMENT';
            }
            $columns[] = $colQuery;
        }
        $keys = array();
        if($schema['keys'] && is_array($schema['keys'])) {
            foreach ($schema['keys'] as $keyName=>$keyCfg) {
                $keyType = strtolower($keyCfg['type']);
                switch ($keyType) {
                    case 'primary':
                        $keys[] = 'PRIMARY KEY (' . implode(',', $keyCfg['columns']) . ')';
                    break;
                    case 'unique':
                        $keys[] = 'UNIQUE KEY (' . implode(',', $keyCfg['columns']) . ')';
                    break;
                    case 'index':
                        $keys[] = 'KEY (' . implode(',', $keyCfg['columns']) . ')';
                    break;
                }
            }
        }

        $createSql .= implode(',', $columns);
        if($keys) {
            $createSql .= ',' . implode(',', $keys);
        }
        $createSql .= ') charset utf8';

        return $this->execute($createSql);
    }

    public function randOrder() {
        return ' rand()';
    }


}