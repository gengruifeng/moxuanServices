<?php
namespace MicroService;

class Util {

    /**
     * @param string $uuid
     * @return string
     */
    public static function uuid($uuid='') {
        if($uuid) {
            return strtoupper(substr($uuid, 0, 36));
        }

        if (function_exists("uuid_create")){
            return strtoupper(uuid_create());
        }else{
            $randMd5 = md5(uniqid(mt_rand(), true));
            $uuid = substr($randMd5, 0, 8) . "-"
                .substr($randMd5, 8, 4) . "-"
                .substr($randMd5,12, 4) . "-"
                .substr($randMd5,16, 4) . "-"
                .substr($randMd5,20,12);
            return strtoupper($uuid);
        }
    }

    /**
     * @param $str
     * @return string
     */
    public static function sbc2dbc($str) {
        static $sbcArr, $dbcArr;
        if(false == $sbcArr) {
            $arr = array(
                array('１', '1'), array('２', '2'), array('３', '3'), array('４', '4'), array('５', '5'),
                array('６', '6'), array('７', '7'), array('８', '8'), array('９', '9'), array('０', '0'),
                array('ａ', 'a'), array('ｂ', 'b'), array('ｃ', 'c'), array('ｄ', 'd'), array('ｅ', 'e'),
                array('ｆ', 'f'), array('ｇ', 'g'), array('ｈ', 'h'), array('ｉ', 'i'), array('ｊ', 'j'),
                array('ｋ', 'k'), array('ｌ', 'l'), array('ｍ', 'm'), array('ｎ', 'n'), array('ｏ', 'o'),
                array('ｐ', 'p'), array('ｑ', 'q'), array('ｒ', 'r'), array('ｓ', 's'), array('ｔ', 't'),
                array('ｕ', 'u'), array('ｖ', 'v'), array('ｗ', 'w'), array('ｘ', 'x'), array('ｙ', 'y'),
                array('ｚ', 'z'), array('Ａ', 'A'), array('Ｂ', 'B'), array('Ｃ', 'C'), array('Ｄ', 'D'),
                array('Ｅ', 'E'), array('Ｆ', 'F'), array('Ｇ', 'G'), array('Ｈ', 'H'), array('Ｉ', 'I'),
                array('Ｊ', 'J'), array('Ｋ', 'K'), array('Ｌ', 'L'), array('Ｍ', 'M'), array('Ｎ', 'N'),
                array('Ｏ', 'O'), array('Ｐ', 'P'), array('Ｑ', 'Q'), array('Ｒ', 'R'), array('Ｓ', 'S'),
                array('Ｔ', 'T'), array('Ｕ', 'U'), array('Ｖ', 'V'), array('Ｗ', 'W'), array('Ｘ', 'X'),
                array('Ｙ', 'Y'), array('Ｚ', 'Z'), array('＇', '\''), array('＂', '"'), array('　', ' '),
                array('～', '~'), array('！', '!'), array('＠', '@'), array('＃', '#'), array('＄', '$'),
                array('％', '%'), array('＾', '^'), array('＆', '&'), array('＊', '*'), array('（', '('),
                array('）', ')'), array('＿', '_'), array('－', '-'), array('＋', '+'), array('＝', '='),
                array('｛', '{'), array('｝', '}'), array('［', '['), array('］', ']'), array('｜', '|'),
                array('＜', '<'), array('＞', '>'), array('？', '?'), array('，', ','), array('．', '.'),
                array('／', '/'), array('＼', '\\'),
            );
            $sbcArr = array();
            $dbcArr = array();
            foreach ($arr as $item) {
                $sbcArr[] = $item[0];
                $dbcArr[] = $item[1];
            }
        }
        return str_replace($sbcArr, $dbcArr, $str);
    }

    /**
     * 手机号码隐藏数字
     * @param $mobile
     * @return string
     */
    public static function secureMobile($mobile) {
        if(strlen($mobile) == 11) {
            return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
        }
        return $mobile;
    }

    /**
     * @param $mobile
     * @return string
     */
    public static function mobile($mobile) {
        $mobile = self::sbc2dbc($mobile);
        if(preg_match('#^1\d{10}$#', $mobile)) {
            return $mobile;
        }
        return '';
    }

    /**
     * 判断是否邮箱，并返回邮箱，如果不是邮箱， 返回空
     * @param $email
     * @return string
     */
    public static function email($email) {
        $email = self::sbc2dbc($email);
        $regex = '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i';
        if(preg_match($regex, $email)) {
            return $email;
        }

        return '';
    }

    /**
     * 判断字符串是否是一个MD5字符串
     * @param $string
     * @return int
     */
    public static function isMd5($string) {
        return preg_match('#^[0-9a-f]{32}$#i', $string);
    }

    /**
     * 判断是否邮箱
     * @param $email
     * @return bool
     */
    public static function isEmail($email) {
        return self::email($email) != '';
    }

