<?php

namespace app\channelwap\controller;

use app\common\lib\constant\SupportConstant;
use app\common\logic\SupportLogic;
use app\common\model\SupportModel;

class SupportController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        if(PID_LEVEL!='1'){
            $this->redirect(url('index/index'));
        }
    }


    private function lSupport()
    {
        return new SupportLogic();
    }


    /**
     * @扶持申请
     *
     * @author: zsl
     * @since: 2020/9/14 14:09
     */
    public function apply()
    {
        //获取游戏列表
        $mSupport = new SupportModel();
        $where = [];
        $where['promote_id'] = PID;
        $game_lists = $mSupport -> field('game_id as id,game_name') -> where($where) -> group('game_id') -> order('create_time desc,id desc') -> select();
        $this -> assign('game_lists_record', $game_lists);
        //游戏筛选列表
        $game_lists = $this -> lSupport() -> applyGameLists(PID);
        $this -> assign('game_lists', $game_lists);
        return $this -> fetch();
    }

    /**
     * @函数或方法说明
     * @获取扶持信息
     * @author: 郭家屯
     * @since: 2020/11/5 14:35
     */
    public function get_apply()
    {
        $param = $this -> request -> param();
        if($param['start_time'] && $param['end_time']){
            $param['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])]];
        }elseif($param['start_time']){
            $param['create_time'] = ['egt',strtotime($param['start_time'])];
        }elseif($param['end_time']){
            $param['create_time'] = ['elt',strtotime($param['end_time'])];
        }
        $lists = $this -> lSupport() -> applyLists($param, PID);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($lists as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $lists[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
        }

        return json($lists['data']);
    }


    /**
     * @新增扶持
     *
     * @author: zsl
     * @since: 2020/9/14 15:16
     */
    public function add()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $result = $this -> lSupport() -> add($param);
            return json($result);
        }
        return $this -> fetch();
    }


    /**
     * @获取游戏首次扶持额度
     *
     * @author: zsl
     * @since: 2020/9/14 17:07
     */
    public function gameFirstSupportNum()
    {
        $game_id = $this -> request -> post('game_id');
        $model = new SupportModel();
        $result = $model -> gameFirstSupportNum($game_id);
        return json($result);
    }


    /**
     * @获取可用额度
     *
     * @author: zsl
     * @since: 2020/9/15 14:57
     */
    public function usableNum()
    {
        $pararm = $this -> request -> param();
        $result = $this -> lSupport() -> usableNum($pararm);
        return json($result);
    }


    /**
     * @后续扶持申请
     *
     * @author: zsl
     * @since: 2020/9/15 17:34
     */
    public function following()
    {
        $param = $this -> request -> post();
        $result = $this -> lSupport() -> following($param);
        return json($result);
    }


    /**
     * @获取游戏区服
     *
     * @author: zsl
     * @since: 2020/9/14 16:59
     */
    public function getserver()
    {
        $pararm = $this -> request -> param();
        $result = $this -> lSupport() -> getServer($pararm);
        return json($result);
    }

    /**
     * @修改用户名
     *
     * @author: zsl
     * @since: 2020/3/17 11:35
     */
    public function changeRoleName()
    {
        $param = $this -> request -> post();
        $result = $this -> lSupport() -> changeRoleName($param);
        return json($result);
    }


    /**
     * @扶持记录
     *
     * @author: zsl
     * @since: 2020/9/15 17:57
     */
    public function get_suprecord()
    {
        $param = $this -> request -> param();
        if($param['start_time'] && $param['end_time']){
            $param['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])]];
        }elseif($param['start_time']){
            $param['create_time'] = ['egt',strtotime($param['start_time'])];
        }elseif($param['end_time']){
            $param['create_time'] = ['elt',strtotime($param['end_time'])];
        }
        $lists = $this -> lSupport() -> recordLists($param, PID);

        return json($lists['data']);
    }


    /**
     * @扶持额度
     *
     * @author: zsl
     * @since: 2020/9/15 19:14
     */
    public function get_statistics()
    {
        $param = $this -> request -> param();
        $lists = $this -> lSupport() -> statistics($param);
        return json($lists['data']);
    }
    /**
     * @获取扶持规则
     *
     * @author: zsl
     * @since: 2021/9/8 21:30
     */
    public function getIntroduction()
    {
        $param = $this->request->param();
        $result = $this -> lSupport() -> getIntroduction($param);
        return json($result);
    }

}