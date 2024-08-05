<?php
/**
 * @是否购买H5分发模块
 *
 * @author: zsl
 * @since: 2020/7/10 15:52
 */
function is_buy_h5_issue()
{
    return class_exists('app\issueh5\logic\GameLogic');
}

/**
 * @是否购买手游分发模块
 *
 * @author: zsl
 * @since: 2020/7/10 15:53
 */
function is_buy_sy_issue()
{
    return class_exists('app\issuesy\logic\GameLogic');
}

/**
 * @是否购买手游分发模块
 *
 * @author: gjt
 * @since: 2020/11/9 15:53
 */
function is_buy_yy_issue()
{
    return class_exists('app\issueyy\logic\GameLogic');
}

/**
 * @获取sdk配置文件列表
 *
 * @author: zsl
 * @since: 2020/7/14 11:47
 */
function getSdkConfig()
{
    $platformConfig = config('sdk_config');
    return $platformConfig;
}

/**
 * @获取sdk配置详情
 *
 * @author: zsl
 * @since: 2021/8/24 20:40
 */
function getSdkConfigItem($sdk_name)
{
    $config = config('sdk_config.' . $sdk_name);
    return $config;
}


/**
 * @获取sdk配置版本
 *
 * @author: zsl
 * @since: 2020/7/14 14:16
 */
function getSdkVersion($sdk_name, $field = "version")
{
    $version = config('sdk_config.' . $sdk_name . '')[$field];
    return $version;
}

/**
 * @获取平台type
 *
 * @author: zsl
 * @since: 2020/7/14 17:54
 */
function get_pt_type($platformId)
{
    $pt_type = \think\Db ::table('tab_issue_open_user_platform') -> where(['id' => $platformId]) -> value('pt_type');
    return $pt_type;
}

/**
 * @获取平台名称
 *
 * @author: zsl
 * @since: 2020/7/15 9:39
 */
function get_pt_account($platformId)
{
    $account = \think\Db ::table('tab_issue_open_user_platform') -> where(['id' => $platformId]) -> value('account');
    return $account;
}

/**
 * @获取平台最小联运币限额
 *
 * @author: zsl
 * @since: 2020/7/25 11:11
 */
function get_pt_min_balance($platformId)
{
    $min_balance = \think\Db ::table('tab_issue_open_user_platform') -> where(['id' => $platformId]) -> value('min_balance');
    return $min_balance;
}



/**
 * @获取平台列表
 *
 * @author: zsl
 * @since: 2020/7/15 10:22
 */
function get_pt_list($field = true, $map = [])
{
    $platformLists = \think\Db ::table('tab_issue_open_user_platform') -> field($field) -> where($map) -> select() -> toArray();
    return $platformLists;
}

/**
 * @获取联运币余额
 *
 * @author: zsl
 * @since: 2020/7/15 17:41
 */
function get_balance($open_user_id)
{
    $balance = \think\Db ::table('tab_issue_open_user') -> where(['id' => $open_user_id]) -> value('balance');
    return $balance;
}

/**
 * @获取注册玩家列表
 *
 * @author: zsl
 * @since: 2020/7/21 11:40
 */
function get_issue_user($field = 'id,account', $where = [])
{
    $lists = \think\Db ::table('tab_issue_user') -> where($where) -> field($field) -> order('create_time desc') -> select();
    return $lists;
}

/**
 * @获取分发游戏名称
 *
 * @author: zsl
 * @since: 2020/7/22 11:22
 */
function get_issue_game_name($issue_game_id)
{
    $game_name = \think\Db ::table('tab_issue_game') -> where(['id' => $issue_game_id]) -> value('game_name');
    return $game_name;
}

/**
 * @获取联运分发用户指定平台游戏
 *
 * @author: zsl
 * @since: 2020/7/22 15:39
 */
function get_open_user_h5_game($open_user_id, $platform_id,$field="*")
{
    $where = [];
    $where['open_user_id'] = $open_user_id;
    $where['platform_id'] = $platform_id;
    $where['sdk_version'] = 3;
    $where['status'] = 1;
    $where['enable_status'] = 1;
    $gameLists = \think\Db ::table('tab_issue_game_apply')->field($field) -> where($where) -> select() -> toArray();
    return $gameLists;
}

