<?php

namespace app\common\logic;

use ApkParser\Parser;
use CFPropertyList\CFPropertyList;

class PackResultLogic
{


    /**
     * @获取ipa包中的Info.pliat文件
     *
     * @since: 2021/7/17 11:57
     * @author: zsl
     */
    public function getInfoPlist($param)
    {
        try {
            $file_path = $param['file_path'];
            $zip = new \ZipArchive();
            if ($zip -> open($file_path) === true) {
                $filesInside = [];
                for ($i = 0; $i < 2; $i++) {
                    array_push($filesInside, $zip->getNameIndex($i));
                }
                $tempPath = 'temp/';
                $plistPath = $filesInside[1].'Info.plist';
                $zip -> extractTo($tempPath, $plistPath);
                $zip -> close();
            }
            $content = file_get_contents($tempPath . $plistPath);
            $plist = new CFPropertyList();
            $plist -> parseBinary($content);
            $res = $plist -> toArray();
            if (empty($res)) {
                return false;
            }
            unlink($tempPath . $plistPath);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }


    /**
     * @获取APK包信息
     *
     * @author: zsl
     * @since: 2021/7/17 14:08
     */
    public function getApkInfo($param)
    {

        try {
            $file_path = $param['file_path'];
            $apk = new Parser($file_path);
            $manifest = $apk -> getManifest();
            $result = [];
            $result['package_name'] = $manifest -> getPackageName();
            $result['version_name'] = $manifest -> getVersionName();
            return $result;

        } catch (\Exception $exception) {
            return false;
        }


    }


}
