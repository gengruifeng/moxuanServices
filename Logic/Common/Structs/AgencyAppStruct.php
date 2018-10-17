<?php
namespace Logic\Common\Structs;

use MicroService\AbstractStruct;

class AgencyAppStruct extends AbstractStruct {
    public static function numberProps()
    {
        return array(
            'agency_id'         //机构ID值
        );
    }

    public static function stringProps()
    {
        return array(
            'app_key',          //appKey, 通过appKey获取机构ID
            'app_secret'        //加密密钥
        );
    }

    public static function mixedProps()
    {
        return array(
            'app_status',       //应用是否有效,
        );
    }

}