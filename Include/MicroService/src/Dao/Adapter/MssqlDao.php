<?php
namespace MicroService\Dao\Adapter;

use MicroService\Dao;
use \RuntimeException;

class MssqlDao extends Dao {
    use Dao\RdbmsTrait;

    protected $intColTypes = array('bit'=>2,  'tinyint'=>4, 'smallint'=>6, 'mediumint'=>9,
                                   'int'=>11, 'bigint'=>20);
    protected $decColTypes = array('float'=>'', 'double'=>'', 'decimal'=>'', 'numeric'=>'');
    protected $strColTypes = array('char'=>'', 'nchar'=>'', 'varchar'=>'', 'nvarchar'=>'','uniqueidentifier'=>'',
                                   'text'=>'', 'ntext'=>'', 'blob'=>'', 'binary'=>'', 'varbinary'=>'');
    protected $dateColTypes = array('date'=>'');
    protected $timeColTypes = array('time'=>'');
    protected $datetimeColTypes = array('datetime'=>'');

    protected function _connect($dbConfig) {
        if(isset($dbConfig['DB_DSN'])) {
            $dsn = $dbConfig['DB_DSN'];
        } else {
            $dbHost = $dbConfig['DB_HOST'];
            if(isset($dbConfig['DB_PORT']) && false == strpos($dbConfig['DB_HOST'], ':')) {
                $dbHost .= ':' . $dbConfig['DB_PORT'];
            }
            $dsn = 'dblib:host=' . $dbHost . ';dbname=' . $dbConfig['DB_NAME'] . ';charset=utf8';
        }
        try {
            $pdo = new \PDO($dsn, $dbConfig['DB_USER'], $dbConfig['DB_PASS']);
            $this->setPdoAttr($pdo);
            $this->lastPing = time();
            return $pdo;
        } catch (\Exception $e) {
            $msg = 'Database "' . $this->connName . '" connect failed on host "' . $dbConfig['DB_HOST'] . '"';
            throw new RuntimeException($msg);
        }
    }

    public function isTableExists($tableName, $isRealTableName = false) {
        if(false == $isRealTableName) {
            $tableName = $this->realTableName($tableName);
        }
        $strQuery = 'SELECT * FROM information_schema.tables 
                     WHERE TABLE_NAME =' . $this->quote($tableName);
        return sizeof($this->getAll($strQuery)) > 0;
    }

