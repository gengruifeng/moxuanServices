<?php
namespace Logic\App;

use MicroService\Dao\Table;

class DayRecord
{

    public static function publish($data)
    {
        $table = Table::getTable('g_day_record', MOXUAN_CONN);
        return $table->save ($data);
    }

    public static function getList($page)
    {
        $condition = [
            'is_remove' => 0
        ];
        $table = Table::getTable('g_day_record', MOXUAN_CONN);
        return $table->findAll ($condition, 'id desc') ;
    }

    public static function  time2Units  ( $createTime )
    {
        $now = time ();
        $time = $now - $createTime;
        $year    =  floor ( $time  /  60  /  60  /  24  /  365 );
        $time   -=  $year  *  60  *  60  *  24  *  365 ;
        $month   =  floor ( $time  /  60  /  60  /  24  /  30 );
        $time   -=  $month  *  60  *  60  *  24  *  30 ;
        $week    =  floor ( $time  /  60  /  60  /  24  /  7 );
        $time   -=  $week  *  60  *  60  *  24  *  7 ;
        $day     =  floor ( $time  /  60  /  60  /  24 );
        $time   -=  $day  *  60  *  60  *  24 ;
        $hour    =  floor ( $time  /  60  /  60 );
        $time   -=  $hour  *  60  *  60 ;
        $minute  =  floor ( $time  /  60 );
        $time   -=  $minute  *  60 ;
        $second  =  $time ;
        $elapse  =  '' ;

        $unitArr  = array( '年'   => 'year' ,  '个月' => 'month' ,   '周' => 'week' ,  '天' => 'day' ,
                           '小时' => 'hour' ,  '分钟' => 'minute' ,  '秒' => 'second'
        );

        foreach (  $unitArr  as  $cn  =>  $u  )
        {
            if ( $ $u  >  0  )
            {
                $elapse  = $ $u  .  $cn ;
                break;
            }
        }

        return  $elapse ;
    }
}