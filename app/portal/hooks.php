<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
return [
    'portal_before_assign_article' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '文章显示之前', // 钩子名称
        "description" => "文章显示之前", //钩子描述
        "once" => 0 // 是否只执行一次
    ],
    'portal_admin_after_save_article' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '后台文章保存之后', // 钩子名称
        "description" => "后台文章保存之后", //钩子描述
        "once" => 0 // 是否只执行一次
    ],
    'portal_admin_article_index_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章管理列表界面', // 钩子名称
        "description" => "门户后台文章管理列表界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_article_add_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章添加界面', // 钩子名称
        "description" => "门户后台文章添加界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_article_edit_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章编辑界面', // 钩子名称
        "description" => "门户后台文章编辑界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_category_index_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章分类管理列表界面', // 钩子名称
        "description" => "门户后台文章分类管理列表界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_category_add_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章分类添加界面', // 钩子名称
        "description" => "门户后台文章分类添加界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_category_edit_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章分类编辑界面', // 钩子名称
        "description" => "门户后台文章分类编辑界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_page_index_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台页面管理列表界面', // 钩子名称
        "description" => "门户后台页面管理列表界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_page_add_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台页面添加界面', // 钩子名称
        "description" => "门户后台页面添加界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_page_edit_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台页面编辑界面', // 钩子名称
        "description" => "门户后台页面编辑界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_tag_index_view' => [
        "type" => 2,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章标签管理列表界面', // 钩子名称
        "description" => "门户后台文章标签管理列表界面", //钩子描述
        "once" => 1 // 是否只执行一次
    ],
    'portal_admin_article_edit_view_right_sidebar' => [
        "type" => 4,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章添加编辑界面右侧栏', // 钩子名称
        "description" => "门户后台文章添加编辑界面右侧栏", //钩子描述
        "once" => 0 // 是否只执行一次
    ],
    'portal_admin_article_edit_view_main' => [
        "type" => 4,//钩子类型(默认为应用钩子;2:应用钩子;3:模板钩子;4:后台模板钩子)
        "name" => '门户后台文章添加编辑界面主要内容', // 钩子名称
        "description" => "门户后台文章添加编辑界面主要内容", //钩子描述
        "once" => 0 // 是否只执行一次
    ],
];