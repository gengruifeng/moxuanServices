<?php
return  array(
    'DB_CONFIGS'=>array(
        #可以配置多连接
        'MOXUAN_CONN'=>array(
            'DB_TYPE'=>'mysql',
            'DB_HOST'=>MOXUAN_DBHOST,
            'DB_USER'=>MOXUAN_DBUSER,
            'DB_PASS'=>MOXUAN_DBPASS,
            'DB_NAME'=>MOXUAN_DBNAME,
            'DB_DSN'=>MOXUAN_DBDSN,
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