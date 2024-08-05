<?php

namespace app\issue\controller;

use app\issue\logic\ChannelLogic;
use cmf\controller\AdminBaseController;

class ChannelController extends AdminBaseController
{


    /**
     * @渠道大全列表
     *
     * @author: zsl
     * @since: 2021/8/12 10:39
     */
    public function lists()
    {
        $lChannel = new ChannelLogic();
        $param = $this -> request -> param();
        $lChannel -> lists($param);
        $this -> assign('lists', $lChannel -> getData());
        return $this -> fetch();
    }


    /**
     * @申请接入
     *
     * @author: zsl
     * @since: 2021/8/24 21:26
     */
    public function apply()
    {
        $param = $this -> request -> param();
        $lChannel = new ChannelLogic();
        if (false === $lChannel -> apply($param)) {
            $this -> error($lChannel -> getErrorMsg());
        }
        $this -> success('接入成功');
    }


    /**
     * @删除渠道
     *
     * @author: zsl
     * @since: 2021/8/30 21:50
     */
    public function delete()
    {
        $param = $this -> request -> param();
        $lChannel = new ChannelLogic();
        if (false === $lChannel -> delete($param)) {
            $this -> error($lChannel -> getErrorMsg());
        }
        $this -> success('删除成功');
    }

}
