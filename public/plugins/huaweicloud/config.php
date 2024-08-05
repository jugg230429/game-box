<?php
return [
    'bucket'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'Bucket名称', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => 'test',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'Bucket名称不能为空'
        ],
        'tip'     => 'Obs的bucket(桶)名称 <a href="https://support.huaweicloud.com/intl/zh-cn/usermanual-obs/zh-cn_topic_0045829088.html" target="_blank">马上获取</a>' //表单的帮助提示
    ],
    'key'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'key', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => 'test',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'key不能为空'
        ],
        'tip'     => '从Obs获得的key' //表单的帮助提示
    ],
    'secret'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'secret', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => 'test',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'secret不能为空'
        ],
        'tip'     => '从Obs获得的 secret' //表单的帮助提示 
    ],
    'endpoint'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '获取终端节点', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => 'test',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '终端节点不能为空'
        ],
        'tip'     => '您选定的Obs终端节点，<a href="https://support.huaweicloud.com/intl/zh-cn/qs-obs/obs_qs_0006.html" target="_blank">马上获取</a>' //https://support.huaweicloud.com/intl/zh-cn/qs-obs/obs_qs_0006.html
    ],

];