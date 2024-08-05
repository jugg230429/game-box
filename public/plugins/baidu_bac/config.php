<?php
return [
    'bucket'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'Bucket名称', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'Bucket名称不能为空'
        ],
        'tip'     => 'BOS的bucket名称 <a href="https://console.bce.baidu.com/bos#/bos/new/overview" target="_blank">马上获取</a>,<a href="https://cloud.baidu.com/doc/BOS/ProductDescription.html" target="_blank">快速开始</a>' //表单的帮助提示
    ],
    'accesskeyid'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'Access Key', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'Access Key不能为空'
        ],
        'tip'     => '<a href="https://console.bce.baidu.com/iam/#/iam/accesslist" target="_blank">从BOS获得的Access Key</a>' //表单的帮助提示
    ],
    'accesskeysecret'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'Secret Key', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'Secret Key不能为空'
        ],
        'tip'     => '<a href="https://console.bce.baidu.com/iam/#/iam/accesslist" target="_blank">从BOS获得的Secret Key</a>' //表单的帮助提示
    ],
    'domain'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '官方/加速域名', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '加速域名不能为空'
        ],
        'tip'     => '您选定的bucket官方域名或者CDN加速域名（<span style="color:red;">不包括Bucket名称</span>），例如 gz.bcebos.com 或 cdn.bcebos.com' //表单的帮助提示
    ],

];