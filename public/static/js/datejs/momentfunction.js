//@author yyh
$(function () {
    var maxDate = window.maxDate||moment().format('YYYY-MM-DD');
    var autoUpdateInput = window.date_autoUpdateInput != undefined?window.date_autoUpdateInput:true;
    var setranges = window.setranges!=undefined?window.setranges:{
        //'最近1小时': [moment().subtract('hours',1), moment()],
        '今日': [moment().startOf('day'), moment()],
        '昨日': getYesterday(),
        '过去3天': getLastNumDays(3),
        '过去7天': getLast7Days(1),
        '过去14天': getLast7Days(2),
        '过去30天': getLast30Days(),
        '所有': getBegin(),
        // '过去4周': getLastWeekDays(4),
        // '过去8周': getLastWeekDays(8),
        // '过去12周': getLastWeekDays(12),
        // '过去3个月': getLastMonthDays(3),
    };
    //区间时间插件
    $("input[name='rangepickdate']").daterangepicker(
        {
            maxDate:maxDate,
            // maxDate:moment(),
            startDate: start,
            endDate: end,
            // dateLimit: {
            //     days: 10
            // }, //起止时间的最大间隔
            showDropdowns: false,//年月份下拉选择
            showWeekNumbers: false, //是否改年显示第几周
            // autoApply:true,
            opens:'right',//位置
            // drops:true,//确定取消按钮
            showDropdowns:true,
            locale:true,
            autoUpdateInput:autoUpdateInput,
            // showWeekNumbers:true,
            ranges: setranges,
            locale: {
                format: "YYYY-MM-DD",
                separator: "至",
                applyLabel: "确认",
                cancelLabel: "取消",
                // fromLabel: "开始时间",
                // toLabel: "结束时间",
                customRangeLabel: "自定义日期",
                daysOfWeek: ["日","一","二","三","四","五","六"],
                monthNames: ["一月","二月","三月","四月","五月","六月","七月","八月","九月","十月","十一月","十二月"],
                firstDay: 1
            },
        }
    ).on('cancel.daterangepicker', function(ev, picker) {
        if($('#rangepickdate').val()!=''){
            $("#rangepickdate").val(picker.startDate.format('YYYY-MM-DD')+"至"+picker.endDate.format('YYYY-MM-DD'));
        }
    }).on('apply.daterangepicker', function(ev, picker) {
        $("#rangepickdate").val(picker.startDate.format('YYYY-MM-DD')+"至"+picker.endDate.format('YYYY-MM-DD'));
    }, function(start, end, label) {
        // $('.daterangepicker').find('.ranges ul li:last-child').addClass('active').siblings().removeClass('active');
        // console.log(start);
    });
    $('.daterangepicker').find('.ranges ul li:last-child').css('color','green');
});

// 获取起始日期至昨日
function getBegin () {
    let date = []
    date.push("2019-08-01")
    date.push(moment().subtract('days', 0).format('YYYY-MM-DD'))
    return date
}
// 获取起始日期至昨日
function getBeginaddToday () {
    let date = []
    date.push("2019-08-01")
    date.push(moment().startOf('day'), moment())
    return date
}
// 获取昨天的开始结束时间
function getYesterday () {
    let date = []
    date.push(moment().subtract('days', 1).format('YYYY-MM-DD'))
    date.push(moment().subtract('days', 1).format('YYYY-MM-DD'))
    return date
}
//获取过去几天的开始结束时间
function getLastNumDays (day) {
    if(day==''||day ==undefined){
        day = 1;
    }
    let date = []
    date.push(moment().subtract('days', (day-1)).format('YYYY-MM-DD'))
    date.push(moment().format('YYYY-MM-DD'))
    return date
}
// 获取最近七天的开始结束时间
function getLast7Days (day) {
    if(day==''||day ==undefined){
        day = 1;
    }
    let date = []
    date.push(moment().subtract('days', (7*day-1)).format('YYYY-MM-DD'))
    date.push(moment().format('YYYY-MM-DD'))
    return date
}
// 获取最近30天的开始结束时间
function getLast30Days () {
    let date = []
    date.push(moment().subtract('days', 29).format('YYYY-MM-DD'))
    date.push(moment().format('YYYY-MM-DD'))
    return date
}
// 获取上一周的开始结束时间
function getLastWeekDays (day) {
    if(day==''||day ==undefined){
        day = 1;
    }
    // debugger
    let date = []
    let weekOfday = parseInt(moment().format('d')) // 计算今天是这周第几天  周日为一周中的第一天
    let start = moment().subtract(weekOfday + (7*day)-1, 'days').format('YYYY-MM-DD') // 周一日期
    // console.log(start);
    let end = moment().subtract(weekOfday, 'days').format('YYYY-MM-DD') // 周日日期
    // console.log(end);

    date.push(start)
    date.push(end)
    return date
}
// 获取上一个月的开始结束时间
function getLastMonthDays () {
    let date = []
    let start = moment().subtract('month', 1).format('YYYY-MM') + '-01'
    let end = moment(start).subtract('month', -1).add('days', -1).format('YYYY-MM-DD')
    date.push(start)
    date.push(end)
    return date
}
// 获取前几个月的开始结束时间
function getLastMonthDays (day) {
    if(day==''||day ==undefined){
        day = 1;
    }
    let date = []
    let start = moment().subtract('month', 1*day).format('YYYY-MM') + '-01'
    let end =  moment(moment().subtract('month', 1).format('YYYY-MM') + '-01').subtract('month', -1).add('days', -1).format('YYYY-MM-DD')
    date.push(start)
    date.push(end)
    return date
}
// 获取当前周的开始结束时间
function getCurrWeekDays () {
    let date = []
    let weekOfday = parseInt(moment().format('d')) // 计算今天是这周第几天 周日为一周中的第一天
    let start = moment().subtract(weekOfday, 'days').format('YYYY-MM-DD') // 周一日期
    let end = moment().add(7 - weekOfday - 1, 'days').format('YYYY-MM-DD') // 周日日期
    date.push(start)
    date.push(end)
    return date
}
// 获取当前月的开始结束时间
function getCurrMonthDays () {
    let date = []
    let start = moment().add('month', 0).format('YYYY-MM') + '-01'
    let end = moment(start).add('month', 1).add('days', -1).format('YYYY-MM-DD')
    date.push(start)
    date.push(end)
    return date
}