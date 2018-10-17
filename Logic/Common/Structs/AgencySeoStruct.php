<?php
namespace Logic\Common\Structs;

use MicroService\AbstractStruct;

class AgencySeoStruct extends AbstractStruct {
    public static function numberProps()
    {
        return array(
            'agency_id',        //机构ID
        );
    }

    public static function stringProps()
    {
        return array(
            'platform_name',    //平台名称
            'site_title',       //网站标题
            'seo_keywords',     //SEO关键词
            'seo_description',  //SEO介绍
        );
    }

    public static function mixedProps()
    {
        return array(

        );
    }

}