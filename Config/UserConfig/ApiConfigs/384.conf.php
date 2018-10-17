<?php
return array(
    'headerCallback'=>function($userId=null) {
        return array(
            'X-USER-ID'=>$userId
        );
    },

    //通用处理方法, 要兼顾到所有的接口
    'paramConverter'=>function($params) {
        return $params;
    },

    //通用处理方法, 要兼顾到所有接口
    'resultConverter'=>function($result) {
        return $result;
    }
);