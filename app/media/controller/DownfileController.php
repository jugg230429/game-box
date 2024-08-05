<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 17:06
 */

namespace app\media\controller;

use app\common\controller\BaseHomeController;

class DownfileController extends BaseController
{
    /**
     * [下载方法]
     * @author 郭家屯[gjt]
     */
    public function down()
    {
        $data = $this->request->param();
        //封禁判断-20210712-byh
        $game_id = \think\Db::table('tab_game')->where(['relation_game_id'=>$data['game_id'],'sdk_version'=>$data['sdk_version']])->value('id');
        if(!judge_user_ban_status($data['promote_id'],$game_id,session('member_auth.user_id'),'',get_client_ip(),$type=4)){
            $this->error('您当前被禁止下载游戏，请联系客服');
        }
        $base = new BaseHomeController();
        $base->down_file($data['game_id'], $data['sdk_version'], session('member_auth.user_id'));
    }
    /**
     * 判断游戏是否封禁状态-ajax请求-下载
     * by:byh-2021-7-14 10:35:00
     */
    public function get_ban_status()
    {
        $data = $this->request->param();
        //判断类型
        if($data['type'] != 4) return json(['code'=>0,'msg'=>'类型错误']);
        //判断游戏
        if(empty($data['game_id'])) return json(['code'=>0,'msg'=>'数据错误']);
        //判断版本对应的游戏id
        if (empty(($data['sdk_version']))) {
            $data['sdk_version'] = 1;
        }
        if(!in_array($data['sdk_version'],[1,2,3])) return json(['code'=>0,'msg'=>'版本类型错误']);
        $game_id = \think\Db::table('tab_game')->where(['relation_game_id'=>$data['game_id'],'sdk_version'=>$data['sdk_version']])->value('id');
        $res = judge_user_ban_status($data['promote_id'],$game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$data['type']);
        if(!$res){
            return json(['code'=>0,'msg'=>'您当前被禁止下载游戏，请联系客服']);
        }
        return json(['code'=>1,'msg'=>'success']);

    }

    /**
     * @函数或方法说明
     * @微端下载
     * @author: 郭家屯
     * @since: 2020/6/24 14:10
     */
    public function weiduan_down()
    {
        $data = $this->request->param();
        //封禁判断-20210712-byh
        if(!judge_user_ban_status($data['promote_id'],$data['game_id'],session('member_auth.user_id'),'',get_client_ip(),$type=4)){
            $this->error('您当前被禁止下载游戏，请联系客服');
        }
        if(empty($data['sdk_version'])){
            $data['sdk_version'] = 1;
        }
        $base = new BaseHomeController();
        $base->down_weiduan_file($data['game_id'], $data['sdk_version'], session('member_auth.user_id'), $data['promote_id']);
    }
}