/**
 * @获取联运分发用户指定平台游戏
 *
 * @author: zsl
 * @since: 2020/7/22 15:39
 */
function get_open_user_sy_game($open_user_id, $platform_id,$field="*")
{
    $where = [];
    $where['open_user_id'] = $open_user_id;
    $where['platform_id'] = $platform_id;
    $where['sdk_version'] = ['lt',3];
    $where['status'] = 1;
    $where['enable_status'] = 1;
    $gameLists = \think\Db ::table('tab_issue_game_apply')->field($field) -> where($where) -> select() -> toArray();
    return $gameLists;
}

/**
 * @获取联运分发用户指定平台游戏
 *
 * @author: zsl
 * @since: 2020/7/22 15:39
 */
function get_open_user_yy_game($open_user_id, $platform_id,$field="*")
{
    $where = [];
    $where['open_user_id'] = $open_user_id;
    $where['platform_id'] = $platform_id;
    $where['sdk_version'] = 4;
    $where['status'] = 1;
    $where['enable_status'] = 1;
    $gameLists = \think\Db ::table('tab_issue_game_apply')->field($field) -> where($where) -> select() -> toArray();
    return $gameLists;
}

function get_open_user_game_num($open_user_id, $platform_id)
{
    $where = [];
    $where['open_user_id'] = $open_user_id;
    $where['platform_id'] = $platform_id;
    $where['status'] = 1;
    $where['enable_status'] = 1;
    $num = \think\Db ::table('tab_issue_game_apply') -> where($where) -> count();
    return $num;
}

//获取游戏游戏版本
function get_game_sdk_version_name($game_id)
{
    $sdk_version_arr = [
            1 => '(安卓版)',
            2 => '(苹果版)',
            3 => '',
    ];
    $sdk_version = \think\Db ::table('tab_issue_game') -> where('id', '=', $game_id) -> value('sdk_version');
    return $sdk_version_arr[$sdk_version];
}

/**
 * @函数或方法说明
 * @获取分发版本
 * @author: 郭家屯
 * @since: 2020/11/9 16:23
 */
function get_issue_type($type = 0){
    $type_name = '';
    if(in_array($type,[0,2,3,6])){
        $type_name .= '手游，';
    }
    if(in_array($type,[0,1,3,5])){
        $type_name .= 'H5，';
    }
    if(in_array($type,[0,4,5,6])){
        $type_name .= '页游，';
    }
    $str = rtrim($type_name,'，');
    return $str;
}

/**
 * @树形结构数据处理
 *
 * @author: zsl
 * @since: 2021/8/18 16:46
 */
function treelist($data, $pid = 0, $deep = 1)
{
    static $tree = array();
    foreach ($data as $row) {
        if ($row ['parent_id'] == $pid) {
            $row ['lever'] = $deep;
            $tree [] = $row;
            treelist($data, $row ['id'], $deep + 1);
        }
    }
    return $tree;
}

/**
 * @获取分发用户结算放肆
 *
 * @author: zsl
 * @since: 2021/8/20 15:49
 */
function get_user_settle_type($open_user_id)
{
    $mIssueOpenUser = new \app\issue\model\OpenUserModel();
    $where = [];
    $where['id'] = $open_user_id;
    $settle_type = $mIssueOpenUser -> where($where) -> value('settle_type');
    return $settle_type;
}

/**
 * @解压zip文件
 *
 * @author: zsl
 * @since: 2021/8/25 10:58
 */
function unzip_file($file, $destination)
{
    // 实例化对象
    $zip = new ZipArchive();
    //打开zip文档，如果打开失败返回提示信息
    if ($zip -> open($file) !== true) {
        return false;
    }
    //将压缩文件解压到指定的目录下
    $zip -> extractTo($destination);
    //关闭zip文档
    $zip -> close();
    return true;
}

//文件夹及子文件夹复制
function recurse_copy($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..') && ($file != 'upgrade_sql')) {
            if (is_dir($src . $file)) {
                recurse_copy($src . $file . '/', $dst . $file . '/');
            } else {
                copy($src . $file, $dst . $file);
            }
        }
    }
    closedir($dir);
}

