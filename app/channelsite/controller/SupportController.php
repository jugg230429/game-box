<?php

namespace app\channelsite\controller;

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

        $param = $this -> request -> get();
        $rangepickdate = $this -> request -> param('rangepickdate');
        if ($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $param['create_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        } else {
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        $lists = $this -> lSupport() -> applyLists($param, PID);
        $this -> assign('data_lists', $lists['data']);
        //游戏筛选列表
        $game_lists = $this -> lSupport() -> applyGameLists(PID);
        $this -> assign('game_lists', $game_lists);
        return $this -> fetch();
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
    public function suprecord()
    {
        $param = $this -> request -> get();
        $rangepickdate = $this -> request -> param('rangepickdate');
        if ($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $param['create_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        } else {
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        $lists = $this -> lSupport() -> recordLists($param, PID);
        $this -> assign('data_lists', $lists['data']);
        //获取游戏列表
        $mSupport = new SupportModel();
        $where = [];
        $where['promote_id'] = PID;
        // 隐藏的游戏不显示
        $hidden_game_ids = get_hidden_game_ids();
        $where['game_id'] = ['notin', $hidden_game_ids];

        $game_lists = $mSupport -> field('game_id as id,game_name') -> where($where) -> group('game_id') -> order('create_time desc,id desc') -> select();
        $this -> assign('game_lists', $game_lists);
        return $this -> fetch();
    }


    /**
     * @扶持额度
     *
     * @author: zsl
     * @since: 2020/9/15 19:14
     */
    public function statistics()
    {
        $param = $this -> request -> get();
        $lists = $this -> lSupport() -> statistics($param);
        $this -> assign('data_lists', $lists['data']);
        //获取游戏列表
        $mSupport = new SupportModel();
        $where = [];
        $where['promote_id'] = PID;

        // 隐藏的游戏不显示
        $hidden_game_ids = get_hidden_game_ids();
        $where['game_id'] = ['notin', $hidden_game_ids];

        $game_lists = $mSupport -> field('game_id as id,game_name') -> where($where) -> group('game_id') -> order('create_time desc,id desc') -> select();
        $this -> assign('game_lists', $game_lists);
        return $this -> fetch();
    }


    /**
     * @获取扶持介绍
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
