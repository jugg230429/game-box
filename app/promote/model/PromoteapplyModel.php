<?php

namespace app\promote\model;

use think\Db;
use think\Model;

/**
 * gjt
 */
class PromoteapplyModel extends Model
{

    protected $table = 'tab_promote_apply';

    protected $autoWriteTimestamp = true;

    /**
     * [setRatioMoney 设置分成比例]
     * @param [type] $data [description]
     * @author [yyh] <[<email address>]>
     */
    public function setRatioMoney($data, $map = [])
    {
        $map['game_id'] = $data["game_id"];
        $map['promote_id'] = $data["promote_id"];
        $result = $this->where($map)->update([$data['field'] => $data['value']]);
        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function apply($add = [])
    {
        $data['game_id'] = $add['game_id'];
        $data['promote_id'] = $add['promote_id'];
        $data['sdk_version'] = $add['sdk_version'];
        $data['promote_money'] = $add['promote_money'];
        $data['promote_ratio'] = $add['promote_ratio'];
        $data['apply_time'] = time();
        $promote_auto_audit = \think\Db::table('tab_promote_config')->field('status')->where(['name' => 'promote_auto_audit_apply'])->find();
        if($promote_auto_audit['status']==1){
            // 默认设置不支持自动审核
            $data['status'] = 1;
            $data['dispose_time'] = time();
            if($add['sdk_version'] == 3){
                // 如果是H5游戏-可使用自动审核
                $and_source = Db::table('tab_game_source')->where(['game_id'=>$add['game_id']])->where('file_type',1)->find();
                if($and_source){
                    $data['and_status'] = 2;
                }
                $ios_source = Db::table('tab_game_source')->where(['game_id'=>$add['game_id']])->where('file_type',2)->find();
                if($ios_source){
                    $data['ios_status'] = 2;
                }
            }elseif($add['sdk_version'] < 3){
                //超级签自动审核
                if($add['down_port'] == 3){
                    $data['pack_type'] = 4;
                    $data['enable_status'] = 1;
                    $data['pack_time'] = time();
                    $game = get_game_entity($add['game_id'],'platform_id');
                    if($game['platform_id'] > 0){
                        if(empty($add['ios_game_address'])){
                            return false;
                        }
                        $third_url = urldecode($add['ios_game_address']);
                        $third_params = explode('?appenddata=',$third_url);
                        $params = json_decode($third_params[1],true);
                        if(empty($params)){
                            return false;
                        }
                        $params['MCHPromoteID'] = $params['MCHPromoteID'] . '_XgPid_' . $add['promote_id'];
                        $url = $third_params[0] . '?appenddata=' . urlencode(json_encode($params));
                    }else {
                        $info['MCHPromoteID'] = (string)$add['promote_id'];
                        $info['XiguSuperSignVersion'] = (string)super_sign_version($add['game_id']);
                        $url = $add['ios_game_address'] . '?appenddata=' . urlencode(json_encode($info));
                    }
                    $data['pack_url'] = $data['dow_url'] = $url;
                }else{
                    $allow = 1;//自动打包 免分包方式
                    $source_file = Db::table('tab_game_source')->where(['game_id'=>$add['game_id']])->find();
                    if (empty($source_file) || !file_exists(ROOT_PATH . 'public/upload/' . $source_file['file_url'])) {//验证原包是否存在
                        $allow = 0;
                    }
                    if($allow){
                        $data['pack_type'] = 1;
                        $data['enable_status'] = 2;
                        $data['pack_time'] = time();
                    }
                }

            }
        }else{
            $data['status'] = 0;
        }
        $res = $this->insert($data);
        if ($res != false) {
            return $res;
        } else {
            return false;
        }
    }
    public function delete($ids='')
    {
        if(empty($ids)){
            return false;
        }
        $data = $this->where(['id'=>['in',$ids]])->delete();
        if($data){
            return true;
        }else{
            return false;
        }
    }
}