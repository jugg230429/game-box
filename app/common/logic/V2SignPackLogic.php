<?php

namespace app\common\logic;

class V2SignPackLogic
{


    /**
     * @apk V2签名方式写入渠道信息
     *
     * @author: zsl
     * @since: 2021/7/22 17:42
     */
    public function pack($file_url, $promote_id, $game_id, $source_version)
    {

        //获取当前apk包签名方式
        $getSignTypeCommand = 'java -jar ' . CMF_ROOT . 'jar/VasDolly.jar get -s ' . $file_url;
        exec($getSignTypeCommand, $out);
        $signType = '1';
        foreach ($out as $ov) {
            if ($ov == 'signature mode:V1_V2') {
                $signType = '2';
                break;
            }
            if ($ov == 'signature mode:V2') {
                $signType = '2';
                break;
            }
        }
        if ($signType == '1') {
            return false;
        }
        //写入渠道信息
        $putChannelInfoCommand = 'java -jar ' . CMF_ROOT . 'jar/walle-cli-all.jar put -c ' . $promote_id . ' -e promote_account=' . get_promote_name($promote_id) .
                ',game_id=' . $game_id . ',game_name="' . get_game_name($game_id) . '",game_appid=' . get_game_list('game_appid', ['id' => $game_id])[0]['game_appid'] .
                ',source_version=' . $source_version . ' ' . $file_url . ' ' . $file_url;
        exec($putChannelInfoCommand, $out, $a);
        chmod($file_url, 0755);
        return true;
    }


}
