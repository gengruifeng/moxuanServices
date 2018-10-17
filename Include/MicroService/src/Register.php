<?php
namespace MicroService;


abstract class Register {
    protected static $regServices = array();
    /**
     * @var Server
     */
    protected static $server = null;
    private static $magicMethods = array(
        "__construct",
        "__destruct",
        "__call",
        "__callStatic",
        "__get",
        "__set",
        "__isset",
        "__unset",
        "__sleep",
        "__wakeup",
        "__toString",
        "__invoke",
        "__set_state",
        "__clone"
    );
    protected function __construct() {}

    public static function setServer($server) {
        self::$server = $server;
    }

    /**
     * 提供服务的类数组，会发布类中所有的public static的方法为接口
     * 每个配置项为一个包含类名， 每个类方法前缀的数组（方法前缀可以为空）
     * eg: array([User::class, 'User', []], [Menu::class, 'Menu', []]);
     * (注意：父类的静态方法不会被发布，需要单独添加父类的注册项)
     * @return array
     */
    abstract public function getClasses();

    /**
     *提供服务的对象数组，会发布对象所有的public方法为接口
     * 每个配置项为一个包含类实例，方法前缀的数组（方法前缀可为空）
     * 因为做接口时通常会缺少类实例化的参数， 所以尽量不采用发布对象方法为接口， 尽量采用发布静态方法
     * eg: array([new User(), 'u', []], [new Menu(), 'm', []])
     * @return array
     */
    abstract public function getObjects();

    /**
     * 这个方法会把每个类方法以及对象方法重建别名
     */
    public static function getMethods() {
        $register = new static();
        $classes = $register->getClasses() ?: array();
        $objects = $register->getObjects() ?: array();


        $staticMethods = array();
        $objectMethods = array();

        foreach ($classes as $classCfg) {
            $methods = self::getStaticMethods($classCfg);
            $prefix = $classCfg[1];
            foreach($methods as $method) {
                if($prefix) {
                    $methodName = $prefix . ucfirst($method);
                } else {
                    $methodName = $method;
                }
                $methodInfo = array(
                    array($classCfg[0], $method),
                    $methodName
                );

                $staticMethods[] = $methodInfo;
            }
        }

        foreach ($objects as $objectCfg) {
            $methods = self::getObjectMethods($objectCfg);
            $prefix = $objectCfg[1];
            foreach($methods as $method) {
                if($prefix) {
                    $methodName = $prefix . ucfirst($method);
                } else {
                    $methodName = $method;
                }
                $methodInfo = array(
                    array($objectCfg[0], $method),
                    $methodName
                );

                $objectMethods[] = $methodInfo;
            }
        }

        return array_merge($staticMethods, $objectMethods);
    }

    public static function getMethodInfo($name) {
        $register = new static();
        $classes = $register->getClasses();

        foreach ($classes as $classCfg) {
            $methods = self::getStaticMethods($classCfg);
            $prefix = $classCfg[1];
            foreach($methods as $method) {
                if($prefix) {
                    $methodName = $prefix . ucfirst($method);
                } else {
                    $methodName = $method;
                }
                $methodInfo = array(
                    array($classCfg[0], $method),
                    $methodName
                );

                if($methodName == $name) {
                    return self::reflectMethodInfo($methodInfo[0]);
                }
            }
        }


        $objects = $register->getObjects();

        foreach ($objects as $objectCfg) {
            $methods = self::getObjectMethods($objectCfg);
            $prefix = $objectCfg[1];
            foreach($methods as $method) {
                if($prefix) {
                    $methodName = $prefix . ucfirst($method);
                } else {
                    $methodName = $method;
                }
                $methodInfo = array(
                    array($objectCfg[0], $method),
                    $methodName
                );

                if($methodName == $name) {
                    return self::reflectMethodInfo($methodInfo[0]);
                }
            }
        }

        return "";

    }

