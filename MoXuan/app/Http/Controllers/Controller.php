<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{

    private $code = 0;
    private $message = '操作成功';
    private $data = [];

    protected function setCode($code){
        $this->code = $code;
    }

    protected function setMessage($message){
        $this->message = $message;
    }

    protected function setData($data){
        $this->data = $data;
    }

    protected function outputJson()
    {
        $returnData = [
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
        return response()->json($returnData);
    }
}
