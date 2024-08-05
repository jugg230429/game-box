<?php

namespace app\issue\logic;

use app\common\controller\BaseController;
use app\issue\model\IssueGameApplyModel;

class ApplyLogic
{


    public function page($request)
    {
        $base = new BaseController();
        $model = new IssueGameApplyModel();
        $map = [];
        if (!empty($request['open_user_id'])) {
            $map['open_user_id'] = $request['open_user_id'];
        }
        if (!empty($request['platform_id'])) {
            $map['platform_id'] = $request['platform_id'];
        }
        if (!empty($request['game_name'])) {
            $map['game_name'] = ['like', ['%' . $request['game_name'] . '%']];
        }
        if ($request['status'] === '0' || $request['status'] == '1') {
            $map['status'] = $request['status'];
        }
        //查询字段
        $exend['field'] = '*';
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,id desc';
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend);
        return $data;
    }

    /**
     * @审核
     *
     * @author: zsl
     * @since: 2020/7/15 10:07
     */
    public function audit($ids)
    {
        $result = ['code' => 1, 'msg' => '审核成功'];
        if (empty($ids)) {
            return false;
        }
        $mGameApply = new IssueGameApplyModel();
        $where = [];
        $where['id'] = ['in', $ids];
        $where['status'] = 0;
        $where['enable_status'] = 0;
        $lists = $mGameApply -> field('id,ratio') -> where($where) -> select();
        foreach ($lists as $v) {
            if (empty($v['ratio'])) {
                $result['code'] = 0;
                $result['msg'] = '请设置分成比例';
                return $result;
            }
        }
        $data['status'] = 1;
        $data['enable_status'] = 1;
        $data['dispose_id'] = cmf_get_current_admin_id();
        $data['dispose_time'] = time();
        $res = $mGameApply -> allowField(true) -> save($data, $where);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '审核失败';
        }
        return $result;
    }


    /**
     * @删除
     *
     * @author: zsl
     * @since: 2020/7/15 10:15
     */
    public function delete($ids)
    {
        if (empty($ids)) {
            return false;
        }
        $mGameApply = new IssueGameApplyModel();
        $where = [];
        $where['id'] = ['in', $ids];
        $result = $mGameApply -> where($where) -> delete();
        return $result;
    }


    /**
     * @设置平台配置
     *
     * @author: zsl
     * @since: 2020/7/15 14:45
     */
    public function setPlatformConfig($param)
    {
        $result = ['code' => 1, 'msg' => '设置成功', 'data' => []];
        if (empty($param['id'])) {
            $result['code'] = 0;
            $result['msg'] = '设置失败';
            return $result;
        }
        $id = $param['id'];
        unset($param['id']);
        $data = [];
        $data['platform_config'] = json_encode($param);
        //更新平台配置
        $mGameApply = new IssueGameApplyModel();
        $res = $mGameApply -> allowField(true) -> save($data, ['id' => $id]);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '设置失败';
            return $result;
        }
        return $result;
    }

    public function setPlatformServiceConfig($param)
    {
        $result = ['code' => 1, 'msg' => '设置成功', 'data' => []];
        if (empty($param['id'])) {
            $result['code'] = 0;
            $result['msg'] = '设置失败';
            return $result;
        }
        $id = $param['id'];
        unset($param['id']);
        $data = [];
        $data['service_config'] = json_encode($param);
        //更新平台配置
        $mGameApply = new IssueGameApplyModel();
        $res = $mGameApply -> allowField(true) -> save($data, ['id' => $id]);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '设置失败';
            return $result;
        }
        return $result;
    }

    /**
     * @获取申请链接配置
     *
     * @author: zsl
     * @since: 2020/7/23 17:16
     */
    public function getConfig($param)
    {
        $mGameApply = new IssueGameApplyModel();
        $field = 'id,game_id,game_name,platform_config,service_config,sdk_version,platform_id,open_user_id';
        $where = [];
        $where['id'] = $param['id'];
        $res = $mGameApply -> field($field) -> where($where) -> find();
        return $res;
    }


}