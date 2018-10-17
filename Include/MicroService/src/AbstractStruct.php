<?php
namespace MicroService;

abstract class AbstractStruct implements IStruct,\ArrayAccess {
    /**
     * @var string
     */
    protected $propKeyMode = self::KEY_MODE_NATURE;

    /**
     * 这个属性在类实例化时会根据下边四个抽象方法进行初始化，之后__set,__get会依赖这个数组
     * @var array
     */
    protected static $propNames = array();
    /**
     * 驼峰式字段名数组
     * @var array
     */
    protected static $propNameMapping = array();


    /**
     * 当设置了键字段的属性后， 调用triggerSetters方法会根据setter方法自动填充关联的其他字段
     * array(
     *      'user_id'=>array('user_name', 'user_gender'),
     *      'dept_id'=>array('dept_name')
     *      )
     */
    protected static $triggerProps = array();


    /**
     * 类的所有属性值都会保存在数组中
     * @var array
     */
    protected $data = array();

    /**
     * 数据结构类构造函数，传递的参数$data需要包含类需要的属性字段
     *
     * @param $data
     * @param $keyMode
     */
    public function __construct($data=array(), $keyMode='') {
        if(false == $data) $data = array();
        $this->initialize($data);
        if($keyMode) {
            $this->propKeyMode = $keyMode;
        }
    }

    /**
     * @param $propNames
     * @param $forceUpdate
     */
    public function triggerSetters($propNames=array(), $forceUpdate=false) {
        if($propNames) $propNames = array_combine($propNames, $propNames);
        #如果设置强制更新，先清除所有关联字段值
        if($forceUpdate) {
            foreach(self::$triggerProps as $propKey=>$props) {
                foreach ($props as $propName) {
                    unset($this->data[$propName]);
                }
            }
        }
        foreach (self::$triggerProps as $propKey=>$props) {
            if($this->data[$propKey]) { #设置了触发器字段
                foreach ($props as $prop) {  #被字段影响的附属字段信息
                    #如果设置了更新哪些字段， 只有这些字段才会自动更新值
                    if(false == $propNames || isset($propNames[$prop])) {
                        if (false == array_key_exists($prop, $this->data)) {
                            #通过getter初始化数据，
                            $method = 'get' . ucfirst($prop);
                            if (method_exists($this, $method)) {
                                $this->$method();
                            }
                        }
                    }
                }
            }
        }
    }

    public function offsetExists($offset) {
        return $this->{$offset} !== null;
    }

    public function offsetGet($offset) {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value) {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset) {
        $classKey = static::classKey();
        $camelKey = self::formatKey($offset, self::KEY_MODE_CAMEL);
        if(isset(static::$propNameMapping[$classKey][$camelKey])) {
            $columnName = static::$propNameMapping[$classKey][$camelKey];
            $methodName = 'set' . ucfirst($camelKey);
            if(method_exists($this, $methodName)) {
                $this->$methodName(null);
            } else {
                $this->data[$columnName] = null;
            }
            unset($this->data[$columnName]);
        }
    }


    protected static function classKey() {
        return md5(static::class);
    }

    /**
     * 对象的数据初始化
     * @param $data
     * @return void
     */
    protected function initialize($data) {
        static::initPropNames();
        $this->data = array();
        foreach($data as $key=>$value) {
            $this->{$key} = $value;
        }
    }

    protected static function initPropNames() {
        $classKey = static::classKey();
        if(false == isset(static::$propNames[$classKey])) {
            $numProps = static::numberProps() ?: array();
            $strProps = static::stringProps() ?: array();
            $mixedProps = static::mixedProps() ?: array();
            foreach ($numProps as $propName) {
                $camelKey = self::formatKey($propName, self::KEY_MODE_CAMEL);
                static::$propNameMapping[$classKey][$camelKey] = $propName;
                static::$propNames[$classKey][$propName] = 'numeric';
            }
            foreach ($strProps as $propName) {
                $camelKey = self::formatKey($propName, self::KEY_MODE_CAMEL);
                static::$propNameMapping[$classKey][$camelKey] = $propName;
                static::$propNames[$classKey][$propName] = 'string';
            }
            foreach ($mixedProps as $propName) {
                $camelKey = self::formatKey($propName, self::KEY_MODE_CAMEL);
                static::$propNameMapping[$classKey][$camelKey] = $propName;
                static::$propNames[$classKey][$propName] = 'mixed';
            }
        }
    }

    /**
     * 魔术方法设置属性值，对不存在的属性，跳过设置
     * @param $key
     * @param $value
     * @return void
     */
    public function __set($key, $value) {
        $classKey = static::classKey();
        $camelKey = self::formatKey($key, self::KEY_MODE_CAMEL);
        if(isset(static::$propNameMapping[$classKey][$camelKey])) {
            $methodName = 'set' . ucfirst($camelKey);
            if(method_exists($this, $methodName)) {
                $this->$methodName($value);
            } else {
                $columnName = static::$propNameMapping[$classKey][$camelKey];
                $this->data[$columnName] = self::formatValue($value, static::$propNames[$classKey][$columnName]);
            }
        }
    }

    protected static function formatValue($value, $valueType) {
        switch ($valueType) {
            case 'numeric':
                return floatval($value);
            break;
            case 'string':
                return trim($value);
            break;
            case 'mixed':
                return $value;
            break;
        }
    }

