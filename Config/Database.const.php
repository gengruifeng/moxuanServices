<?php
#通用数据设置
defined('COMMON_DATA_CONN')   || define('COMMON_DATA_CONN',  'COMMON_DATA_CONN');
defined('COMMON_DATA_DBHOST') || define('COMMON_DATA_DBHOST', 'mysql-server');
defined('COMMON_DATA_DBNAME') || define('COMMON_DATA_DBNAME', 'common_data');
defined('COMMON_DATA_DBUSER') || define('COMMON_DATA_DBUSER', 'root');
defined('COMMON_DATA_DBPASS') || define('COMMON_DATA_DBPASS', '123456');
defined('COMMON_DATA_DBDSN')  || define('COMMON_DATA_DBDSN', 'mysql:host=' . COMMON_DATA_DBHOST . ';dbname=' . COMMON_DATA_DBNAME);

#机构配置数据库设置,这里目前仅设置接口对应URL, 以及域名绑定信息登
defined('AGENCY_CONFIG_CONN')   || define('AGENCY_CONFIG_CONN',  'AGENCY_CONFIG_CONN');
defined('AGENCY_CONFIG_DBHOST') || define('AGENCY_CONFIG_DBHOST', 'mysql-server');
defined('AGENCY_CONFIG_DBNAME') || define('AGENCY_CONFIG_DBNAME', 'atf_agency_configs');
defined('AGENCY_CONFIG_DBUSER') || define('AGENCY_CONFIG_DBUSER', 'root');
defined('AGENCY_CONFIG_DBPASS') || define('AGENCY_CONFIG_DBPASS', '123456');
defined('AGENCY_CONFIG_DBDSN')  || define('AGENCY_CONFIG_DBDSN', 'mysql:host=' . AGENCY_CONFIG_DBHOST . ';dbname=' . AGENCY_CONFIG_DBNAME);


#CRM数据库设置
defined('CRM_CONN')   || define('CRM_CONN',  'CRM_CONN');
defined('CRM_DBHOST') || define('CRM_DBHOST', 'mysql-server');
defined('CRM_DBNAME') || define('CRM_DBNAME', 'atf_crm');
defined('CRM_DBUSER') || define('CRM_DBUSER', 'root');
defined('CRM_DBPASS') || define('CRM_DBPASS', '123456');
defined('CRM_DBDSN')  || define('CRM_DBDSN', 'mysql:host=' . CRM_DBHOST . ';dbname=' . CRM_DBNAME);


#SQLSERVER数据库配置
defined('MSSQL_CONN')   || define('MSSQL_CONN',  'MSSQL_CONN');
defined('MSSQL_DBHOST') || define('MSSQL_DBHOST', 'db.gaosiedu.com');
defined('MSSQL_DBNAME') || define('MSSQL_DBNAME', 'gstest');
defined('MSSQL_DBUSER') || define('MSSQL_DBUSER', 'vipsys');
defined('MSSQL_DBPASS') || define('MSSQL_DBPASS', '');
defined('MSSQL_DBDSN')  || define('MSSQL_DBDSN', 'dblib:host=' . MSSQL_DBHOST . ';dbname=' . MSSQL_DBNAME);


#KMS数据库配置,
defined('KMS_CONN')   || define('KMS_CONN',  'KMS_CONN');
defined('KMS_DBHOST') || define('KMS_DBHOST', '192.168.1.250');
defined('KMS_DBNAME') || define('KMS_DBNAME', 'kms_gaosi');
defined('KMS_DBUSER') || define('KMS_DBUSER', 'root');
defined('KMS_DBPASS') || define('KMS_DBPASS', '123456');
defined('KMS_DBDSN')  || define('KMS_DBDSN', 'mysql:host=' . KMS_DBHOST . ';dbname=' . KMS_DBNAME);


