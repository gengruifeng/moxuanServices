<?php
namespace Logic\Common;

use MicroService\Dao\Table;

class Tables {
    protected static $connName = AGENCY_CONFIG_CONN;

    #机构域名绑定表
    protected static $agencyDomainTable = 'atf_agency_domains';
    #机构appKey设置表
    protected static $agencyAppsTable = 'atf_agency_apps';
    #机构接口调用配置表,如果非第三方机构,不需要设置, 对于第三方机构需要有一个生成请求头的回调函数
    protected static $agencyApiConfigsTable = 'atf_agency_api_config';
    #机构接口URL设置表, 每个表要有一个接口查询条件参数转换的回调函数
    protected static $agencyApiUrlsTable = 'atf_agency_api_urls';
    #用户登录TOKEN表
    protected static $accessTokenTable = 'atf_access_tokens';


    /**
     * @param Table $table
     * @param string $createQuery
     * @throws \Exception
     */
    protected static function createTable($table, $createQuery) {
        $table->getDao()->execute($createQuery);
        if(false == $table->isExists(true)) {
            throw new \RuntimeException('数据表' . $table . '' . '创建失败');
        }
    }

    /**
     * 机构域名绑定表, 每个机构可能有多条记录:
     * xxx.video.aitifen.com    默认开通的三级域名
     * uname1.domain.com        机构自己做的CNAME解析
     * @return Table
     * @throws \Exception
     */
    public static function agencyDomainTable() {
        static $table;
        if($table) return $table;
        $tableName = self::$agencyDomainTable;
        $table = Table::getTable($tableName, self::$connName);
        if(false == $table->isExists()) {
            #如果设置了数据表前缀, 通过__toString方法可以获取真实表名
            $createQuery = self::agencyDomainSql($table . '');
            self::createTable($table, $createQuery);
        }
        return $table;

    }

    /**
     * @return Table
     * @throws \Exception
     */
    public static function agencyAppsTable() {
        static $table;
        if($table) return $table;
        $tableName = self::$agencyAppsTable;
        $table = Table::getTable($tableName, self::$connName);
        if(false == $table->isExists()) {
            #如果设置了数据表前缀, 通过__toString方法可以获取真实表名
            $createQuery = self::agencyAppSql($table . '');
            self::createTable($table, $createQuery);
        }
        return $table;
    }

    /**
     * @return Table
     * @throws \Exception
     */
    public static function apiConfigTable() {
        static $table;
        if($table) return $table;
        $tableName = self::$agencyApiConfigsTable;
        $table = Table::getTable($tableName, self::$connName);

        if(false == $table->isExists()) {
            #如果设置了数据表前缀, 通过__toString方法可以获取真实表名
            $createQuery = self::apiConfigSql($table . '');
            self::createTable($table, $createQuery);
        }
        return $table;
    }

    /**
     * @return Table
     * @throws \Exception
     */
    public static function apiUrlsTable() {
        static $table;
        if($table) return $table;
        $tableName = self::$agencyApiUrlsTable;
        $table = Table::getTable($tableName, self::$connName);
        if(false == $table->isExists()) {
            #如果设置了数据表前缀, 通过__toString方法可以获取真实表名
            $createQuery = self::apiUrlsSql($table . '');
            self::createTable($table, $createQuery);
        }
        return $table;
    }

    public static function accessTokenTable() {
        static $table;
        if($table) return $table;
        $tableName = self::$accessTokenTable;
        $table = Table::getTable($tableName, self::$connName);
        if(false == $table->isExists()) {
            #如果设置了数据表前缀, 通过__toString方法可以获取真实表名
            $createQuery = self::accessTokenSql($table . '');
            self::createTable($table, $createQuery);
        }
        return $table;
    }

