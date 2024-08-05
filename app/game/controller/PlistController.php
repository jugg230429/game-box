<?php

namespace app\game\controller;

use cmf\controller\AdminBaseController;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class PlistController extends AdminBaseController
{

    //生成游戏渠道plist文件
    public function create_plist($game_id = 0, $promote_id = 0, $marking = "", $url = "")
    {
        $url = cmf_get_file_download_url($url);
        //强制https
        if (strpos($url, 'https://') === false) {
            $url = str_replace("http://", "https://", $url);
        }
        $game = get_game_entity($game_id);
        $xml = new \DOMDocument();
        $xml->load('./upload/plist/testdemo.plist');
        $online = $xml->getElementsByTagName('dict');//查找节点
        $asd = $online->item(1)->getElementsByTagName('string');//第二个节点下所有string
        foreach ($asd as $key => $value) {
            switch ($value->textContent) {
                case 'ipa_url':
                    $value->nodeValue = $url;
                    break;
                case 'icon':
                    $value->nodeValue = cmf_get_image_url($game['icon']);
                    break;
                case 'com.dell':
                    $value->nodeValue = $marking;
                    break;
                case '1.0.0':
                    $value->nodeValue = $game['sdk_version'];
                    break;
                case 'mchdemo':
                    $value->nodeValue = $game['relation_game_name'];
                    break;

            }
            if ($promote_id == 0) {
                $xml->save("./upload/sourceplist/$game_id.plist");
            } else {
                $pname = $game_id . "-" . $promote_id;
                $xml->save("./upload/GamePlist/$pname.plist");
            }
        }
        if ($promote_id == 0) {
            return "./public/upload/sourceplist/$game_id.plist";
        } else {
            return "./public/upload/GamePlist/$pname.plist";
        }


    }


    //生成App plist文件  // 新增加变量 $single_img 用于渠道自定义上传ios包是自定义的图标
    public function create_plist_app($version = "", $app_id=0,$promote_id = 0, $marking = "", $url = "",$single_img='')
    {
        $url = cmf_get_file_download_url($url);
        // var_dump($url);exit;
        //强制https
        if (strpos($url, 'https://') === false) {
            $url = str_replace("http://", "https://", $url);
        }
        $xml = new \DOMDocument();
        $xml->load('./upload/plist/testdemo.plist');
        $online = $xml->getElementsByTagName('dict');//查找节点
        $asd = $online->item(1)->getElementsByTagName('string');//第二个节点下所有string

        foreach ($asd as $key => $value) {
            switch ($value->textContent) {
                case 'ipa_url':
                        $value->nodeValue = $url;
                    break;
                case 'icon':
                    // 新增添加渠道自定义ios包时指定图片
                    $value->nodeValue = str_replace("http://", "https://", cmf_get_image_url(cmf_get_option('app_set')['app_logo'])); // 原
                    if(!empty($single_img)){
                        $value->nodeValue = str_replace("http://", "https://", cmf_get_image_url($single_img));
                    }
                    break;
                case 'com.dell':
                    $value->nodeValue = $marking == "" ? 'app' : $marking;
                    break;
                case '1.0.0':
                    $value->nodeValue = $version == "" ? '2' : $version;
                    break;
                case 'mchdemo':
                    $value->nodeValue = "APP";
                    break;

            }
            $pname = $promote_id.'_'.$app_id;
            if ($promote_id == 0) {
                $xml->save("./upload/sourceappplist/$app_id.plist");
            } else {
                $xml->save("./upload/appplist/$pname.plist");
            }
        }
        if ($promote_id == 0) {
            return "sourceappplist/$app_id.plist";
        } else {
            return "appplist/$pname.plist";
        }
    }

    /**
     * 重写 create_plist 方法。方便 开放平台 调用
     * @param int $game
     * @param int $promote_id
     * @param string $marking
     * @param string $url
     * author: xmy 280564871@qq.com
     */
    public function createPlist($game = 0, $promote_id = 0, $marking = "", $url = "")
    {
        $xml = new \DOMDocument();
        $xml->load('./public/upload/Plist/testdemo.plist');
        $online = $xml->getElementsByTagName('dict');//查找节点
        $asd = $online->item(1)->getElementsByTagName('string');//第二个节点下所有string
        foreach ($asd as $key => $value) {
            switch ($value->textContent) {
                case 'ipa_url':
                    if (preg_match("/Uploads/", $url)) {
                        $value->nodeValue = "https://" . $_SERVER['HTTP_HOST'] . ltrim($url, ".");//"https://iosdemo.vlcms.com/app/MCHSecretary.ipa";//替换xml对应的值
                    } else {
                        $value->nodeValue = $url;
                    }
                    break;
                case 'icon':
                    $value->nodeValue = "https://" . $_SERVER["HTTP_HOST"] . get_cover($game['icon'], 'path');;
                    break;
                case 'com.dell':
                    $value->nodeValue = $marking;
                    break;
                case '1.0.0':
                    $value->nodeValue = $game['sdk_version'];
                    break;
                case 'mchdemo':
                    $value->nodeValue = substr($game['game_name'], 0, strpos($game, "("));
                    break;

            }
            if ($promote_id == 0) {
                $xml->save("./Uploads/sourceplist/{$game['id']}.plist");
            } else {
                $pname = $game['id'] . "-" . $promote_id;
                $xml->save("./Uploads/GamePlist/$pname.plist");
            }
        }
    }
}