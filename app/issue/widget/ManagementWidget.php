<?php

namespace app\issue\widget;

use app\issue\logic\PlatformLogic;
use think\Controller;
use think\Request;

class ManagementWidget extends Controller
{


    public function __construct(Request $request = null)
    {
        parent ::__construct($request);
    }


    public function css()
    {
        return $this -> fetch('issuepublic@widget/css');
    }


    public function header()
    {
        //获取当前用户平台列表
        $lPlatform = new PlatformLogic();
        $platformLists = $lPlatform -> getUserPlatform(['open_user_id' => OID]);
        $this -> assign('platformLists', $platformLists);
        //获取当前平台权限
        $pt_type = get_pt_type(PID);
        $this -> assign('pt_type', $pt_type);
        if (in_array($pt_type,[0,1,3,5])) {
            //获取当前平台对接H5游戏
            $h5Game = get_open_user_h5_game(OID, PID);
            $this -> assign('h5GameCount', count($h5Game));
        }
        if (in_array($pt_type,[0,2,3,6])) {
            //获取当前平台对接手游
            $syGame = get_open_user_sy_game(OID, PID);
            $this -> assign('syGameCount', count($syGame));
        }
        if (in_array($pt_type,[0,4,5,6])) {
            //获取当前平台对接页游
            $syGame = get_open_user_yy_game(OID, PID);
            $this -> assign('yyGameCount', count($syGame));
        }
        return $this -> fetch('issuepublic@widget/header');
    }

    public function left()
    {
        //获取菜单高亮
        $path = $this->request->module().'/'.$this->request->controller().'/'.$this->request->action();
        switch ($path){
            case 'issue/Management/index':$highlight = 'index';break;
            case 'issue/Management/game':$highlight = 'game';break;
            case 'issue/Stat/user_lists':$highlight = 'statuser';break;
            case 'issue/Stat/recharge_lists':$highlight = 'statpay';break;
            case 'issue/Currency/index':$highlight = 'currency';break;
            case 'issue/Currency/orders':$highlight = 'currency';break;
            case 'issue/Currency/withdraw':$highlight = 'currency';break;
            case 'issue/Article/index':$highlight = 'article';break;
            case 'issue/Auth/index':$highlight = 'auth';break;
            case 'issue/Auth/settlement':$highlight = 'auth';break;
            case 'issue/Auth/changepassword':$highlight = 'auth';break;
            default:$highlight = 'index';break;
        }
        $this->assign('highlight',$highlight);
        return $this -> fetch('issuepublic@widget/left');
    }

    public function footer()
    {
        return $this->fetch('issuepublic@widget/footer');
    }


}