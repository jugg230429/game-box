<?php
namespace app\sdksimplify\Controller;

use app\issue\model\UserModel;
use think\Db;

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
            echo json_encode(array('code'=>1003,'msg'=>'账号信息丢失'));exit();
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
        }
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
}
