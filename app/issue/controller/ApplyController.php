<?php

namespace app\issue\controller;

use app\issue\logic\ApplyLogic;
use app\issue\logic\GameLogic;
use app\issue\model\IssueGameApplyModel;
use app\issue\model\OpenUserModel;
use cmf\controller\AdminBaseController;

class ApplyController extends AdminBaseController
{


    /**
     * @联运申请列表
     *
     * @author: zsl
     * @since: 2020/7/14 19:50
     */
    public function lists()
    {

        $lApply = new ApplyLogic();
        $getData = request() -> get();
        $data = $lApply -> page($getData);
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('page', $page);
        $this -> assign("data_lists", $data);
        return $this -> fetch();
    }


    /**
     * @审核申请
     *
     * @author: zsl
     * @since: 2020/7/15 9:58
     */
    public function audit()
    {
        $lApply = new ApplyLogic();
        $ids = $this -> request -> param('ids/a');
        $result = $lApply -> audit($ids);
        if ($result['code'] == '0') {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);

    }


    /**
     * @删除申请
     *
     * @author: zsl
     * @since: 2020/7/15 10:14
     */
    public function delete()
    {

        $lApply = new ApplyLogic();
        $ids = $this -> request -> param('ids/a');
        $result = $lApply -> delete($ids);
        if (false === $result) {
            $this -> error('删除失败');
        } else {
            $this -> success('删除成功');
        }
    }

    /**
     * @修改字段值
     *
     * @author: zsl
     * @since: 2020/7/15 10:47
     */
    public function changeField()
    {
        $param = $this -> request -> param();
        if ($param['field'] != 'ratio' && $param['field'] != 'enable_status') {
            $this -> error('发生错误');
        }
        $mIssueGame = new IssueGameApplyModel();
        $where = [];
        $where['id'] = $param['id'];
        $data = [];
        $data[$param['field']] = $param['value'];
        $result = $mIssueGame -> allowField(false) -> save($data, $where);
        if (false === $result) {
            $this -> error('修改失败');
        }
        $this -> success('修改成功');
    }


    /**
     * @获取申请游戏配置
     *
     * @author: zsl
     * @since: 2020/7/23 17:26
     */
    public function gameConfig()
    {
        $param = $this -> request -> param();
        $lGameApply = new ApplyLogic();
        $configInfo = $lGameApply -> getConfig($param);
        if($param['type']=='1'){
            $configInfo['platform_config'] = json_decode($configInfo['platform_config'], true);
        }else{
            $configInfo['platform_config'] = json_decode($configInfo['service_config'], true);
        }

        $this -> assign('configInfo', $configInfo->toArray());

        //获取游戏地址
        $lGame = new GameLogic();
        $param['apply_id'] = $param['id'];
        $param['platform_id'] = $configInfo['platform_id'];
        $param['open_user_id'] = $configInfo['open_user_id'];
        $result = $lGame -> getGameUrl($param);
        $this->assign('game_url',$result['data']);
        $openusermodel = new OpenUserModel();
        $openusermodel->where('id',$configInfo['open_user_id']);
        $openusermodel->field('settle_type');
        $openuser = $openusermodel->find();
        $this->assign('settle_type',$openuser['settle_type']);
        return $this -> fetch();
    }


}