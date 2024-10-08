<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
if (file_exists(CMF_ROOT . "data/conf/config".CONF_EXT)) {
    $runtimeConfig = include CMF_ROOT . "data/conf/config".CONF_EXT;
} else {
    $runtimeConfig = [];
}
//加载后台配置
if (file_exists(CMF_ROOT . 'app/tool_config'.CONF_EXT)) {
    $toolConfig = include CMF_ROOT . 'app/tool_config'.CONF_EXT;
} else {
    $toolConfig = [];
}

// 异常错误报错级别,
error_reporting(E_ERROR | E_PARSE);
$configs = [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    // 应用命名空间
    'app_namespace' => 'app',
    // 应用模式状态
    'app_status' => APP_DEBUG ? 'debug' : 'release',
    // 是否支持多模块
    'app_multi_module' => true,
    // 入口自动绑定模块
    'auto_bind_module' => false,
    // 注册的根命名空间
    'root_namespace' => ['cmf' => CMF_PATH, 'plugins' => PLUGINS_PATH, 'themes' => PLUGINS_PATH . '../themes', 'api' => CMF_ROOT . 'api/'],
    // 扩展函数文件
    'extra_file_list' => [CMF_PATH . 'common' . EXT, THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type' => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
    // 默认时区
    'default_timezone' => 'PRC',
    // 是否开启多语言
    'lang_switch_on' => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => 'htmlspecialchars',
    // 默认语言
    'default_lang' => 'zh-cn',
    // 应用类库后缀
    'class_suffix' => true,
    // 控制器类后缀
    'controller_suffix' => true,
    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------
    // 默认模块名
    'default_module' => 'media',
    // 禁止访问模块
    'deny_module_list' => ['common'],
    // 默认控制器名
    'default_controller' => 'Index',
    // 默认操作名
    'default_action' => 'index',
    // 默认验证器
    'default_validate' => '',
    // 默认的空控制器名
    'empty_controller' => 'Error',
    // 自动搜索控制器
    'controller_auto_search' => false,
    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------
    'pathinfo_depr' => '/',
    // URL伪静态后缀
    'url_html_suffix' => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 0,
    // 是否开启路由
    'url_route_on' => true,
    // 路由配置文件（支持配置多个）
    'route_config_file' => ['route'],
    // 是否强制使用路由
    'url_route_must' => false,
    // 域名部署
    'url_domain_deploy' => false,
    // 域名根，如thinkphp.cn
    'url_domain_root' => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert' => true,
    // 默认的访问控制器层
    'url_controller_layer' => 'controller',
    // 表单请求类型伪装变量
    'var_method' => '_method',
    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------
    'template' => [
        // 模板引擎类型 支持 php think 支持扩展
        'type' => 'Think',
        // 视图根目录
        'view_base' => '',
        // 模板路径
        'view_path' => '',
        // 模板后缀
        'view_suffix' => 'html',
        // 模板文件名分隔符
        'view_depr' => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin' => '{',
        // 模板引擎普通标签结束标记
        'tpl_end' => '}',
        // 标签库标签开始标记
        'taglib_begin' => '<',
        // 标签库标签结束标记
        'taglib_end' => '>',
        'taglib_build_in' => 'cmf\\lib\\taglib\\Cmf,cx',
        'tpl_cache' => APP_DEBUG ? false : true,
        'tpl_deny_php' => false,
        // +----------------------------------------------------------------------
        // | CMF模板 设置
        // +----------------------------------------------------------------------
        'cmf_theme_path' => 'themes/',
        'cmf_default_theme' => 'simpleboot3',
        'cmf_admin_theme_path' => 'themes/',
        'cmf_admin_default_theme' => 'admin_simpleboot3',
    ],
    // 视图输出字符串内容替换
    'view_replace_str' => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump_success.html',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump_error.html',
    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------
    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',
    // 错误显示信息,非调试模式有效
    'error_message' => '您访问的页面不存在~',
    // 显示错误信息
    'show_error_msg' => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => '',
    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
    'log' => [
        // 日志记录方式，内置 file socket 支持扩展
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH,
        // 日志记录级别
        'level' => [],
		'max_files'   => 30,
    ],
    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace' => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],
    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache' => [
        // 使用复合缓存类型
        'type'  =>  'complex',
        // 默认使用的缓存
        'default'   =>  [
            // 驱动方式
            'type'   => 'File',
        ],
        // 文件缓存
        'file'   =>  [
            // 驱动方式
            'type'   => 'file',
            'path' => CACHE_PATH,
            // 缓存前缀
            'prefix' => '',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
        // redis缓存
        'redis_advert_login'   =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'       => '127.0.0.1',
            'expire' => 3600,
            'port'   => '6379',
            'timeout' => 3600,
            'prefix' => 'advert_login_',
        ],
    ],
    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------
    'session' => [
        'id' => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix' => 'think',
        // 驱动方式 支持redis memcache memcached
        'type' => '',
        // 是否自动开启 SESSION
        'auto_start' => true,
    ],
    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie' => [
        // cookie 名称前缀
        'prefix' => '',
        // cookie 保存时间
        'expire' => 0,
        // cookie 保存路径
        'path' => '/',
        // cookie 有效域名
        'domain' => '',
        //  cookie 启用安全传输
        'secure' => false,
        // httponly设置
        'httponly' => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],
    // +----------------------------------------------------------------------
    // | 数据库设置
    // +----------------------------------------------------------------------
    'database' => [
        // 数据库调试模式
        'debug' => APP_DEBUG,
        // 数据集返回类型
        'resultset_type' => 'collection',
        // 自动写入时间戳字段
        'auto_timestamp' => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => false,
        // 是否需要进行SQL性能分析
        'sql_explain' => APP_DEBUG,
    ],
    'db_mongo' => [
        'type'    =>   '\think\mongo\Connection',
        'hostname'    =>   '数据库服务器IP地址',
        'database'    =>   '数据库名',
        'username'    =>   '用户名',
        'password'     =>   '密码',
        'hostport'    =>   27017,
    ],
    //分页配置
    'paginate' => [
        'type' => '\\cmf\\paginator\\Bootstrap',
        'var_page' => 'page',
        'list_rows' => 10,
    ],
    'queue' => [
        'connector' => '\\cmf\\queue\\connector\\Database'
    ],
    //静态化缓存 仅适用于get方式
    'html_file_suffix' => '.shtml', // 设置静态缓存文件后缀
    'html_cache_compile_type' => 'file',//缓存存储驱动
    'html_cache_rules' => array( // 定义静态缓存规则
//        定义格式1 数组方式   需要页面支持
//        '静态地址' => array('静态规则', '有效期', '附加规则'),
//        任意控制器的任意操作都适用
//        '*'=>array('{$_SERVER.REQUEST_URI|md5}'),
//        任意控制器的md5操作
//        'media:game:game_center'=>array('{:module}/{:controller}/{$_SERVER.REQUEST_URI|md5}'),
        'media:index:index' => array('{:module}/{:controller}/{:action}'),
        'media:game:game_center' => array('{:module}/{:controller}/{$_SERVER.REQUEST_URI|md5}'),
        'media:article:index' => array('{:module}/{:controller}/{$_SERVER.REQUEST_URI|md5}'),
        'mobile:index:index' => array('{:module}/{:controller}/{:action}{$_SERVER.REQUEST_URI|md5}'),
        'mobile:game:search' => array('{:module}/{:controller}/{:action}'),
        'mobile:article:index' => array('{:module}/{:controller}/{:action}_{category_id|md5}'),
    ),
];
return array_replace_recursive($configs, $runtimeConfig, $toolConfig);