    protected static function agencyDomainSql($tableName) {
        return "CREATE TABLE `${tableName}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `platform_name` varchar(10) DEFAULT NULL COMMENT '平台标识',
                  `agency_id` int(11) NOT NULL COMMENT '机构ID',
                  `agency_domain` varchar(50) NOT NULL COMMENT '绑定域名或机构名称前缀',
                  `domain_status` int(11) NOT NULL DEFAULT '1' COMMENT '域名状态,1:启用,2停用',
                  `create_at` datetime DEFAULT NULL COMMENT '创建时间',
                  `update_at` datetime DEFAULT NULL COMMENT '更新时间',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_platform_domain` (`platform_name`,`agency_id`,`agency_domain`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机构域名绑定表'";

    }

    protected static function agencyAppSql($tableName) {
        return "CREATE TABLE `${tableName}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
                  `agency_id` int(11) NOT NULL COMMENT '机构ID',
                  `app_key` varchar(50) NOT NULL COMMENT 'APP_KEY',
                  `app_secret` varchar(50) NOT NULL COMMENT '签名加密密钥',
                  `invalid_time` int(11) NOT NULL DEFAULT '0' COMMENT '设置无效的时间,默认为0,即有效',
                  `create_at` datetime DEFAULT NULL COMMENT '创建时间',
                  `update_at` datetime DEFAULT NULL COMMENT '更新时间',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_app_id` (`agency_id`,`invalid_time`),
                  UNIQUE KEY `uk_app_key` (`app_key`,`invalid_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机构APP_KEY设置'";
    }

    protected static function apiConfigSql($tableName) {
        return "CREATE TABLE `${tableName}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `agency_id` int(11) NOT NULL COMMENT '机构ID',
                  `header_callback` varchar(255) DEFAULT NULL COMMENT '回调方法,接收\$agencyId, \$userId(可选)两个参数,建议采用ClassName::methodName的形式, 也可以保存为Json',
                  `create_at` datetime DEFAULT NULL COMMENT '添加时间',
                  `update_at` datetime DEFAULT NULL COMMENT '修改时间',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机构接口HEADER头回调函数设置表，所有的函数都必须有一个可选的userId参数'";
    }

    protected static function apiUrlsSql($tableName) {
        return "CREATE TABLE `${tableName}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `agency_id` int(11) NOT NULL COMMENT '机构ID',
                  `api_key` varchar(100) NOT NULL COMMENT 'API_URL接口KEY',
                  `api_url` varchar(255) NOT NULL COMMENT '接口URL地址',
                  `api_method` varchar(10) DEFAULT 'GET' COMMENT '接口请求方法',
                  `param_converter` varchar(255) DEFAULT NULL COMMENT '接口参数转换方法,需要接收\$agencyId,\$params两个参数',
                  `result_converter` varchar(255) DEFAULT NULL COMMENT '返回结果转换方法,需要接收\$agencyId,\$result两个参数',
                  `create_at` datetime DEFAULT NULL COMMENT '创建时间',
                  `update_at` datetime DEFAULT NULL COMMENT '修改时间',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_api_key` (`agency_id`,`api_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机构接口URL设置表'";
    }

    protected static function accessTokenSql($tableName) {
        return "CREATE TABLE `${tableName}` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `agency_id` int(11) NOT NULL COMMENT '机构ID',
                  `platform_name` varchar(10) DEFAULT NULL COMMENT '平台标识',
                  `user_type` varchar(20) NOT NULL COMMENT '用户类型',
                  `user_id` varchar(40) NOT NULL COMMENT '平台中的用户ID,这里采用varchar类型,',
                  `third_party_uid` varchar(40) COMMENT '第三方机构的用户ID, 这个字段可以为空',
                  `access_token` varchar(50) NOT NULL COMMENT '用户TOKEN',
                  `expire_at` int(11) DEFAULT NULL COMMENT 'TOKEN过期时间',
                  `create_at` datetime DEFAULT NULL COMMENT '创建时间',
                  `update_at` datetime DEFAULT NULL COMMENT '更新时间',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_access_token` (`access_token`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户接口访问的TOKEN'";
    }
}