<?php
namespace Logic\Common;

use Logic\Common\Structs\AgencyAppStruct;

class AgencyApp {
    /**
     * @param $appKey
     * @param $skipStatus
     * @return AgencyAppStruct
     * @throws \Exception
     */
    public static function getAppInfoByKey($appKey, $skipStatus=false) {
        //todo:从redis缓存中获取
        $agencyAppTable = Tables::agencyAppsTable();
        $condition = array(
            'app_key'=>$appKey,
        );
        if(false == $skipStatus) {
            $condition['invalid_time'] = 0;
        }
        $appInfo = $agencyAppTable->find($condition);
        if(false == $appInfo) {
            throw new \RuntimeException('没有查询机构的AppKey设置,请联系管理员!');
        }
        return new AgencyAppStruct($appInfo);
    }


    /**
     *
     * @param $agencyId
     * @return AgencyAppStruct
     * @throws \Exception
     */
    public static function getAgencyAppInfo($agencyId) {
        //todo:从redis缓存中获取
        $appTable = Tables::agencyAppsTable();

        $condition = array(
            'agency_id'=>$agencyId,
            'invalid_time'=>0
        );

        $appInfo = $appTable->find($condition);
        if($appInfo) {
            return new AgencyAppStruct($appInfo);
        }
        $appKey = uniqid('A' . $agencyId . '-');
        $appSecret = md5(microtime(true));
        $appInfo = compact('agencyId', 'appKey', 'appSecret');
        $appInfoStruct = new AgencyAppStruct($appInfo);
        $appTable->save($appInfoStruct->toArray());

        return $appInfoStruct;

    }

}