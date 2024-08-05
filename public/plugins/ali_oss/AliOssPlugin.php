<?php
namespace plugins\ali_oss;

use cmf\lib\Plugin;
use OSS\OssClient;

class AliOssPlugin extends Plugin{

    public $info = [
        'name'        => 'AliOss',
        'title'       => '阿里云存储',
        'description' => '阿里云存储',
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

        $storageOption['storages']['AliOss'] = ['name' => '阿里云存储', 'driver' => '\\plugins\\ali_oss\\lib\\AliOss'];

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

        unset($storageOption['storages']['AliOss']);

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
            $internal = $config['internal'];
            $auth  = new OssClient($accesskeyid, $accesskeysecret,$domain);
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