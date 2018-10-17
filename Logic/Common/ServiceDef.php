<?php
/**
 * Created by PhpStorm.
 * User: gengruifeng
 * Date: 2018/4/23
 * Time: 上午11:03
 */

function setEexcetion ($message = '', $code = 0)
{
    return json_encode ([ 'message' => $message, 'code' => $code ]);
}

function getEexcetion ($json)
{

    $data = json_decode ($json);

    if($data->code == 3001){

        //清cookie
    }

    return $data;
}