<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\promote\controller;

use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteModel;
use app\member\model\UserModel;
use app\promote\model\PromotecoinModel;
use app\promote\model\PromotedepositModel;
use app\recharge\model\SpendModel;
use think\Request;
use think\Db;

class DatamanageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
        $action = request()->action();
        $array = [];
        if (AUTH_GAME != 1 && in_array($action, $array)) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    //渠道平台币转移纪录 yyh
    public function cointransfer()
    {
        $model = new PromotecoinModel;
        $base = new BaseController;
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }

        $promote_id = $this->request->param('promote_id', '');
        if ($promote_id != '') {
            $map['promote_id'] = $promote_id;
        }
        $source_id = $this->request->param('source_id', '');
        if ($source_id != '') {
            $map['source_id'] = $source_id;
        } else {
            $map['source_id'] = ['gt', 0];
        }
        $map['promote_type'] = 1;
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($model, $map, $exend);
        $exend['field'] = 'sum(num) as total';
        //累计
        $total = $base->data_list_select($model, $map, $exend);
        //今日充值
        $map['create_time'] = ['between', total(1, 2)];
        $today = $base->data_list_select($model, $map, $exend);
        //昨日充值
        $map['create_time'] = ['between', total(5, 2)];
        $yestoday = $base->data_list_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $this->assign("total", $total[0]);//累计充值
        $this->assign("today", $today[0]);//今日
        $this->assign("yestoday", $yestoday[0]);//今日
        return $this->fetch();
    }

    //渠道充值玩家平台币 yyh
    public function coinrecord()
    {
        $model = new PromotedepositModel;
        $base = new BaseController;
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $pay_order_number = $this->request->param('pay_order_number', '');
        if ($pay_order_number != '') {
            $map['pay_order_number'] = ['like', "%" . addcslashes($pay_order_number, '%') . '%'];
        }
        $promote_id = $this->request->param('promote_id', '');
        if ($promote_id != '') {
            $map['promote_id'] = $promote_id;
        }
        $to_id = $this->request->param('to_id', '');
        if ($to_id != '') {
            $map['to_id'] = $to_id;
        }
        $pay_way = $this->request->param('pay_way', '');
        if ($pay_way != '') {
            $map['pay_way'] = $pay_way;
        }
        $pay_status = $this->request->param('pay_status', '');
        if ($pay_status != '') {
            $map['pay_status'] = $pay_status;
        }
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($model, $map, $exend);
        $exend['field'] = 'sum(pay_amount) as total';
        //累计充值
        $map['pay_status'] = 1;
        $total = $base->data_list_select($model, $map, $exend);
        $map = [];
        //今日充值
        $map['pay_status'] = 1;
        $map['create_time'] = ['between', total(1, 2)];
        $today = $base->data_list_select($model, $map, $exend);
        //昨日充值
        $map['pay_status'] = 1;
        $map['create_time'] = ['between', total(5, 2)];
        $yestoday = $base->data_list_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("total", $total[0]['total']);//累计充值
        $this->assign("today", $today[0]['total']);//累计充值
        $this->assign("yestoday", $yestoday[0]['total']);//累计充值
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取子渠道
     * @author: 郭家屯
     * @since: 2019/6/20 20:04
     */
    public function get_child_promote()
    {
        $model = new PromoteModel();
        $promote_id = $this->request->param('promote_id', 0, 'intval');
        if (empty($promote_id)) $this->error('参数错误');
        $data = $model->field('id,account')->where('parent_id', $promote_id)->select();
        return json(['code' => 1, 'msg' => '获取成功', 'data' => $data]);
    }
}