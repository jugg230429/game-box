<?php

namespace app\issue\logic;

class ChannelLogic
{

    //接口地址
    private $domain = "http://issue.gameslm.com";

    //错误信息
    private $errorMsg = '';

    //返回数据
    private $data = [];


    /**
     * @渠道大全列表
     *
     * @author: zsl
     * @since: 2021/8/24 21:55
     */
    public function lists($param)
    {
        $url = $this -> domain . "/api/channel/index/lists";
        $postData = [];
        $postData['version'] = get_system_version();
        $result = request_post($url, $postData);
        $data = json_decode($result, true);
        $data = $data['data'];
        $config = include(APP_PATH . 'issue/config.php');
        foreach ($data as &$v) {
            //检查是否已接入
            if (isset($config['sdk_config'][$v['tag_name']])) {
                $v['is_apply'] = 1;
            } else {
                $v['is_apply'] = 0;
            }
            //检查是否有更新
            if($config['sdk_config'][$v['tag_name']]['resource_version']<$v['resource_version']){
                $v['has_new_version'] = 1;
            }else{
                $v['has_new_version'] = 0;
            }
        }
        $this -> data = $data;
        return true;
    }


    /**
     * @申请接入
     *
     * @author: zsl
     * @since: 2021/8/24 21:49
     */
    public function apply($param)
    {
        try {

            $url = $this -> domain . "/api/channel/index/getInfo";
            //验证参数
            if (empty($param['channel_id'])) {
                $this -> errorMsg = '渠道信息不存在';
                return false;
            }
            //请求接口
            $postData = [];
            $postData['version'] = get_system_version();
            $postData['channel_id'] = $param['channel_id'];
            $result = request_post($url, $postData);
            $data = json_decode($result, true);
            if (empty($data)) {
                $this -> errorMsg = '接口请求失败,请重试';
                return false;
            }
            if ($data['code'] != '1') {
                $this -> errorMsg = $data['msg'];
                return false;
            }
            //下载渠道资源文件
            $dirPath = RUNTIME_PATH . 'issue_resource';
            $filePaht = $dirPath . '/' . $param['channel_id'] . '_resource.zip';
            $expToPath = $dirPath . '/' . $param['channel_id'] . '_resource';
            if (!is_dir($dirPath)) {
                @mkdir($dirPath, 0755, true);
            }
            $file = file_get_contents($data['data']['resource_url']);
            $downRes = file_put_contents($filePaht, $file);
            if (empty($downRes)) {
                $this -> errorMsg = '资源包下载失败';
                return false;
            }
            //解压资源文件
            $res = unzip_file($filePaht, $expToPath);
            if (false === $res) {
                $this -> errorMsg = '资源包解压失败';
                return false;
            }
            //覆盖资源文件
            if (is_dir($expToPath . '/resources/')) {
                recurse_copy($expToPath . '/resources/', APP_PATH . 'issuesy/resources/');
            }
            if (is_dir($expToPath . '/sdk/')) {
                recurse_copy($expToPath . '/sdk/', APP_PATH . 'issuesy/logic/sdk/');
            }
            //合并config文件
            $config = include($expToPath . '/config.php');
            if ($config) {
                $setRes = $this -> setConfig($config['name'], $config);
                if (false === $setRes) {
                    $this -> errorMsg = '配置文件更新失败';
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @删除接入渠道
     *
     * @author: zsl
     * @since: 2021/8/31 9:12
     */
    public function delete($param)
    {
        try {
            if (empty($param['tag'])) {
                $this -> errorMsg = '参数错误';
                return false;
            }
            $result = $this -> removeConfig($param['tag']);
            if (false === $result) {
                $this -> errorMsg = '删除失败';
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }

    /**
     * @修改配置文件内容
     *
     * @author: zsl
     * @since: 2021/8/24 21:54
     */
    private function setConfig($key, $value)
    {
        $fileUrl = APP_PATH . "issue/config.php";
        $config = include($fileUrl);
        if (!isset($config['sdk_config'])) {
            $config = [];
        }
        unset($config['sdk_config'][$key]);
        $config['sdk_config'][$key] = $value[$key];
        $result = file_put_contents($fileUrl, '<?php return ' . var_export($config, true) . ';');
        return !!$result;
    }


    /**
     * @移除配置项
     *
     * @param $key
     *
     * @author: zsl
     * @since: 2021/8/31 9:15
     */
    private function removeConfig($key)
    {
        $fileUrl = APP_PATH . "issue/config.php";
        $config = include($fileUrl);
        unset($config['sdk_config'][$key]);
        $result = file_put_contents($fileUrl, '<?php return ' . var_export($config, true) . ';');
        return !!$result;
    }


    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this -> errorMsg;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this -> data;
    }

}
