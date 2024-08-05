<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\upgrade\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class IndexController extends AdminBaseController
{
    protected $authorization_code;
    protected $ip;
    protected $domain;
    protected $version;
    protected function initialize()
    {
        parent::_initialize();
        $res = check_upgrade();
        $this->authorization_code = $res[0]['from'];
        $this->ip = $this->request->server()['SERVER_ADDR'];
        $this->domain = $this->request->domain();
        $this->version = $res[1];
    }
    public function index()
    {
        $version_list = vesion_list($this->authorization_code,$this->version);
        $data = json_decode($version_list,true);
        $data_lists = [];
        if($data['code']==200){
            $data_lists = $data['data'];
        }
        $error_path = WEB_ROOT.'../data/'.'upgrade/error.txt';
        $errorurl = url('sql_error');
        $errorsql = file_get_contents($error_path);
        if($errorsql!=''){
            $up_error = 1;
        }
        $this->assign('up_error',$up_error?:0);
        $this->assign('data_lists',$data_lists);
        $this->assign('data',$data);
        $this->assign('errorurl',$errorurl);
        return $this->fetch();
    }
    public function upgrade_info()
    {
        //检测是否允许写入
        if(!is_writeable(WEB_ROOT.'../data/'))  return 'data目录不存在或不可写，请检查后重试！';
        return $this->fetch();
    }
    // 更新检测
    public function check_version()
    {
        //检测是否允许写入
        session('upgrade_session',null);
        if(!is_writeable(WEB_ROOT.'../data/'))  return json([code=>'1004','msg'=>'data目录不存在或不可写，请检查后重试！']);
        $res=upgrade_version(1,$this->authorization_code,$this->version,$this->domain,$this->ip);
        $res_arr = json_decode($res,true);
        if($res_arr['code']==200){
            $res = upgrade_version(2,$this->authorization_code,$this->version,$this->domain,$this->ip);
            $res_arr = json_decode($res,true);
            $res_arr['data'] = [$res_arr['data'][count($res_arr['data'])-1]];
            if($res_arr['code']==200){
                session('upgrade_session',$res_arr);
            }
        }
        return $res;
    }

    public function upgrade_option()
    {
        $step = $this->request->param('step');
        $res_arr = session('upgrade_session');
        if(empty($res_arr)){
            $this->error('数据错误');
        }
        switch ($step){
            case 1:
                session('upgrade_session',null);
                //下载更新包
                $files = $this->down_file($res_arr['data']);
                session('upgrade_session',$files);
                $this->success('更新包下载完成');
                break;
            case 2:
                //解压更新包
                $files = session('upgrade_session');
                $unzipres = $this->unzip($files);
                if($unzipres==1003){
                    $this->error('无法打开更新包');
                }else{
                    $this->success('更新包解压完成');
                }
                break;
            case 3:
                //备份数据库
                $this->success('sql解压完成');
                break;
            case 4:
                $files = session('upgrade_session');
                //获取文件夹目录及文件列表
                $scandir = $this->scandir($files);
                //获取sql执行文件
                foreach ($scandir as $sk=>&$sv){
                    foreach ($sv as $skk=>$svv){
                        if($skk=='upgrade_sql'){
                            $sql[$sk] = $svv;
                            unset($sv[$skk]);
                        }
                    }
                }
                ksort($sql);
                ksort($scandir);
                session('upgrade_session',$scandir);
                //创建及覆盖
                $overview  = $this->execute_sql($sql,$scandir);
                break;
            case 5:
                //覆盖更新文件
                $scandir = session('upgrade_session');
                $this->overview($scandir);
                break;
        }



    }
    //下载更新包
    private function down_file($data){
        $files = [];
        foreach ($data as $k=>$v){
            $fileurl = $v['file_urls'];
            $files[] = $filename = $v['version_num'].'.'.pathinfo($v['file_names'])['extension'];
            $path = WEB_ROOT.'../data/'.'upgrade/';
            if(!is_dir($path)){
                mkdir($path);
            }
            $file=file_get_contents($fileurl);
            file_put_contents($path.$filename,$file);
        }
        return $files;
    }
    //解压更新包
    private function unzip($files)
    {
        $path = WEB_ROOT.'../data/'.'upgrade/';
        foreach ($files as $k=>$v){
            $pathinfo = pathinfo($v);
            $filepath = $path.$v;
            //实例化ZipArchive类
            $zip = new \ZipArchive();
            //打开压缩文件，打开成功时返回true
            if ($zip->open($filepath) === true) {
                //解压文件到获得的路径a文件夹下
                $zip->extractTo($path.$pathinfo['filename'].'/');
                //关闭
                $zip->close();
            } else {
                return 1003;//无法打开压缩包
            }
        }
        return 200;
    }
    //获取文件列表
    private function scandir($files)
    {
        $path = WEB_ROOT.'../data/'.'upgrade/';
        $file = [];
        foreach ($files as $k=>$v){
            $pathinfo = pathinfo($v);
            $dirname = $path.$pathinfo['filename'];
            $file[$pathinfo['filename']] = my_scandir($dirname);
//            $result = [];
//            array_walk_recursive($file[$pathinfo['filename']], function($value) use (&$result) {
//                array_push($result, $value);
//            });
//            $res[$pathinfo['filename']] = $result;
        }
        return $file;
    }
    //执行更新sql
    private function execute_sql($sql,$data)
    {
        $dbConfig             = [];
        $dbConfig['type']     = config('database.type');
        $dbConfig['hostname'] = config('database.hostname');
        $dbConfig['username'] = config('database.username');
        $dbConfig['password'] = config('database.password');
        $dbConfig['database'] = config('database.database');
        $dbConfig['hostport'] = config('database.hostport');
        $dbConfig['charset']  = config('database.charset');
        $dbConfig['params'] = [
            \PDO::ATTR_CASE              => \PDO::CASE_LOWER,
            \PDO::ATTR_EMULATE_PREPARES  => true,
        ];
        $db = Db::connect($dbConfig);
        $error = '';
        foreach ($data as $k=>$v){
            if(!empty($sql[$k])){
                $sqlcontent = cmf_split_sql($sql[$k][0],'');
                foreach ($sqlcontent as $sk=>$sv){
                    $sqlToExec = characet($sv) ;
                    $sqlToExec = trim($sqlToExec);
                    $result = upgrade_execute_sql($db,$sqlToExec);
                    if($result['error']!=0){
                        \think\Log::record('sql-'.$sqlToExec.'执行失败-'.$result['messageinfo']);
                        $error = $error.'sql'.'<br>'.$sqlToExec.'<br>'.'执行失败'.'<br>'.$result['messageinfo'].'<br>';
//                        $this->error('sql执行失败，请检查重试，或者联系客服 溪谷官网http://www.vlcms.com');
                    }
                }
            }
            $code = 1;
            $msg = '更新sql执行完成';
            $error_path = WEB_ROOT.'../data/'.'upgrade/error.txt';
            $errorurl = url('sql_error');
            file_put_contents(@iconv('UTF-8','GBK',$error_path),$error);
            if($error!=''){
                $code = 0;
                $msg = "部分sql语句执行失败，错误日志：<a target='_blank' href='$errorurl'>查看</a>|<a class='error_close' href='javascript:;'>关闭</a>|<a class='overviewfile' href='javascript:;'>继续</a>";
            }
            exit(json_encode(['code'=>$code,'msg'=>$msg]));
        }
    }
    //文件覆盖
    private function overview($data)
    {
        foreach ($data as $k=>$v){
            $dirpath = WEB_ROOT.'../data/'.'upgrade/'.$k.'/';
            $topath = WEB_ROOT.'../';
            recurse_copy($dirpath,$topath);
            $upgradeversion = WEB_ROOT.'../data/'.'upgrade_version.txt';
            file_put_contents($upgradeversion,$k);
            exit(json_encode(['code'=>1,'msg'=>'更新文件覆盖成功','status'=>1]));
//            $ks = (string)$k;
//            foreach ($v as $kk=>$vv){
//                $str = $ks.'/';
//                $pointer = strpos($vv,$str);
//                $file = CMF_ROOT.substr($vv,$pointer+strlen($str));
//                copy($vv,$file);
//                echo($str.'<br>');
//            }
        }
    }
    public function sql_error(){
        $error_path = WEB_ROOT.'../data/'.'upgrade/error.txt';
        $error_content = file_get_contents($error_path);
        return $error_content;
    }
    public function solve_error(){
        $error_path = WEB_ROOT.'../data/'.'upgrade/error.txt';
        $error_content = file_put_contents($error_path,'');
        $this->success('已解决');
    }
}

