<?php

use think\Route;
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
if (file_exists(CMF_ROOT . "data/conf/route".CONF_EXT)) {
    $runtimeRoutes = include CMF_ROOT . "data/conf/route".CONF_EXT;
} else {
    $runtimeRoutes = [];
}

return $runtimeRoutes;