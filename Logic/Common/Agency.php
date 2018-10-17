<?php
namespace Logic\Common;

use Aitifen\Library\Common\Structs\AgencyAppStruct;
use Aitifen\Library\Common\Structs\AgencyInfoStruct;
use MicroService\Dao\Table;

class Agency {
    /**
     * 第三方机构列表,因为目前CRM中没有相关设定, 所以这里暂时通过数组设置
     * 键为机构ID, 值为机构域名前缀
     */
    protected static $thirdAgencys = array(
        '384'=>'gsvip',
    );

    /**
     * 这个方法应该是调用CRM接口获取,暂时直接查库获取
     * @param $agencyId
     * @param bool $requireAppKey
     * @return AgencyInfoStruct
     * @throws \Exception
     */
    public static function getAgencyInfoById($agencyId, $requireAppKey=false) {
        $agencyTable = Table::getTable('crm_school', CRM_CONN);
        $gCompanyTable = Table::getTable('crm_resource', CRM_CONN);
        $condition = array(
            'id'=>$agencyId
        );
        $agencyInfo = $agencyTable->find($condition);
        $agencyNamesArr = array();

        $isThirdParty = '';
        $domainPrefix = '';

        if(isset(self::$thirdAgencys[$agencyId])) {
            $isThirdParty = true;
            $domainPrefix = self::$thirdAgencys[$agencyId];
        }

        if($agencyInfo) {
            $gCompanyId = $agencyInfo['resource_id'];
            $gCompanyInfo = $gCompanyTable->find(array(
                'id'=>$gCompanyId
            ));
            $agencyNamesArr[] = $gCompanyInfo['agency_name'];
            $agencyNamesArr[] = $agencyInfo['school_name'];
            $agencyNamesArr = array_unique($agencyNamesArr);
            $agencyName = implode('-', $agencyNamesArr);

            $agencyInfo = array(
                'agency_id'=>$agencyId,
                'agency_name'=>$agencyName,
                'agency_status'=>1,
                'is_third_party'=>$isThirdParty,
                'agency_domain_prefix'=>$domainPrefix,
                'agency_logo'=>'',
            );

            if($requireAppKey) {
                $agencyAppInfo = AgencyApp::getAgencyAppInfo($agencyId);
                $agencyInfo['app_key'] = $agencyAppInfo->appKey;
                $agencyInfo['app_secret'] = $agencyAppInfo->appSecret;
            }
        } else {
            $agencyInfo = array();
        }

        return new AgencyInfoStruct($agencyInfo);
    }

    /**
     * @param $domain
     * @return AgencyInfoStruct
     * @throws \Exception
     */
    public static function getAgencyInfoByDomain($domain) {
        $agencyDomainTable = Tables::agencyDomainTable();
        $condition = array(
            'domain_status'=>1,
            'agency_domain'=>$domain
        );

        $domainInfo = $agencyDomainTable->find($condition);
        if(false == $domainInfo && in_array(APP_ENV, ['production', 'testing', 'demo'])) {
            throw new \RuntimeException('无法根据域名获取机构信息!');
        }
        if($domainInfo) {
            $agencyId = $domainInfo['agency_id'];
            return self::getAgencyInfoById($agencyId, true);
        } else {
            return new AgencyInfoStruct();
        }

    }

}