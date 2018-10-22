<?php
/**
 * Created by PhpStorm.
 * User: gengruifeng
 * Date: 2018/10/17
 * Time: 下午4:32
 */

namespace App\Http\Controllers;


use App\Http\Library\Upload;
use Illuminate\Http\Request;

class DayRecord extends Controller
{

    public function uploadImg()
    {

        try {

            if(empty($_FILES['file'])){
                throw new \Exception(setEexcetion('参数错误', 301));
            }
            $filePath = Upload::uploadDo ($_FILES['file']);
            $this->setData (['img_path' => $filePath]);

        } catch(\Exception $e) {
            $excetion = getEexcetion($e->getMessage());
            $this->setCode($excetion->code);
            $this->setMessage($excetion->message);
        }

        $this->outputJson ();
    }

    public function publish(Request $request)
    {
        try {
            $apiArgs = $request->input();

            if(empty($apiArgs['diary']) && empty($apiArgs['imgs'])){
                throw new \Exception(setEexcetion('内容不能为空！', 301));
            }
            $rpcClient = $this->getRpcClient (APP_SERVICE_NAME, 'appService');
            $rpcClient->publish($apiArgs);

        } catch(\Exception $e) {
            $excetion = getEexcetion($e->getMessage());
            $this->setCode($excetion->code);
            $this->setMessage($excetion->message);
        }

        $this->outputJson ();
    }

    public function getDayRecordList(Request $request)
    {
        try {
            $apiArgs = $request->input();

            $page = !empty($apiArgs['page'])?$apiArgs['page']:1;
            $rpcClient = $this->getRpcClient (APP_SERVICE_NAME, 'appService');
            $list = $rpcClient->getDayRecordList($page);
            $this->setData ($list);

        } catch(\Exception $e) {
            $excetion = getEexcetion($e->getMessage());
            $this->setCode($excetion->code);
            $this->setMessage($excetion->message);
        }

        $this->outputJson ();
    }


}