<?php
function vesion_list($authorization_code,$version,$no_create_file=0){
    $url = get_upgrade_domain().'/upgrade/index/version_list';
    $param = ['authorization_code'=>$authorization_code,'version'=>$version,'no_create_file'=>$no_create_file];
    $res = request_post($url,$param);
    return $res;
}

function check_upgrade()
{
    $upgradefile = WEB_ROOT.'../data/'.'upgrade_code.txt';
    $upgradeversion = WEB_ROOT.'../data/'.'upgrade_version.txt';
    if(!file_exists($upgradefile)){
        if(request()->isAjax()){
            $this->error('尊敬的用户，您的系统暂不支持自动更新，可联系溪谷售后进行更新升级。 溪谷官网：http://www.vlsdk.com');
        }
        exit('无更新权限，请联系溪谷客服。 溪谷官网：http://www.vlsdk.com');
    }
    if(!file_exists($upgradeversion)){
        file_put_contents($upgradeversion,0);
    }
    $upgradeconfig = json_decode(file_get_contents($upgradefile),true);
    $version = file_get_contents($upgradeversion);
    return [$upgradeconfig,$version];
}
//更新
function upgrade_version($step=1,$authorization_code,$version,$domain,$ip)
{
    switch ($step){
        case 1:
            $url = get_upgrade_domain().'/upgrade/index/upgrade_version/step/1';
            break;
        case 2:
            $url = get_upgrade_domain().'/upgrade/index/upgrade_version/step/2';
            break;
    }
    $param = ['authorization_code'=>$authorization_code,'version'=>$version,'domain'=>$domain,'ip'=>$ip];
    $res = request_post($url,$param);
    return $res;
}

/**
 * @函数或方法说明
 * @获取更新地址域名
 * @return string
 *
 * @author: 郭家屯
 * @since: 2020/9/1 14:32
 */
if (!function_exists('get_upgrade_domain')) {
    function get_upgrade_domain()
    {
        return 'https://fuwu.vlsdk.com';
    }
}

/**
 * 将读取到的目录以数组的形式展现出来
 * @return array
 * opendir() 函数打开一个目录句柄，可由 closedir()，readdir() 和 rewinddir() 使用。
 * is_dir() 函数检查指定的文件是否是目录。
 * readdir() 函数返回由 opendir() 打开的目录句柄中的条目。
 * @param array $files 所有的文件条目的存放数组
 * @param string $file 返回的文件条目
 * @param string $dir 文件的路径
 * @param resource $handle 打开的文件目录句柄
 */
function my_scandir($dir)
{
    //定义一个数组
    $files = array();
    //检测是否存在文件
    if (is_dir($dir)) {
        //打开目录
        if ($handle = opendir($dir)) {
            //返回当前文件的条目
            while (($file = readdir($handle)) !== false) {
                //去除特殊目录
                if ($file != "." && $file != "..") {
                    //判断子目录是否还存在子目录
                    if (is_dir($dir . "/" . $file)) {
                        //递归调用本函数，再次获取目录
                        $files[$file] = my_scandir($dir . "/" . $file);
                    } else {
                        //获取目录数组
                        $files[] = $dir . "/" . $file;
                    }
                }
            }
            //关闭文件夹
            closedir($handle);
            //返回文件夹数组
            return $files;
        }
    }
}
function upgrade_execute_sql($db,$sql)
{
    $sql = trim($sql);
    preg_match('/CREATE TABLE .+ `([^ ]*)`/', $sql, $matches);
    if ($matches) {
        $table_name = $matches[1];
        $msg        = "创建数据表{$table_name}";
        try {
            $db->execute($sql);
            return [
                'error'   => 0,
                'message' => $msg . ' 成功！'
            ];
        } catch (\Exception $e) {
            return [
                'error'     => 1,
                'message'   => $msg . ' 失败！',
                'messageinfo' => $e->getMessage()
            ];
        }

    } else {
        try {
            $db->execute($sql);
            return [
                'error'   => 0,
                'message' => 'SQL执行成功!'
            ];
        } catch (\Exception $e) {
            return [
                'error'     => 1,
                'message'   => 'SQL执行失败！',
                'messageinfo' => $e->getMessage()
            ];
        }
    }
}
function show($message) {
    echo '<p>';
    echo $message;
    echo '</p>';
    ob_flush();
    flush();
}
//文件夹及子文件夹复制
function recurse_copy($src,$dst){
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )&&( $file != 'upgrade_sql' )) {
            if ( is_dir($src . $file) ) {
                recurse_copy($src . $file . '/',$dst  . $file . '/');
            }else {
                copy($src . $file,$dst . $file);
            }
        }
    }
    closedir($dir);
}
//编码转utf-8
function characet($data){
    if( !empty($data) ){
        $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
        if( $fileType != 'UTF-8'){
            $data = mb_convert_encoding($data ,'utf-8' , $fileType);
        }
    }
    return $data;
}
