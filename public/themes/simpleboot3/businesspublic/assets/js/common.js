(function (w) {
    var ThinkPHP =　w.Think || {};

    ThinkPHP || $.error('ThinkPHP不存在');

    ThinkPHP.setValue = function(name, value){
        var first = name.substr(0,1), input, i = 0, val;
        if(value === "") return;
        if("#" === first || "." === first){
            input = $(name);
        } else {
            input = $("[name='" + name + "']");
        }

        if(input.eq(0).is(":radio")) { //鍗曢€夋寜閽�
            input.filter("[value='" + value + "']").each(function(){this.checked = true});
        } else if(input.eq(0).is(":checkbox")) { //澶嶉€夋
            if(!$.isArray(value)){
                val = new Array();
                val[0] = value;
            } else {
                val = value;
            }
            for(i = 0, len = val.length; i < len; i++){
                input.filter("[value='" + val[i] + "']").each(function(){this.checked = true});
            }
        } else {  //鍏朵粬琛ㄥ崟閫夐」鐩存帴璁剧疆鍊�
            input.val(value);
        }
    }

    w.Think = ThinkPHP;
}(window));

$(".select_gallery").select2();

//退出登录
$('body').on('click','.loginout', function() {
    $.ajax({
        type: "POST",
        url: logoutUrl,
        success: function(res) {
            layer.msg('退出成功', {
                icon: 1,
            });
            setTimeout(function() {
                window.location.href = indexUrl;
            }, 1500);
        },
        error: function() {
            layer.msg('服务器故障，请稍候再试');
        }
    })
});


//搜索功能
$("body").on('click', '#search', function() {
    var sdate = $("#sdate").val();
    var edate = $("#edate").val();

    if(($("#sdate").data('required')==true || $("#edate").data('required')==true) && sdate == "" && edate == "") {
        layer.msg("请选择时间范围");
        return false;
    }

    if(sdate != "" && edate != "" && sdate > edate) {
        layer.msg("开始时间必须小于等于结束时间");
        return false;
    }

    var url = $(this).attr('url');
    var query = $('.jssearch').find('input').serialize();
    query += "&" + $('.jssearch').find('select').serialize();
    query = query.replace(/(&|^)(\w*?\d*?\-*?_*?)*?=?((?=&)|(?=$))/g, '');
    query = query.replace(/^&/g, '');
    if(typeof url == 'undefined'){
        url = '?' + query;
    }else if(url.indexOf('?') > 0) {
        url += '&' + query;
    } else {
        url += '?' + query;
    }
    window.location.href = url;
    return false;
});
$('.jspagerow').change(function(){
    value = $('.jspagerow option:selected').attr('vv');
    $('#page').remove();
    $('#search').append('<input type="hidden" id="page" name="row" value="'+value+'">');
    $('#search').click();
});
// 分页
$(".page-item").wrapAll("<div class='fr jspageitem'></div>");
var $one_li = $(".jspagerow");
var $two_li = $(".jspageitem");
$two_li.insertBefore($one_li);

//导出数据
/*$("body").on('click','#exportData',function() {
    var url = $(this).attr('href');
    console.log(url);
    var query = $('.jssearch').find('input').serialize();
    query += "&" + $('.jssearch').find('select').serialize();
    query = query.replace(/(&|^)(\w*?\d*?\-*?_*?)*?=?((?=&)|(?=$))/g, '');
    query = query.replace(/^&/g, '');
    if(url.indexOf('?') > 0) {
        url += '&' + query;
    } else {
        url += '?' + query;
    }
    window.location.href = url;
});*/
