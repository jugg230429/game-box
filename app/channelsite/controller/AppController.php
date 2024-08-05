<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yyh
// +----------------------------------------------------------------------
namespace app\channelsite\controller;

use app\promote\model\PromoteappModel;
use app\promote\model\PromoteunionModel;
use app\promote\validate\PromoteAppValidate;
use app\site\model\AppModel;
use think\Db;
use think\Request;

class AppController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
    }

    /**
     * @函数或方法说明
     * @APP申请列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/24 16:38
     */
    public function app_lists()
    {
        $model = new AppModel();
        $data = $model->getPromotelist(PID);
        if(!empty($data)){
            foreach($data as $k1=>$v2){
                // 对于自定义包的大小 只修改ios的版本
                if($v2['app_version'] == 2 && $v2['is_user_define'] == 1){
                    $data[$k1]['file_size'] = $v2['file_size2'];
                }
            }
        }
        $this -> assign('data_lists', $data);
        // 获取联盟站配置
        $mPromoteUnion = new PromoteunionModel();
        $union_set = $mPromoteUnion -> field('id,union_id,union_set') -> where(['union_id' => PID]) -> value('union_set');
        $this -> assign('union_set', json_decode($union_set, true));

        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @申请链接
     * @author: 郭家屯
     * @since: 2020/2/24 17:23
     */
    public function apply()
    {
        if($this->request->isPost()){
            $app_id = $this->request->param('app_id');
            $param = $this->request->param();
            $model = new PromoteappModel();
            $app_apply = $model->where('app_id',$app_id)->where('promote_id',PID)->find();
            if($app_apply){
                $this->error('您已申请过了');
            }
            // 安卓自定义
            if($app_id == 1){
                if ($param['is_user_define'] == '1') {
                    //自定义渠道
                    $vPromoteApp = new PromoteAppValidate();
                    if (!$vPromoteApp -> scene('user_define') -> check($param)) {
                        $this -> error($vPromoteApp -> getError());
                    }
                }
            }
            // 苹果盒子包自定义
            if($app_id == 2){
                if ($param['is_user_define'] == '1') {
                    //自定义渠道

                }
            }
            $result = $model -> apply($app_id, PID, $param);
            if($result){
                $config = Db::table('tab_promote_config')->field('status')->where(array('name' => 'promote_auto_audit_app'))->find();
                if($config['status'] == 1){
                    $this->success('申请成功');
                }else{
                    $this->success('申请提交成功，请等待审核');
                }
            }else{
                $this->error('申请失败');
            }
        }else{
            $this->error('申请失败');
        }
    }

    // 一级渠道下面的子渠道游戏盒子 -- wjd
    public function child_app_lists(Request $request){
        $req = $request->param();

        $row = $req['row'] ?? 10;

        $promote_id = PID;
        $map['id'] = $promote_id;
        $promote_info = Db::table('tab_promote')->where($map)->find();
        // 判断只有 一级渠道
        $allow_check_subbox = $promote_info['allow_check_subbox'] ?? 0;  // 是否允许渠道审核子渠道盒子,0:不允许, 1:允许'
        if(PID_LEVEL != 1 || $allow_check_subbox != 1){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/channelsite';
            // header($url);
            header('Location: '.$url);
            exit;
        }
        // 查询当前渠道下面的所有未禁用的渠道id  parent_id or top_promote_id
        $map1 = [];
        $promote_id = (int) $promote_id;
        $sub_promote_ids = Db::table('tab_promote')
            ->where("(parent_id = $promote_id OR top_promote_id = $promote_id) AND status = 1")
            // ->where(['parent_id'=>$promote_id])
            // ->whereOr(['top_promote_id'=>$promote_id])
            ->field('id')
            ->order('id desc')
            ->select();
        // 查询当前渠道下面的子渠道游戏盒子
        $sub_promote_ids2 = [];
        foreach($sub_promote_ids as $k1=>$v1){
            $sub_promote_ids2[] = $v1['id'];
        }
        $where2 = [];
        $promote_id2 = $req['promote_id2'] ?? 0;
        $status2 = $req['status'] ?? '';
        $is_user_define2 = $req['is_user_define'] ?? '';
        if(!empty($promote_id2)){
            $where2['promote_id'] = $promote_id2;
        }
        if(!empty($status2)){
            $where2['pa.status'] = $status2;
        }
        if($status2 === '0'){
            $where2['pa.status'] = $status2;
        }
        if(!empty($is_user_define2)){
            $where2['pa.is_user_define'] = $is_user_define2;
        }
        if($is_user_define2 === '0'){
            $where2['pa.is_user_define'] = $is_user_define2;
        }

        // 查询子渠道包
        $subbox = Db::table('tab_promote_app')
            ->alias('pa')
            ->field('pa.*,p.id as p_id,p.account as p_account,a.file_size,a.version,a.file_url')
            ->join(['tab_promote'=>'p'],'p.id=pa.promote_id','left')
            ->join(['tab_app'=>'a'],'a.id=pa.app_id','left')
            ->where(['promote_id'=>['in',$sub_promote_ids2]])
            ->order('id desc')
            ->where($where2)
            ->paginate($row, false, ['query' => $req])
            ->each(function($item, $key){
                if($item['app_version'] == 2 && $item['is_user_define'] == 1){
                    $item['file_size'] = $item['file_size2'];
                }

                return $item;
            });

        // return json($subbox);
        $page = $subbox->render();
        $this->assign("data_lists", $subbox);
        $this->assign("page", $page);

        return $this->fetch();
        // var_dump($sub_promote_ids2);
        // var_dump(empty($sub_promote_ids2));
        // var_dump(empty($sub_promote_ids));
        // var_dump($sub_promote_ids);exit;
        // return $this->fetch();
    }
    // 审核子渠道游戏盒子
    public function check_subbox(Request $request){

    }

    // 审核子渠道游戏盒子
    public function changeAppStatus()
    {
        $ids2 = [];
        $data = $this->request->param();
        $ids = $data['ids'];
        if (!is_array($ids)) {
            $ids = explode(',',$ids);
            foreach($ids as $k1=>$v1){
                if($v1 != 0){
                    $ids2[] = $v1;
                }
            }
            // $id = $ids;
            // $ids = [];
            // $ids[] = $id;
        }
        if (empty($ids2)) $this->error('请选择要操作的数据');

        $save['dispose_id'] = cmf_get_current_admin_id();
        $save['dispose_time'] = time();
        $save['status'] = 1;
        $model = new PromoteappModel();
        Db::startTrans();
        foreach ($ids2 as $key => $value) {
            $map['id'] = $value;
            $apply = $model->field('promote_id,app_id,type')->where($map)->find();
            if($apply['type'] == 1){  // 苹果超级签
                $app = Db::table('tab_app')->where('id',$apply['app_id'])->find();
                $save['enable_status'] = 1;  // 打包成功
                $info['MCHPromoteID'] = (string)$apply['promote_id'];
                $url = $app['file_url'] . '?appenddata=' . urlencode(json_encode($info));
                $save['dow_url'] = $url;
            }else{
                $save['enable_status'] = 2;  // 准备打包
            }
            $result = $model->where($map)->update($save);
            if (!$result) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }
}
