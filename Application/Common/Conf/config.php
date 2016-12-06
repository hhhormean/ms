<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 系统配文件
 * 所有系统级别的配置
 */
return array(
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => array('Addons' => ONETHINK_ADDON_PATH), //扩展模块列表
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common', 'User'),
    //'MODULE_ALLOW_LIST'  => array('Home','Admin'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'cYaDyvw3~{=[V7IUZQr}qpoPi>?n#meB9:-`Fz05', //默认数据加密KEY

    /* 调试配置 */
    'SHOW_PAGE_TRACE' => false,

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 3, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 数据库配置 */
    'DB_TYPE'   => 'mysqli', // 数据库类型
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => 'ms', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => '123456',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'ms_', // 数据库表前缀

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DOCUMENT_MODEL_TYPE' => array(2 => '主题', 1 => '目录', 3 => '段落'),
    /*以下为短信接口的一些参数*/
    'message' => array(
        //申请的短信接口平台
        'http' => 'http://api.sms.cn/sms/',
        //申请时候的用户账号
        'uid' => 'hhhormean',
        //申请时候的用户密码 
        'pwd' => '123456',                                                  
        ),



        /*以下为容联云短信接口的一些参数*/
        //主帐号,对应开官网发者主账号下的 ACCOUNT SID
    'AccountSid' => '8a48b551544cd73f015460a336da1704',

        //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
    'AccountToken' => '8b8154f24e284a8d8063c72901bdb5b2',

        //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
        ////在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
    'AppId' => '8a216da855e8eb7b0155ed748f330383',

        //请求地址
        //沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
        //生产环境（用户应用上线使用）：app.cloopen.com
    'ServerIP' => 'app.cloopen.com',

        //请求端口，生产环境和沙盒环境一致
    'ServerPort' => '8883',

        //REST版本号，在官网文档REST介绍中获得。
    'SoftVersion' => '2013-12-26',

        //短信模板号
    'TempId' => '101548',

        /*乐视云配置*/
        'letv_uid' => '831890',
    'letv_user_unique' => 'xvo3fmiwd2',
    'letv_secretkey' => '2be5862a74dd26478e2d5325c42f0db7',
    'letv_format' => 'json',
    'letv_version' => '2.0',
    'letv_api_url' => 'http://api.letvcloud.com/open.php',
);
