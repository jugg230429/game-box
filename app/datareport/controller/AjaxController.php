<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\datareport\controller;

use app\common\controller\BaseController as Base;
use think\Db;
use think\Request;
use app\recharge\model\SpendModel;

class AjaxController extends Base
{
    /**
     * 构造函数
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request = null)
    {
        parent::__construct();
        $isajax = $this->request->isAjax();
        if (!$isajax) {
            return json(['status' => 0, 'msg' => '请求方式错误', 'data' => []]);
        }
    }

    public function get_pay_detail()
    {
        $request = $this->request->param();
        if (empty($request['user_ids'])) {
            $map['user_id'] = 0;
        } else {
            $map['user_id'] = ['in', $request['user_ids']];
        }
        if ($request['daterange'] != '') {
            $datarange = explode('至', $request['daterange']);
            $map['pay_time'] = ['between', [strtotime($datarange[0]), strtotime($datarange[1]) + 24 * 3600 - 1]];
        } else {
            $map['pay_time'] = ['between', [strtotime($request['date']), strtotime($request['date']) + 24 * 3600 - 1]];
        }
        $map['pay_status'] = 1;
        if ($request['game_id'] > 0) {
            $map['game_id'] = $request['game_id'];
        }
        if ($request['promote_id'] > 0) {
            $pids = Db::table('tab_promote')->where(['id|parent_id' => $request['promote_id']])->column('id');
            $map['promote_id'] = ['in', implode(',', $pids)];
        }
        $model = new SpendModel();
        $data = $model->getPayDetail($map);
        return json(['status' => 1, 'msg' => '请求成功', 'data' => $data]);
    }

    public function get_user_lists_info()
    {
        $request = $this->request->param();
        if (empty($request['datas'])) {
            $map['user_id'] = 0;
        } else {
            $map['user_id'] = ['in', $request['user_ids']];
        }
        $data = Db::table('tab_user')->field('account')->where($map)->select()->toArray();
        return json(['status' => 1, 'msg' => '请求成功', 'data' => $data]);
    }
}