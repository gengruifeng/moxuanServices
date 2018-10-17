<?php
namespace Logic\Common\Structs;

use MicroService\AbstractStruct;

class AgencyInfoStruct extends AbstractStruct {
    protected $propKeyMode = self::KEY_MODE_NATURE;
    public static function numberProps()
    {
        return array(
            'agency_id',                //机构ID
            'is_third_party',           //是否第三方机构(第三方机构需要设置特定的数据获取接口)
            'agency_status',            //机构有效状态,
        );
    }

    public static function stringProps()
    {
        return array(
            'agency_name',              //机构名称
            'agency_domain_prefix',     //机构域名前缀
            'agency_logo',              //机构LOGO
            'app_key',                  //机构的APP_KEY
            'app_secret',               //机构的签名加密密钥
        );
    }

    public static function mixedProps()
    {
        return array(
            //其他字段
        );
    }

}