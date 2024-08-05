<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 17:06
 */

namespace app\app\controller;

use app\common\controller\BaseHomeController;
use app\game\model\GameModel;
use app\promote\model\PromoteapplyModel;
use think\Db;

class DownfileController extends BaseController
{
    /**
     * [下载方法]
     * @author 郭家屯[gjt]
     */
    public function down_record()
    {
        $data = $this->request->param();
        if(!$data['game_id']||!$data['sdk_version']||!USER_ID){
            $this->set_message(1053,'请求数据不完整');
        }
        $game = get_game_entity($data['game_id'],'relation_game_id,sdk_version');
        $relation_game_id = $game['relation_game_id'];
        if($game['sdk_version'] == 3){
            $status = get_weiduan_down_status($relation_game_id,$data['sdk_version'],$data['promote_id']);
        }else{
            $status = get_down_status($relation_game_id,$data['sdk_version']);
        }
        if(!$status){
            $this->set_message(1054,'无法下载');
        }
        (new BaseHomeController())->add_down_stat($data['game_id'],USER_ID);
        $this->set_message(200,'允许下载');
    }

    /**
     * @函数或方法说明
     * @下载
     * @param int $game_id
     * @param int $promote_id
     * @param int $user_id
     *
     * @author: 郭家屯
     * @since: 2020/7/1 16:06
     */
    public function down($game_id=0,$sdk_version=1,$promote_id=0,$user_id=0)
    {
        //封禁判断-20210712-byh
        if(!judge_user_ban_status($promote_id,$game_id,$user_id,$this->request->param('equipment_num',''),get_client_ip(),$type=4)){
            $this->set_message(1005, '您当前被禁止下载游戏，请联系客服');
        }
        if(empty($game_id)){
            $this->set_message(200,'获取成功',['url'=>'']);
        }
        $game = get_game_entity($game_id);
        if (empty($game)){
            $this->set_message(200,'获取成功',['url'=>'']);
        }
        if($game['sdk_version'] == 3){
            $down_url = $this->get_weiduan_down_url($game_id,$sdk_version,$promote_id);
        }else{
            $down_url = $this->get_down_url($game_id,$sdk_version,$promote_id,$user_id);
        }
        $this->set_message(200,'获取成功',['url'=>$down_url]);
    }

    /**
     * @函数或方法说明
     * @手游下载地址
     * @author: 郭家屯
     * @since: 2020/7/1 16:13
     */
    protected function get_down_url($game_id=0,$sdk_version=1,$promote_id=0,$user_id=0)
    {
        if($promote_id > 0){
            $url = $this->promote_down_file($game_id, $promote_id, $sdk_version, $user_id);
        }else{
            $url = $this->official_down_file($game_id,$sdk_version,$user_id);
        }
        return $url;
    }
    /**
     *官方游戏下载
     * @param int $game_id [游戏关联id]
     * @param int $sdk_version [sdk 类型] 1 安卓 2 苹果
     * @param int $system_type [下载系统类型] 0 window 1 苹果手机或ipa
     */
    public function official_down_file($game_id = 0, $sdk_version = 1, $user_id = 0)
    {
        $game = get_game_entity($game_id);
        if($game['sdk_version'] == 1){
            if($game['down_port'] == 1){
                $url = cmf_get_file_download_url($game['and_dow_address']);
            }else{
                $url = cmf_get_file_download_url($game['add_game_address']);
            }
        }elseif($game['sdk_version'] == 2){
            if($game['down_port'] == 1){
                $url = "https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $game['ios_dow_plist'];
            }elseif($game['down_port']==3){
                $info = [];
                $info['MCHPromoteID'] = (string)'0';
                $info['XiguSuperSignVersion'] = (string)super_sign_version($game['game_id']);
                $game['ios_game_address'] = $game['ios_game_address'] . '?appenddata=' . urlencode(json_encode($info));
                $url = cmf_get_file_download_url($game['ios_game_address']);
            }else{
                $url = cmf_get_file_download_url($game['ios_game_address']);
            }
        }
        if(!empty($url)){
            $model = new GameModel();
            $model->where('id', $game_id)->setInc('dow_num');
            $this->add_down_stat($game_id, $user_id);
        }
        return $url;
    }

