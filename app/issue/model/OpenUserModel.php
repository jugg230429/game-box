<?php

namespace app\issue\model;

use think\Model;

class OpenUserModel extends Model
{
    protected $table = 'tab_issue_open_user';

    protected $autoWriteTimestamp = true;


    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '<fond style="color: red">锁定</fond>', 1 => '正常'];
        return $status[$data['status']];
    }

    public function getAuthStatusTextAttr($value, $data)
    {
        $status = [0 => '未认证', 1 => '已认证', 2 => '<fond style="color: orange">待审核</fond>', 3 => '<font style="color: red">已驳回</font>'];
        return $status[$data['auth_status']];
    }


    /**
     * @保存认证信息
     *
     * @author: zsl
     * @since: 2020/7/20 16:53
     */
    public function saveAuthInfo($param)
    {

        $this -> id = $param['id'];
        $this -> company_name = $param['company_name'];
        $this -> linkman = $param['linkman'];
        $this -> job = $param['job'];
        $this -> mobile = $param['mobile'];
        $this -> qq = $param['qq'];
        $this -> wechat = $param['wechat'];
        $this -> address = $param['address'];
        $this -> business_license_img = $param['business_license_img'];
        $this -> wenwangwen_img = $param['wenwangwen_img'];
        $this -> icp_img = $param['icp_img'];
        $this -> auth_status = 2; //待审核
        $res = $this -> allowField(true) -> isUpdate(true) -> save();
        return $res;
    }


    /**
     * @保存结算信息
     *
     * @author: zsl
     * @since: 2020/7/21 9:27
     */
    public function saveBankInfo($param)
    {
        $this -> id = $param['id'];
        $this -> account_type = $param['account_type'];
        $this -> bank_phone = $param['bank_phone'];
        $this -> bank_card = $param['bank_card'];
        $this -> bank_name = $param['bank_name'];
        $this -> bank_account = $param['bank_account'];
        $this -> province = $param['province'];
        $this -> city = $param['city'];
        $this -> county = $param['county'];
        $this -> account_openin = $param['account_openin'];
        $this -> check_people = $param['check_people'];
        $this -> check_people_qq = $param['check_people_qq'];
        $this -> check_people_phone = $param['check_people_phone'];
        $this -> alipay_account = $param['alipay_account'];
        $this -> alipay_realname = $param['alipay_realname'];
        $res = $this -> allowField(true) -> isUpdate(true) -> save();
        return $res;

    }


}
