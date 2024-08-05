<?php

namespace app\common\model;

use think\Model;

class DateListModel extends Model
{

    protected $table = 'sys_date_list';

    protected $autoWriteTimestamp = true;


    public function get_date_type($date = '')
    {
        //  过去四周
        $fourweekstart = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 4 * 7, date("Y")));
        $fourweekend = date("Y-m-d", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y")));
        //  过去八周
        $eightweekstart = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 8 * 7, date("Y")));
        $eightweekend = date("Y-m-d", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y")));
        //  过去十二周
        $twelvekstart = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 12 * 7, date("Y")));
        $twelveweekend = date("Y-m-d", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y")));
        //过去三月
        $threemonthstart = date("Y-m-d", mktime(0, 0, 0, date("m") - 3, 1, date("Y")));
        $threemonthend = date("Y-m-d", mktime(23, 59, 59, date("m"), 0, date("Y")));
        $datearr['fourweek'] = $fourweekstart . '~' . $fourweekend;
        $datearr['eightweek'] = $eightweekstart . '~' . $eightweekend;
        $datearr['twelveweek'] = $twelvekstart . '~' . $twelveweekend;
        $datearr['threemonth'] = $threemonthstart . '~' . $threemonthend;
        $res = array_search($date, $datearr);
        return $res;
    }
}