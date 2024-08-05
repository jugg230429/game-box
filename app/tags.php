<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

// 应用行为扩展定义文件
return [
    // 应用初始化
    'app_init' => [
        'cmf\\behavior\\InitHookBehavior',
    ],
    'view_filter' => [
        'app\\common\\behavior\\WriteHtmlCacheBehavior',
    ],
    // 应用开始
    'app_begin' => [
        'cmf\\behavior\\LangBehavior',
    ],
    // 模块初始化
    'module_init' => [
        'cmf\\behavior\\InitAppHookBehavior',
        'app\\common\\behavior\\ReadHtmlCacheBehavior',//静态化
    ],
//    // 操作开始执行
//    'action_begin' => [],
//    // 视图内容过滤
//    'view_filter'  => [],
//    // 日志写入
//    'log_write'    => [],
//    //日志写入完成
//    'log_write_done'=>[],
//    // 应用结束
//    'app_end'      => [],
    // 应用开始
    'admin_init' => [
        'cmf\\behavior\\AdminLangBehavior',
    ],
    'home_init' => [
        'cmf\\behavior\\HomeLangBehavior',
    ]
];
