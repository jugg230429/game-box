<?php

namespace app\api\controller;

use app\api\model\migrate\GameModel;
use app\api\model\migrate\GameTypeModel;
use app\api\model\migrate\SpendModel;
use app\api\model\migrate\UserModel;
use app\api\model\migrate\UserPlayModel;
use app\api\validate\migrate\GameValidate;
use app\api\validate\migrate\PromoteValidate;
use app\api\validate\migrate\UserValidate;
use app\api\model\migrate\PromoteModel;
use think\Controller;

/**
 * Class MigrateController
 *
 * @ 数据迁移接口，外部平台调用接口，可将数据入库
 *
 * @package app\api\controller
 */
class MigrateController extends Controller
{


    /**
     * @迁移用户数据
     *
     * @author: zsl
     * @since: 2021/2/4 9:24
     */
    public function user()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/userData/user' . $filename . '.data'), true);
        //写入用户数据
        try {
            $mUser = new UserModel();
            $res = $mUser -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }
    }


    /**
     * @迁移用户绑币数据
     *
     * @author: zsl
     * @since: 2021/2/6 11:23
     */
    public function userPlay()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/userPlayData/userplay' . $filename . '.data'), true);
        //写入用户数据
        try {
            $mUser = new UserPlayModel();
            $res = $mUser -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }
    }


    /**
     * @推广员数据迁移
     *
     * @author: zsl
     * @since: 2021/2/4 10:54
     */
    public function promote()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/promote/promote' . $filename . '.data'), true);
        //写入推广员数据
        try {
            $mPromote = new PromoteModel();
            $res = $mPromote -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }
    }

    public function busier()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/busier/busier' . $filename . '.data'), true);
        //写入推广员数据
        try {
            $mPromote = new PromoteModel();
            $res = $mPromote -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }
    }

    /**
     * @游戏数据转移
     *
     * @author: zsl
     * @since: 2021/2/4 14:38
     */
    public function game()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/game/game' . $filename . '.data'), true);
        //写入游戏数据
        try {
            $mPromote = new GameModel();
            $res = $mPromote -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }
    }


    /**
     * @游戏分类数据转移
     *
     * @author: zsl
     * @since: 2021/2/5 9:49
     */
    public function gameType()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/gameType/gametype' . $filename . '.data'), true);
        //写入游戏分类数据
        try {
            $mPromote = new GameTypeModel();
            $res = $mPromote -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }

    }


    /**
     * @游戏订单数据转移
     *
     * @author: zsl
     * @since: 2021/2/5 10:02
     */
    public function gameOrder()
    {
        $result = ['code' => 200, 'msg' => '同步成功', 'data' => []];
        $filename = $this -> request -> get('f', 1, 'intval');
        $param = json_decode(file_get_contents(dirname(__FILE__) . '/migrate/gameOrder/gameorder' . $filename . '.data'), true);
        //写入游戏分类数据
        try {
            $model = new SpendModel();
            $res = $model -> migrateData($param);
            if (false === $res) {
                $result['code'] = 0;
                $result['msg'] = '同步失败';
                return json($result);
            }
            return json($result);
        } catch (\Exception $e) {
            $result['code'] = 0;
            $result['msg'] = $e -> getMessage();
            return json($result);
        }
    }
}