<?php
/**  media 模块下 公用函数  */

/**
 * 获取优先显示顺序 手游 H5 页游
 * created by wjd
 * 2021-7-14 09:18:07
*/
function get_media_sort()
{
    // 获取排序
    $data = cmf_get_option('media_set');
    $game_sort_top1 = $data['game_sort_top1'] ?? 0; // 1 手游 2 H5 3页游
    $game_sort_top2 = $data['game_sort_top2'] ?? 0; // 1 手游 2 H5 3页游
    $h5_sort = 0;
    $sy_sort = 0;
    $yy_sort = 0;
    if ($game_sort_top1 == 1) {
        $sy_sort = 1;
    } elseif ($game_sort_top1 == 2) {
        $h5_sort = 1;
    } else {
        $yy_sort = 1;
    }
    if ($game_sort_top2 == 1) {
        $sy_sort = 2;
    } elseif ($game_sort_top2 == 2) {
        $h5_sort = 2;
    } else {
        $yy_sort = 2;
    }
    $first_show = '';
    if ($h5_sort == 1) {
        $first_show = 'h5_sort';
    }
    if ($sy_sort == 1) {
        $first_show = 'sy_sort';
    }
    if ($yy_sort == 1) {
        $first_show = 'yy_sort';
    }
    $mixData = [
            'h5_sort' => $h5_sort == 0 ? 3 : $h5_sort,
            'sy_sort' => $sy_sort == 0 ? 3 : $sy_sort,
            'yy_sort' => $yy_sort == 0 ? 3 : $yy_sort,
    ];
    $i = 0; // 3出现的次数
    foreach ($mixData as $k => $v) {
        if ($v == 3) {
            $i ++;
        }
        if ($i >= 2) {
            if (!in_array(1, $mixData)) {
                $mixData[$k] = 1;
            } elseif (!in_array(2, $mixData)) {
                $mixData[$k] = 2;
            }
        }
    }
    foreach ($mixData as $k => $v) {
        if ($v == 1) {
            $first_show = $k;
        }
    }
    $mixData['first_show'] = empty($first_show) ? 'h5_sort' : $first_show;
    if ($mixData['first_show'] == 'h5_sort') {
        $mixData['first_show_num'] = 2;
    }
    if ($mixData['first_show'] == 'sy_sort') {
        $mixData['first_show_num'] = 1;
    }
    if ($mixData['first_show'] == 'yy_sort') {
        $mixData['first_show_num'] = 3;
    }
    $PERMI = PERMI;
    $YPERMI = YPERMI;
    if (PERMI == '1' && $data['pc_navigation_sy_status'] == '0') {
        $PERMI = 0;
    }
    if (PERMI == '2' && $data['pc_navigation_h5_status'] == '0') {
        $PERMI = 0;
    }
    if (PERMI == '3' && $data['pc_navigation_sy_status'] == '0') {
        $PERMI = 2;
    }
    if (PERMI == '3' && $data['pc_navigation_h5_status'] == '0') {
        $PERMI = 1;
    }
    if (PERMI == '3' && $data['pc_navigation_sy_status'] == '0' && $data['pc_navigation_h5_status'] == '0') {
        $PERMI = 0;
    }
    if (YPERMI == '1' && $data['pc_navigation_yy_status'] == '0') {
        $YPERMI = 0;
    }
    if ($PERMI == '1' && empty($YPERMI)) {
        //单手游
        $mixData['sy_sort'] = 1;
        $mixData['yy_sort'] = 2;
        $mixData['h5_sort'] = 3;
        $mixData['first_show'] = 'sy_sort';
        $mixData['first_show_num'] = '1';
    } else if ($PERMI == '2' && empty($YPERMI)) {
        //单H5
        $mixData['h5_sort'] = 1;
        $mixData['yy_sort'] = 2;
        $mixData['sy_sort'] = 3;
        $mixData['first_show'] = 'h5_sort';
        $mixData['first_show_num'] = '2';
    } else if (empty($PERMI) && $YPERMI == '1') {
        //单页游
        $mixData['yy_sort'] = 1;
        $mixData['h5_sort'] = 2;
        $mixData['sy_sort'] = 3;
        $mixData['first_show'] = 'yy_sort';
        $mixData['first_show_num'] = '3 ';
    } else if ($PERMI == '3' && empty($YPERMI)) {
        //手游H5
        if ($mixData['sy_sort'] < $mixData['h5_sort']) {
            $mixData['sy_sort'] = 1;
            $mixData['h5_sort'] = 2;
            $mixData['yy_sort'] = 3;
            $mixData['first_show'] = 'sy_sort';
            $mixData['first_show_num'] = '1';
        } else {
            $mixData['h5_sort'] = 1;
            $mixData['sy_sort'] = 2;
            $mixData['yy_sort'] = 3;
            $mixData['first_show'] = 'h5_sort';
            $mixData['first_show_num'] = '2';
        }
    } else if ($PERMI == '1' && $YPERMI == '1') {
        //手游页游
        if ($mixData['sy_sort'] < $mixData['yy_sort']) {
            $mixData['sy_sort'] = 1;
            $mixData['yy_sort'] = 2;
            $mixData['h5_sort'] = 3;
            $mixData['first_show'] = 'sy_sort';
            $mixData['first_show_num'] = '1';
        } else {
            $mixData['yy_sort'] = 1;
            $mixData['sy_sort'] = 2;
            $mixData['h5_sort'] = 3;
            $mixData['first_show'] = 'yy_sort';
            $mixData['first_show_num'] = '3';
        }
    } else if ($PERMI == '2' && $YPERMI == '1') {
        //H5页游
        if ($mixData['h5_sort'] < $mixData['yy_sort']) {
            $mixData['h5_sort'] = 1;
            $mixData['yy_sort'] = 2;
            $mixData['sy_sort'] = 3;
            $mixData['first_show'] = 'h5_sort';
            $mixData['first_show_num'] = '2';
        } else {
            $mixData['yy_sort'] = 1;
            $mixData['h5_sort'] = 2;
            $mixData['sy_sort'] = 3;
            $mixData['first_show'] = 'yy_sort';
            $mixData['first_show_num'] = '3';
        }
    } else if ($PERMI == '3' && $YPERMI == '1') {
    }
    $data['pc_navigation_sy_status'] = $data['pc_navigation_sy_status'] != '0' ? '1' : $data['pc_navigation_sy_status'];
    $data['pc_navigation_h5_status'] = $data['pc_navigation_h5_status'] != '0' ? '1' : $data['pc_navigation_h5_status'];
    $data['pc_navigation_yy_status'] = $data['pc_navigation_yy_status'] != '0' ? '1' : $data['pc_navigation_yy_status'];
    if (PERMI == '1' && empty(YPERMI)) {
        //单手游
        $mixData['total_num'] = 1;
        $mixData['nav_num'] = $data['pc_navigation_sy_status'];
    } else if (PERMI == '2' && empty(YPERMI)) {
        //单H5
        $mixData['total_num'] = 1;
        $mixData['nav_num'] = $data['pc_navigation_h5_status'];
    } else if (empty(PERMI) && YPERMI == '1') {
        //单页游
        $mixData['total_num'] = 1;
        $mixData['nav_num'] = $data['pc_navigation_yy_status'];
    } else if (PERMI == '3' && empty(YPERMI)) {
        //手游H5
        $mixData['total_num'] = 2;
        $mixData['nav_num'] = $data['pc_navigation_sy_status'] + $data['pc_navigation_h5_status'];
    } else if (PERMI == '1' && YPERMI == '1') {
        //手游页游
        $mixData['total_num'] = 2;
        $mixData['nav_num'] = $data['pc_navigation_sy_status'] + $data['pc_navigation_yy_status'];
    } else if (PERMI == '2' && YPERMI == '1') {
        //H5页游
        $mixData['total_num'] = 2;
        $mixData['nav_num'] = $data['pc_navigation_h5_status'] + $data['pc_navigation_yy_status'];
    } else if (PERMI == '3' && YPERMI == '1') {
        //三平台
        $mixData['total_num'] = 3;
        $mixData['nav_num'] = $data['pc_navigation_sy_status'] + $data['pc_navigation_h5_status'] + $data['pc_navigation_yy_status'];
    }
    return $mixData;
}

