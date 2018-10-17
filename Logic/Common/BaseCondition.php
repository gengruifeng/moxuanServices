<?php
namespace Logic\Common;

use MicroService\AbstractStruct;

/**
 * 这个类并不是抽象类, 仅用于方法获取到参数数组时初始化条件对象,并获取对象的类名
 * 例如:
 *
 *
 * /##
 *  # 服务方
 *  # @param BaseCondition $searchArgs
 *  # @param array $sortOrder
 *  # @param int $currentPage
 *  # @param int $pageSize
 *  # @return array [UserInfoStruct]
 *  #/
 *
 * public static function getUserList(Array $searchArgs, $sortOrder, $currentPage, $pageSize) {
 *      $cond = new BaseCondition($searchArgs);  //创建最简结构体对象,仅用于获取条件结构体类名
 *      $condClassName = $cond->conditionClassName;
 *
 *      switch($condClassName) {
 *          case InvalidTeacherCondition::class:
 *              return self::getInvalidUsers($searchArgs, $sortOrder, $currentPage, $pageSize);
 *          break;
 *      }
 * }
 *
 * protected static function getInvalidUsers($searchArgs, $sortOrder, $currentPage, $pageSize) {
 *      $searchAgs = new InvalidTeacherCondition($searchArgs);
 *      $startTime = $searchArgs->startTime;
 *      $endTime = $
 *      //todo:xxxxxxx
 * }
 *
 *
 *  /##
 *   # 调用方
 *   #/
 * public static function invalidTeachers() {
 *     $searchArgs = new InvalidTeacherCondition(array());
 *     $searchArgs->filter = true;
 *     self::getUserList($searchArgs->toArray(), array(),  1, 100);  //参数转数组是因为hprose查询参数需要设置为数组
 * }
 *
 *
 */
class BaseCondition extends AbstractStruct {
    public function __construct($data=array(), $keyMode='')
    {
        parent::__construct($data, $keyMode);
        $this->data['condition_class_name'] = __CLASS__;
    }


    public static function numberProps()
    {
        // TODO: Implement numberProps() method.
    }

    public static function stringProps()
    {
        return array(
            'condition_class_name',  //后续的继承类也必须包含这个字段,字段的值应设置为: static::class
        );
    }

    public static function mixedProps()
    {
        // TODO: Implement mixedProps() method.
    }

}


