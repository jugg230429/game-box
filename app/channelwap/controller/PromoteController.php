<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\channelwap\controller;

use app\channelsite\logic\WelfareLogic;
use app\common\controller\BaseHomeController;
use app\common\logic\PromoteLogic;
use app\portal\service\ApiService;
use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteBehaviorModel;
use app\promote\model\PromoteunionModel;
use app\site\model\PortalPostModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Request;
use app\promote\validate\PromoteValidate;
use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use app\member\model\UserPlayModel;
use app\promote\model\PromotecoinModel;
use app\promote\model\PromoteModel;
use app\site\service\PostService;
use app\datareport\event\PromoteController as Promote;
use app\game\model\ServerModel;
use cmf\paginator\Bootstrap;
use app\promote\logic\PromoteLevelLogic;
use app\game\model\GameAttrModel;

class PromoteController extends BaseController
{
    // 后台首页
    public function index()
    {
        if(PID_LEVEL < 3){
            $zicount = count(get_song_promote_lists(PID,1));
        }
        $this->assign('zicount',$zicount);
        $article_status = $this->get_article_status();
        $this->assign('article_status',$article_status);

        //合作规则说明
        $this->cooperationRules();
        // 弹窗显示资讯
        $this->getFirstArticle();

        // 渠道等级
        $this->getMyLevel();

        return $this->fetch();
    }

    /**
     * 获取渠道当前等级
     *
     * @author: Juncl
     * @time: 2021/09/11 15:51
     */
    private function getMyLevel()
    {
        $PromoteLevel = new PromoteLevelLogic();
        $myLevel = $PromoteLevel->getPromoteLevel(PID);
        if(empty($myLevel)){
            $myLevel['level'] = 0;
            $myLevel['level_name'] = '暂无';
        }
        $levels = cmf_get_option('promote_level_set');
        $levelCount = count($levels);
        if(!empty($levels)){
            $GameAttrModel = new GameAttrModel();
            foreach ($levels as $key => $val)
            {
                $count = $GameAttrModel->where('promote_level_limit','elt',$val['level'])->count('game_id');
                $levels[$key]['game_count'] = $count;
                if($key == ($levelCount -1)){
                    $levels[$key]['sum_moneys'] = $val['sum_money'] . '及以上';
                }else{
                    $levels[$key]['sum_moneys'] = $val['sum_money'] . '~' . ($levels[$key+1]['sum_money']-1);
                }
            }
        }
        $this->assign('levels',$levels);
        $this->assign('myLevel',$myLevel);
    }

    private function cooperationRules()
    {
        $articleapi = new ApiService;
        $param['where']['post.id'] = 17;
        $articles = $articleapi -> articles($param);
        $cooperation = empty($articles) ? [] : $articles['articles'] -> toarray();
        $this -> assign('cooperation', $cooperation[0]);
        $this -> assign('promote_id', PID);
    }

    /**
     * @函数或方法说明
     * @获取文章阅读状态
     * @author: 郭家屯
     * @since: 2020/4/3 10:02
     */
    protected function get_article_status()
    {
        $postService = new PostService();
        $map['category'] = '2,4';
        $map['post_status'] = 1;
        $newarticle = $postService->NewArticle($map, false);
        if(!$newarticle){
           return 0;
        }
        $behavior = Db::table('tab_promote_behavior')->where('promote_id',PID)->field('update_time')->find();
        if(!$behavior){
            return 1;
        }
        if($newarticle['create_time'] > $behavior['update_time']){
            return 1;
        }
        return 0;
    }

