<?php
namespace MicroService;

interface IStruct {
    /**
     * toArray返回字段格式为驼峰
     */
    const KEY_MODE_CAMEL = 'CAMEL';

    /**
     * toArray返回字段格式与属性格式相同
     */
    const KEY_MODE_NATURE = 'NATURE';

    /**
     * 数值型属性列表
     * @return array
     */
    public static function numberProps();

    /**
     * 字符型属性列表
     * @return array
     */
    public static function stringProps();

    /**
     * 混合型属性列表，可以为任意值，如果一个属性类型会变化， 在这个方法中返回
     * 例如：对象的所属机构信息 或者操作员信息等属性可能以ID，数组或者关联键值形式返回
     * @return array
     */
    public static function mixedProps();
}