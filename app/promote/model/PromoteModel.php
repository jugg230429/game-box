<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;

class PromoteModel extends Model
{

    protected $table = 'tab_promote';

    protected $autoWriteTimestamp = true;

    /**
     * [登录接口]
     * @param $data
     * @param int $type
     * @author yyh
     */
    public function login($data)
    {
        $user = $this->field('id,account,password,status,parent_id')->where('account', $data['account'])->find();
        if (empty($user) || $user['status'] != 1) {
            return -1;//账户不存在 或 账户被禁用
        }
        if (!xigu_compare_password($data['password'], $user['password'])) {
            return -2;//密码错误
        }
        $this->updateLogin($user); //更新用户登录信息
        return $user['id'];
    }

    /**
     * [登录接口]
     * @param $data
     * @param int $type
     * @author yyh
     */
    public function register($data)
    {
        $add['account'] = $data['account'];
        $add['password'] = cmf_password($data['password']);
        $add['real_name'] = $data['real_name']?:'';
        $add['mobile_phone'] = $data['mobile_phone']?:'';
        $add['register_type'] = $data['register_type']?:0;
        $promote_auto_audit = \think\Db::table('tab_promote_config')->field('status')->where(['name' => 'promote_auto_audit'])->find();
        $add['status'] = $promote_auto_audit['status'] ? 1 : 0;
        $add['create_time'] = time();
        $res = $this->insertGetId($add);
        if ($res != false) {
            return $res;
        } else {
            return false;
        }
    }

    /**
     * @函数或方法说明
     *添加子渠道
     * @author: 郭家屯
     * @since: 2019/8/10 10:33
     */
    public function add_child($data=[]){
        $parentdata = $this->field('busier_id')->find($data['parent_id'])->toArray();
        $add['account'] = $data['account'];
        $add['nickname'] = $data['account'];
        $add['password'] = cmf_password($data['password']);
        $add['real_name'] = $data['real_name']?:'';
        $add['parent_id'] = $data['parent_id'];
        $add['parent_name'] = $data['parent_name'];
        $add['pattern'] = $data['pattern'];
        $add['promote_level'] = $data['promote_level']?:1;
        $add['top_promote_id'] = $data['top_promote_id']?:0;
        $add['status'] = 1;
        $add['busier_id'] = $parentdata['busier_id'];
        $add['create_time'] = time();
        $add['second_pwd'] = $data['second_pwd'] ? cmf_password($data['second_pwd']) : '';
        $add['mobile_phone'] = $data['mobile_phone']?:'';
        $add['email'] = $data['email']?:'';
        $add['mark1'] = $data['mark1']?:'';
        $res = $this->insertGetId($add);
        if ($res != false) {
            return $res;
        } else {
            return false;
        }
    }

    /**
     * [更新用户最后登录信息]
     * @param $id
     * @author yyh
     */
    private function updateLogin($user)
    {
        $save['last_login_time'] = time();
        $save['latest_session_id'] = $this->getSessionId();
        $this->where('id', $user['id'])->update($save);
        session('PID', $user['id']);
        session('PNAME', $user['account']);
        session('PARENT_ID', $user['parent_id']);
    }
    // 获取当前的session_id
    protected function getSessionId()
    {
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        return session_id();
    }
}
