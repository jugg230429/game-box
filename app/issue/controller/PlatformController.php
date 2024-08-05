<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-09
 */

namespace app\issue\controller;

use app\issue\logic\PlatformLogic;
use app\issue\model\IssueGameApplyModel;
use app\issue\model\IssueGameModel;
use app\issue\model\PlatformModel;
use app\issue\validate\PlatformValidate;
use cmf\controller\AdminBaseController;
use think\Db;

class PlatformController extends AdminBaseController
{


    public function lists(PlatformLogic $lPlatform)
    {

        $getData = request() -> get();
        $data = $lPlatform -> page($getData);
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('page', $page);
        $this -> assign("data_lists", $data);
        return $this -> fetch();
    }


    public function add(PlatformLogic $lPlatform)
    {
        if ($this -> request -> isPost()) {

            $param = $this -> request -> param();
            $validate = new PlatformValidate();
            $result = $validate -> scene('create') -> check($param);
            if ($result == false) {
                $this -> error($validate -> getError());
            }
            $lPlatform -> add($param);
            $this -> success('添加成功', url('lists'));
        }
        return $this -> fetch();
    }


    public function edit(PlatformLogic $lPlatform)
    {

        $param = $this -> request -> param();
        if (request() -> isPost()) {
            $result = $lPlatform -> edit($param);
            if (false === $result) {
                $this -> error('保存失败');
            }
            $this -> success('保存成功',url('issue/platform/lists'));
        }
        $info = $lPlatform -> info($param['id']);
        if (empty($info)) {
            $this -> error('平台不存在');
        }

        $info['platform_config_h5'] = str_replace(',', PHP_EOL, $info['platform_config_h5']);
        $info['platform_config_sy'] = str_replace(',', PHP_EOL, $info['platform_config_sy']);
        $info['platform_config_yy'] = str_replace(',', PHP_EOL, $info['platform_config_yy']);
        $info['service_config'] = str_replace(',', PHP_EOL, $info['service_config']);
        $this -> assign('info', $info);
        return $this -> fetch();
    }


    public function changestatus()
    {

        $data = $this -> request -> param();
        $where = [];
        $where['id'] = $data['id'];
        if (isset($data['status'])) {
            $result = Db ::table('tab_issue_open_user_platform') -> where($where) -> setField('status', $data['status']);
        }
        if (isset($data['min_balance'])) {
            $result = Db ::table('tab_issue_open_user_platform') -> where($where) -> setField('min_balance', $data['min_balance']);
        }
        if (false === $result) {
            $this -> error('操作失败');
        }
        $this -> success('操作成功');

    }


    /**
     * @获取平台配置
     *
     * @author: zsl
     * @since: 2020/7/13 11:20
     */
    public function getConfigure()
    {
        $param = $this -> request -> post();
        $lPlatform = new PlatformLogic();
        $configure = $lPlatform -> getConfigure($param);
        $this -> success('请求成功', '', $configure -> toArray());
    }

    /**
     * @保存平台配置
     *
     * @author: zsl
     * @since: 2020/7/13 10:00
     */
    public function saveConfigure()
    {
        $param = $this -> request -> post();
        $lPlatform = new PlatformLogic();
        $result = $lPlatform -> saveConfigure($param);
        if (false === $result) {
            $this -> error('设置失败');
        }
        $this -> success('设置成功');
    }


    /**
     * @获取SDK版本
     *
     * @author: zsl
     * @since: 2020/7/14 14:14
     */
    public function getSdkVersion()
    {
        $sdk_name = $this -> request -> post('sdk_name');
        $sdkConfig = getSdkConfigItem($sdk_name);
        $result = ['code' => 1, 'msg' => '获取成功', 'data' => $sdkConfig];
        return json($result);
    }


    /**
     * @获取平台禁用游戏
     *
     * @author: zsl
     * @since: 2020/7/23 9:09
     */
    public function getPlatformGame()
    {
        if ($this->request->isAjax()) {

            $id = $this->request->param('id', 0, 'intval');
            $model = new PlatformModel();
            $info = $model->field('id,account,game_ids,open_user_id')->where('id', $id)->find();
            $info = $info ? $info->toArray():[];
            $gamemodel = new IssueGameModel();
            $data['data']['game_list'] = $gamemodel->getAllGameLists();

            $sdk_version_arr = [1=>'(安卓版)',2=>'(苹果版)',3=>''];
            if(!empty($data['data']['game_list'])){
                foreach ($data['data']['game_list'] as $k=>$v) {
                    $data['data']['game_list'][$k]['sdk_version_name'] = $sdk_version_arr[$v['sdk_version']];
                    $data['data']['game_list'][$k]['initial'] = substr($v['short'], 0,1);
                }
            }

            $info['game_ids'] = empty($info['game_ids']) ? '' : explode(',', $info['game_ids']);
            $info['open_user_account'] = get_issue_open_useraccount($info['open_user_id']);
            $data['data']['promote_info'] = $info;
            $data['code'] = 1;
            return json($data);

        }
    }


    /**
     * @设置平台禁用游戏
     *
     * @author: zsl
     * @since: 2020/7/23 9:15
     */
    public function savePlatformGame()
    {
        $data = $this -> request -> param();
        if (!$data['promote_id']) {
            $this -> error('请选择渠道');
        }
        if (empty($data['game_ids'])) {
            $game_ids = '';
            $data['game_ids'] = [];
        } else {
            $game_ids = implode(',', $data['game_ids']);
        }
        $promote_info = get_promote_entity($data['promote_id'], 'id,game_ids');
        if ($promote_info['game_ids']) {
            $old_game_ids = explode(',', $promote_info['game_ids']);
        } else {
            $old_game_ids = [];
        }
        $change = array_merge(array_diff($old_game_ids, $data['game_ids']), array_diff($data['game_ids'], $old_game_ids));
        $model = new PlatformModel();
        Db ::startTrans();
        try {
            //修改一级渠道以及子渠道
            $model -> where('id', $data['promote_id']) -> setField('game_ids', $game_ids);
            // 提交事务
            Db ::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db ::rollback();
            $this -> error('设置失败');
        }
        $this -> success('修改成功');

    }


    /**
     * @获取已申请游戏
     *
     * @author: zsl
     * @since: 2020/7/23 9:32
     */
    public function getApplyGames()
    {
        $param = $this -> request -> param();
        $row = $param['row'] ? $param['row'] : 10;
        $mApply = new IssueGameApplyModel();
        $field = "id,game_id,game_name,ratio,update_time,sdk_version";
        $where = [];
        $where['status'] = 1;
        $where['enable_status'] = 1;
        $where['platform_id'] = $param['platform_id'];
        $gameLists = $mApply -> field($field) -> where($where) -> paginate($row);
        $this -> assign('gameLists', $gameLists);
        return $this -> fetch();
    }

    /**
     * @设置申请链接比例
     *
     * @author: zsl
     * @since: 2020/7/23 15:12
     */
    public function setApplyRatio()
    {
        $param = $this -> request -> param();
        $mApply = new IssueGameApplyModel();
        $result = $mApply -> allowField(true) -> isUpdate(true) -> save(['ratio' => $param['ratio']], ['id' => $param['id']]);
        if (false === $result) {
            $this -> error('设置失败');
        }
        $this -> success('设置成功');

    }

}
