<?php

namespace app\thirdgame\model;

use think\Model;
use cmf\lib\Storage;

class GameSourceModel extends Model
{
    protected $table = 'tab_game_source';

    public function importSource($game_id=0,$param=[])
    {
         set_time_limit(0);
         if(empty($param['file_url'])){
             return false;
         }
         // 更新超级签游戏下载地址
         if($param['down_port'] == 3){
             $GameData['ios_game_address'] = $param['file_url'];
             $GameData['game_size'] = $param['file_size']?:'';
             $GameData['down_port'] = 3;
             $GameModel = new GameModel();
             $res = $GameModel->where('id',$game_id)->update($GameData);
             if($res !== false){
                 //更新游戏关联表
                 $source_info['promote_id'] = $param['promote_id'];
                 $source_info['promote_account'] = $param['promote_account'];
                 $GameAttrData['third_source_info'] = json_encode($source_info);
                 $GameAttrModel = new GameAttrModel();
                 $GameAttrModel->where('game_id',$game_id)->update($GameAttrData);
                 // 更新苹果付费下载
                 $PayDownloadModel = new GameIosPayToDownloadModel();
                 $payDownData['pay_download'] = $param['pay_download'];
                 $payDownData['pay_price'] = $param['pay_price'];
                 $payDownData['update_time'] = time();
                 if($PayDownloadModel->where('game_id',$game_id)->count()){
                     $PayDownloadModel->where('game_id',$game_id)->update($payDownData);
                 }else{
                     $payDownData['create_time'] = time();
                     $PayDownloadModel->where('game_id',$game_id)->insert($payDownData);
                 }
                 return true;
             }else{
                 return false;
             }
         }elseif($param['down_port'] == 1){
             // 更新游戏原包
             $game_info = get_game_entity($game_id,'sdk_version,game_name');
             $SourceData = [];
             $SourceData['bao_name'] = $param['bao_name']?:'';//包名
             $SourceData['file_size'] = $param['file_size']?:'';//包的大小
             $SourceData['source_version'] = $param['source_version']?:'';//版本号
             $SourceData['source_name'] = $param['source_name']?:'';//版本名称
             $SourceData['create_time'] = time();
             $SourceData['remark'] = $param['remark']?:'';//备注
             $SourceData['op_id'] = cmf_get_current_admin_id();
             $SourceData['op_account'] = cmf_get_current_admin_name();
             //渠道打包信息
             $packInfo = [];
             $packInfo['game_id'] = $param['game_id'];
             $packInfo['game_name'] = $param['game_name'];
             $packInfo['game_appid'] = $param['game_appid'];
             $packInfo['promote_id'] = $param['promote_id'];
             $packInfo['promote_account'] = $param['promote_account'];
             $packInfo['source_version'] = $param['source_version'];
             $SourceData['third_source_info'] = json_encode($packInfo);
             if($game_info['sdk_version'] == 1){
                 $file_suffix = 'ipk';
                 $file_name = "./public/upload/android/game_package" . $game_id . ".apk";
                 $SourceData['file_name'] = "game_package" . $game_id . ".apk";
                 $SourceData['file_url'] = "android/game_package" . $game_id . ".apk";
             }else if($game_info['sdk_version'] == 2){
                 $file_suffix = 'ipa';
                 $file_name = "./public/upload/ios/game_package" . $game_id . ".ipa";
                 $SourceData['file_name'] = "game_package" . $game_id . ".ipa";
                 $SourceData['plist_url'] = "sourceplist/" . $game_id . ".plist";
                 $SourceData['file_url'] = "ios/game_package" . $game_id . ".ipa";
                 $this->create_plist($game_id, 0, $SourceData['bao_name'], $SourceData['file_url']);
             }else{
                 return false;
             }
             $source_file = file_get_contents($param['file_url']);
             $res = file_put_contents(ROOT_PATH . $file_name,$source_file);
             if($res === false){
                 return false;
             }
             // 上传第三方
             $tmp_flag=1;
             $upload_res = $this->uploadcloud($SourceData['file_url'], './upload/' . $SourceData['file_url'], $game_info['sdk_version'], $file_suffix,$tmp_flag);
             // 如果上传失败 防止页面卡死在哪里
             $upload_code = $upload_res['code'] ?? 0;
             if($upload_code == -1){
                 // 上传失败
                 return false;
             }
             $game_source = $this->where('game_id',$game_id)->find();
             if(empty($game_source)){
                 // 新增原包
                 $SourceData['game_id'] = $game_id;
                 $SourceData['game_name'] = $game_info['game_name'];
                 $SourceData['sdk_version'] = 0;
                 $SourceData['file_type'] = $game_info['sdk_version'];
                 $result = $this->insertGetId($SourceData);
             }else{
                 // 修改原包
                 $result = $this->where('game_id',$game_id)->update($SourceData);
             }
             //游戏修改为原包下载
             $GameModel = new GameModel();
             $gameSave['down_port'] = 1;
             $gameSave['game_size'] = $param['game_size'];
             if($game_info['sdk_version'] == 1){
                 $gameSave['and_dow_address'] = $SourceData['file_url'];
             }else{
                 $gameSave['ios_dow_address'] = $SourceData['file_url'];
                 $gameSave['ios_dow_plist'] = $SourceData['plist_url'];
             }
             $GameModel->where('id',$game_id)->update($gameSave);
             if($result > 0){
                 return true;
             }else{
                 return false;
             }
         }else{
             return false;
         }
    }

    //生成游戏渠道plist文件
    public function create_plist($game_id = 0, $promote_id = 0, $marking = "", $url = "")
    {
        $url = cmf_get_file_download_url($url);
        //强制https
        if (strpos($url, 'https://') === false) {
            $url = str_replace("http://", "https://", $url);
        }
        $game = get_game_entity($game_id);
        $xml = new \DOMDocument();
        $xml->load('./upload/plist/testdemo.plist');
        $online = $xml->getElementsByTagName('dict');//查找节点
        $asd = $online->item(1)->getElementsByTagName('string');//第二个节点下所有string
        foreach ($asd as $key => $value) {
            switch ($value->textContent) {
                case 'ipa_url':
                    $value->nodeValue = $url;
                    break;
                case 'icon':
                    $value->nodeValue = cmf_get_image_url($game['icon']);
                    break;
                case 'com.dell':
                    $value->nodeValue = $marking;
                    break;
                case '1.0.0':
                    $value->nodeValue = $game['sdk_version'];
                    break;
                case 'mchdemo':
                    $value->nodeValue = $game['relation_game_name'];
                    break;

            }
            if ($promote_id == 0) {
                $xml->save("./upload/sourceplist/$game_id.plist");
            } else {
                $pname = $game_id . "-" . $promote_id;
                $xml->save("./upload/GamePlist/$pname.plist");
            }
        }
        if ($promote_id == 0) {
            return "./public/upload/sourceplist/$game_id.plist";
        } else {
            return "./public/upload/GamePlist/$pname.plist";
        }
    }

    /**
     * [非本地上传]
     * @author 郭家屯[gjt]
     */
    protected function uploadcloud($file_url = '', $upload_url = '', $filetype = '', $suffix = '',$tmp_flag=0)
    {
        $storage = cmf_get_option('storage');
        if ($storage['type'] != 'Local') { //  增加存储驱动
            $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
            session_write_close();
            $upload_res = $storage->upload($file_url, './upload/' . $file_url, $filetype,'',$tmp_flag);
            return $upload_res;
        }
    }
}