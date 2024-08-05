<?php

namespace app\promote\command;

use app\common\logic\V2SignPackLogic;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Package extends Command
{

    protected function configure()
    {
        $this->setName('package')
            ->addArgument('upload_oss', Argument::OPTIONAL, '上传云')
            ->setDescription('渠道打包');
    }

    protected function execute(Input $input, Output $output)
    {
        app_auth_value();//获取权限
        if (AUTH_PROMOTE != 1) {
            $output->newLine();
            $output->writeln("没有渠道权限");
            return false;
        }
        if (AUTH_GAME != 1) {
            $output->newLine();
            $output->writeln("没有游戏权限");
            return false;
        }
        $find_web_stie = cmf_get_option('admin_set')['web_site'];
        if (empty($find_web_stie)) {
            $output->newLine();
            $output->writeln("管理后台未设置网站域名");
            return false;
        }
//        上传云存储
        $upload_oss = $input->getArgument('upload_oss');
        $upload_oss = empty($upload_oss) ? 0 : 1;
        if ($upload_oss) {
//            上传云存储
            if(PERMI != 2) {//手游上传
                $this->upload_oss($input, $output);
            }
            if(PERMI != 1) {//H5上传
                $this->h5upload_oss($input, $output);
            }
            $output->newLine();
            $output->writeln("上传结束");
        } else {
            if(PERMI != 2){//手游打包
                //百度云批量打包
                //$this->bcepack($find_web_stie, $input, $output);
                //服务器渠道手游打包
                $this->localpack($input, $output);
            }
            if(PERMI != 1){//H5打包
                //服务器渠道H5打包
                $this->h5localpack($input, $output);
            }
            //服务器渠道打包app
            $this->applocalpack($input,$output);

        }

    }

    //生成游戏渠道plist文件
    protected function create_plist($game_id = 0, $promote_id = 0, $baoming = "", $url = "")
    {
        $find_web_stie = cmf_get_option('admin_set')['web_site'];
//        先不上传云存储  本地文件
        $newurl = 'https://' . $find_web_stie . '/upload/' . $url;
        //强制https
        if (strpos($url, 'https://') === false) {
            $url = str_replace("http://", "https://", $newurl);
        }
        $xml = new \DOMDocument();
        $xml->load(ROOT_PATH . 'public/upload/plist/testdemo.plist');
        $online = $xml->getElementsByTagName('dict');//查找节点
        $asd = $online->item(1)->getElementsByTagName('string');//第二个节点下所有string
        foreach ($asd as $key => $value) {
            switch ($value->textContent) {
                case 'ipa_url':
                    $value->nodeValue = $url;
                    break;
                case 'icon':
                    $value->nodeValue = "https://" . $find_web_stie . '/upload/' . get_game_list('icon', ['id' => $game_id])[0]['icon'];
                    break;
                case 'com.dell':
                    $value->nodeValue = $baoming;
                    break;
                case 'mchdemo':
                    $value->nodeValue = get_game_entity($game_id,'relation_game_name')['relation_game_name'];
                    break;

            }
            if ($promote_id == 0) {
                $xml->save(ROOT_PATH . "public/upload/sourceplist/$game_id.plist");
            } else {
                $pname = $game_id . "-" . $promote_id;
                $xml->save(ROOT_PATH . "public/upload/promotegameplist/$pname.plist");
            }
        }
        if ($promote_id == 0) {
            return "sourceplist/$game_id.plist";
        } else {
            return "promotegameplist/$pname.plist";
        }
    }

    //生成App plist文件
    public function create_plist_app($version = "", $app_id=0,$promote_id = 0, $marking = "", $url = "")
    {
        $find_web_stie = cmf_get_option('admin_set')['web_site'];
//        先不上传云存储  本地文件
        $url = 'https://' . $find_web_stie . '/upload/' . $url;
        $xml = new \DOMDocument();
        $xml->load(ROOT_PATH . 'public/upload/plist/testdemo.plist');
        $online = $xml->getElementsByTagName('dict');//查找节点
        $asd = $online->item(1)->getElementsByTagName('string');//第二个节点下所有string
        foreach ($asd as $key => $value) {
            switch ($value->textContent) {
                case 'ipa_url':
                    $value->nodeValue = $url;
                    break;
                case 'icon':
                    $value->nodeValue = 'https://' . $find_web_stie . '/upload/' .cmf_get_option('app_set')['app_logo'];
                    break;
                case 'com.dell':
                    $value->nodeValue = $marking == "" ? 'app' : $marking;
                    break;
                case '1.0.0':
                    $value->nodeValue = $version == "" ? '2' : $version;
                    break;
                case 'mchdemo':
                    $value->nodeValue = "APP";
                    break;

            }
            $pname = $promote_id.'_'.$app_id;
            if ($promote_id == 0) {
                $xml->save(ROOT_PATH . "public/upload/sourceappplist/$app_id.plist");
            } else {
                $xml->save(ROOT_PATH . "public/upload/appplist/$pname.plist");
            }
        }
        if ($promote_id == 0) {
            return "sourceappplist/$app_id.plist";
        } else {
            return "appplist/$pname.plist";
        }
    }

    // 百度云批量打包
    private function bcepack($find_web_stie, $input, $output)
    {
        //检查是否需要百度云打包
        $tsvmap['status'] = 1;
        $tsvmap['enable_status'] = ['eq', '2'];//准备打包状态
        $tsvmap['pack_type'] = ['eq', '3'];//百度批量打包方式
        $apply_tsv_data = Db::table('tab_promote_apply')->field('id,game_id,promote_id,sdk_version,pack_type')->where($tsvmap)->order('id asc')->select()->toarray();
        $storage = cmf_get_option('storage');
        if ($storage['type'] != 'BaiduBac') {
            $output->newLine();
            $output->writeln("未开启百度云存储，无法进行批量打包");
            // return false;
        }
        $BaiduBac = Db::name('plugin')->field('config')->where(['name' => 'BaiduBac'])->find();
        $bdconfig = json_decode($BaiduBac['config'], true);
        if (empty($bdconfig['accesskeysecret'])) {
            $output->newLine();
            $output->writeln("百度云存储未配置完整");
            // return false;
        }

        //根据游戏分组
        $tsv_data = array_group_by($apply_tsv_data, 'game_id');
        if (empty($tsv_data)) {
            $output->newLine();
            $output->writeln("全部批量打包已打包完成");
            // return false;
        }
        //每个游戏执行一次打包命令 后面break跳出
        foreach ($tsv_data as $key => $value) {
            $game_id = $key;
            $source_file = $this->game_source($game_id);
            if (!file_exists(ROOT_PATH . 'public/upload/' . $source_file['file_url'])) {
                $output->newLine();
                $output->writeln("本地不存在游戏原包");
                continue;
            }
            $pfile = [];
            $apply_id = [];
            $promote_pack = [];

            foreach ($value as $k => $v) {
                //sdk内写入文件名
                $pfile[] = 'xigu~game_id=' . $source_file['game_id'] . '~game_name=' . $source_file['game_name'] . '~promote_id=' . $v['promote_id'] . '~promote_account=' . get_promote_name($v['promote_id']) . '~sdk_version=' . $v['sdk_version'];
                //渠道的包名
                $promote_pack[] = "game_package" . $source_file['game_id'] . "-" . $v['promote_id'] . '.apk';
                $apply_id[] = $v['id'];
            }
            //生成tsv文件 百度批量打包需要
            $tsv_header = 'insertedFileName' . "\t" . 'distributionPackageName';
            $content = '';
            foreach ($pfile as $key => $value) {
                $content .= PHP_EOL;
                $content .= replace_specialChar($pfile[$key], '', "/\/|\～|\，|\。|\！|\？|\“|\”|\【|\】|\『|\』|\：|\；|\《|\》|\’|\‘|\ |\·|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\\\|\|/") . "\t" . $promote_pack[$key];
            }
            //批量打包tsv文件
            $tsvpath = 'tsv/tsv' . $game_id . '.tsv';
            $tsvdatapath = 'tsv/tsv' . $game_id . '.txt';
            //删除上次文件
            @unlink(ROOT_PATH . 'public/upload/' . $tsvpath);
            @unlink(ROOT_PATH . 'public/upload/' . $tsvdatapath);
            if ($content != '') {
                $header = '';
                if (file_exists(ROOT_PATH . 'public/upload/' . $tsvpath) == false) {
                    $header = $tsv_header;
                }
                $tsv = $header . $content;
                //写入tsv文件
                $fp = fopen(ROOT_PATH . 'public/upload/' . $tsvpath, 'a');
                // 写入并关闭资源
                fwrite($fp, $tsv);
                fclose($fp);

                // 写入txt文件
                $fp = fopen(ROOT_PATH . 'public/upload/' . $tsvdatapath, 'a');
                // 写入并关闭资源
                fwrite($fp, ',' . implode(',', $apply_id));
                fclose($fp);
                //上传云存储
                $storage = cmf_get_option('storage');
                $storage = new \cmf\lib\Storage($storage['type'], $storage['storages'][$storage['type']]);
                $result = $storage->upload($tsvpath, ROOT_PATH . "public/upload/" . $tsvpath, 'file');
                if (empty($result)) {
                    $output->newLine();
                    $output->writeln("上传云失败");
                    continue;
                }
                if (file_exists(ROOT_PATH . 'public/upload/' . $tsvpath) != false) {
                    $return_val = 1;//命令执行结果 1为失败
                    //判断系统
                    if (PATH_SEPARATOR == ';') {
                        //windows
                        $command = 'set PYTHONIOENCODING=utf-8 &&';
                    } else {
                        //linux
                        $command = '';
                    }
                    $python = '"python"';
                    $parampath = ROOT_PATH . 'public/upload/tsv/param.txt';

                    $paramcontent = '';
                    $command = '';
                    // 实时修改param.txt
                    $paramcontent .= '{"algorithm" : "v1","channelFile" : "';
                    $paramcontent .= $tsvpath;
                    $paramcontent .= '","targetPrefix" : "android/"}';
                    //写入tsv文件
                    $fp = fopen($parampath, 'w');
                    // 写入并关闭资源
                    fwrite($fp, $paramcontent);
                    fclose($fp);
                    $errpath = '>' . dirname(__FILE__) . '/result.txt';
                    $callbackurl = 'http://' . $find_web_stie . '/promote/Bacpack/get_bac_androidpack_callback';
                    $bospath = "bos:/{$bdconfig['bucket']}/" . $source_file['file_url'];
                    $bcepath = dirname(__FILE__) . '/bce-cli-0.10.10/bce';
                    $py = " " . $bcepath . " bos process apk-pack " . $bospath . " --parameter " . $parampath . " --url " . $callbackurl . $errpath;
                    $command .= $python . $py . ' 2>&1';
                    $return_val = 1;
                    exec($command, $out, $return_val);
                    if ($return_val) {
                        $output->newLine();
                        $output->writeln("批量打包命令执行失败");
                    } else {
                        $result = json_decode(file_get_contents(dirname(__FILE__) . '/result.txt'), true);
                        if ($result['code'] != 66) {
                            $output->newLine();
                            $output->writeln("百度云Android批量打包失败");
                        } else {
                            $tsvcontent = file_get_contents(ROOT_PATH . 'public/upload/' . $tsvdatapath);
                            $d['requestid'] = $result['requestId'];
                            $d['jobids'] = implode(',', $result['jobIds']);
                            $d['apply_id'] = $tsvcontent;
                            $d['create_time'] = time();
                            $add = Db::table('tab_promote_bce_package')->insert($d);
                            if ($add != false) {
                                copy(ROOT_PATH . 'public/upload/' . $tsvpath, ROOT_PATH . 'public/upload/tsv/' . $d['requestid'] . '.tsv');
                                //删除文件
                                @unlink(ROOT_PATH . 'public/upload/' . $tsvpath);
                                @unlink(ROOT_PATH . 'public/upload/' . $tsvdatapath);
                                $output->newLine();
                                $output->writeln("百度云Android批量打包成功！");
                            }
                        }
                    }
                }
            } else {
                continue;
            }
        }
    }

    /**
     * @函数或方法说明
     * @本地H5渠道打包
     * @param $input
     * @param $output
     *
     * @author: 郭家屯
     * @since: 2020/6/17 17:08
     */
    private function h5localpack($input, $output)
    {
        $map['status'] = 1;
        $map['and_status|ios_status'] = 2;//准备打包状态  不含打包中3
        $apply_data = Db::table('tab_promote_apply')->field('id,game_id,promote_id,sdk_version,and_status,ios_status')->where($map)->limit(20)->order('id desc')->select()->toarray();
        if (!$apply_data) {
            $output->newLine();
            $output->writeln("全部H5渠道已打包完成");
        }
        foreach ($apply_data as $key => $value) {
            $output->newLine();
            $output->writeln("");
            if($value['and_status'] == 2){
                $this->and_package($value,$output);
            }
            if($value['ios_status'] == 2){
                $this->ios_package($value,$output);
            }
        }

    }
    /**
     * @函数或方法说明
     * @微端苹果打包
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/6/17 17:22
     */
    protected function ios_package($data=[],$output)
    {
        $game_so = Db::table('tab_game_source')->field('id,file_name,file_url,source_version')->where(['game_id' => $data['game_id'],'file_type'=>2])->find();
        $file_url = $game_so['file_url'];
        if (empty($game_so) || $file_url == '') {
            Db::table('tab_promote_apply')->where(['id' => $data['id']])->setField('ios_status', -1);
        } else {
            if (strpos($file_url, 'http') === false) {
                $file_url = ROOT_PATH . "public/upload/" . $file_url;
            }
            if(! file_exists($file_url)){
                Db::table('tab_promote_apply')->where(['id' => $data['id']])->setField('ios_status', -1);
            }else{
                Db::table('tab_promote_apply')->where(['id' => $data['id']])->setField('ios_status', 3);
                $zip_open_game_source = zip_open($file_url);
                if ($zip_open_game_source) {
                    while ($zip_entry = zip_read($zip_open_game_source)) {
                        if (preg_match("/.app/", zip_entry_name($zip_entry))) {
                            $ios_app = substr(zip_entry_name($zip_entry), 8) . "<br/>";
                        }
                        $new_ios_1 = explode("/", $ios_app);
                        if (preg_match("/MCHChannel/", zip_entry_name($zip_entry))) {
                            $deletedir[] = zip_entry_name($zip_entry);
                        }
                    }
                }
                $str_ver = ".ipa";
                $file_name = "ios";
                $new_name = "game_package" . $data['game_id'] . "-" . $data['promote_id'] . $str_ver;
                $tourl = $file_name . "/" . $new_name;
                $to = "public/upload/" . $tourl;
                copy($file_url, ROOT_PATH . $to);
                $zip = new \ZipArchive;
                $res = $zip->open(ROOT_PATH . $to, \ZipArchive::CREATE);
                if ($res == true) {
                    if($deletedir){
                        foreach ($deletedir as $key=>$v){
                            $zip->deleteName($v);
                        }
                    }
                    $dirname = $data['promote_id'].'#&'.get_promote_name($data['promote_id']).'#&'.$data['game_id'];
                    $url_ver = "Payload/" . $new_ios_1[0] . "/MCHChannel/".$dirname;
                    $zip->addEmptyDir($url_ver);
                    $zip -> close();
                    //记录上传云存储
                    Db::table('tab_promote_apply')->update(['id' => $data['id'], 'ios_upload' => 0]);
                    $storage = cmf_get_option('storage');
                    if (empty($storage['type'])) {
                        $storage['type'] = 'Local';
                    }
                    if ($storage['type'] != 'Local') {
                        $upmap['promote_apply_id'] = $data['id'];
                        $upmap['type'] = 2;
                        $is_exist = Db::table('tab_promote_apply_upload')->field('id')->where($upmap)->find();
                        if ($is_exist) {
                            $save['id'] = $is_exist['id'];
                            $save['status'] = 0;
                            $save['update_time'] = time();
                            $record_upload = Db::table('tab_promote_apply_upload')->update($save);
                        } else {
                            $add['promote_apply_id'] = $data['id'];
                            $add['status'] = 0;
                            $add['create_time'] = time();
                            $add['update_time'] = time();
                            $add['type'] = 2;
                            $record_upload = Db::table('tab_promote_apply_upload')->insert($add);
                        }
                    }
                    $promote = array('game_id' => $data['game_id'], 'promote_id' => $data['promote_id']);
                    $plist_url = $this->create_plist($promote['game_id'], $promote['promote_id'], get_payload_name($data['game_id'],2), $tourl);
                    $jieguo = $this->updateh5info($data['id'], $tourl, $promote, $plist_url,2);
                    $output->newLine();
                    $output->writeln("{$new_name}已打包完成");
                }
            }
        }
    }

    /**
     * @函数或方法说明
     * @微端安卓打包
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/6/17 17:22
     */
    protected function and_package($data=[],$output)
    {
        $game_so = Db::table('tab_game_source')->field('id,file_name,file_url,source_version')->where(['game_id' => $data['game_id'],'file_type'=>1])->find();
        $file_url = $game_so['file_url'];
        if (empty($game_so) || $file_url == '' ) {
            Db::table('tab_promote_apply')->where(['id' => $data['id']])->setField('and_status', -1);
        } else {
            if (strpos($file_url, 'http') === false) {
                $file_url = ROOT_PATH . "public/upload/" . $file_url;
            }
            if(!file_exists($file_url)){
                Db::table('tab_promote_apply')->where(['id' => $data['id']])->setField('and_status', -1);
            }else{
                Db::table('tab_promote_apply')->where(['id' => $data['id']])->setField('and_status', 3);
                $str_ver = ".apk";
                $file_name = "android";
                $url_ver = "META-INF/mch.properties";
                $new_name = "game_package" . $data['game_id'] . "-" . $data['promote_id'] . $str_ver;
                $tourl = $file_name . "/" . $new_name;
                $to = "public/upload/" . $tourl;
                copy($file_url, ROOT_PATH . $to);
                $zip = new \ZipArchive;
                $res = $zip->open(ROOT_PATH . $to, \ZipArchive::CREATE);
                if ($res == true) {
                    #打包数据
                    $game = get_game_entity($data['game_id'],'game_name,game_appid');
                    $pack_data = array(
                            "game_id" => $data["game_id"],
                            "game_name" => $game['game_name'],
                            "game_appid" => $game['game_appid'],
                            "promote_id" => $data['promote_id'],
                            "promote_account" => get_promote_name($data['promote_id']),
                            "source_version" => $game_so['source_version'],
                    );
                    $zip -> addFromString($url_ver, json_encode($pack_data));
                    $zip -> close();
                    //记录上传云存储
                    Db::table('tab_promote_apply')->update(['id' => $data['id'], 'and_upload' => 0]);
                    $storage = cmf_get_option('storage');
                    if (empty($storage['type'])) {
                        $storage['type'] = 'Local';
                    }
                    if ($storage['type'] != 'Local') {
                        $upmap['promote_apply_id'] = $data['id'];
                        $upmap['type'] = 1;
                        $is_exist = Db::table('tab_promote_apply_upload')->field('id')->where($upmap)->find();
                        if ($is_exist) {
                            $save['id'] = $is_exist['id'];
                            $save['status'] = 0;
                            $save['update_time'] = time();
                            $record_upload = Db::table('tab_promote_apply_upload')->update($save);
                        } else {
                            $add['promote_apply_id'] = $data['id'];
                            $add['status'] = 0;
                            $add['create_time'] = time();
                            $add['update_time'] = time();
                            $add['type'] = 1;
                            $record_upload = Db::table('tab_promote_apply_upload')->insert($add);
                        }
                    }
                    $promote = array('game_id' => $data['game_id'], 'promote_id' => $data['promote_id']);
                    $jieguo = $this->updateh5info($data['id'], $tourl, $promote, '',1);
                    $output->newLine();
                    $output->writeln("{$new_name}已打包完成");
                }
            }
        }

    }

    // 本地渠道打包
    private function localpack($input, $output)
    {
        $map['status'] = 1;
        $map['enable_status'] = ['eq', '2'];//准备打包状态  不含打包中3
        $map['pack_type'] = ['neq', '3'];//不含百度批量打包
        $apply_data = Db::table('tab_promote_apply')->field('id,game_id,promote_id,sdk_version,pack_type')->where($map)->limit(2)->order('id desc')->select()->toarray();
        if (!empty($apply_data)) {
            foreach ($apply_data as $key => $value) {
                $err = 0;
                $output->newLine();
                $output->writeln("");
                $game_so = Db::table('tab_game_source')->field('id,file_name,file_url,source_version,third_source_info')->where(['game_id' => $value['game_id']])->find();
                $game_info = get_game_entity($value['game_id'],'platform_id');
                // 第三方游戏打包信息
                $thirdSourceInfo = json_decode($game_so['third_source_info'],true);
                $file_url = $game_so['file_url'];
                if ($file_url != '') {
                    if (strpos($file_url, 'http') === false) {
                        $file_url = ROOT_PATH . "public/upload/" . $file_url;
                    } else {
                        $file_url = $file_url;
                    }
                    if (!file_exists($file_url) || empty($game_so)) {
                        $err = 1;
                    }
                } else {
                    $err = 1;
                }
                if ($err) {
                    Db::table('tab_promote_apply')->where(['id' => $value['id']])->setField('enable_status', -1);
                    // Db::table('tab_promote_apply')->where(['id'=>$value['id']])->setField('pack_mark','原包不存在');
                    continue;
                }
                Db::table('tab_promote_apply')->where(['id' => $value['id']])->setField('enable_status', 3);


                if ($value['sdk_version'] == 1) {
                    $str_ver = ".apk";
                    $file_name = "android";
                    $url_ver = "META-INF/mch.properties";
                } else {
                    $zip_open_game_source = zip_open($file_url);
                    if ($zip_open_game_source) {
                        while ($zip_entry = zip_read($zip_open_game_source)) {
                            if (preg_match("/.app/", zip_entry_name($zip_entry))) {
                                $ios_app = substr(zip_entry_name($zip_entry), 8) . "<br/>";
                            }
                            $new_ios_1 = explode("/", $ios_app);
                            if (preg_match("/MCHChannel/", zip_entry_name($zip_entry))) {
                                $deletedir[] = zip_entry_name($zip_entry);
                            }
                        }
                    }
                    $str_ver = ".ipa";
                    $file_name = "ios";
                }
                $new_name = "game_package" . $value['game_id'] . "-" . $value['promote_id'] . $str_ver;
                $tourl = $file_name . "/" . $new_name;
                $to = "public/upload/" . $tourl;
                $copy = copy($file_url, ROOT_PATH . $to);

                //V2签名打包
                if ($value['sdk_version'] == '1' && $game_info['platform_id']==0) {
                    $lV2 = new V2SignPackLogic();
                        // 联运渠道包
                    $v2signRes = $lV2 -> pack(ROOT_PATH . $to, $value['promote_id'], $value['game_id'], $game_so['source_version']);
                    if ($v2signRes) {
                        //V2打包完成
                        $promote = array('game_id' => $value['game_id'], 'promote_id' => $value['promote_id']);
                        $this -> updateinfo($value['id'], $tourl, $promote, '');
                        $output->newLine();
                        $output->writeln("{$new_name} V2已打包完成");
                        continue;
                    }
                }

                $zip = new \ZipArchive;
                $res = $zip->open(ROOT_PATH . $to, \ZipArchive::CREATE);
                if ($res == true) {
                    #打包数据
                    $pack_data = array(
                        "game_id" => $value["game_id"],
                        "game_name" => get_game_name($value['game_id']),
                        "game_appid" => get_game_list('game_appid', ['id' => $value["game_id"]])[0]['game_appid'],
                        "promote_id" => $value['promote_id'],
                        "promote_account" => get_promote_name($value['promote_id']),
                        "source_version" => $game_so['source_version'],
                    );
                    if ($value['sdk_version'] == 1) {
                        // 第三方渠道包打包数据判断
                        if($game_info['platform_id'] > 0 && !empty($thirdSourceInfo)) {
                            $pack_data = array(
                                "game_id" => $thirdSourceInfo["game_id"],
                                "game_name" => $thirdSourceInfo['game_name'],
                                "game_appid" => $thirdSourceInfo['game_appid'],
                                "promote_id" => $thirdSourceInfo['promote_id'] . '_XgPid_' . $value['promote_id'],
                                "promote_account" => $thirdSourceInfo['promote_account'],
                                "source_version" => $thirdSourceInfo['source_version'],
                            );
                        }
                        $zip -> addFromString($url_ver, json_encode($pack_data));
                        $zip -> close();
                    }else{
                        if($deletedir){
                            foreach ($deletedir as $key=>$v){
                                $zip->deleteName($v);
                            }
                        }
                        // 第三方渠道包打包数据判断
                        if($game_info['platform_id'] > 0 && !empty($thirdSourceInfo)) {
                            $dirname = $thirdSourceInfo['promote_id'].'_XgPid_'.$value['promote_id'].'#&'.$thirdSourceInfo['promote_account'].'#&'.$thirdSourceInfo['source_version'];
                        }else{
                            $dirname = $value['promote_id'].'#&'.get_promote_name($value['promote_id']).'#&'.$game_so['source_version'];
                        }
                        $url_ver = "Payload/" . $new_ios_1[0] . "/MCHChannel/".$dirname;
                        $zip->addEmptyDir($url_ver);
                        $zip -> close();
                    }
                    //记录上传云存储
                    Db::table('tab_promote_apply')->update(['id' => $value['id'], 'is_upload' => 0]);
                    $storage = cmf_get_option('storage');
                    if (empty($storage['type'])) {
                        $storage['type'] = 'Local';
                    }
                    if ($storage['type'] != 'Local') {
                        $upmap['promote_apply_id'] = $value['id'];
                        $is_exist = Db::table('tab_promote_apply_upload')->field('id')->where($upmap)->find();
                        if ($is_exist) {
                            $save['id'] = $is_exist['id'];
                            $save['status'] = 0;
                            $save['update_time'] = time();
                            $record_upload = Db::table('tab_promote_apply_upload')->update($save);
                        } else {
                            $add['promote_apply_id'] = $value['id'];
                            $add['status'] = 0;
                            $add['create_time'] = time();
                            $add['update_time'] = time();
                            $record_upload = Db::table('tab_promote_apply_upload')->insert($add);
                        }
                    }
                    $promote = array('game_id' => $value['game_id'], 'promote_id' => $value['promote_id']);
                    $plist_url = '';
                    if ($value['sdk_version'] == 2) {
                        $plist_url = $this->create_plist($promote['game_id'], $promote['promote_id'], get_payload_name($value['game_id']), $tourl);
                    }
                    $jieguo = $this->updateinfo($value['id'], $tourl, $promote, $plist_url);
                    $output->newLine();
                    $output->writeln("{$new_name}已打包完成");
                }
            }
        } else {
            $output->newLine();
            $output->writeln("全部渠道已打包完成");
        }
    }

    // 本地渠道打包
    private function applocalpack($input, $output)
    {
        $map['status'] = 1;
        $map['enable_status'] = ['eq', '2'];//准备打包状态  不含打包中3
        $map['is_user_define'] = 0;//不是自定义渠道包
        $app_data = Db::table('tab_promote_app')->field('id,promote_id,app_version,app_id')->where($map)->limit(20)->order('id desc')->select()->toarray();
        if (!empty($app_data)) {
            foreach ($app_data as $key => $value) {
                $err = 0;
                $output->newLine();
                $output->writeln("");
                $app_so = Db::table('tab_app')->field('id,file_url,bao_name')->where(['id' => $value['app_id']])->find();
                $file_url = $app_so['file_url'];
                if ($file_url != '') {
                    if (strpos($file_url, 'http') === false) {
                        $file_url = ROOT_PATH . "public/upload/" . $file_url;
                    } else {
                        $file_url = $file_url;
                    }
                    if (!file_exists($file_url) || empty($app_so)) {
                        $err = 1;
                    }
                } else {
                    $err = 1;
                }
                if ($err) {
                    Db::table('tab_promote_app')->where(['id' => $value['id']])->setField('enable_status', -1);
                    continue;
                }
                Db::table('tab_promote_app')->where(['id' => $value['id']])->setField('enable_status', 3);
                if ($value['app_version'] == 1) {
                    $str_ver = ".apk";
                    $file_name = "app_android";
                    $url_ver = "META-INF/mch.properties";
                } else {
                    $zip_open_game_source = zip_open($file_url);
                    if ($zip_open_game_source) {
                        while ($zip_entry = zip_read($zip_open_game_source)) {
                            if (preg_match("/.app/", zip_entry_name($zip_entry))) {
                                $ios_app = substr(zip_entry_name($zip_entry), 8) . "<br/>";
                            }
                            $new_ios_1 = explode("/", $ios_app);
                            if (preg_match("/MCHChannel/", zip_entry_name($zip_entry))) {
                                $deletedir[] = zip_entry_name($zip_entry);
                            }
                        }
                    }
                    $str_ver = ".ipa";
                    $file_name = "app_ios";
                }
                $new_name = "app_package" . $value['app_id'] . "-" . $value['promote_id'] . $str_ver;
                $tourl = $file_name . "/" . $new_name;
                $to = "public/upload/" . $tourl;
                $copy = copy($file_url, ROOT_PATH . $to);
                $zip = new \ZipArchive;
                $res = $zip->open(ROOT_PATH . $to, \ZipArchive::CREATE);
                if ($res == true) {
                    #打包数据
                    $pack_data = array(
                            "promote_id" => $value['promote_id'],
                            "promote_account" => get_promote_name($value['promote_id'])
                    );
                    if ($value['app_version'] == 1) {
                        $zip -> addFromString($url_ver, json_encode($pack_data));
                        $zip -> close();
                    }else{
                        if($deletedir){
                            foreach ($deletedir as $key=>$v){
                                $zip->deleteName($v);
                            }
                        }
                        $dirname = $value['promote_id'].'#&'.get_promote_name($value['promote_id']);
                        $url_ver = "Payload/" . $new_ios_1[0] . "/MCHChannel/".$dirname;
                        $zip->addEmptyDir($url_ver);
                        $zip -> close();
                    }

                    $plist_url = '';
                    if ($value['app_version'] == 2) {
                        $plist_url = $this->create_plist_app(cmf_get_option('app_set')['app_version'],$value['app_id'], $value['promote_id'], $app_so['bao_name'], $tourl);
                    }
                    $jieguo = $this->updateappinfo($value['id'], $tourl, $plist_url);
                    $output->newLine();
                    $output->writeln("{$new_name}已打包完成");
                }
            }
        } else {
            $output->newLine();
            $output->writeln("全部渠道APP已打包完成");
        }
    }

//    上传oss
    protected function upload_oss($input, $output)
    {
        $map['status'] = ['in', '0,3'];
        $map['type'] = 0;
        $data = Db::table('tab_promote_apply_upload')->where($map)->limit(2)->order('id asc')->select()->toArray();
        foreach ($data as $k => $v) {
            $storage = cmf_get_option('storage');
            if (empty($storage['type'])) {
                $storage['type'] = 'Local';
            }
            if ($storage['type'] != 'Local') {
                $watermark = cmf_get_plugin_config($storage['type']);
                $storage = new \cmf\lib\Storage($storage['type'], $storage['storages'][$storage['type']]);
                $apply_data = Db::table('tab_promote_apply')->field('promote_id,game_id,pack_url,sdk_version')->where(['id' => $v['promote_apply_id']])->find();
                //无数据或未打包
                if (empty($apply_data['pack_url'])) {
                    //打包记录已删除
                    Db::table('tab_promote_apply_upload')->where(['id' => $v['id']])->update(['status' => -1]);
                    continue;
                } else {
//                    $status = Db::table('tab_promote_apply_upload')->where(['id'=>$v['id']])->update(['status'=>2]);
                    if ($apply_data['sdk_version'] == 1) {
                        $str_ver = ".apk";
                        $file_name = "android";
                    } else {
                        $str_ver = ".ipa";
                        $file_name = "ios";
                    }
                }
                $new_name = "game_package" . $apply_data['game_id'] . "-" . $apply_data['promote_id'] . $str_ver;
                $tourl = $file_name . "/" . $new_name;
                if (!file_exists(ROOT_PATH . "public/upload/" . $tourl)) {
                    Db::table('tab_promote_apply_upload')->where(['id' => $v['id']])->update(['status' => -1]);
                    $output->newLine();
                    $output->writeln("{$new_name}游戏原包本地不存在");
                    continue;
                }
                $result = $storage->upload($tourl, ROOT_PATH . "public/upload/" . $tourl, 'file');
                if (!empty($result)) {
                    Db::table('tab_promote_apply_upload')->where(['id' => $v['id']])->update(['status' => 1]);
                    Db::table('tab_promote_apply')->where(['id' => $v['promote_apply_id']])->update(['is_upload' => 1]);
                    $unlink = 1;
                    //plist下载路径修改为云存储路径
                    if ($apply_data['sdk_version'] == 2) {
                        $localplist = ROOT_PATH . "public/upload/promotegameplist/" . $apply_data['game_id'] . "-" . $apply_data['promote_id'] . '.plist';
                        $xmldoc = new \DOMDocument();
                        $xmldoc->load($localplist);
                        $assets = $xmldoc->getElementsByTagName('string');
                        if ($assets->length) {
                            $find_web_stie = cmf_get_option('admin_set')['web_site'];
                            $oldurl = $assets->item(1);
                            if(cmf_get_option('storage')['type'] == 'Qcloud'){
                                $Qcloud = cmf_get_plugin_config('Qcloud');
                                if($Qcloud['domain']){
                                    $newurl = 'https://' . $Qcloud['domain'] . '/' . $apply_data['pack_url'];
                                }else{
                                    $newurl = 'https://' . $Qcloud['bucket'] .'.cos.'.$Qcloud['region'].'.myqcloud.com' . '/' . $apply_data['pack_url'];
                                }
                            }else{
                                $newurl = 'https://' . $watermark['bucket'] . '.' . $watermark['domain'] . '/' . $apply_data['pack_url'];
                            }
                            $oldurl->nodeValue = $newurl;
                            $replace = $xmldoc->save($localplist);
                        } else {
                            $unlink = 0;
                        }

                    }
                    if ($unlink == 1) {
                        @unlink(ROOT_PATH . "public/upload/" . $tourl);
                    }
                }
            }
        }
    }

    //    H5上传oss
    protected function h5upload_oss($input, $output)
    {
        $map['status'] = ['in', '0,3'];
        $map['type'] = ['gt',0];
        $data = Db::table('tab_promote_apply_upload')->where($map)->limit(10)->order('id asc')->select()->toArray();
        foreach ($data as $k => $v) {
            $storage = cmf_get_option('storage');
            if (empty($storage['type'])) {
                $storage['type'] = 'Local';
            }
            if ($storage['type'] != 'Local') {
                $watermark = cmf_get_plugin_config($storage['type']);
                $storage = new \cmf\lib\Storage($storage['type'], $storage['storages'][$storage['type']]);
                $apply_data = Db::table('tab_promote_apply')->field('promote_id,game_id,pack_url,sdk_version,and_url,ios_url')->where(['id' => $v['promote_apply_id']])->find();
                //无数据或未打包
                if ($v['type'] == 1) {
                    $str_ver = ".apk";
                    $file_name = "android";
                    $apply_data['pack_url'] = $apply_data['and_url'];
                } else {
                    $str_ver = ".ipa";
                    $file_name = "ios";
                    $apply_data['pack_url'] = $apply_data['ios_url'];
                }
                if (empty($apply_data['pack_url'])) {
                    continue;
                }
                $new_name = "game_package" . $apply_data['game_id'] . "-" . $apply_data['promote_id'] . $str_ver;
                $tourl = $file_name . "/" . $new_name;
                if (!file_exists(ROOT_PATH . "public/upload/" . $tourl)) {
                    $output->newLine();
                    $output->writeln("{$new_name}游戏原包本地不存在");
                    continue;
                }
                $result = $storage->upload($tourl, ROOT_PATH . "public/upload/" . $tourl, 'file');
                if (!empty($result)) {
                    Db::table('tab_promote_apply_upload')->where(['id' => $v['id']])->update(['status' => 1]);
                    if($v['type'] == 1){
                        $field = "and_upload";
                    }else{
                        $field = "ios_upload";
                    }
                    Db::table('tab_promote_apply')->where(['id' => $v['promote_apply_id']])->update([$field => 1]);
                    $unlink = 1;
                    //plist下载路径修改为云存储路径
                    if ($v['type'] == 2) {
                        $localplist = ROOT_PATH . "public/upload/promotegameplist/" . $apply_data['game_id'] . "-" . $apply_data['promote_id'] . '.plist';
                        $xmldoc = new \DOMDocument();
                        $xmldoc->load($localplist);
                        $assets = $xmldoc->getElementsByTagName('string');
                        if ($assets->length) {
                            $find_web_stie = cmf_get_option('admin_set')['web_site'];
                            $oldurl = $assets->item(1);
                            if($storage['type'] == 'Qcloud'){
                                if($watermark['domain']){
                                    $newurl = $watermark['schema'] . '://' . $watermark['domain'] . '/';
                                }else{
                                    $newurl = $watermark['schema'] . '://' . $watermark['bucket'] .'.cos.'. $watermark['region'].'.myqcloud.com' . '/';
                                }
                            }else{
                                $newurl = 'https://' . $watermark['bucket'] . '.' . $watermark['domain'] . '/' . $apply_data['pack_url'];
                            }
                            $oldurl->nodeValue = $newurl;
                            $replace = $xmldoc->save($localplist);
                        } else {
                            $unlink = 0;
                        }

                    }
                    if ($unlink == 1) {
                        @unlink(ROOT_PATH . "public/upload/" . $tourl);
                    }
                }
            }
        }
    }
    /**
     *修改h5申请信息
     */
    protected function updateh5info($id, $pack_url, $promote, $plist_url,$sdk_version=1)
    {
        $data['id'] = $id;
        if($sdk_version == 1){
            $data['and_url'] = $pack_url;
            $data['and_status'] = 1;
        }else{
            $data['ios_url'] = $pack_url;
            $data['ios_status'] = 1;
        }
        $data['plist_url'] = $plist_url;
        $res = Db::table('tab_promote_apply')->update($data);
        return $res;
    }
    /**
     *修改申请信息
     */
    protected function updateinfo($id, $pack_url, $promote, $plist_url)
    {
        $data['id'] = $id;
        $data['pack_url'] = $pack_url;
        // $data['dow_url']  = '/index.php?s=/Home/Down/down_file/game_id/'.$promote['game_id'].'/promote_id/'.$promote['promote_id'];
        $data['enable_status'] = 1;
        $data['plist_url'] = $plist_url;
        $res = Db::table('tab_promote_apply')->update($data);
        return $res;
    }

    /**
     *修改申请app信息
     */
    protected function updateappinfo($id, $pack_url, $plist_url)
    {
        $data['id'] = $id;
        $data['dow_url'] = $pack_url;
        $data['enable_status'] = 1;
        $data['plist_url'] = $plist_url;
        $res = Db::table('tab_promote_app')->update($data);
        return $res;
    }

    protected function game_source($game_id = '')
    {
        $map['game_id'] = $game_id;
        $data = Db::table('tab_game_source')->field('id,game_id,game_name,file_url')->where($map)->find();
        return $data;
    }



}