    protected function isIntColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return isset($this->intColTypes[$colType]);
    }

    protected function isDecimalColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return isset($this->decColTypes[$colType]);
    }

    protected function isStrColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return isset($this->strColTypes[$colType]);
    }

    protected function isDateColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return $colType == 'date';
    }

    protected function isTimeColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return $colType == 'time';
    }

    protected function isDatetimeColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return $colType == 'datetime';
    }

    protected function isTimestampColumn($column) {
        if(false == isset($column['type'])) {
            $column['type'] = $column['data_type'];
        }
        $typeInfo = explode('(', $column['type']);
        $colType = strtolower(trim($typeInfo[0]));
        return $colType == 'timestamp';
    }


    public function getColumnType($column) {
        if($this->isIntColumn($column)) return Dao::COLUMN_TYPE_INT;
        if($this->isStrColumn($column)) return Dao::COLUMN_TYPE_STRING;
        if($this->isDecimalColumn($column)) return Dao::COLUMN_TYPE_DECIMAL;
        if($this->isDateColumn($column)) return Dao::COLUMN_TYPE_DATE;
        if($this->isTimeColumn($column)) return Dao::COLUMN_TYPE_TIME;
        if($this->isDatetimeColumn($column)) return Dao::COLUMN_TYPE_DATETIME;
        if($this->isTimestampColumn($column)) return Dao::COLUMN_TYPE_TIMESTAMP;
    }

    public function getTableSchema($tableName, $isRealTableName = false) {
        #$conn = $this->readConn();
        #$conn->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        if(false == $isRealTableName) {
            $tableName = $this->realTableName($tableName);
        }

        $strQuery = 'SELECT column_name, column_default, is_nullable,data_type,character_maximum_length,
                            numeric_precision, numeric_scale,
                            COLUMNPROPERTY(OBJECT_ID(' . $this->quote($tableName) . '),COLUMN_NAME,\'IsIdentity\') auto_increment
                     FROM information_schema.columns
                     WHERE TABLE_NAME =' . $this->quote($tableName);

        $columnList = $this->getAll($strQuery);
        $columns = array();
        foreach ($columnList as $column) {
            $colName = $column['column_name'];
            $colType = $this->toColType($column);
            $notnull = $column['is_nullable'] == 'NO';
            $default = $column['column_default'];
            if($default) {
                $default = str_replace(array('((', '))'), '', $default);
                $ltrim = preg_replace('#^\(#', '', $default);
                if($ltrim != $default) {
                    $default = preg_replace('#\)$#', '', $ltrim);
                }
                $default = trim($default, "'");
            }
            $auto = $column['auto_increment'];

            $colInfo = array('type'=>$colType);
            if($notnull) $colInfo['notnull'] = true;
            if(false == is_null($default)) $colInfo['default'] = $default;
            if($auto) $colInfo['auto'] = true;

            $columns[$colName] = $colInfo;
        }
        $schema = array('columns'=>$columns);

        $keys = array();
        $strQuery = 'SELECT constraint_name,constraint_type 
                     FROM information_schema.table_constraints 
                     WHERE table_name=' . $this->quote($tableName);
        $keyTypes = $this->getMapArray($strQuery, 'constraint_name', 'constraint_type');
        $strQuery = 'SELECT * FROM information_schema.key_column_usage 
                     WHERE table_name=' . $this->quote($tableName) . '
                     ORDER BY constraint_name,ordinal_position';

        $keyColumns = $this->getAll($strQuery);

        foreach ($keyColumns as $key) {
            $keyName = $key['constraint_name'];
            if(false == isset($keys[$keyName])) {
                $keyType = $keyTypes[$keyName];
                if ($keyType) {
                    switch ($keyType) {
                        case 'PRIMARY KEY':
                            $keyType = 'primary';
                        break;
                        case 'UNIQUE':
                            $keyType = 'unique';
                        break;
                        default:
                            $keyType = '';
                    }
                    if($keyType) {
                        $keys[$keyName] = array('type'=>$keyType, 'columns'=>array());
                    }
                }
            }
            if(isset($keys[$keyName])) {
                $keys[$keyName]['columns'][] = $key['column_name'];
            }
        }

        if($keys) {
            $schema['keys'] = $keys;
        }
        #$conn->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        return $schema;
    }

    protected function toColType($column) {
        $colType = $this->getColumnType($column);
        switch ($colType) {
            case Dao::COLUMN_TYPE_INT:
                return 'int';
            break;
            case Dao::COLUMN_TYPE_DECIMAL:
                return 'numeric(' . $column['numeric_precision'] . ', ' . $column['numeric_scale'] . ')';
            break;
            case Dao::COLUMN_TYPE_STRING:
                $type = preg_replace('#^n#', '', $column['data_type']);
                $len = intval($column['character_maximum_length']);
                if($len >0 && $len < 1000) {
                    return $type . '(' . $len . ')';
                }
                return $type;
            break;
            case Dao::COLUMN_TYPE_DATE:
                return 'date';
            break;
            case Dao::COLUMN_TYPE_TIME:
                return 'time';
            break;
            case Dao::COLUMN_TYPE_DATETIME:
                return 'datetime';
            break;
            case Dao::COLUMN_TYPE_TIMESTAMP:
                return 'timestamp';
            break;

        }
    }


    public function createTable($tableName, $schema, $isRealTableName = false) {
        throw new RuntimeException('暂不实现！');
    }

    protected function closeConn($conn) {
        // TODO: Implement closeConn() method.
    }

    protected function limitQuery($strQuery, $order='', $currentPage = 1, $pageSize = 20) {
        $minRowNum = ($currentPage - 1) * $pageSize + 1;
        $maxRowNum = $minRowNum + $pageSize - 1;
        $order = trim($order);
        if($order) {
            $strQuery = str_ireplace($order, " ", $strQuery);
        } else {
            throw new RuntimeException('MSSQL执行LIMIT查询需传入$order参数');
        }

        if(false == preg_match("#ORDER BY#i", $order)) {
            $order = "ORDER BY " . $order;
        }

        $strQuery = 'SELECT * FROM (
            SELECT ROW_NUMBER() OVER(' . $order . ') _rownum,* FROM (
                ' . $strQuery . '
            ) _tbl
        ) _tbl  WHERE _rownum >=' . abs($minRowNum) . ' AND _rownum <=' . abs($maxRowNum);

        return $strQuery;
    }

    public function randOrder() {
        return ' newid()';
    }

    public function timestamp($timestamp) {
        return null;
    }


}