    /**
     * 魔术方法获取属性， 对于不存在的属性返回NULL
     * @param $key
     * @return mixed|null
     */
    public function __get($key) {
        $camelKey = self::formatKey($key, self::KEY_MODE_CAMEL);
        $classKey = static::classKey();
        if(isset(static::$propNameMapping[$classKey][$camelKey])) {
            $columnName = static::$propNameMapping[$classKey][$camelKey];
            $methodName = 'get' . ucfirst($key);
            if(method_exists($this, $methodName)) {
                return $this->$methodName();
            } else {
                return $this->data[$columnName];
            }
        }
        return null;
    }

    /**
     * 是否设置了某一个属性
     * @param $key
     * @return bool
     */
    public function hasSet($key) {
        $camelKey = self::formatKey($key, self::KEY_MODE_CAMEL);
        $classKey = static::classKey();
        if(isset(static::$propNameMapping[$classKey][$camelKey])) {
            $columnName = static::$propNameMapping[$classKey][$camelKey];
            return array_key_exists($columnName, $this->data);
        }
        return false;

    }

    /**
     * 返回结构体类实例化所需的数据结构，简单的通过该方法就可以实现数据的格式化
     * $fieldMapping中定义的是$data的$key字段对应类的属性($propName)变量名，
     * 例如：构造函数需要的data数据需要uid字段， 但是原始数据的字段为 user_id,
     * 格式化方法为：  buildData(array('user_id'=>1), array('user_id'=>'uid'))
     * 注意：fieldMapping中的值字段必须是属性方法中定义的字段名
     * $fieldMapping中只需要定义不存在的字段
     *
     * @param array $data
     * @param array $fieldMapping [default=array]  array($fieldName=>$propName) $fieldName是$data的键名，$propName是类的属性方法中定义的字段名
     * @return array
     */
    public static function buildData($data, $fieldMapping=array()) {
        static::initPropNames();
        $arr = array();
        $classKey = static::classKey();
        foreach($data as $key=>$value) {
            #优先查找字段映射表
            if (isset($fieldMapping[$key])) {
                $propName = $fieldMapping[$key];
                $arr[$propName] = $value;
                continue;
            }
            #映射表不存在映射时字段转驼峰形式，然后判断是不是属于标准属性
            $key = self::formatKey($key, self::KEY_MODE_CAMEL);
            if(isset(static::$propNames[$classKey][$key])) {
                $propName = self::$propNames[$classKey][$key];
                $arr[$propName] = $value;
                continue;
            }
            #因为下划线和驼峰最终都会转化为标准驼峰， 所以不需要处理其他类型了
        }


        return $arr;
    }

    public static function format($data, $fieldMapping=array(), $returnAsArray=true) {
        $data = static::buildData($data, $fieldMapping);
        $object = new static($data);
        if($returnAsArray) return $object->toArray();
        return $object;
    }

    /**
     * @param $dataList
     * @param $fieldMapping
     * @return array
     */
    public static function formatAll($dataList, $fieldMapping=array()) {
        $resultList = array();
        foreach ($dataList as $key=>$data) {
            $data = static::buildData($data, $fieldMapping);
            $obj = new static($data);
            $resultList[$key] = $obj->toArray();
        }
        return $resultList;
    }

    /**
     * 字段格式化
     * @param $key
     * @param $keyMode
     * @return mixed|string
     */
    public static function formatKey($key, $keyMode) {
        static $keyArray = array();
        static $lowerUnderlineChars = array();
        static $upperChars = array();

        if(false == $lowerUnderlineChars) {
            $lowerChars = range('a', 'z');
            foreach ($lowerChars as $char) {
                $lowerUnderlineChars[] = '_' . $char;
            }
        }
        if(false == $upperChars) {
            $upperChars = range('A', 'Z');
        }

        if($keyMode == self::KEY_MODE_NATURE) {
            return $key;
        }
        $key = Util::lowerFirst($key);
        if(isset($keyArray[$keyMode][$key])) {
            return $keyArray[$keyMode][$key];
        }

        if($keyMode == self::KEY_MODE_CAMEL) {
            $formatKey = str_replace($lowerUnderlineChars, $upperChars, $key);
        } else {
            throw new \RuntimeException('结构体类的字段请使用下划线方式定义，格式化类型使用原生类型！');
        }
        $keyArray[$keyMode][$key] = $formatKey;

        return $formatKey;
    }

    protected function cleanKey($key) {
        $key = strtolower($key);
        $key = str_replace('_', '', $key);
        return $key;
    }

    /**
     * 返回对象的数据data
     * @param $keyMode
     * @return array
     */
    public function toArray($keyMode='') {
        $keyMode = $keyMode ?: $this->propKeyMode;
        $arrayData = array();

        foreach ($this->data as $key=>$value) {
            if($keyMode == self::KEY_MODE_CAMEL) {
                $key = self::formatKey($key, $keyMode);
            }
            if($value !== null) {
                if(is_array($value)) {
                    $arrayData[$key] = self::recurseToArray($value);
                } elseif(is_object($value)) {
                    if(method_exists($value, 'toArray')) {
                        $arrayData[$key] = $value->toArray();
                    } else {
                        $arrayData[$key] = (array)$value;
                    }
                } else {
                    $arrayData[$key] = $value;
                }
            }
        }
        return $arrayData;
    }

    /**
     * @param $data
     * @return  array
     */
    protected static function recurseToArray($data) {
        $arr = array();
        foreach ($data as $key=>$value) {
            if(is_array($value)) {
                $arr[$key] = self::recurseToArray($data);
            } else if(is_object($value)) {
                if(method_exists($value, 'toArray')) {
                    $arr[$key] = $value->toArray();
                } else {
                    $arr[$key] = (array)$value;
                }
            } else {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    public function toJson() {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function isEmpty() {
        return false == $this->data;
    }

}