<?php
namespace plugins\baidu_bac;

use cmf\lib\Plugin;
use BaiduBce\Services\Bos\BosClient;

class BaiduBacPlugin extends Plugin{

    public $info = [
        'name'        => 'BaiduBac',
        'title'       => '百度BOS',
        'description' => '百度云存储',
        'status'      => 1,
        'author'      => 'Yyh',
        'version'     => '1.0.0'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        $storageOption['storages']['BaiduBac'] = ['name' => '百度云存储', 'driver' => '\\plugins\\baidu_bac\\lib\\BaiduBac'];

        cmf_set_option('storage', $storageOption);
        return true;//安装成功返回true，失败false
    }


    // 插件卸载
    public function uninstall()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        unset($storageOption['storages']['BaiduBac']);

        cmf_set_option('storage', $storageOption);
        return true;//卸载成功返回true，失败false
    }


    public function fetchUploadView()
    {
        $tab = request()->param('tab');

        if ($tab == 'cloud') {
            $config     = $this->getConfig();
            $accesskeyid = $config['accesskeyid'];
            $accesskeysecret = $config['accesskeysecret'];
            $domain = $config['domain'];
            // $auth  = new OssClient($accesskeyid, $accesskeysecret,$domain);
            $content = $this->fetch('upload');
        } else {
            $content = "has_cloud_storage";
        }
        return $content;

    }

    public function cloudStorageTab(&$param)
    {



    }


}