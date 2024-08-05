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
        'tip'     => 'OSS的bucket名称 <a href="https://www.aliyun.com/product/oss" target="_blank">马上获取</a>,<a href="https://help.aliyun.com/document_detail/31883.html" target="_blank">快速开始</a>' //表单的帮助提示
    ],
    'accesskeyid'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'AccessKey ID', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'AccessKey ID不能为空'
        ],
        'tip'     => '从OSS获得的AccessKeyId' //表单的帮助提示
    ],
    'accesskeysecret'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'Access Key Secret', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'Access Key Secret不能为空'
        ],
        'tip'     => '从OSS获得的AccessKeySecret' //表单的帮助提示
    ],
    'domain'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '访问域名', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '访问域名不能为空'
        ],
        'tip'     => '您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.com' //表单的帮助提示
    ],
    'domain_upload'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '上传域名', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '上传域名不能为空'
        ],
        'tip'     => '您选定上传OSS数据中心域名，例如oss-cn-hangzhou.com' //表单的帮助提示
    ],
    'internal'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'oss内网上传', // 表单的label标题
        'type'    => 'radio',
        'options' => [
            '1' => '启用',
            '0' => '不启用'
        ],
        'value'   => 1,
        "message" => [
            "require" => 'Bucket必须与服务器在同一地区'
        ],
        'tip'     => '您选定的Bucket必须与服务器在同一地区，例如oss-cn-hangzhou-internal.aliyuncs.com' //表单的帮助提示
    ],

];