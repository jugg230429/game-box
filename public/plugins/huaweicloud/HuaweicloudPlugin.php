<?php
namespace plugins\huaweicloud;

use cmf\lib\Plugin;
// use OSS\OssClient;

class HuaweicloudPlugin extends Plugin{

    public $info = [
        'name'        => 'Huaweicloud',
        'title'       => '华为云存储',
        'description' => '华为云存储',
        'status'      => 1,
        'author'      => 'xbb',
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

        $storageOption['storages']['Huaweicloud'] = ['name' => '华为云存储', 'driver' => '\\plugins\\huaweicloud\\lib\\Huaweicloud'];

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
        unset($storageOption['storages']['Huaweicloud']);

        cmf_set_option('storage', $storageOption);
        return true; //卸载成功返回true，失败false
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