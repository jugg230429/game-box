<?php
namespace app\sdk\Controller;

use app\issue\model\UserModel;
use app\member\logic\UserLogic;
use app\member\model\UserModel as ModelUserModel;
use app\member\model\UserPlayModel;
use think\Db;
use think\facade\Cache;

/**
 * 支付游戏回调控制器
 * @author 小纯洁
 */
class LoginNotifyController{

    /**
     *服务器登录验证
     */
    public function login_verify(){
        $request = request()->post();
        if (empty($request['user_id'])||empty($request['token'])) {
            $request = request()->get();
            if (empty($request['user_id'])||empty($request['token'])) {
                echo json_encode(array('code'=>1003,'msg'=>'账号信息丢失'));exit();
            }
        }
        $token = json_decode(simple_decode($request['token']),true);
        if(!empty($token)&&$token['ff_platform']!=''){//分发
            $user_id = substr($request['user_id'],4);//分发id拼接su_
            if($user_id!=$token['user_id']){
                echo json_encode(array('code'=>1002,'msg'=>'token验证失败'));exit();
            }
            $mUser = new UserModel();
            $mUser->where('id','=',$user_id);
            $mUser->where('platform_id','=',$token['ff_platform']);
            $mUser->where('open_user_id','=',$token['open_user_id']);
            $mUser->field('id');
            $mUserData = $mUser->find();
            if(!empty($mUserData)){
                $mUserData->user_id = $request['user_id'];
                echo json_encode(array('code'=>200,'msg'=>'请求成功','data'=>$mUserData->toarray()));exit();
            }else{
                echo json_encode(array('code'=>1002,'msg'=>'token验证失败'));exit();
            }
        }
        $user = Db::table('tab_user')->field('id,account,token,real_name,idcard,puid')->find($request['user_id']);
        if($user['puid'] > 0){
            $puser = Db::table('tab_user')->field('real_name,idcard')->find($user['puid']);
            $user['real_name'] = $puser['real_name'];
            $user['idcard'] = $puser['idcard'];
            $rmap['user_id'] = $user['puid'];
        }else{
            $rmap['user_id'] = $request['user_id'];
        }
        $rmap['pi'] = array('neq','');
        $age_cord = Db::table('tab_user_age_record')->field('pi')->where($rmap)->find();
        $data['pi'] = empty($age_cord) ? '' :$age_cord['pi'];
        $data['user_id'] = $user['id'];
        $data['real_name'] = $user['real_name'];
        $data['age'] = $this->get_age($user['idcard']);
        $data['oversea'] = false;
        $data['id_type'] = 0;
        $data['Id'] = $user['idcard'];
        $data['verify_status'] = 1;
        $data['birthday'] = $this->get_birthday_by_idcard($user['idcard'],'Ymd');;
        if(password_verify($user['token'],$request['token'])){
            echo json_encode(array('code'=>200,'msg'=>'请求成功','data'=>$data));exit();
        }else{
            echo json_encode(array('code'=>1002,'msg'=>'token验证失败'));exit();
        }
    }


       /**
     *旧post-服务器登录验证
     */
    public function login_verify_new(){
        $request = request()->post();
        if (empty($request['user_id'])||empty($request['token'])) {
            $request = request()->get();
            if (empty($request['user_id'])||empty($request['token'])) {
                echo json_encode(array('code'=>1003,'msg'=>'账号信息丢失'));exit();
            }
        }
        $token = json_decode(simple_decode($request['token']),true);
        if(!empty($token)&&$token['ff_platform']!=''){//分发
            $user_id = substr($request['user_id'],4);//分发id拼接su_
            if($user_id!=$token['user_id']){
                echo json_encode(array('code'=>1002,'msg'=>'token验证失败'));exit();
            }
            $mUser = new UserModel();
            $mUser->where('id','=',$user_id);
            $mUser->where('platform_id','=',$token['ff_platform']);
            $mUser->where('open_user_id','=',$token['open_user_id']);
            $mUser->field('id');
            $mUserData = $mUser->find();
            if(!empty($mUserData)){
                $mUserData->user_id = $request['user_id'];
                echo json_encode(array('code'=>200,'msg'=>'请求成功','data'=>$mUserData->toarray()));exit();
            }else{
                echo json_encode(array('code'=>1002,'msg'=>'token验证失败'));exit();
            }
        }
        $user = Db::table('tab_user')->field('id,account,token,real_name,idcard,puid')->find($request['user_id']);
        if($user['puid'] > 0){
            $puser = Db::table('tab_user')->field('real_name,idcard')->find($user['puid']);
            $user['real_name'] = $puser['real_name'];
            $user['idcard'] = $puser['idcard'];
            $rmap['user_id'] = $user['puid'];
        }else{
            $rmap['user_id'] = $request['user_id'];
        }
        $rmap['pi'] = array('neq','');
        $age_cord = Db::table('tab_user_age_record')->field('pi')->where($rmap)->find();
        $data['pi'] = empty($age_cord) ? '' :$age_cord['pi'];
        $data['user_id'] = $user['id'];
        $data['real_name'] = $user['real_name'];
        $data['age'] = $this->get_age($user['idcard']);
        $data['oversea'] = false;
        $data['id_type'] = 0;
        $data['Id'] = $user['idcard'];
        $data['verify_status'] = 1;
        $data['birthday'] = $this->get_birthday_by_idcard($user['idcard'],'Ymd');;
        if(password_verify($user['token'],$request['token'])){
            echo json_encode(array('code'=>200,'msg'=>'请求成功','data'=>$data));exit();
        }else{
            echo json_encode(array('code'=>1002,'msg'=>'token验证失败'));exit();
        }
    }


