<?php

function task_icon($key=''){
    if(!$key) return '';
    $iconurl = '/themes/simpleboot3/mobilepublic/assets/images/mall/';
    switch ($key){
        case 'improve_address'://完善收货地址
            $iconurl = $iconurl.'task_tuijian_ico_grxx.png';
            break;
        case 'bind_email'://绑定邮箱
            $iconurl = $iconurl.'task_tuijian_ico_youxiang.png';
            break;
        case 'first_pay'://首充奖励
            $iconurl = $iconurl.'task_tuijian_ico_jiangli.png';
            break;
        case 'vip_upgrade'://VIP升级奖励
            $iconurl = $iconurl.'task_tuijian_ico_vip1.png';
            break;
        case 'register_award'://注册奖励
            $iconurl = $iconurl.'task_tuijian_ico_zcjl.png';
            break;
        case 'bind_phone'://绑定手机号码
            $iconurl = $iconurl.'task_tuijian_ico_sjh.png';
            break;
        case 'play_game'://游戏体验
            $iconurl = $iconurl.'task_tuijian_ico_jitiyan.png';
            break;
        case 'sign_in'://天天签到
            $iconurl = $iconurl.'task_tuijian_ico_qiandao.png';
            break;
        case 'pay_award'://充值送积分
            $iconurl = $iconurl.'task_tuijian_ico_czsjf.png';
            break;
        case 'game_login'://每日登录游戏
            $iconurl = $iconurl.'task_tuijian_ico_grxx.png';
            break;
        case 'try_game'://试玩有奖
            $iconurl = $iconurl.'task_tuijian_ico_swyj.png';
            break;
        case 'auth_idcard'://实名认证
            $iconurl = $iconurl.'task_tuijian_ico_smrz.png';
            break;
        case 'change_headimg'://更新头像
            $iconurl = $iconurl.'task_tuijian_ico_ghtx.png';
            break;
        case 'change_nickname'://更换昵称
            $iconurl = $iconurl.'task_tuijian_ico_ghnc.png';
            break;
        case 'bind_qq'://绑定qq
            $iconurl = $iconurl.'task_tuijian_ico_bdqq.png';
            break;
        case 'subscribe_wechat'://关注微信公众号
            $iconurl = $iconurl.'task_tuijian_ico_wechat.png';
            break;
        case 'first_pay_every_day'://每日游戏首充
            $iconurl = $iconurl.'task_tuijian_ico_mrsc.png';
            break;
        case 'wx_friends_share'://微信朋友圈分享
            $iconurl = $iconurl.'task_tuijian_ico_pyq.png';
            break;
        case 'qq_zone_share'://QQ空间分享
            $iconurl = $iconurl.'task_tuijian_ico_qqkj.png';
            break;
        default:
            $iconurl = '';
            break;
    }
    return $iconurl;
}

