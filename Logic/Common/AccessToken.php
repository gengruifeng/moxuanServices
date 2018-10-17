<?php
namespace Logic\Common;

use Aitifen\Library\Common\Exceptions\TokenExpireException;
use Aitifen\Library\Common\Structs\AccessTokenStruct;
use MicroService\AbstractStruct;

class AccessToken {
    protected static $tokenLifeTime = 7200;  //每个token有效期两个小时,但是可以通过touch方法对token进行更新

    /**
     * @param $agencyId
     * @param $platformName
     * @param $userType
     * @param $userId
     * @param $thirdPartyUid
     * @return AccessTokenStruct
     * @throws \RuntimeException
     */
    public static function createNew($agencyId, $platformName, $userType, $userId, $thirdPartyUid='') {
        $args = compact('agencyId', 'platformName', 'userType', 'userId');
        $accessToken = new AccessTokenStruct($args);
        #定义常量时可能会定义出带特殊字符的平台标识
        $platformName = str_ireplace(array('APP', '_', '-'), '', $platformName);
        $accessToken['accessToken'] = $platformName . '-' . date('Ymd') . '-' . md5(uniqid(microtime(true)));
        $accessToken['expire_at'] = time() + self::$tokenLifeTime;
        $accessToken['third_party_uid'] = $thirdPartyUid;

        #以下划线形式返回
        $saveData = $accessToken->toArray(AbstractStruct::KEY_MODE_NATURE);
        $accessTokenTable = Tables::accessTokenTable();
        try {
            $accessTokenTable->save($saveData);
            $now = date('Y-m-d H:i:s');
            $accessToken['create_at'] = $now;
            $accessToken['update_at'] = $now;
            return $accessToken;
        } catch (\Exception $e) {
            throw new \RuntimeException('用户AccessToken创建失败!');
        }
    }

    /**
     * @param $accessToken
     * @return AccessTokenStruct
     */
    public static function getUserInfoByToken($accessToken) {
        $table = Tables::accessTokenTable();
        $condition = array(
            'access_token'=>$accessToken,
            'expire_at'=>array('$gt'=>time()),
        );
        $tokenInfo = $table->find($condition);
        if(false == $tokenInfo) {
            throw new TokenExpireException('用户请求Token已过期,请重新登陆!');
        }
        $accessToken = new AccessTokenStruct($tokenInfo);
        return $accessToken;
    }

    /**
     * 在swoole的taskWorker中异步执行,这里是一个回调方法,通过ServiceContext类异步调用
     * @param $accessToken
     * @return bool
     * @throws \Exception
     */
    public static function touch($accessToken) {
        $table = Tables::accessTokenTable();
        $saveData = array(
            'expire_at'=>time() + self::$tokenLifeTime
        );
        $condition = array(
            'access_token'=>$accessToken
        );
        return $table->save($saveData, $condition);
    }
}