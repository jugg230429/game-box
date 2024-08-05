<?php
/**
 *
 */

namespace app\mobile\controller;

use app\common\controller\BaseHomeController;
use app\common\logic\TplayLogic;
use app\member\model\UserTplayModel;
use think\Db;


class TplayController extends BaseController
{
    /**
     * TplayController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        /*
         * 用户权限判断
         */
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
        $this->isLogin();
    }
    /**
     * @函数或方法说明
     * @首页
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/23 11:33
     */
    public function index()
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $model = new UserTplayModel();
        $map['tab_user_tplay.status'] = 1;
        $map['tab_user_tplay.start_time'] = ['lt',time()];
        $extend['field'] = 'tab_user_tplay.id,tab_user_tplay.end_time as endtime,tab_user_tplay.server_name,quota,time_out,g.id as game_id,g.sdk_version,g.game_name,g.icon,r.award,r.status,r.end_time';
        $extend['join1'] = [['tab_user_tplay_record'=>'r'],'tab_user_tplay.id = r.tplay_id and r.user_id='.UID,'left'];
        $extend['join2'] = [['tab_game'=>'g'],'tab_user_tplay.game_id=g.id','left'];
        $extend['order'] = "tab_user_tplay.id desc";
        $base  = new BaseHomeController();
        $list = $base->data_list_join_select($model,$map,$extend);
        foreach ($list as $key=>$v){
            //未完成的超时任务
            if(($v['end_time'] && $v['status'] == 0 && time() >= $v['end_time']) || $v['status'] == 2){
                unset($list[$key]);
                continue;
            }
            //未报名的超时任务
            if($v['endtime']>0 && $v['endtime'] < time() && empty($v['end_time'])){
                unset($list[$key]);
                continue;
            }
            //未报名的名额已满任务
            $count = Db::table('tab_user_tplay_record')->field('id')->where('tplay_id',$v['id'])->count();
            if($count >= $v['quota'] && empty($v['end_time'])){
                unset($list[$key]);
                continue;
            }
            $list[$key]['sign'] = $count;
            $list[$key]['icon'] = cmf_get_image_url($v['icon']);
            if($v['end_time'] && $v['status'] == 0){
                $list[$key]['remain_time'] = $this->remain_time($v['end_time'] - time());
            }
        }
        $this->assign('data',$list);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @任务详情页
     * @author: 郭家屯
     * @since: 2020/3/23 11:48
     */
    public function detail()
    {
        //任务详情
        $id = $this->request->param('id/d');
        $logic = new TplayLogic();
        $detail = $logic->getDetail($id);
        //游戏信息
        $game = get_game_entity($detail['game_id'],'relation_game_id,sdk_version');
        if(empty($detail) || $detail['status'] == 0){
            $this->error('任务不存在或已关闭');
        }
        $map['tplay_id'] = $detail['id'];
        $map['user_id'] = UID;
        //报名详情
        $record = $logic->getRecordDetail($map);
        if($record && $record['status'] == 0 && time() > $record['end_time']){
            $record['status'] = 2;
        }
        if($record && $record['status'] == 0){
            $record['remain_time'] = $this->remain_time($record['end_time'] - time());
        }
        if($game['sdk_version'] != 3){
            $detail['down_url'] = url('Downfile/down', ['game_id' => $game['relation_game_id'], 'sdk_version' => get_devices_type()]);
            $detail['down_status'] = get_down_status2($game['relation_game_id'], get_devices_type()) ? 1 : 0;
        }
        $detail['sdk_version'] = $game['sdk_version'];
        $detail['award'] = explode('/',$detail['award']);
        $detail['level'] = explode('/',$detail['level']);
        $detail['cash'] = explode('/',$detail['cash']);
        //报名人数判断
        $sign = Db::table('tab_user_tplay_record')->where('tplay_id',$id)->count();
        if($sign >= $detail['quota'] && $detail['quota'] > 0){
            $detail['is_quota'] = 1;
        }
        $this->assign('detail',$detail);
        $this->assign('record',$record);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @报名
     * @author: 郭家屯
     * @since: 2020/3/23 11:33
     */
    public function sign()
    {
        $id = $this->request->param('id/d');
        $logic = new TplayLogic();
        $result = $logic->sign($id,UID);
        if($result){
            $detail = $logic->getDetail($id);
            $this->success('距离任务结束还有'.$detail['time_out'].'小时，请尽快完成提交',url('index'));
        }else{
            switch ($result){
                case -1:
                    $this->error('报名名额已满');
                    break;
                case -2:
                    $this->error('报名已结束');
                    break;
                default:
                    $this->error('报名失败');
            }
        }
    }

    /**
     * @函数或方法说明
     * @完成任务
     * @author: 郭家屯
     * @since: 2020/3/23 13:54
     */
    public function complete()
    {
        $id = $this->request->param('id/d');
        $logic = new TplayLogic();
        $result = $logic->complete($id,UID);
        if($result > 0){
            $this->success('提交成功，稍后可前往消息中心查收',url('index'));
        }else{
            switch ($result){
                case -1:
                    $this->error('任务已超时，无法获得奖励');
                    break;
                case -2:
                    $this->error('抱歉，没有获取到小号信息');
                    break;
                case -3:
                    $this->error('抱歉，您还未完成任务');
                    break;
                case -4 :
                    $this->error('请实名认证后再发布');
                    break;
                default:
                    $this->error('提交失败');
            }
        }
    }

    /**
     * @函数或方法说明
     * @任务规则
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/23 15:54
     */
    public function rules()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取剩余时间
     * @param int $time
     *
     * @author: 郭家屯
     * @since: 2020/3/23 13:43
     */
    protected function remain_time($time=0)
    {
        $str = '';
        $hour = (int)($time/3600);
        if($hour > 0){
            $str .= $hour."小时";
        }
        $min_time = $time % 3600;
        $min =  (int)($min_time/60);
        if($min > 0){
            $str .= $min."分钟";
        }
        return $str;
    }
}