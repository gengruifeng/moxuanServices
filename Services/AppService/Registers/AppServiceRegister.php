<?php
namespace Registers;

use MicroService\Register;

/**
 * 机构服务注册类, 一个微服务启动可以发布一些子服务, 这里只是一个子服务
 * @package Registers
 */
class AppServiceRegister extends Register {
    public function getClasses()
    {
        #数组项的三个字段意义:   0:类的完整名, 1:接口方法的前缀,  2:排除
        return array(
            array(\Com\AppService::class, '', array()),
        );
    }

    #尽量不发布对象方法, 全都通过getClass中返回, 都是发布静态方法,即所有接口都是public static function xxx这种定义形式
    public function getObjects()
    {
        // TODO: Implement getObjects() method.
    }

}