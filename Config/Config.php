<?php
return  array(
    'DB_CONFIGS'=>array(
        #可以配置多连接

        'COMMON_DATA_CONN'=>array(
            'DB_TYPE'=>'mysql',
            'DB_HOST'=>COMMON_DATA_DBHOST,
            'DB_USER'=>COMMON_DATA_DBUSER,
            'DB_PASS'=>COMMON_DATA_DBPASS,
            'DB_NAME'=>COMMON_DATA_DBNAME,
            'DB_DSN'=>COMMON_DATA_DBDSN,
        ),

        'AGENCY_CONFIG_CONN'=>array(
            'DB_TYPE'=>'mysql',
            'DB_HOST'=>AGENCY_CONFIG_DBHOST,
            'DB_USER'=>AGENCY_CONFIG_DBUSER,
            'DB_PASS'=>AGENCY_CONFIG_DBPASS,
            'DB_NAME'=>AGENCY_CONFIG_DBNAME,
            'DB_DSN'=>AGENCY_CONFIG_DBDSN,
        ),

        'CRM_CONN'=>array(
            'DB_TYPE'=>'mysql',
            'DB_HOST'=>CRM_DBHOST,
            'DB_USER'=>CRM_DBUSER,
            'DB_PASS'=>CRM_DBPASS,
            'DB_NAME'=>CRM_DBNAME,
            'DB_DSN'=>CRM_DBDSN,
        ),


        'MSSQL_CONN'=>array(
            'DB_TYPE'=>'mysql',
            'DB_HOST'=>MSSQL_DBHOST,
            'DB_USER'=>MSSQL_DBUSER,
            'DB_PASS'=>MSSQL_DBPASS,
            'DB_NAME'=>MSSQL_DBNAME,
            'DB_DSN'=>MSSQL_DBDSN,
        ),

    ),

    'REDIS_CONFIGS'=>array(
        #可以配置多连接
        'DEFAULT_REDIS'=>array(
            'host'=>DEFAULT_REDIS_HOST,
            'port'=>DEFAULT_REDIS_PORT,
            'password'=>DEFAULT_REDIS_AUTH,
            'db'=>DEFAULT_REDIS_DB,
            'prefix'=>DEFAULT_REDIS_PREFIX
        ),
    ),

    'DEFAULT_REDIS'=>'DEFAULT_REDIS',

    'CACHE_CONFIG'=>array(
        'redis_conn'=>'DEFAULT_REDIS',
        'cache_lifetime'=>600,  //默认10分钟
    ),




);