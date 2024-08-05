<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-06-10
 */
declare (strict_types = 1);

namespace app\sdkyy;

use cmf\controller\HomeBaseController;

class BaseController extends HomeBaseController
{
    /**
     * @错误代码
     *
     * @var array
     */
    protected $baseErrorCode = [1000 => '网络错误,请稍候再试'];

    public function _initialize()
    {
        if (session('member_auth') ) {
            $userdata = get_user_entity(session('member_auth.user_id'),false,'password,lock_status,vip_level,point,receive_address');
            if(session('member_auth.password')!=$userdata['password']||$userdata['lock_status']!=1){
                session('member_auth', null);
            }
        }
        define('UID', session('member_auth.user_id') ?: 0);
        define('YPID',session('union_host')?session('union_host.union_id'):0);
    }
    /**
     * @返回数据方法
     *
     * @param string $data 返回数据
     * @param string $format 返回格式
     *
     * @return \think\Response
     *
     * @author: imdong
     * @since: 2020/3/23 11:56
     */
    protected function response ($data = '', $format = 'json')
    {
        if (function_exists($format)) {
            return $format($data);
        } else {
            return response($data);
        }
    }

    /**
     * @异常和错误信息返回方法
     *
     * @param int    $code返回状态
     * @param string $msg 返回信息
     *
     * @return array
     *
     * @author: imdong
     * @since: 2020/3/23 11:59
     */
    protected function failResult ($code = 0, string $msg = ''): array
    {
        return [
            'status'    => empty($code) || is_string($code) ? 0 : $code,
            'message' => empty($msg) ? (is_string($code) ? $code : $this -> baseErrorCode[$code]) : $msg,
            'data'    => [],
        ];
    }

    /**
     * @成功返回信息
     *
     * @param string $data 返回数据
     * @param string $msg
     *
     * @return array
     *
     * @author: imdong
     * @since: 2020/3/23 12:00
     */
    protected function successResult ($data = '', string $msg = ''): array
    {
        return ['status' => 200, 'message' => $msg, 'data' => $data];
    }
}