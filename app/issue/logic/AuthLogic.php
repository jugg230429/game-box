<?php

namespace app\issue\logic;

use app\issue\model\OpenUserModel;
use app\issue\validate\OpenUserValidate;

class AuthLogic
{


    /**
     * @获取认证信息
     *
     * @author: zsl
     * @since: 2020/7/20 16:59
     */
    public function getInfo($param)
    {
        $mOpenUser = new OpenUserModel();
        $field = "id,account,company_name,linkman,job,mobile,qq,wechat,address,business_license_img,wenwangwen_img,icp_img,auth_status,
        bank_phone,bank_card,bank_name,bank_account,province,city,county,account_openin,check_people,check_people_qq,check_people_phone,alipay_account,alipay_realname,account_type";
        $where = [];
        $where['id'] = $param['id'];
        $info = $mOpenUser -> field($field) -> where($where) -> find();
        return $info;
    }


    /**
     * @保存认证信息
     *
     * @author: zsl
     * @since: 2020/7/20 16:37
     */
    public function saveInfo($param)
    {
        $result = ['code' => 1, 'msg' => '提交成功，请等待审核', 'data' => []];
        $vOpenUser = new OpenUserValidate();
        if (!$vOpenUser -> scene('auth') -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vOpenUser -> getError();
            return $result;
        }
        $mOpenUser = new OpenUserModel();
        $param['id'] = OID;
        $res = $mOpenUser -> saveAuthInfo($param);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '提交失败';
            return $result;
        }
        return $result;
    }


    /**
     * @函数或方法说明
     *
     * @author: zsl
     * @since: 2020/7/21 9:15
     */
    public function saveBankInfo($param)
    {
        $result = ['code' => 1, 'msg' => '保存成功', 'data' => []];
        $vOpenUser = new OpenUserValidate();
        if (!$vOpenUser -> scene($param['account_type']) -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vOpenUser -> getError();
            return $result;
        }
        $mOpenUser = new OpenUserModel();
        $param['id'] = OID;
        $res = $mOpenUser -> saveBankInfo($param);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '保存失败';
            return $result;
        }
        return $result;
    }


    /**
     * @修改密码
     *
     * @author: zsl
     * @since: 2020/7/20 19:08
     */
    public function changePassword($param)
    {
        $result = ['code' => 1, 'msg' => '修改成功', 'data' => []];
        $vOpenUser = new OpenUserValidate();
        if (!$vOpenUser -> scene('change_pass') -> check($param)) {
            $result['code'] = 0;
            $result['msg'] = $vOpenUser -> getError();
            return $result;
        }
        $mOpenUser = new OpenUserModel();
        $password = $mOpenUser -> where(['id' => OID]) -> value('password');
        if (!xigu_compare_password($param['password'], $password)) {
            $result['code'] = 0;
            $result['msg'] = '原密码错误';
            return $result;
        }
        $res = $mOpenUser -> save(['password' => cmf_password($param['new_password'])], ['id' => OID]);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '修改失败';
            return $result;
        }
        return $result;
    }


}