    /**
     * @函数或方法说明
     * @公告活动文章
     * @author: 郭家屯
     * @since: 2020/4/3 10:12
     */
    public function article()
    {
        //记录阅读行为
        $model = new PromoteBehaviorModel();
        $model->addBehavior(PID);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取文章列表
     * @author: 郭家屯
     * @since: 2020/4/3 10:47
     */
    public function get_article()
    {
        $postService = new PostService();
        $limit = $this->request->param('row/d');
        $map['category'] = '2,4';
        $map['post_status'] = 1;
        $articles = $postService->ArticleList($map, false, $limit?:10)->each(function($item,$key){
            $item['url'] = url('article_detail',['id'=>$item['id']]);
            $item['create_time'] = date('Y-m-d',$item['create_time']);
            return $item;
        });
        return json($articles);
    }

    /**
     * @函数或方法说明
     * @文章详情
     * @author: 郭家屯
     * @since: 2020/4/3 10:28
     */
    public function article_detail()
    {
        $id = $this->request->param('id/d', 0);

        $portalPostModel = new PortalPostModel();
        $data = $portalPostModel
                ->field('id,post_title,post_content,create_time')
                ->where('id', $id)
                ->find();
        if ($data) {
            $data = $data->toArray();
            $portalPostModel->where('id', $id)->setInc('post_hits');
        }else{
            $this->error('不存在文章或文章已删除');
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    private function get_rule($data=[]){
        switch ($data['tab']) {
            case 1:
                $rule = [
                        'real_name' => 'require|min:2|max:25|checkChineseName',
                        'mobile_phone' => 'require|isMobileNO',
                        'email' => 'unique'
                ];
                $msg = [
                        'real_name.require' => '姓名不能为空',
                        'mobile_phone.require' => '手机号不能为空',
                        'mobile_phone.isMobileNO' => '请输入正确的手机号',
                        'email.require' => '邮箱地址不能为空',
                        'email.email' => '邮箱地址错误',
                        'email.unique' => '邮箱地址已被使用',
                ];
                break;
            case 2:
                $rule = [
                        'bank_phone' => 'require|length:11|isMobileNO',
                        'bank_card' => 'require|number|length:10,19',
                        'bank_name' => 'require',
                        'bank_account' => 'require|checkChineseName',
                        'account_openin' => 'require|min:2',
                ];
                $msg = [
                        'bank_phone.require' => '请输入正确手机号',
                        'bank_phone.length' => '请输入11位手机号码',
                        'bank_phone.isMobileNO' => '请输入正确手机号',
                        'bank_card.require' => '银行卡号不能为空',
                        'bank_card.number' => '银行卡号格式错误',
                        'bank_card.length' => '银行卡号格式错误',
                        'bank_name.require' => '收款银行不能为空',
                        'bank_account.require' => '持卡人不能为空',
                        'bank_account.checkChineseName' => '姓名长度需要在2-25个字符之间',
                        'account_openin.require' => '开户网点不能为空',
                        'account_openin.min' => '开户网点错误',
                        'account_openin.checkChineseName' => '开户网点错误'
                ];
                break;
            case 3:
                $rule = [
                        'old_password' => 'require',
                        'password' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
                ];
                $msg = [
                        'old_password.require' => '原密码不能为空',
                        'password.require' => '新密码不能为空',
                        'password.confirm' => '两次输入的密码不一致',
                ];
                break;
            case 4:
                $rule = [
                        'second_pwd' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
                ];
                $msg = [
                        'second_pwd.require' => '新密码不能为空',
                        'second_pwd.confirm' => '两次输入的密码不一致',
                ];
                break;
            case 5:
                $rule = [
                        'alipay_account' => 'require',
                        'alipay_name' => 'require|min:2|max:25|checkChineseName',
                ];
                $msg = [
                        'alipay_account.require' => '请输入支付宝账号',
                        'alipay_name.require' => '请输入支付宝姓名',
                        'alipay_name.min' => '姓名长度需要在2-25个字符之间',
                        'alipay_name.max' => '姓名长度需要在2-25个字符之间',
                        'alipay_name.checkChineseName' => '姓名格式错误',
                ];
                break;
            default:
                $this->error('数据错误');
                break;
        }
        $return['rule'] = $rule;
        $return['msg'] = $msg;
        return $return;
    }

    // 账户余额
    public function balance()
    {
        if (AUTH_PAY != 1) {
            $this->redirect('shift');
        }
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1])->select()->toarray();
        $this->assign('balance', null_to_0($this->promote['balance_coin']));
        $this->assign('paytype', $paytype);
        return $this->fetch();
    }
    //检查平台币充值结果
    //yyh
    public function check_order_status()
    {
        $order_no = $this->request->param('order_no');
        if (empty($order_no)) {
            $this->error('无订单数据');
        }
        $res = Db::table('tab_promote_deposit')->field('pay_order_number,pay_status,promote_id,to_id,pay_amount,pay_way,create_time')->where(['promote_id' => PID, 'pay_order_number' => $order_no])->find();
        if (request()->isAjax()) {
            if ($res['pay_status'] == 1) {
                $this->success('充值成功', cmf_get_domain() . '/channelsite/Promote/check_order_status/order_no/' . $order_no);
            } else {
                $this->error('未充值成功');
            }
        } else {
            $this->assign('data', $res);
            if (empty($res)) {
                $this->error('数据错误');
            } elseif ($res['pay_status'] == 1) {
                return $this->fetch('pay_success');
            } else {
                return $this->fetch('pay_failure');
            }
        }
    }
    //检查代充平台币充值结果
    //yyh
    public function check_bind_order_status()
    {
        $order_no = $this->request->param('order_no');
        if (empty($order_no)) {
            $this->error('无订单数据');
        }
        $res = Db::table('tab_promote_bind')->field('pay_order_number,user_account,game_name,pay_status,pay_amount,pay_way,1 as is_bind_pay')->where(['promote_id' => PID, 'pay_order_number' => $order_no])->find();
        if (request()->isAjax()) {
            if ($res['pay_status'] == 1) {
                $this->success('充值成功', cmf_get_domain() . '/channelsite/Promote/check_bind_order_status/order_no/' . $order_no);
            } else {
                $this->error('未充值成功');
            }
        } else {
            $this->assign('data', $res);
            if (empty($res)) {
                $this->error('数据错误');
            } elseif ($res['pay_status'] == 1) {
                return $this->fetch('pay_success');
            } else {
                return $this->fetch('pay_failure');
            }
        }
    }

    //检测账号是否存在
    public function checkAccount()
    {
        $data = $this->request->param();
        if (empty($data['account'])) {
            $this->error('请输入账号');
        } else {
            $res = Db::table('tab_promote')->field('id,parent_id,status')->where(['account' => $data['account']])->find();
            if ($data['validate'] == 1) {//js验证 存在为true
                if (empty($res)) {
                    return false;
                } elseif ($res['status'] != 1) {
                    return false;
                } else {
                    return true;
                }
            }
            if ($data['validate'] == 2) {//js验证 不存在为true
                if (empty($res)) {
                    return true;
                } else {
                    return false;
                }
            }
            if (empty($res)) {
                $this->error('账号不存在');
            } else {
                echo json_encode(['code' => 1, 'msg' => '账号存在', 'data' => $res]);
            }
        }
    }

    // 给下级充值
    public function shift()
    {
        if(PID_LEVEL == 3){
            $this->redirect('balance');
        }
        $map['parent_id'] = PID;
        $pdata = Db::table('tab_promote')->field('id,account,balance_coin')->where($map)->select();
        if (request()->isPost()) {
            $data = $this->request->param();
            if (!$data['promotezi']) {
                $this->error('请选择子渠道');
            }
            if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
                $this->error('金额错误，请输入正整数');
            }
            //验证二级密码
            if(empty($data['second_pwd'])){
                $this->error('请输入二级密码');
            }
            if(empty($this->promote['second_pwd'])){
                $this->error('请先设置二级密码');
            }
            if (!xigu_compare_password($data['second_pwd'], $this->promote['second_pwd'])) {
                $this->error('二级密码错误');
            }
            if ($this->promote['balance_coin'] < $data['amount']) {
                $this->error('账户余额不足');
            }
            $model = new PromotecoinModel();
            $data['pid'] = PID;
            $result = $model->transfer($data);
            if($result){
                $this->success('转移成功');
            }else{
                $this->error('转移失败');
            }
        }
        $this->assign('pdata', $pdata);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @平台币记录
     * @author: 郭家屯
     * @since: 2020/4/8 9:24
     */
    public function record()
    {
        return $this->fetch();
    }

    // 平台币记录
    public function get_record()
    {
        $dpmodel = new PromotedepositModel();
        $czmap['pay_status'] = 1;
        $czmap['to_id'] = PID;
        $cz = $dpmodel->lists($czmap)->toarray();//平台币充值
        foreach ($cz as $key=>$v){
            $cz[$key]['promote_id'] = '自己';
            $cz[$key]['type'] = 2;
        }
        //平台币转移扣除
        $zymodel = new PromotecoinModel();
        $zymap['promote_id'] = PID;
        $zymap['type'] = 1;
        $zy = $zymodel->lists($zymap, 'promote_id,source_id as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 1 as ptbtype')->toarray();
        //平台币转移获得
        $zymodel = new PromotecoinModel();
        $zymap['promote_id'] = PID;
        $zymap['type'] = 2;
        $zyj = $zymodel->lists($zymap, 'promote_id,source_id as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 2 as ptbtype')->toarray();

        //后台发放
        $pcmodel = new \app\recharge\model\SpendpromotecoinModel;
        $pcmap['promote_id'] = PID;
        $pcmap['type'] = 1;
        $pc = $pcmodel->lists($pcmap, '"自己" as promote_id,"自己" as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 2 as ptbtype')->toarray();

        //后台回收
        $hsmap['promote_id'] = PID;
        $hsmap['type'] = 2;
        $hs = $pcmodel->lists($hsmap, '"自己" as promote_id,"自己" as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 3 as ptbtype')->toarray();
        $alldata = $data = my_sort(array_merge($cz, $zy, $zyj,$pc,$hs), 'create_time', SORT_DESC);
        // 搜索开始
        foreach ($data as $key => &$value) {
            //数据整理
            if($value['ptbtype'] == 1) {
                $alldata[$key]['type'] = $data[$key]['type'] = 3;
            } elseif ($value['ptbtype'] == 2) {
                $alldata[$key]['type'] = $data[$key]['type'] = 4;
            }elseif ($value['ptbtype'] == 3) {
                $alldata[$key]['type'] = $data[$key]['type'] = 5;
            }
            $to_id = $this->request->param('to_id');
            if (!empty($to_id) && $to_id != '自己') {
                if ($value['to_id'] != $to_id) {
                    unset($data[$key]);
                }
            } elseif ($to_id == '自己') {
                if ($value['promote_id'] != $to_id) {
                    unset($data[$key]);
                }
            }
            $type = $this->request->param('type');
            if ($type > 0) {
                if ($value['type'] != $type) {
                    unset($data[$key]);
                }
            }
        }
        $total['shouru'] = null_to_0(deposit_record($alldata,1));
        $total['zhichu'] = null_to_0(deposit_record($alldata,2));
        $curpage = input('page') ? input('page') : 1;
        $listRow = $this->request->param('row') ?: config('paginate.list_rows');
        $dataTo = array_chunk($data, $listRow);
        if ($dataTo) {
            $showdata = $dataTo[$curpage - 1];
        } else {
            $showdata = [];
        }
        foreach ($showdata as $key=>$vo){
            if($vo['promote_id'] != '自己'){
                $showdata[$key]['promote_id'] = get_promote_name($vo['to_id']);
            }
            $showdata[$key]['create_time'] = date('m-d H:i',$vo['create_time']);
            if($vo['type'] == 1){
                $showdata[$key]['pay_amount'] = '+'.null_to_0($vo['pay_amount']);
                $showdata[$key]['get_way'] = '充值渠道';
            }elseif($vo['type'] == 2){
                $showdata[$key]['pay_amount'] = '+'.null_to_0($vo['pay_amount']);
                $showdata[$key]['get_way'] = '渠道充值';
            }elseif($vo['type'] == 3){
                $showdata[$key]['pay_amount'] = '-'.null_to_0($vo['pay_amount']);
                $showdata[$key]['get_way'] = '平台币转移';
            }elseif($vo['type'] == 4){
                $showdata[$key]['pay_amount'] = '+'.null_to_0($vo['pay_amount']);
                $showdata[$key]['get_way'] = '发放/转移';
            }elseif($vo['type'] == 5){
                $showdata[$key]['pay_amount'] = '-'.null_to_0($vo['pay_amount']);
                $showdata[$key]['get_way'] = '后台收回';
            }
            $showdata[$key]['pay_way'] = $vo['pay_way']>0 ? get_pay_way($vo['pay_way']) : $vo['pay_way'];
        }
        $return['data'] = $showdata;
        $return['total'] = $total;
        return json($return);
    }

    /**
     * @函数或方法说明
     * @代充记录
     * @author: 郭家屯
     * @since: 2020/4/8 10:24
     */
    public function bind_record()
    {
        return $this->fetch();
    }
    /**
     * @函数或方法说明
     * @代充记录数据
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/8 10:24
     */
    public function get_bind_record()
    {
        $request = $this->request->param();
        //条件
        $account = $request['user_account'];
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        if($request['start_time'] && $request['end_time']){
            $map['pay_time'] = ['between', [strtotime($request['start_time']), strtotime($request['end_time'])+86399]];
        }elseif($request['end_time']){
            $map['pay_time'] = ['lt',strtotime($request['end_time'])+86399];
        }elseif($request['start_time']){
            $map['pay_time'] = ['gt',strtotime($request['start_time'])];
        }
        $ys_show_promote = get_promote_privicy_two_value();
        $model = new PromotebindModel();
        $base = new \app\common\controller\BaseHomeController();
        $map['pay_status'] = 1;
        $map['promote_id'] = PID;
        $exend['field'] = 'game_id,game_name,user_id,user_account,cost,discount,pay_amount,pay_time,pay_way,pay_order_number';
        $exend['order'] = 'pay_time desc';
        $data = $base->data_list_join($model, $map, $exend)->each(function($item,$key) use($ys_show_promote){
            $item['pay_time'] = date('m-d H:i',$item['pay_time']);
            $item['pay_way'] = get_pay_way($item['pay_way']);
            // 判断当前渠道是否有权限显示完成整手机号或完整账号
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $item['user_account'] = get_ys_string($item['user_account'],$ys_show_promote['account_show_promote']);
            }

            return $item;
        });
        //累计充值
        $exend['order'] = null;
        $exend['field'] = 'sum(cost) as scost,sum(pay_amount) as spay_amount';
        $total = $base->data_list_join_select($model, $map, $exend);
        // 获取分页显示
        $return['data'] = $data->toarray()['data'];
        $return['total'] = $total[0];
        return json($return);
    }
    private function promote_game_lists()
    {
        //渠道禁止申请游戏
        $promote = $this->promote;
        if ($promote['game_ids']) {
            $map['g.id'] = ['notin', explode(',', $promote['game_ids'])];
        }
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $map['g.game_status'] = 1;
        $map['tab_promote_apply.promote_id'] = PID;
//        $exend['field'] = 'g.id,g.game_name,IF(a.promote_discount>0,a.promote_discount,g.discount) as discount';
        $exend['field'] = 'g.id,g.game_name';
        $exend['order'] = 'g.id asc';
        $exend['join1'] = [['tab_game' => 'g'],'g.id = tab_promote_apply.game_id','left'];
        $exend['join2'] = [['tab_promote_agent' => 'a'],'a.game_id = tab_promote_apply.game_id and tab_promote_apply.promote_id = a.promote_id','left'];
        $gdata = $base->data_list_join_select($model, $map, $exend);
        return $gdata;
    }
    // 二级渠道
    public function mychild()
    {
        return $this->fetch();
    }

