<?php
/**
 * Created by PhpStorm.
 * User: gengruifeng
 * Date: 2018/10/17
 * Time: 下午5:13
 */

namespace Com;



use Logic\App\DayRecord;

class AppService
{

    public static function publish ($data)
    {
        if(!empty($data['imgs'])){
            $data['images'] = str_replace ('-grf-', ',', ltrim ($data['imgs'], '-grf-'));
            unset($data['imgs']);
        }
        $data['uid'] = 1;
        $ret = DayRecord::publish ($data);
        if(!$ret){
            throw new \Exception(setEexcetion('发表失败，请稍后再发！', 301));
        }
    }

    public static function getDayRecordList($page)
    {
        $data = [
            'page' => $page,
            'list' => []
        ];
        $list = DayRecord::getList ($page);
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['images'] = explode (',', $v['images']);
                $list[$k]['time'] = DayRecord::time2Units (strtotime ($v['create_at'])).'前';
            }
        }
        $data['list'] = $list;
        return $data;
    }
}