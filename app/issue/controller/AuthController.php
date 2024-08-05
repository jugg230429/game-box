<?php

namespace app\issue\controller;

use app\issue\logic\AuthLogic;

class AuthController extends ManagementBaseController
{


    /**
     * @基本信息
     *
     * @author: zsl
     * @since: 2020/7/20 15:57
     */
    public function index()
    {

        $lAuth = new AuthLogic();
        $info = $lAuth -> getInfo(['id' => OID]);
        $this -> assign('info', $info);
        return $this -> fetch();
    }

    /**
     * @保存信息
     *
     * @author: zsl
     * @since: 2020/7/20 16:27
     */
    public function saveInfo()
    {
        $param = $this -> request -> param();
        $lAuth = new AuthLogic();
        $result = $lAuth -> saveInfo($param);
        if ($result['code'] == '0') {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);

    }


    /**
     * @结算信息
     *
     * @author: zsl
     * @since: 2020/7/20 15:57
     */
    public function settlement()
    {

        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {
            $lAuth = new AuthLogic();
            $result = $lAuth -> saveBankInfo($param);
            if ($result['code'] == '0') {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg']);
        }

        $lAuth = new AuthLogic();
        $info = $lAuth -> getInfo(['id' => OID]);
        $this -> assign('info', $info);


        return $this -> fetch();
    }

    /**
     * @修改密码
     *
     * @author: zsl
     * @since: 2020/7/20 15:58
     */
    public function changepassword()
    {
        $param = $this -> request -> param();
        if ($this -> request -> isPost()) {

            $lAuth = new AuthLogic();
            $result = $lAuth -> changePassword($param);
            if ($result['code'] == '0') {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg']);
        }
        return $this -> fetch();
    }


}