<?php

namespace app\issueyy\controller;

use app\issue\model\IssueGameModel;
use app\issueyy\logic\GameLogic;
use app\issueyy\validate\GameValidate;
use cmf\controller\AdminBaseController;
use think\Db;

class AdminGameController extends AdminBaseController
{


    /**
     * @页游分发游戏后台列表
     *
     * @author: zsl
     * @since: 2020/7/10 19:30
     */
    public function lists()
    {

        $gameLogic = new GameLogic();
        $getData = request() -> get();
        $data = $gameLogic -> page($getData);
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('page', $page);
        $this -> assign("data_lists", $data);
        return $this -> fetch('issueyy@game/lists');
    }

    /**
     * @页游分发游戏修改状态
     *
     * @author: zsl
     * @since: 2020/7/10 19:34
     */
    public function xt_lists()
    {
        $gameLogic = new GameLogic();
        $getData = request() -> get();
        $data = $gameLogic -> xt_page($getData);
        $data = $data -> toarray()['data'];
        foreach ($data as $key => &$value) {
            unset($value['game_key']);
            unset($value['access_key']);
            unset($value['agent_id']);
            $value['initial'] = substr($value['short'], 0,1);
        }
        return json(['code' => 200, 'msg' => '获取成功', 'data' => $data]);
    }

    /**
     * @添加页游分发游戏
     *
     * @author: zsl
     * @since: 2020/7/10 19:54
     */
    public function add()
    {
        $gameLogic = new GameLogic();
        $postData = request() -> post();
        return $gameLogic -> add($postData);
    }

    /**
     * @编辑页游分发游戏
     *
     * @author: zsl
     * @since: 2020/7/10 20:13
     */
    public function edit()
    {
        $gameLogic = new GameLogic();
        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {

            //处理游戏类型-20210820-byh-s
            if(!empty($param['game_type_id'])){
                if(count($param['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $param['game_type_id'] = implode(',',$param['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            //处理游戏类型-20210820-byh-e

            //验证参数
            $vali = new GameValidate();
            if (!$vali -> scene('edit') -> check($param)) {
                $this -> error($vali -> getError());
            }
            //更新游戏信息
            $lGame = new GameLogic();
            $result = $lGame -> edit($param);
            if (false === $result) {
                $this -> error('保存失败');
            }
            $this -> success('保存成功');
        }
        //查询游戏详情
        $data = $gameLogic -> info($param['id']);
        //处理游戏类型-20210820-byh
        if(!empty($data)){
            $data = $data->toArray();
            $data['game_type_id'] = explode(',',$data['game_type_id']);
        }
        $this -> assign('data', $data);
        return $this -> fetch('issueyy@game/edit');
    }


    /**
     * @页游分发游戏修改状态
     *
     * @author: zsl
     * @since: 2020/7/10 19:30
     */
    public function changestatus()
    {
        $data = $this -> request -> param();
        $where = [];
        $where['id'] = $data['ids'];
        if (isset($data['status'])) {
            $result = Db ::table('tab_issue_game') -> where($where) -> setField('status', $data['status']);
        }
        if (false === $result) {
            $this -> error('操作失败');
        }
        $this -> success('操作成功');
    }

    /**
     * @删除分发游戏
     *
     * @author: zsl
     * @since: 2020/7/10 20:10
     */
    public function delete()
    {
        $data = $this -> request -> param();
        $where = [];
        $where['id'] = $data['ids'];
        $result = Db ::table('tab_issue_game') -> where($where) -> delete();
        if (false === $result) {
            $this -> error('删除失败');
        }
        $this -> success('删除成功');
    }


    /**
     * @设置字段属性
     *
     * @author: zsl
     * @since: 2020/7/13 14:25
     */
    public function changeField()
    {
        $param = $this -> request -> post();
        if ($param['field'] != 'ff_ratio' && $param['field'] != 'cp_ratio' && $param['field'] != 'sort') {
            $this -> error('发生错误');
        }
        $mIssueGame = new IssueGameModel();
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


}