    /**
     * 判断是否手机号
     * @param $mobile
     * @return bool
     */
    public static function isMobile($mobile) {
        return self::mobile($mobile) != '';
    }

    /**
     * 判断是否本地IP,
     * @param $ip
     * @return bool
     */
    public static function isLocalIp($ip) {
        if($ip == '127.0.0.1') {
            return true;
        }
        $long=ip2long($ip);
        $data=array(
            24=>'10.255.255.255',
            20=>'172.31.255.255',
            16=>'192.168.255.255'
        );
        foreach($data as $k=>$v){
            if($long >> $k === ip2long($v)>>$k){
                return true;
            }
        }

        $config = Config::getConfig();
        $localIpArray = $config->get('LOCAL_IPS');
        if(in_array($ip, $localIpArray)) {
            return true;
        }

        return false;
    }

    /**
     * 根据数组获取查询排序设置，必须包含column以及order键值， 可以用二维数组进行多字段排序
     * @param $sortOrder
     * @return string
     */
    public static function sortOrder($sortOrder) {
        $order  = '';
        if($sortOrder) {
            if(is_array($sortOrder)) {
                if ($sortOrder['column']) {
                    $order = $sortOrder['column'] . ' ' . $sortOrder['order'];
                } else {
                    $orders = array();
                    foreach ($sortOrder as $colOrder) {
                        $orders[] = $colOrder['column'] . ' ' . $colOrder['order'];
                    }
                    $order = implode(',', $orders);
                }
            } else if (is_string($sortOrder)){
                $order = $sortOrder;
            } else {
                throw new \RuntimeException('查询条条件设置错误！');
            }
        }
        return $order;
    }



    /**
     * 字符串首字母小写
     * @param $str
     * @return string
     */
    public static function lowerFirst($str) {
        return strtolower($str[0]) . substr($str, 1);
    }

    /**
     * @param array  $rows
     * @param int    $recordCount
     * @param int    $currentPage
     * @param int    $pageSize
     * @return array
     */
    public static function listResults($rows, $recordCount, $currentPage=1, $pageSize=20) {
        $currentPage = abs($currentPage);
        $pageSize = abs($pageSize);
        $pageSize = $pageSize ?: 20;
        $currentPage = $currentPage  ?: 1;
        if($recordCount == 0) {
            $rows = array();
            return array(
                'rows'=>$rows,
                'recordCount'=>0,
                'pageCount'=>1,
                'pageSize'=>$pageSize,
                'currentPage'=>1,
            );
        }
        $pageCount = ceil($recordCount / $pageSize);
        if($currentPage > $pageCount) $currentPage = $pageCount;

        return array(
            'rows'=>$rows,
            'recordCount'=>$recordCount,
            'pageCount'=>$pageCount,
            'currentPage'=>$currentPage,
            'pageSize'=>$pageSize
        );
    }

    /**
     * @param $key
     * @return string
     */
    public static function toPascalKey($key) {
        static $upperChars = array();
        static $lowerChars = array();
        if(false == $upperChars) {
            $upperChars = range('A', 'Z');
            foreach ($upperChars as $char) {
                $lowerChars[] = '_' . strtolower($char);
            }
        }
        return str_replace($upperChars, $lowerChars, $key);
    }

    /**
     * @param $key
     * @return string
     */
    public static function toCamelKey($key) {
        static $upperChars = array();
        static $lowerChars = array();
        if(false == $upperChars) {
            $upperChars = range('A', 'Z');
            foreach ($upperChars as $char) {
                $lowerChars[] = '_' . strtolower($char);
            }
        }

        return str_replace($lowerChars, $upperChars, $key);
    }

    public static function cliColor($str, $fgColor='', $bgColor='') {
        $fgColors =array(
            'black'         => '0;30',
            'dark_gray'     => '1;30',
            'blue'          => '0;34',
            'light_blue'    => '1;34',
            'green'         => '0;32',
            'light_green'   => '1;32',
            'cyan'          => '0;36',
            'light_cyan'    => '1;36',
            'red'           => '0;31',
            'light_red'     => '1;31',
            'purple'        => '0;35',
            'light_purple'  => '1;35',
            'brown'         => '0;33',
            'yellow'        => '1;33',
            'light_gray'    => '0;37',
            'white'         => '1;37',
        );

        $bgColors = array(
            'black'         => '40',
            'red'           => '41',
            'green'         => '42',
            'yellow'        => '43',
            'blue'          => '44',
            'magenta'       => '45',
            'cyan'          => '46',
            'light_gray'    => '47',
        );

        $colorStr = '';
        if($fgColor && isset($fgColors[$fgColor])) {
            $colorStr .= "\033[" . $fgColors[$fgColor] . 'm';
        }
        if($bgColor && isset($bgColors[$bgColor])) {
            $colorStr .= "\033[" . $bgColors[$bgColor] . 'm';
        }

        $colorStr .= $str . "\033[0m";
        return $colorStr;

    }
}