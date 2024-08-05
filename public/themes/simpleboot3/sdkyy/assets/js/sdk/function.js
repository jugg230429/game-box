/**
 * Created by yyh on 2020/06/10.
 */
/**
 * @desc ajax post
 * @param string url
 * @param json data
 * @param function
 * */
if (typeof jQuery == 'undefined') {
    document.write(unescape("%3Cscript src='/static/js/jquery-1.11.1.min.js' type='text/javascript'%3E%3C/script%3E"));
}

function ajax_post(url,data,callback){
    $.ajax({
        type: "post",
        url: url,
        dataType: 'json',
        async : false,
        data: data,
        success: function (data) {
            callback(data);
        },
        error:function (e) {
            //调试数据
            // var str = JSON.stringify(e);
            // alert(str);
            alert("连接失败，请刷新后重试");
        }
    });
}

//1.判断 PC WEIXIN
function isPCWeixin(){
    return navigator.userAgent.toLowerCase().match(/WindowsWechat/i)=="windowswechat";
}
//2.判断 微信
function isWeixin() {
    return navigator.userAgent.toLowerCase().match(/MicroMessenger/i)=="micromessenger";
}
//3.判断手机
function isMobile() {
    var userAgent = navigator.userAgent.toLowerCase();
    var agents = ["android","iphone","symbianos","windows phone","ipad","ipod"];
    for(var v = 0; v < agents.length; v++) {
        if(userAgent.indexOf(agents[v]) >= 0) {
            return true;
        }
    }
    return false;
}

//4.判断 qq
function isQQ(){
    return navigator.userAgent.toLowerCase().match(/QQ/i)=='qq';
}
//5. 判断 IOS设备
function isIos(){
    return !!navigator.userAgent.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);
}
function isSafari() {
    return navigator.userAgent.indexOf("Safari")>0;
}
//检测手机号码
function checkPhone(tel) {
    var relue = /^1[0-9]{10}$/;
    if(!relue.test(tel)){
        return false;
    }else {
        return true
    }
}
//检测email
function checkEmail(email) {
    var relue = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
    if(!relue.test(email)){
        return false;
    }else {
        return true
    }
}

function setCookie(key,value,time)
{
    var date=new Date();  //本地时间
    date.setDate(date.getDate()+time);  //设置设置一个时间间隔
    document.cookie=key+ "=" +encodeURI(value)+  ((time==null) ? "" : ";expires="+date.toGMTString()); //encodeURI(value)把值转成编码防止当cookie是想中文的这种特殊字符时读取不出来
}

//读取cookie
function getCookie(key) {
    if (document.cookie.length>0)
    {
        var cookieArr =document.cookie.split('; ');
        for(var i=0;i<cookieArr.length;i++)
        {
            var keyArr = cookieArr[i].split('=');
            for(var j=0;j<keyArr.length;j++)
            {
                if(keyArr[0]==key)
                {
                    return decodeURI(keyArr[1])
                }
            }
        }
    }
}

//解析url
function parseURL(url) {
    var a =  document.createElement('a');
    a.href = url;
    return {
        source: url,   //详细的url
        protocol: a.protocol.replace(':',''),   //例如http协议
        host: a.hostname,
        port: a.port,
        query: a.search,
        params: (function(){
            var ret = {},
                seg = a.search.replace(/^\?/,'').split('&'),
                len = seg.length, i = 0, s;
            for (;i<len;i++) {
                if (!seg[i]) { continue; }
                s = seg[i].split('=');
                ret[s[0]] = s[1];
            }
            return ret;
        })(),
        file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
        hash: a.hash.replace('#',''),
        path: a.pathname.replace(/^([^\/])/,'/$1'),
        relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,''])[1],
        segments: a.pathname.replace(/^\//,'').split('/')
    };
}