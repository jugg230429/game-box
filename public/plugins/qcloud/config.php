<?php
return [
    'bucket'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '存储桶空间名称', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '存储桶空间名称不能为空'
        ],
        'tip'     => '存储桶空间名称 <a href="https://console.cloud.tencent.com/cos5/bucket" target="_blank">马上获取</a>,<a href="https://cloud.tencent.com/document/product/436/12266" target="_blank">快速开始</a>' //表单的帮助提示
    ],
    'schema'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '域名协议', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => 'http',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '域名协议不能为空'
        ],
        'tip'=>'请填写http或https,默认http'
    ],
    'region'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '所属区域', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '所属区域不能为空'
        ],
        'tip'=>'<a href="https://console.cloud.tencent.com/cos5/bucket" target="_blank">获得所属区域</a>，一般ap-*，如：ap-nanjing,ap-guangzhou' //表单的帮助提示
    ],
    'domain'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '加速域名', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
//        "rule"    => [
//            "require" => true
//        ],
//        "message" => [
//            "require" => '域名不能为空'
//        ],
        'tip'     => '您选定的CDN加速域名（<span style="color:red;">不包括域名协议</span>），例如 gz..cos.ap-nanjing.myqcloud.com 或 cdn..cos.ap-nanjing.myqcloud.com' //表单的帮助提示
    ],
    'secretId'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'SecretId', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'SecretId不能为空'
        ],
        'tip'     => '<a href="https://console.cloud.tencent.com/cam/capi" target="_blank">获得SecretId</a>' //表单的帮助提示
    ],
    'secretKey'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'SecretKey', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'SecretKey不能为空'
        ],
        'tip'     => '<a href="https://console.cloud.tencent.com/cam/capi" target="_blank">获得SecretKey</a>' //表单的帮助提示
    ],


];