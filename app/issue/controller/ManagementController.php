<?php

namespace app\issue\controller;

use app\issue\logic\ApplyLogic;
use app\issue\logic\GameLogic;
use app\issue\logic\PlatformLogic;
use app\issue\logic\StatLogic;
use app\issue\model\OpenUserModel;
use think\paginator\driver\Bootstrap;

class ManagementController extends ManagementBaseController
{

    /**
     * @分发平台管理中心首页
     *
     * @author: zsl
     * @since: 2020/7/11 14:35
     */
    public function index(StatLogic $lStat)
    {
        $getData['open_user_id'] = OID;
        $getData['platform_id'] = PID;
        $getData['pay_status'] = '1';
        $head = $lStat->overview($getData);
        $table = $this->table_data();
        $data = json_decode($table,true)['data'];
        //数据分页
        $currentPage = input('page','1','intval');
        $rows = 8;
        $count = count($data);//计算总数据量
        $skip = ($currentPage - 1) * $rows;//计算分页偏移量
        $pagingData = array_slice($data,$skip,$rows,true);//实现数组分页
        $option = [
                'path' => url('/issue/management/index',['type'=>input('type')]),
        ];
        $bootstrap = new Bootstrap($pagingData, 8, $currentPage, $count, false, $option);
        $this->assign('table_data',$bootstrap->toArray()['data']);
        $this->assign('page',$bootstrap->render());

        $this->assign('head',$head);
//        $this->assign('table_data',json_decode($table,true)['data']);
        return $this -> fetch();
    }

    public function table_data()
    {
        $getData['open_user_id'] = OID;
        $getData['platform_id'] = PID;
        $getData['type'] = $this->request->param('type');
        if(empty($getData['type'])){
            $getData['type'] = 1;
        }
        $lStat = new StatLogic();
        $table = $lStat->table($getData);
        return json_encode(['code'=>200,'data'=>$table]);
    }


    /**
     * @游戏管理
     *
     * @author: zsl
     * @since: 2020/7/14 10:03
     */
    public function game()
    {
        $param = $this -> request -> param();
        $pt_type = get_pt_type(PID);
        if (in_array($pt_type,[0,1,3,5])) {
            $type = input('type', 1, 'intval');
        } elseif (in_array($pt_type,[0,2,3,6])) {
            $type = input('type', 2, 'intval');
        } else {
            $type = input('type', 3, 'intval');
        }
        $this -> assign('type', $type);
        $lGame = new GameLogic();
        $param['platform_id'] = PID;
        $param['sdk_version'] = $type == 1 ? 3: ($type == 2 ? ['in', '1,2'] : 4);
        $gameLists = $lGame -> platformGameLists($param);
        $this -> assign('gameLists', $gameLists);
        //查询当前选择平台配置
        $lPlatform = new PlatformLogic();
        $configure = $lPlatform -> getConfigure(['id' => PID]);
        if ($type == '1') {
            $this -> assign('config', array_filter(explode(PHP_EOL, $configure['platform_config_h5'])));
        }elseif($type == '3'){
            $this -> assign('config', array_filter(explode(PHP_EOL, $configure['platform_config_yy'])));
        } else {
            $this -> assign('service_config', array_filter(explode(PHP_EOL, $configure['service_config'])));
            $this -> assign('config', array_filter(explode(PHP_EOL, $configure['platform_config_sy'])));
        }
        return $this -> fetch();
    }


    /**
     * @申请游戏
     *
     * @author: zsl
     * @since: 2020/7/14 16:55
     */
    public function applyGame()
    {
        $param = $this -> request -> param();
        $lGame = new GameLogic();
        $param['platform_id'] = PID;
        $result = $lGame -> applyGame($param);
        return json($result);

    }

    /**
     * @获取游戏url
     *
     * @author: zsl
     * @since: 2020/7/15 11:29
     */
    public function getGameUrl()
    {
        $param = $this -> request -> param();
        $lGame = new GameLogic();
        $param['platform_id'] = PID;
        $param['open_user_id'] = OID;
        $openusermodel = new OpenUserModel();
        $openusermodel->where('id',OID);
        $openusermodel->field('settle_type');
        $openuser = $openusermodel->find();
        $param['settle_type'] = $openuser['settle_type'];
        $result = $lGame -> getGameUrl($param);
        return json($result);
    }


    /**
     * @获取申请链接配置参数
     *
     * @author: zsl
     * @since: 2020/7/15 15:52
     */
    public function getApplyConfig()
    {
        $param = $this -> request -> param();
        $lGame = new GameLogic();
        $config = $lGame -> getApplyPlatformConfig($param);
        return $config ? json($config) : [];

    }

    public function getApplyServiceConfig()
    {
        $param = $this -> request -> param();
        $lGame = new GameLogic();
        $config = $lGame -> getApplyServiceConfig($param);
        return $config ? json($config) : [];

    }

    /**
     * @设置平台配置
     *
     * @author: zsl
     * @since: 2020/7/15 14:41
     */
    public function setPlatformConfig()
    {
        $param = $this -> request -> param();
        $param = array_map('trim',$param);
        $lApply = new ApplyLogic();
        $result = $lApply -> setPlatformConfig($param);
        return json($result);
    }

    /**
     * @设置服务端配置
     *
     * @author: zsl
     * @since: 2020/7/21 10:27
     */
    public function setPlatformServiceConfig()
    {
        $param = $this -> request -> param();
        $param = array_map('trim',$param);
        $lApply = new ApplyLogic();
        $result = $lApply -> setPlatformServiceConfig($param);
        return json($result);
    }


}