    // 二级渠道
    public function get_mychild()
    {
        $base = new \app\common\controller\BaseHomeController;
        $model = new PromoteModel;
        $child_id = $this->request->param('child_id', '');
        if ($child_id > 0) {
            $map['id'] = $child_id;
        }
        $map['parent_id'] = PID;
        $exend['order'] = 'create_time desc';
        $exend['field'] = 'id,account,status,create_time';
        $data = $base->data_list($model, $map, $exend)->each(function($item,$key){
            $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            return $item;
        });

        // 联盟站设置
        $mUnion = new PromoteunionModel();
        foreach ($data as &$v) {
            $v['union'] = $mUnion -> field('id,union_id,status,domain_url') ->where(['union_id'=>$v['id']]) -> find();
        }

        return json($data);
    }

    // 添加渠道
    public function add_child()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $rule = [
            ];
            $msg = [
                'account.require' => '账号不能为空',
                'account.checkaccount' => '账号已存在',
                'account.regex' => '账号为6-30位字母或数字组合',
                'account.min' => '账号为6-30位字母或数字组合',
                'account.max' => '账号为6-30位字母或数字组合',
                'password.require' => '密码不能为空',
                'password.min' => '密码为6-30位字母或数字组合',
                'password.max' => '密码为6-30位字母或数字组合',
                'password.regex' => '密码为6-30位字母或数字组合',
                'password.confirm' => '两次输入密码不一致',
                'real_name.min' => '联系人姓名，2~25个字符',
                'real_name.max' => '联系人姓名，2~25个字符',
                'real_name.checkChineseName' => '姓名格式错误',
            ];
            $validate = new PromoteValidate($rule,$msg);
            if (!$validate->scene('addzipromote1')->check($data)) {
                $this->error($validate->getError());
            }
            $model = new PromoteModel();
            $data['parent_id'] = PID;
            $data['parent_name'] = PNAME;
            $data['promote_level'] = PID_LEVEL+1;
            $top_id = get_top_promote_id(PID);
            $data['top_promote_id'] = $top_id;
            $top_pattern = get_promote_entity($top_id,'pattern')['pattern'];
            $data['pattern'] = $top_pattern;
            $res = $model->add_child($data);
            if ($res != false) {
                $this->success('子渠道添加成功',url('mychild'));
            } else {
                $this->error('子渠道添加失败');
            }
        } else {
            return $this->fetch();
        }
    }

    public function changeStatus()
    {
        $data = $this->request->param();
        $map['parent_id'] = PID;
        $map['id'] = $data['zid'];
        $res = Db::table('tab_promote')->field('id,status')->where($map)->find();
        $return = false;
        if (empty($res)) {
            $this->error('渠道不存在');
        } else {
            $save['status'] = $data['value'] < 1 ? 1 : -1;
            $save['id'] = $res['id'];
            $return = Db::table('tab_promote')->update($save);
        }
        if ($return !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    // 修改渠道
    public function edit_child()
    {
        $data = $this->request->param();
        $zidata = Db::table('tab_promote')->field('id,account,real_name,mobile_phone,email,bank_phone,bank_card,bank_name,bank_account,account_openin,bank_area,alipay_account,alipay_name,status,mark1')->where(['parent_id' => PID, 'id' => $data['id']])->find();
        if (empty($zidata)) {
            $this->error('该渠道不属于您的子渠道');
        }
        if (request()->isPost()) {
            switch ($data['tab']) {
                case 1:
                    $rule = [
                        'password' => 'min:6|max:30|regex:^[A-Za-z0-9]+$',
                        'real_name' => 'require|checkChineseName',
                        'mobile_phone' => 'require|isMobileNO',
                        'email' => 'unique'
                    ];
                    $msg = [
                        'password.min' => '密码为6-30位字母或数字组合',
                        'password.max' => '密码为6-30位字母或数字组合',
                        'password.regex' => '密码为6-30位字母或数字组合',
                        //'real_name.require'   => '姓名不能为空',
                        'real_name.min' => '姓名长度需要在2-25个字符之间',
                        'real_name.checkChineseName' => '姓名格式错误',
                        //'mobile_phone.require'   => '手机号不能为空',
                        'mobile_phone.isMobileNO' => '请输入正确的手机号',
                        //'email.require'   => '邮箱不能为空',
                        'email.email' => '邮箱地址错误',
                        'email.unique' => '邮箱地址已被使用',
                    ];
                    if($data['mobile_phone'] && strlen($data['mobile_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    $validate = new PromoteValidate($rule, $msg);
                    $valires = $validate->scene('editzipromote' . $data['tab'])->check($data);
                    break;
                case 2:
                    $data['bank_area'] = $data['province'] . ',' . $data['city'] . ',' . $data['county'];
                    unset($data['province'], $data['city'], $data['county']);
                    if ($data['bank_area'] == '省,市,区/县') {
                        $data['bank_area'] = '';
                    }
                    if($data['bank_phone'] && strlen($data['bank_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    $rule = [
                        'bank_phone' => 'require|isMobileNO',
                        'bank_card' => 'require|number|max:19',
                        'bank_name' => 'require',
                        'bank_account' => 'require|checkChineseName',
                        'account_openin' => 'require|min:2',
                    ];
                    $msg = [
                        'bank_phone.require' => '预留手机号不能为空',
                        'bank_phone.isMobileNO' => '预留手机号格式不正确',
                        'bank_card.require' => '银行卡号不能为空',
                        'bank_card.number' => '银行卡号格式不正确',
                        'bank_card.max' => '银行卡号长度不正确',
                        'bank_name.require' => '请选择收款银行',
                        'bank_account.require' => '持卡人不能为空',
                        'bank_account.checkChineseName' => '持卡人格式不正确',
                        'account_openin.require' => '开户网点不能为空',
                        'account_openin.min' => '开户网点错误',
                        'account_openin.checkChineseName' => '开户网点错误',
                    ];
                    $validate = new PromoteValidate($rule, $msg);
                    $valires = $validate->scene('proinfo' . $data['tab'])->check($data);
                    break;
                case 3:
                    $rule = [
                            'alipay_account' => 'require',
                            'alipay_name' => 'require|min:2|max:25|checkChineseName',
                    ];
                    $msg = [
                            'alipay_account.require' => '请输入支付宝账号',
                            'alipay_name.require' => '请输入支付宝姓名',
                            'alipay_name.min' => '姓名长度需要在2-25个字符之间',
                            'alipay_name.max' => '姓名长度需要在2-25个字符之间',
                            'alipay_name.checkChineseName' => '姓名格式错误',
                    ];
                    $validate = new PromoteValidate($rule, $msg);
                    $valires = $validate->scene('proinfo5')->check($data);
                    break;
                default:
                    $this->error('数据错误');
                    break;
            }
            if (!$valires) {
                $this->error($validate->getError());
            }
            unset($data['tab']);
            if (!empty($data['password'])) {
                $data['password'] = cmf_password($data['password']);
            } else {
                unset($data['password']);
            }
            if (!empty($data['second_pwd'])) {
                $data['second_pwd'] = cmf_password($data['second_pwd']);
            } else {
                unset($data['second_pwd']);
            }
            $res = Db::table('tab_promote')->update($data);
            if ($res !== false) {
                $this->success('修改成功',url('mychlid'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $zidata['bank_area'] = explode(',', $zidata['bank_area']);
            $this->assign('data', $zidata);
            return $this->fetch();
        }
    }
    public function bind_balance()
    {
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1])->select()->toarray();
        $gdata = $this->promote_game_lists();
        session('bind_recharge_game'.PID,$gdata);
        $this->assign('paytype', $paytype);
        $this->assign('gdata',$gdata);
        return $this->fetch();
    }
    public function get_user_play()
    {
        if($this->request->isAjax()){
            $model = new UserPlayModel;
            $base = new BaseHomeController;
            $game_id = $this->request->param('game_id');
            $map['u.promote_id'] = PID;
            $map['game_id'] = $game_id;
            $exend['field'] = 'user_id,user_account';
            $exend['order'] = 'user_id asc';
            $exend['join1'][] = ['tab_user' => 'u'];
            $exend['join1'][] = 'u.id = tab_user_play.user_id';
            $data = $base->data_list_join_select($model, $map, $exend);
            session('bind_recharge_user'.PID,$data);
            if(empty($data)){
                return json(['code'=>0,'msg'=>'无玩家数据']);
            }
            return json(['code'=>1,'msg'=>'玩家获取成功','data'=>$data]);
        }
    }

    /**
     *  * 获取首充或者续充的折扣
     *  * by:byh-20210429
     * @return \think\response\Json
     */
    public function get_discount()
    {
        $game_id = $this->request->param('game_id',0);
        $user_id = $this->request->param('user_id',0);
        if($game_id<1){
            return json(['code'=>0,'msg'=>'参数错误']);
        }
        // $discount = get_promote_dc_discount(PID,$game_id,$user_id);
        $discount_arr = get_promote_dc_discount(PID,$game_id,$user_id);

        return json(['code'=>1,'msg'=>'获取成功','data'=>['discount'=>$discount_arr]]);
    }

    /**
     * @函数或方法说明
     * @我的福利
     * @author: 郭家屯
     * @since: 2020/2/10 10:50
     */
    public function welfare()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取福利数据
     * @author: 郭家屯
     * @since: 2020/11/4 14:02
     */
    public function get_welfare()
    {
        $request = $this->request->param();
        $logic = new PromoteLogic();
        $result = $logic->welfare(PID,$request['game_id']);
        $page = $request['page']?:1;
        $row = $request['row']?:10;
        $data = array_slice($result,($page-1)*$row,$row);

        // 将游戏名称处理一下-----------------wjd
        foreach($data as $k1=>$v1){
            if(strstr($v1['game_name'], '安卓版') !== false){
                $data[$k1]['game_name'] = str_replace("安卓版","安卓",$v1['game_name']); //strtr($v1['game_name'],"安卓版","安卓");
            }
            if(strstr($v1['game_name'], '苹果版') !== false){
                $data[$k1]['game_name'] = str_replace("苹果版","IOS",$v1['game_name']);
            }

        }
        // var_dump($data);exit;
        return json($data);
    }

    /**
     * 微信h5支付中间页
     */
    public function wechatJumpPage()
    {

        $jump_url = input('jump_url');
        if (!empty($jump_url)) {
            $jump_url = str_replace('&amp;', '&', $jump_url);
            $this->assign('jump_url', $jump_url);
        }

        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @渠道设置
     * @author: 郭家屯
     * @since: 2020/11/3 20:24
     */
    public function set()
    {
        //查询短信通知等数据
        $promote_sms_notice_info = Db::table('tab_promote')->field('mobile_phone,sms_notice_switch')->where('id',PID)->find();//渠道短息开关和手机号
        $promote_sms_notice_info['promote_sms_notice_switch'] = cmf_get_option('admin_set')['promote_sms_notice_switch']??0;//管理后台总开关
        $this->assign('promote_sms_notice_info', $promote_sms_notice_info);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @保存设置
     * @author: 郭家屯
     * @since: 2020/11/3 20:26
     */
    public function do_set()
    {
        if($this->request->isPost()){
            $tab = $this->request->param('tab');
            $data = $this->request->param();
            switch ($tab){
                case 1:
                    if(strlen($data['mobile_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    break;
                case 2:
                    if(empty($data['bank_card'])){
                        $this->error('银行卡号不能为空');
                    }
                    $data['bank_area'] = $data['province'] . ',' . $data['city'] . ',' . $data['county'];
                    unset($data['province'], $data['city'], $data['county']);
                    if ($data['bank_area'] == '省,市,区/县') {
                        $data['bank_area'] = '';
                    }
                    if(strlen($data['bank_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    $data['bank_card'] = intval($data['bank_card']);
                    break;
                case 3:
                    if (!$data['old_password']) {
                        $this->error('原密码不能为空');
                    } else {
                        $password = Db::table('tab_promote')->field('password')->find(PID);
                        $compare_password = xigu_compare_password($data['old_password'], $password['password']);
                        if (!$compare_password) {
                            $this->error('原密码错误');
                        }
                    }
                    break;
                case 4:
                    $second_pwd = Db::table('tab_promote')->where(['id'=>PID])->value('second_pwd');
                    if(!empty($second_pwd)){
                        if(empty($data['old_second_pwd'])){
                            $this->error('原密码不能为空');
                        }
                        if (!$data['second_pwd_confirm']) {
                            $this->error('确认密码不能为空');
                        }
                        if($data['second_pwd_confirm'] != $data['second_pwd']){
                            $this->error('两次输入的密码不一致');
                        }
                        $compare_second_pwd = xigu_compare_password($data['old_second_pwd'], $second_pwd);
                        if (!$compare_second_pwd) {
                            $this->error('原二级密码错误');
                        }
                    }
                    break;
                case 5:
                    break;
                default:
                    $this->error('请求错误');
            }
            $rule_info = $this->get_rule($data);
            $validate = new PromoteValidate($rule_info['rule'], $rule_info['msg']);
            if (!$validate->scene('proinfo' . $data['tab'])->check($data)) {
                $this->error($validate->getError());
            }
            unset($data['tab']);
            if (!empty($data['password'])) {
                $data['password'] = cmf_password($data['password']);
                unset($data['old_password']);
                unset($data['password_confirm']);
            }
            if (!empty($data['second_pwd'])) {
                $data['second_pwd'] = cmf_password($data['second_pwd']);
                unset($data['second_pwd_confirm']);
            }
            $saveData = [];
            $saveData['id'] = PID;
            if($tab==1){
                $saveData['real_name'] = $data['real_name'];
                $saveData['mobile_phone'] = $data['mobile_phone'];
                $saveData['email'] = $data['email'];
            }elseif($tab==2){
                //银行卡
                $saveData['bank_phone'] = $data['bank_phone'];
                $saveData['bank_card'] = $data['bank_card'];
                $saveData['bank_name'] = $data['bank_name'];
                $saveData['bank_account'] = $data['bank_account'];
                $saveData['bank_area'] = $data['bank_area'];
                $saveData['account_openin'] = $data['account_openin'];
                $saveData['settment_type'] = 0;
            }elseif($tab==3){
                // 修改密码
                $saveData['password'] = $data['password'];
            }elseif($tab==4) {
                $saveData['second_pwd'] = $data['second_pwd'];
            }else{
                //支付宝
                $saveData['alipay_account'] = $data['alipay_account'];
                $saveData['alipay_name'] = $data['alipay_name'];
                $saveData['settment_type'] = 1;
            }
            $res = Db::table('tab_promote')->field(true)->update($saveData);
            if ($res !== false) {
                // 修改密码的时候需要退出后,重新登录  新加的
                // by wjd
                if($tab==3){

                    // session('PID', null);
                    // session('PNAME', null);
                    // session('PARENT_ID', null);
                    // // session('PID', null);
                    // // session('PNAME', null);
                    // // session('PARENT_ID', null);
                    // cookie(null, 'p_');
                    // // $this->redirect('Index/index');
                }
                $this->success('修改成功');

            } else {
                $this->error('修改失败');
            }
        }
        $this->error('请求错误');
    }

    /**
     * @函数或方法说明
     * @账号设置
     * @author: 郭家屯
     * @since: 2020/11/4 9:48
     */
    public function baseinfo()
    {
        $data = $this->promote;
        $data['bank_area'] = explode(',', $data['bank_area']);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @修改密码
     * @author: 郭家屯
     * @since: 2020/11/4 11:03
     */
    public function password()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @修改二级密码
     * @author: 郭家屯
     * @since: 2020/11/4 11:03
     */
    public function secondpwd()
    {
        $data = $this->promote;
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @子渠道功能
     * @author: 郭家屯
     * @since: 2020/11/5 17:33
     */
    public function child()
    {
        return $this->fetch();
    }

    /*
        开服预告页面展示
        created by wjd
        2021-5-14 20:12:09
    */
    public function myserver_foreshow(Request $request)
    {
        $promote_id = PID;
        // var_dump($promote_id);exit;
        $this->assign('promote_id',$promote_id);
        return $this->fetch();
    }
    /*
        今日开服
        created by wjd
        2021-5-14 20:12:09
    */
    // myserverinfo
    public function myserver_foreshow_data(Request $request)
    {
        $promote_id = PID;
        $param = $request->param();
        $serverModel = new ServerModel();
        $map = [];
        $type = $param['type'] ?? 0;
        $d_time_int = time();
        $today_time_int = strtotime(date('Y-m-d'));
        $order="start_time asc";
        if ($type == 1) {
            // 新服预告
            $map['start_time'] = ['between', [$today_time_int + 86400, $today_time_int + 86400 * 8 - 1]];  // 后七日开服信息
            $order = "start_time asc";
        } else {
            //今日开服
            $map['start_time'] = ['between', [$today_time_int, $today_time_int + 86399]];
        }

        if(!empty($param['game_id'])){
            $map['game_id'] = $param['game_id'];
        }else{
            // 获取渠道申请通过的游戏得到游戏id
            $already_applied_game_id = get_promote_apply_game_id($promote_id);
            $map['game_id'] = ['in', $already_applied_game_id];
        }

        $server_id = (int) $param['server_id'] ?? 0;
        if($server_id > 0){
            $map['s.id'] = $server_id;
        }

        $row = (int) $param['row'] ?: ($this -> request -> param('row') ?: config('paginate.list_rows'));//每页数量

        $server_info = $serverModel->getserver_page($map,$row,$order)->each(function($item,$key){

            $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);

            // $item['game_name'] = str_replace('安卓版','安卓',$item['game_name']);
            // $item['game_name'] = str_replace('苹果版','IOS',$item['game_name']);
        });

        $data['data'] = $server_info->toarray()['data'];
        $data['promote_id'] = $promote_id;
        // $this->assign('promote_id', $promote_id);
        return json($data);
        // return $this->fetch();
    }
    /*
        新服预告
        created by wjd
        2021-5-14 20:12:09
    */
    // myserverinfo
    public function myserver_foreshow_data2(Request $request)
    {
        $promote_id = PID;
        $param = $request->param();
        $serverModel = new ServerModel();
        $map = [];
        $type = $param['type'] ?? 0;
        $d_time_int = time();
        $today_time_int = strtotime(date('Y-m-d'));

        // 新服预告
        $request_time = $param['start_time'] ?? 0;
        $request_time_int = strtotime($request_time);
        if(!empty($request_time_int)){
            $map['start_time'] = [['between', [$request_time_int, $request_time_int + 86399]], ['>', $today_time_int + 86400],'and'];
        }else{
            $map['start_time'] = ['between', [$today_time_int + 86400, $today_time_int + 86400 * 8 - 1]];  // 后七日开服信息
        }
        // var_dump($map);exit;
        $server_id = (int) $param['server_id'] ?? 0;
        if($server_id > 0){
            $map['s.id'] = $server_id;
        }

        if (!empty($param['game_id'])) {
            // 获取渠道申请通过的游戏得到游戏id
            $map['s.game_id'] = $param['game_id'];
        }else{
            // 获取渠道申请通过的游戏得到游戏id
            $already_applied_game_id = get_promote_apply_game_id($promote_id);
            $map['s.game_id'] = ['in', $already_applied_game_id];
        }

        $row = (int) $param['row'] ?: ($this -> request -> param('row') ?: config('paginate.list_rows'));//每页数量

        $server_info = $serverModel->getserver_page($map,$row,$order="start_time asc")->each(function($item,$key){

            $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
        });

        $data['data'] = $server_info->toarray()['data'];
        $data['promote_id'] = $promote_id;
        // $this->assign('promote_id', $promote_id);
        return json($data);
        // return $this->fetch();
    }

    // 根据游戏名称获取区服列表

    public function get_server(Request $request)
    {
        $game_id = $request->param('game_id');
        $result = ['code' => 1, 'msg' => '', 'data' => []];
        if (empty($game_id)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        $where = [];
        $where['game_id'] = $game_id;
        $lists = Db ::table('tab_game_server') -> field('id,server_name,server_num') -> where($where) -> order('id desc') -> select();
        if (!empty($lists)) {
            $result['data'] = $lists;
        }
        // return $result;
        return json($result);
    }
    // 渠道刚登录进入首页,每天展示一次资讯信息
    protected function getFirstArticle()
    {
        $promote_id = PID;
        // 获取当前渠道所申请的游戏
        $promoteGameInfo = Db::table('tab_promote_apply')->alias('pa')
            ->field('g.id as game_gid,g.relation_game_id,g.promote_ids,g.test_game_status,g.promote_ids2,g.only_for_promote,g.game_status,g.apply_status,pa.promote_id')
            ->where(['g.test_game_status'=>0, 'g.game_status'=>['=', 1], 'g.apply_status'=>1, 'pa.promote_id'=>$promote_id])
            ->join(['tab_game' => 'g'], 'g.id=pa.game_id','right')
            ->select()->toArray();

        $promoteGameIds = [];
        if(!empty($promoteGameInfo)){
            foreach($promoteGameInfo as $k10=>$v10){
                if($v10['only_for_promote'] == 1){
                    $forbid2_promote_ids = explode(',', $v10['promote_ids2']);
                    if(!in_array($promote_id, $forbid2_promote_ids)){
                        unset($promoteGameInfo[$k10]);
                    }
                }
            }
            foreach($promoteGameInfo as $k11=>$v11){
                $promoteGameIds[] = $v11['relation_game_id'];
            }

        }
        // echo json_encode($promoteGameIds);
        // // // var_dump();
        // exit;
        // 如果未指定游戏的 将游戏 id = 0 的条件拼接到渠道申请的游戏列表中
        $promoteGameIds[] = 0;

        $portalPostModel = new PortalPostModel();
        $gonggaoInfo = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                // ->where(['cp.category_id'=>['in',[2,4]],'p.delete_time'=>0,'website'=>17])
                ->where(['cp.category_id'=>['in',[2,4]],'p.delete_time'=>0, 'website'=>8])
                ->where(['p.game_id'=>['in',$promoteGameIds]])
                ->where(['p.post_status'=>1])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','right')
                ->order('p.sort desc,p.id desc')
                ->limit(0,2)
                ->select()->toArray();
        // var_dump($gonggaoInfo);exit;
        $this->assign('gonggao_info', $gonggaoInfo);
        //每日显示一次

    }

    // 获取文章的上一篇下一篇 by wjd 2021-7-13 10:13:34
    public function getPreviousNextArticle(Request $request)
    {
        $param = $request->param();
        $promote_id = PID;
        // 获取当前渠道所申请的游戏
        $promoteGameInfo = Db::table('tab_promote_apply')->alias('pa')
            ->field('g.id as game_gid,g.relation_game_id,g.promote_ids,g.test_game_status,g.promote_ids2,g.only_for_promote,g.game_status,g.apply_status,pa.promote_id')
            ->where(['g.test_game_status'=>0, 'g.game_status'=>['=', 1], 'g.apply_status'=>1, 'pa.promote_id'=>$promote_id])
            ->join(['tab_game' => 'g'], 'g.id=pa.game_id','right')
            ->select()->toArray();

        $promoteGameIds = [];
        if(!empty($promoteGameInfo)){
            foreach($promoteGameInfo as $k10=>$v10){
                if($v10['only_for_promote'] == 1){
                    $forbid2_promote_ids = explode(',', $v10['promote_ids2']);
                    if(!in_array($promote_id, $forbid2_promote_ids)){
                        unset($promoteGameInfo[$k10]);
                    }
                }
            }
            foreach($promoteGameInfo as $k11=>$v11){
                $promoteGameIds[] = $v11['relation_game_id'];
            }

        }
        // 如果未指定游戏的 将游戏 id = 0 的条件拼接到渠道申请的游戏列表中
        $promoteGameIds[] = 0;

        $post_id = $param['post_id'];
        $portalPostModel = new PortalPostModel();
        $d_gonggaoInfo = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                ->where(['p.delete_time'=>0, 'p.id'=>$post_id])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','right')
                // ->order('p.sort desc p.id desc')
                ->find();
        $all_gonggao = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                ->where(['cp.category_id'=>['in',[2,4]],'p.delete_time'=>0, 'website'=>8])
                ->where(['p.game_id'=>['in',$promoteGameIds]])
                ->where(['p.post_status'=>1])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','right')
                ->order('p.sort desc,p.id desc')
                ->select()->toArray();
        // 去重
        $tmp_post_ids22 = [];
        foreach($all_gonggao as $k2=>$v2){
            if(in_array($v2['id'], $tmp_post_ids22)){
                unset($all_gonggao[$k2]);
            }
            $tmp_post_ids22[] = $v2['id'];
        }
        $all_gonggao = array_values($all_gonggao);
        foreach($all_gonggao as $k=>$v){
            if ($d_gonggaoInfo['id'] == $v['id']) {
                //获取下一篇
                if (isset($all_gonggao[$k+1])) {
                    $next_id = $all_gonggao[$k+1]['id'];
                } else {
                    $next_id = '';
                }
                //获取上一篇
                if (isset($all_gonggao[$k-1])) {
                    $pre_id = $all_gonggao[$k-1]['id'];
                } else {
                    $pre_id = '';
                }
            }
        }

        if($next_id != ''){
            $next_info = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail')
                ->where(['p.id'=>$next_id])
                ->find();
        }else{
            $next_info = '';
        }

        if($pre_id != ''){
            $pre_info = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail')
                ->where(['p.id'=>$pre_id])
                ->find();
        }else{
            $pre_info = '';
        }
        $mixData = [
            'd_gonggaoInfo'=>$d_gonggaoInfo,
            'next_gonggaoInfo'=>$pre_info,
            'previous_gonggaoInfo'=>$next_info,
        ];
        return json(['code'=>1, 'msg'=>'获取成功', 'data'=>$mixData]);

    }

    /**
     * 设置修改渠道开启或关闭短信通知状态
     * by:byh 2021-7-15 21:16:15
     */
    public function set_sms_notice_status()
    {
        //判断后台总开关是否开启
        $zong_switch = cmf_get_option('admin_set')['promote_sms_notice_switch']??0;
        if($zong_switch != 1) return json(['code'=>0, 'msg'=>'不可操作!']);
        //查询渠道手机号是否存在和渠道开关状态
        $promote = Db::table('tab_promote')->field('mobile_phone,sms_notice_switch')->where('id',PID)->find();
        if(empty($promote['mobile_phone']))  return json(['code'=>0, 'msg'=>'账号未绑定手机号!']);
        if($promote['sms_notice_switch'] == 1){
            $change['sms_notice_switch'] = 0;
            $msg = "短信通知关闭成功!";
        }else{
            $change['sms_notice_switch'] = 1;
            $msg = "短信通知开通成功!";
        }
        $res = Db::table('tab_promote')->where('id',PID)->update($change);
        if(!$res){
            return json(['code'=>0, 'msg'=>'操作失败!']);
        }
        return json(['code'=>1, 'msg'=>$msg]);

    }

    /**
     * 玩家福利/绑币首充续充折扣渠道自定义配置列表
     */
    public function user_bind_discount()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('index'));
        return $this->fetch();
    }
    /**
     * js获取绑币首充续充折扣数据
     * by:byh 2021-9-1 19:12:53
     */
    public function get_user_bind_discount()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)return json(['code'=>0,'msg'=>'当前渠道不可操作']);
        $request = $this->request->param();
        $logic = new PromoteLogic();
        $data = $logic->get_user_bind_discount_list(PID,$request);
        return json($data);
    }
    /**
     * 渠道新增自定义绑币首充续充折扣数据
     * by:byh 2021-9-1 20:23:37
     */
    public function add_user_bind_discount()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('index'));

        if($this->request->isPost()){
            $data=  $this->request->param();
            if(empty($data))$this->error('参数错误');
            if(empty($data['game_id']))$this->error('请选择游戏');
            $data['first_discount'] = !empty($data['first_discount'])?$data['first_discount']:10;
            $data['continue_discount'] = !empty($data['continue_discount'])?$data['continue_discount']:10;
            $logic = new PromoteLogic();
            //判断折扣配置是否正常
            $gf_limit = $logic->get_promote_game_user_limit_discount($data['game_id'],PID);
            if($data['first_discount']<$gf_limit || $data['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            //加入正常开启参数
            $data['first_status'] = 1;
            $data['continue_status'] = 1;
            $result = $logic->add_user_bind_discount($data);
            switch ($result){
                case -1:
                    $this->error('该游戏已设置折扣');
                    break;
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('添加成功',url('user_bind_discount'));
                    break;
                default:
                    $this->error('添加失败');
            }
        }

        return $this->fetch();
    }

    /**
     * 编辑渠道自定义玩家绑币充值-首充续充
     * by:byh 2021-9-2 09:18:22
     */
    public function edit_user_bind_discount()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));

        $id = $this -> request -> param('id',0,'intval');
        $logic = new PromoteLogic();
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            if(empty($param['game_id'])|| empty($param['id']))$this->error('数据错误');
            $param['first_discount'] = !empty($param['first_discount'])?$param['first_discount']:10;
            $param['continue_discount'] = !empty($param['continue_discount'])?$param['continue_discount']:10;
            //判断折扣配置是否正常

            $gf_limit = $logic->get_promote_game_user_limit_discount($param['game_id'],PID);
            if($param['first_discount']<$gf_limit || $param['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            //加入正常开启参数
            $result = $logic->edit_user_bind_discount($param);
            switch ($result){
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('编辑成功',url('user_bind_discount'));
                    break;
                default:
                    $this->error('编辑失败');
            }
        }
        if(empty($id))$this->error('参数错误');
        $info = $logic->get_user_bind_discount_detail($id);//编辑时的原数据详情
        $this -> assign('info', $info);
        return $this -> fetch();
    }


    /**
     * 首充续充折扣渠道自定义配置列表页面
     */
    public function user_welfare()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('index'));
        return $this->fetch();
    }
    /**
     * js获取渠道自定义游戏首冲续充配置列表
     * by:byh 2021-9-2 09:37:19
     */
    public function get_user_welfare_list()
    {
        $request = $this->request->param();

        $logic = new PromoteLogic();
        $data = $logic->get_user_welfare_list(PID,$request);
        return json($data);
    }
    /**
     * 玩家福利-首充续充新增
     * by:byh 2021-9-2 10:04:25
     */
    public function add_user_welfare()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));
        if($this->request->isPost()){
            $data=  $this->request->param();
            if(empty($data))$this->error('参数错误');
            if(empty($data['game_id']))$this->error('请选择游戏');
            $data['first_discount'] = !empty($data['first_discount'])?$data['first_discount']:10;
            $data['continue_discount'] = !empty($data['continue_discount'])?$data['continue_discount']:10;
            $logic = new PromoteLogic();
            //判断折扣配置是否正常
            $gf_limit = $logic->get_promote_game_user_limit_discount($data['game_id'],PID);
            if($data['first_discount']<$gf_limit || $data['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            //加入正常开启参数
            $data['first_status'] = 1;
            $data['continue_status'] = 1;
            $result = $logic->add_user_welfare($data);
            switch ($result){
                case -1:
                    $this->error('该游戏已设置折扣');
                    break;
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('添加成功',url('user_welfare'));
                    break;
                default:
                    $this->error('添加失败');
            }
        }
        return $this->fetch();
    }
    /**
     * 编辑渠道自定义玩家福利-首充续充
     * by:byh 2021-9-2 10:04:29
     */
    public function edit_user_welfare()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));

        $id = $this -> request -> param('id',0,'intval');
        $logic = new PromoteLogic();
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            if(empty($param['game_id'])|| empty($param['id']))$this->error('数据错误');
            $param['first_discount'] = !empty($param['first_discount'])?$param['first_discount']:10;
            $param['continue_discount'] = !empty($param['continue_discount'])?$param['continue_discount']:10;
            //判断折扣配置是否正常
            $gf_limit = $logic->get_promote_game_user_limit_discount($param['game_id'],PID);
            if($param['first_discount']<$gf_limit || $param['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            $result = $logic->edit_user_welfare($param);
            switch ($result){
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('编辑成功',url('user_welfare'));
                    break;
                default:
                    $this->error('编辑失败');
            }
        }
        if(empty($id))$this->error('参数错误');
        $info = $logic->get_user_welfare_detail($id);//编辑时的原数据详情
        $this -> assign('info', $info);
        return $this -> fetch();
    }
    /**
     * 又新增-编辑页面显示选择游戏的官方配置的最低折扣
     * by:byh 2021-9-16 11:50:23
     */
    public function getGameGfLimitDiscount($game_id=0)
    {
        if(empty($game_id)){
            return json(['gf_limit_discount'=>'10.00']);
        }
        //
        $logic = new PromoteLogic();
        $gf_limit = $logic->get_promote_game_user_limit_discount($game_id,PID);
        return json(['gf_limit_discount'=>$gf_limit]);
    }

}
