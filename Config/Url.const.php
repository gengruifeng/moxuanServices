<?php
#视频直播系统机构域名后缀, 例如CRM中设置机构标志位 GSVIP, 则机构直播系统域名为  strtolower(gsvip).video.atf.com;
#机构可以设置CNAME解析,将机构自己的域名解析到这个三级URL上
defined('AGENCY_VIDEO_URL_SUFFIX') || define('AGENCY_VIDEO_URL_SUFFIX', 'video.atf.com');