    /**
     *推广游戏下载
     * @param int $game_id [游戏关联id]
     * @param int $promote_id [渠道id]
     * @param int $system_type [系统环境] 1 win  2 iphone 或 iPad
     * @param int $sdk_version [sdk 类型] 1 安卓 2 苹果
     */
    public function promote_down_file($game_id = 0, $promote_id = 0, $sdk_version = 0, $user_id = 0)
    {
        //不可推广游戏
        $promote = get_promote_entity($promote_id);
        if ($promote['game_ids']) {
            $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
        }
        $applyModel = new PromoteapplyModel();
        $map['status'] = 1;
        $map['enable_status'] = 1;
        $map['game_status'] = 1;
        $map['pack_type'] = ['neq', 2];
        $map['promote_id'] = $promote_id;
        $data = $applyModel
                ->alias('a')
                ->join(['tab_game' => 'g'], "g.id=a.game_id and g.id = $game_id and a.sdk_version=$sdk_version", 'left')
                ->field('game_id,g.game_name,promote_id,relation_game_id,pack_url,plist_url,status,enable_status,a.sdk_version,is_upload,pack_type')
                ->where($map)
                ->find();
        if (empty($data)) {
            return $this->official_down_file($game_id, $sdk_version, $user_id);
        } else {
            $model = new GameModel();
            $model->where('id', $data['game_id'])->setInc('dow_num');
            $this->add_down_stat($data['game_id'], $user_id);
            $pack_url = $data['pack_url'];
            if ($pack_url) {
                if ($sdk_version == 1) {
                    $url =  promote_game_get_file_download_url($pack_url, $data['is_upload']);
                } else {
                    if($data['pack_type'] == 4){
                        $url =  promote_game_get_file_download_url($pack_url, $data['is_upload']);
                    }else{
                        $url =   "https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $data['plist_url'];
                    }
                }
            } else {
                $url =   '';
            }
        }
        return $url;
    }

    /**
     * @函数或方法说明
     * @获取微端下载地址
     * @param $game_id
     * @param $sdk_version
     * @param int $promote_id
     *
     * @author: 郭家屯
     * @since: 2020/7/1 10:19
     */
    protected function get_weiduan_down_url($game_id=0,$sdk_version=1,$promote_id=0)
    {
        //官方地址
        if(empty($promote_id)){
            $model = new GameModel();
            $map['relation_game_id'] = $game_id;
            $data = $model
                    ->field('sdk_version,game_name,id as game_id,add_game_address,ios_game_address,and_dow_address,ios_dow_address,ios_dow_plist')
                    ->where($map)
                    ->find();
            if (empty($data)) {
                return '';
            }
            $data = $data->toArray();
            switch ($sdk_version) {
                case 1:
                    if ($data['and_dow_address'] && varify_url(cmf_get_file_download_url($data['and_dow_address']))) {
                        return cmf_get_file_download_url($data['and_dow_address']);
                    }else if ($data['add_game_address']) {
                        return cmf_get_file_download_url($data['add_game_address']);
                    } else {
                        return '';
                    }
                    break;
                case 2:
                    if ($data['ios_dow_address'] && varify_url(cmf_get_file_download_url($data['ios_dow_address']))) {
                        return "https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $data['ios_dow_plist'];
                    }elseif ($data['ios_game_address']) {
                        return cmf_get_file_download_url($data['ios_game_address']);
                    } else {
                        return '';
                    }
                    break;
                default:
                    return '';
            }
        }else {//渠道地址
            //不可推广游戏
            $promote = get_promote_entity($promote_id);
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
            $applyModel = new PromoteapplyModel();
            $map['status'] = 1;
            if($sdk_version == 1){
                $map['and_status'] = 1;
            }else{
                $map['ios_status'] = 1;
            }
            $map['promote_id'] = $promote_id;
            $map['game_id'] = $game_id;
            $data = $applyModel
                    ->alias('a')
                    ->join(['tab_game' => 'g'], "g.id=a.game_id and relation_game_id = $game_id and a.sdk_version=$sdk_version", 'left')
                    ->field('game_id,g.game_name,promote_id,relation_game_id,plist_url,status,enable_status,a.sdk_version,is_upload,and_url,ios_url,ios_status,and_status,and_upload,ios_upload')
                    ->where($map)
                    ->find();
            if (empty($data)) {
                return '';
            }
            if($sdk_version == 1){
                $pack_url = $data['and_url'];
            }else{
                $pack_url = $data['ios_url'];
            }
            if ($pack_url) {
                if ($sdk_version == 1) {
                    return promote_game_get_file_download_url($pack_url, $data['and_upload']);
                } else {
                    return "https://" . $_SERVER['HTTP_HOST'] . '/upload/' . $data['plist_url'];
                }
            } else {
                return '';
            }
        }
    }
    /**
     *游戏下载统计
     */
    public function add_down_stat($game_id = 0, $user_id = 0)
    {
        if ($user_id) {
            $result = Db::table('tab_game_down_record')
                    ->field('id')
                    ->where('game_id', $game_id)
                    ->where('user_id', $user_id)
                    ->find();
            if (empty($result)) {
                $save['game_id'] = $game_id;
                $save['user_id'] = $user_id;
                $save['sdk_version'] = get_game_entity($game_id,'sdk_version')['sdk_version'];
                $save['create_time'] = time();
                Db::table('tab_game_down_record')->insert($save);
            } else {
                $save['create_time'] = time();
                Db::table('tab_game_down_record')->where('id', $result['id'])->update($save);
            }
        }
    }
}
