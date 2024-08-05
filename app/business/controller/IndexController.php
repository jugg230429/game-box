<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\business\controller;

use think\Db;
use app\business\model\PromoteBusinessModel;
use app\common\logic\CaptionLogic;
use app\promote\logic\PromoteLogic;
use app\promote\model\PromoteModel;
use app\common\controller\BaseHomeController;
use app\datareport\event\PromoteController as Promote;
class IndexController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index(){
        $this->redirect(url('index/login'));
    }
    /**
     * 登录
     * @return mixed
     */
    public function login()
    {
        if($this->request->isPost()){
            $model = new PromoteBusinessModel;
            $data = $this->request->param();
            $account = $data['account'];
            $password = $data['pwd'];
            if (empty($account) || empty($password)) {
                $this->error('账号或密码不能为空');
            }
            if (empty($data['verify_tag']) || empty($data['verify_token'])) {
                $this->error('请先进行智能验证');
            }else{
                $res = (new CaptionLogic()) -> checkToken($data['verify_token'], $data['verify_tag']);
                if ($res['code']!=200) {
                    $this->error($res['info']);
                }
            }
            $res = $model->login($data);
            if ($res > 0) {
                $this->success('登录成功', url('login'));
            } elseif ($res == -1) {
                $this->error('账号不存在或被禁用', url('login'));
            } elseif ($res == -2) {
                $this->error('密码错误', url('login'));
            }
        }else{
            $bid = is_b_login();
            if(!$bid){
                return $this->fetch('login');
            }else{
                $this->redirect(url('index/data_summary'));
            }
        }
    }

    //退出
    public function logout()
    {
        session('BID', null);
        session('BNAME', null);
        return json(['code'=>200]);
    }
    public function data_summary()
    {
        $request = $this->request->param();
        //时间
        $start_time = $this->request->param('begtime', '');
        $end_time = $this->request->param('endtime', '');
        if ($start_time && $end_time) {
            $date = $start_time. '至' . $end_time;
        } elseif ($end_time) {
            $date = '2019-08-01' . '至' . $end_time;
        } elseif ($start_time) {
            $date = $start_time. '至' . date("Y-m-d", strtotime("-1 day"));
        }else{
            $date = '2019-08-01' . '至' . date("Y-m-d", strtotime("-1 day"));
        }
        $dateexp = explode('至', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $promote_id = $request['promote_id'];
        if(!$promote_id){
            $promote_ids = array_column(get_busier_promote_list(['busier_id'=>session('BID')]),'id');
            if(empty($promote_ids)){
                return $this->fetch('data_summary');
            }else{
                $promote_ids = Db::table('tab_promote')->where(['promote_level'=>1,'parent_id'=>0,'id'=>['in',$promote_ids]])->column('id');
            }
        }else{
            $promote_ids[] = $promote_id;
        }

        $promoteevent = new Promote();
        $new_data = $promoteevent->promote_data($starttime, $endtime, $promote_ids,"datareporttoppid_");
        $list_data = $new_data['data'];
        $promotelogic = new PromoteLogic();
        $all_register_ip = [];
        foreach ($list_data as $k=>$v){
            $register_ip = $promotelogic->get_promote_user_info(['id'=>['in',$v['new_register_user']],'puid'=>0],'register_ip');
            $register_ip = array_filter(array_unique(array_column($register_ip,'register_ip')));
            $list_data[$k]['register_ip'] = explode(',',$register_ip);
            $list_data[$k]['count_register_ip'] = count($register_ip);
            $all_register_ip = array_merge($all_register_ip,$register_ip);
        }
        $total_data = $new_data['total_data'];
        $total_data['count_register_ip'] = count(array_filter(array_unique($all_register_ip)));
        $data_cache = serialize([$list_data,$total_data]);

        file_put_contents(dirname(dirname(__FILE__)).'/data/data_summary_'.BID,$data_cache);
        //排序
        $list_data = parent::array_order($list_data, $request['sort_type']?:'', $request['sort']);
        parent::array_page($list_data, $request);
        $this->assign('total_data',$total_data);
        return $this->fetch();
    }
}
