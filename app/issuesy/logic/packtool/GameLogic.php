<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-13
 */

namespace app\issuesy\logic\packtool;

use app\issue\logic\PublicLogic;
use app\issue\model\GamePackRecordModel;
use app\issue\model\IssueGameApplyModel;
use app\issue\model\IssueGameModel;
use app\issue\model\OpenUserModel;
use app\issue\model\PlatformModel;
use app\issue\validate\PublicValidate;
use think\Request;

class GameLogic extends BaseLogic
{
    public function get_game_lists($data){
        $game = new IssueGameModel();
        $game->field('tab_issue_game.game_name,apply.game_id,icon');
        $game->where('open_user_id','=',$data['user_id']);
        $game->where('platform_id','=',$data['platform_id']);
        $game->where('tab_issue_game.sdk_version','=',$data['packet_os']);
        $game->where('tab_issue_game.status','=',1);
        $game->join(['tab_issue_game_apply'=>'apply'],'apply.game_id = tab_issue_game.id');
        $game->order('apply.create_time desc');
        $gameData = $game->select()->toarray();
        if (empty($gameData)) {
            return ['code'=>1009,'msg'=>'没有游戏数据'];
        }
        foreach ($gameData as $key=>&$value){
            $value['icon'] = cmf_get_image_preview_url($value['icon']);
        }
        unset($value);
        return ['code'=>200,'msg'=>'请求成功','data'=>['game_lists'=>$gameData]];
    }

    public function doPack($data)
    {
        $platformData = $this->get_platform_data($data['user_id'],$data['platform_id']);
        $platformData['packet_os'] = $data['packet_os'];
        $resources_url = $this->get_resources($platformData);
        if(!$resources_url){
            return ['code'=>1010,'msg'=>'没有该平台资源包'];
        }
        $applyData = $this->get_apply_data($data);
        if($applyData==false){
            return ['code'=>1011,'msg'=>'平台未申请游戏或已被禁用'];
        }
        $this->pack_record($data);
        $platform_config = json_decode($applyData['platform_config'],true);
        $service_config = json_decode($applyData['service_config'],true);
        $platform_config['game_channel_id'] = $applyData['id'];

        //参数名拼接xigu_前缀
        switch ($platformData['sdk_config_name']){
            case '360':
                $platform_config['QHOPENSDK_PRIVATEKEY'] = md5($service_config['appsecret'].'#'.$platform_config['QHOPENSDK_APPKEY']);
                $platform_config['game_channel_id'] = 'xigu_' . $platform_config['game_channel_id'];
                break;
            case 'huawei':
                $platform_config = array_map('htmlspecialchars_decode',array_map('htmlspecialchars_decode',$platform_config));
            default:
                foreach ($platform_config as $k => &$v) {
                    $v = 'xigu_'.$v;
                }
                break;
        }
        unset($applyData['service_config']);
        return [
                'code'=>200,
                'msg'=>'请求成功',
                'data'=>[
                        'resources_url'=>$resources_url,
                        'game_channel_id'=>$applyData['id'],
                        'ff_url'=>'http://'.request()->host(),
                        'config'=>$platform_config,
                        'resource_name'=>$platformData['sdk_config_name'],
                        'resource_version'=>$platformData['sdk_config_version'],
                ]
        ];
    }

    private function get_platform_data($open_user_id,$platform_id)
    {
        $mPlat = new PlatformModel();
        $mPlat->field('id,platform_config_sy,controller_name_sy,service_config,sdk_config_name,sdk_config_version');
        $mPlat->where('open_user_id','=',$open_user_id);
        $mPlat->where('id','=',$platform_id);
        $mPlatData = $mPlat->find();
        return $mPlatData;
    }

    private function get_resources($platformData)
    {
        if($platformData['packet_os']==2){
            $file_name = $platformData['sdk_config_name'].'_'.$platformData['sdk_config_version'].'_ios.zip';
        }else{
            $file_name = $platformData['sdk_config_name'].'_'.$platformData['sdk_config_version'].'.zip';
        }
        $file_url = dirname(dirname(dirname(__FILE__))).'/resources/'.$file_name;
        if(file_exists($file_url)){
            $copy_path = WEB_ROOT.'upload/issueresources';
            @mkdir($copy_path);
            $copy_file = $copy_path.'/'.$file_name;
            copy($file_url,$copy_file);
            return 'http://'.request()->host().'/upload/issueresources/'.$file_name;
        }
        return false;
    }

    private function get_apply_data($data)
    {
        $mApply = new IssueGameApplyModel();
        $mApply->field('id,platform_config,service_config');
        $mApply->where('game_id','=',$data['game_id']);
        $mApply->where('platform_id','=',$data['platform_id']);
        $mApply->where('status','=',1);
        $mApply->where('enable_status','=',1);
        $mApplyData = $mApply->find();
        if(empty($mApplyData)){
            return false;
        }
        return $mApplyData;
    }
    private function pack_record($request)
    {
        $mRecord = new GamePackRecordModel();
        $mRecord->game_id = $request['game_id'];
        $mRecord->platform_id = $request['platform_id'];
        $mRecord->open_user_id = $request['user_id'];
        $mRecord->pack_ip = request()->ip();
        $mRecord->save();
    }
}
