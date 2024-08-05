<?php

use think\Route;
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
Route::rule('/tg$','/channelsite');
Route::rule('/tg/:c/:s','channelsite/:c/:s');