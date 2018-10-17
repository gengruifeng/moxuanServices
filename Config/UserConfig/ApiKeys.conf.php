<?php
//这个文件用来记录第三方机构需要实现的接口KEY值,管理端如果实现设置接口URL的功能会用到
return array(
    'USER_AUTHORIZE_API'=>'通用用户认证接口，按需实现',
    'TEACHER_AUTHORIZE_API'=>'教师用户认证接口',
    'STUDENT_AUTHORIZE_API'=>'学员认证API',
    'SET_USER_UID_API'=>'回传用户UID接口，用于机构更新本地数据',

    'USER_INFO_API'=>'通用用户信息获取接口，按需实现',
    'TEACHER_INFO_API'=>'获取教师信息接口',
    'STUDENT_INFO_API'=>'获取学员信息接口',

    'USER_UPDATE_API'=>'通用编辑用户信息接口，按需实现',
    'TEACHER_UPDATE_API'=>'',
    'STUDENT_UPDATE_API'=>'',

    'USER_CHANGE_PWD_API'=>'通用修改密码接口，按需实现',
    'TEACHER_CHANGE_PWD_API'=>'教师修改密码接口',
    'STUDENT_CHANGE_PWD_API'=>'学员修改密码接口',

    'TEACHER_CLASSES_API'=>'教师教授班级列表接口',
    'STUDENT_CLASSES_API'=>'学员班级列表接口',

);