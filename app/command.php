<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

$apps = cmf_scan_dir(APP_PATH . '*', GLOB_ONLYDIR);

$returnCommands = [];

foreach ($apps as $app) {
    $commandFile = APP_PATH . $app . '/command'.CONF_EXT;

    if (file_exists($commandFile)) {
        $commands = include $commandFile;

        $returnCommands = array_merge($returnCommands, $commands);
    }

}

return $returnCommands;