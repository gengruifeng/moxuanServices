<?php
/**
 * Created by PhpStorm.
 * User: gengruifeng
 * Date: 2018/10/17
 * Time: 下午4:32
 */

namespace App\Http\Controllers;


class DayRecord extends Controller
{

    public function uploadImg()
    {
        try {
            if(!empty($_FILES['img'])){
                throw new \Exception('参数错误');
            }


        } catch(\Exception $e) {

        }
    }
}