    protected static function reflectMethodInfo($methodInfo) {
        if(is_object($methodInfo[0])) {
            $className = get_class($methodInfo[0]);
        } else {
            $className = $methodInfo[0];
        }

        $reflectMethod = new \ReflectionMethod($className, $methodInfo[1]);
        return $reflectMethod->getDocComment();

    }

    public static function register(Service $service, $uri) {
        $uri = strtoupper($uri);
        $uri = '/' . trim($uri, '/') . '/';
        $methods = self::getMethods();

        $funcArray = array();
        $aliasArray = array();
        foreach ($methods as $methodCfg) {
            $func = $methodCfg[0];
            $alias = $methodCfg[1];
            $funcArray[] = $func;
            if(isset($aliasArray[$alias])) {
                throw new \RuntimeException('存在重名发布方法"' . $alias . '"，请修改！');
            }
            $aliasArray[$alias] = $alias;
        }
        $service->addFunctions($funcArray, array_values($aliasArray));
        self::$regServices[$uri] = $service;
    }

    /**
     * @param $uri
     * @return \Hprose\Swoole\Http\Service
     */
    public static function getService($uri) {
        $uriInfo = explode('?', $uri);
        $uri = strtoupper($uriInfo[0]);
        $uri = '/' . trim($uri, '/')  . '/';
        if(isset(self::$regServices[$uri])) {
            $service = self::$regServices[$uri];
        } else {
            $service =  self::$server->getBaseService();
        }
        $beforeInvoke = self::$server->getBeforeInvoke();
        $afterInvoke = self::$server->getAfterInvoke();
        $errorHandler = self::$server->getErrorHandler();
        if($beforeInvoke && is_callable($beforeInvoke)) {
            $service->setBeforeInvoke($beforeInvoke);
        }
        if($afterInvoke && is_callable($afterInvoke)) {
            $service->setAfterInvoke($afterInvoke);
        }
        if($errorHandler && is_callable($errorHandler)) {
            $service->setErrorHandler($errorHandler);
        }
        return $service;
    }

    /**
     * @param array $classCfg
     * @return array
     */
    protected static function getStaticMethods($classCfg) {
        $className = $classCfg[0];
        $excludeMethods = isset($classCfg[2]) ? $classCfg[2] : array();
        if(false == is_array($excludeMethods)) {
            $excludeMethods = array($excludeMethods);
        }
        $result = get_class_methods($className);
        if (($parentClass = get_parent_class($className)) !== false) {
            $inherit = get_class_methods($parentClass);
            $result = array_diff($result, $inherit);
        }
        if(false == $result) $result = array();
        $methods = array_diff($result, self::$magicMethods);
        $staticMethods = array();
        foreach ($methods as $name) {
            if(false == in_array($name, $excludeMethods)) {
                $method = new \ReflectionMethod($className, $name);
                if ($method->isPublic() &&
                    $method->isStatic() &&
                    !$method->isAbstract()) {
                    $staticMethods[] = $name;
                }
            }
        }
        return $staticMethods;
    }

    /**
     * @param $objectCfg
     * @return array
     */
    protected static function getObjectMethods($objectCfg) {
        $object = $objectCfg[0];
        $className = get_class($object);
        $excludeMethods = isset($classCfg[2]) ? $objectCfg[2] : array();
        if(false == is_array($excludeMethods)) {
            $excludeMethods = array($excludeMethods);
        }
        $result = get_class_methods($className);
        if (($parentClass = get_parent_class($className)) !== false) {
            $inherit = get_class_methods($parentClass);
            $result = array_diff($result, $inherit);
        }
        $methods = array_diff($result, self::$magicMethods);
        $objectMethods = array();
        foreach ($methods as $name) {
            if(false == in_array($name, $excludeMethods)) {
                $method = new \ReflectionMethod($className, $name);
                if ($method->isPublic() &&
                    !$method->isStatic() &&
                    !$method->isConstructor() &&
                    !$method->isDestructor() &&
                    !$method->isAbstract()) {
                    $instanceMethods[] = $name;
                }
            }
        }
        return $objectMethods;
    }

}