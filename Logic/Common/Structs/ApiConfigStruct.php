<?php
namespace Logic\Common\Structs;

use MicroService\AbstractStruct;

class ApiConfigStruct extends AbstractStruct {
    public static function numberProps()
    {
        return array(
            'agency_id',        //机构ID
        );
    }

    public static function stringProps()
    {
        return array(
            //请求头回调函数,  函数必须可以接受$agencyId, $userId参数,这里的$agencyId用于取一些配置数据
            'header_callback',   //array(AgencyCallback::class, 'methodName') 或者  AgencyCallback::methodName
        );
    }

    public static function mixedProps()
    {
        return array(
            #'create_at', //创建时间
            #'update_at'  //更新时间
        );
    }

}