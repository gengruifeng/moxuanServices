<?php
namespace Logic\Common;

use Hprose\Http\Client;

class RpcClients {

    protected static $rpcClients = array();
    /**
     * @param $serviceName
     * @param $serviceModule
     * @param $async [default:false]
     * @return Client
     */
    public static function getClient($serviceName, $serviceModule, $async=false) {

        $services = self::services();
        if(isset($services[$serviceName])) {
            $baseUrl = $services[$serviceName];
        }
        $serviceUrl = $baseUrl . '/' . trim($serviceModule, '/');

        $serviceKey = md5($serviceUrl . '_' . abs($async));
        if(false == isset(self::$rpcClients[$serviceKey])) {
            self::$rpcClients[$serviceKey] = new Client($serviceUrl, $async);
        }
        return self::$rpcClients[$serviceKey];
    }

    protected static function services() {
        return array(
            APP_SERVICE_NAME=>APP_SERVICE_URL,
        );
    }
}