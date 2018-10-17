<?php
namespace Logic\Common\Structs;

use MicroService\AbstractStruct;

/**
 * 这个类要考虑通用性, 不是只有某一个项目调用, 后期应该是所有的平台都要调用这个类
 */
class AccessTokenStruct extends AbstractStruct {
    public static function numberProps()
    {
        return array();
    }

    public static function stringProps()
    {
        return array();
    }

    public static function mixedProps()
    {
        return array(
            'agency_id',        //机构ID
            'platform_name',    //应用平台ID,便于后期按平台统计每日登录数
            'user_type',        //用户类型
            'user_id',          //平台用户ID
            'third_party_uid',  //第三方机构的UID
            'access_token',     //accessToken
            'expire_at',        //过期时间
            #'create_at',       //可以通过创建时间进行每日登录次数统计,也可以当做一个登录用户记录表,返回数据中不体现
            #'update_at',       //最后更新时间, 会根据最后更新时间判断是否需要刷新token,相当于对token进行续期,返回数据中不体现
        );
    }
}