    /**
     * @函数或方法说明
     * @获取年龄
     * @param string $idcard
     *
     * @return int
     *
     * @author: 郭家屯
     * @since: 2019/12/10 15:17
     */
    protected function get_age($idcard='')
    {
        if(empty($idcard)){
            return 0;
        }
        $birthday = $this->get_birthday_by_idcard($idcard,'Y-m-d');
        $year = $this->get_age_diff(date('Y-m-d'),$birthday);
        return $year;
    }

    protected function get_age_diff($start, $end = FALSE)
    {
        $time1 = explode('-',$start);
        $time2 = explode('-',$end);
        $year = $time1[0] - $time2[0];
        if($time1[1] < $time2[1]){
            $year = $year - 1;
        }elseif($time1[1] == $time2[1] && $time1[2] < $time2[2]){
            $year = $year - 1;
        }
        return $year;
    }

    /**
     * @从身份证号中取生日
     *
     * @author: zsl
     * @since: 2019/9/18 17:16
     */
    protected function get_birthday_by_idcard($idNum,$formart="Ymd")
    {
        if (empty($idNum)) {
            return '';
        }
        $birth = date($formart, strtotime(substr($idNum, 6, 8)));
        return $birth;

    }


    public function insertUser(){
        exit("happy");
        $ids = request()->get('ids');
        $userIds = explode("\n",$ids);
        if(count($userIds) < 0){
           exit('新增用户信息为空');
        }
        foreach($userIds as $userId){
            $usermodel = new ModelUserModel();
            //判断是否存在
            $existUser = Db::table('tab_user')->where('id',$userId)->find();
            if($existUser){
                echo "已存在用户:$userId". "\n";
                continue;
            }
            $data = [];
            $data['id'] = $userId;
            do {
                $data['account'] = 'yk_' . sp_random_string();
                $account = $usermodel->field('id')->where(['account' => $data['account']])->find();
            } while (!empty($account));
            $data['password'] = 'a123456';
            $data['nickname'] = $data['account'];
            $data['unionid'] = null;
            $data['game_id'] = 20;
            $data['head_img'] = '';
            $data['game_name'] = '梦幻宝可梦(安卓版)';
            $data['promote_id'] =  0;
            $data['register_way'] = 1;
            $data['register_type'] = 0;
            $data['type'] = 1;
            $data['equipment_num'] = '';
            $data['device_name'] = '';
            $result = $usermodel->register($data,'sdk');
            $this->add_user_play($result, ['game_id'=>20],1);

            //创建小号
            $userlogic = new UserLogic();
            $userlogic->add_small($result,$data['game_id'],$result['account'].'_@_1',$result['account'].'@小号1',$data['sdk_version']); 
            
            echo "成功创建用户:$userId". "\n";
        } 
       
        exit("退出");
    }

    private function add_user_play($user = array(), $request = array(),$register = 0)
    {
        //查询是否存在账号
        $game = Cache::get('sdk_game_data'.$request['game_id']);
        $map["game_id"] = $request["game_id"];
        $map["user_id"] = $user["id"];
        $model = new UserPlayModel();
        $model->login($map,$user,$game,$register);
    }
}
