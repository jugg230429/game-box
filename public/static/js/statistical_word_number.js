/**
 * Created by Administrator on 2020/3/12.
 */
/*
 *******************************************************************
 重要，因为设置maxLength的缘故，textarea自带属性的计算方式为无论中英文都计算为一个字符，
 和本文有出入，如果希望同步需要只需要切换最下方向标签内写值的方法即可（已提供）
 *******************************************************************
 */

//传入参数依次为textarea的id和需要输出字数的span的id
function startMonitor(id, outId,max) {

    //给textarea附加最大字数限制
    $('#' + id + '').attr('maxLength',max);

    var EventUtil = function() {};

    EventUtil.addEventHandler = function(obj, EventType, Handler) {
        //如果是FF
        if (obj.addEventListener) {
            obj.addEventListener(EventType, Handler, false);
        }
        //如果是IE
        else if (obj.attachEvent) {
            obj.attachEvent('on' + EventType, Handler);
        } else {
            obj['on' + EventType] = Handler;
        }
    }

    if ($("#" + id + "")) {
        var target = document.getElementById(id);
        EventUtil.addEventHandler(target, 'propertychange', CountChineseCharacters);
        EventUtil.addEventHandler(target, 'input', CountChineseCharacters);
        //EventUtil.addEventHandler($('chaptercontent'),'keydown',CountChineseCharacters('chaptercontent'));
    }
    window.onload = CountChineseCharacters();

    function CountChineseCharacters() {
        Words = $('#' + id + '').val();
        var W = new Object();
        var Result = new Array();
        var iNumwords = 0;
        var sNumwords = 0;
        var sTotal = 0; //双字节字符;
        var iTotal = 0; //中文字符；
        var eTotal = 0; //Ｅ文字符
        var otherTotal = 0;
        var bTotal = 0;
        var inum = 0;

        for (i = 0; i < Words.length; i++) {
            var c = Words.charAt(i);
            if (c.match(/[\u4e00-\u9fa5]/)) {
                if (isNaN(W[c])) {
                    iNumwords++;
                    W[c] = 1;
                }
                iTotal++;
            }
        }

        for (i = 0; i < Words.length; i++) {
            var c = Words.charAt(i);
            if (c.match(/[^\x00-\xff]/)) {
                if (isNaN(W[c])) {
                    sNumwords++;
                }
                sTotal++;
            } else {
                eTotal++;
            }
            if (c.match(/[0-9]/)) {
                inum++;
            }
        }
        //新浪计算方式
        //$('#' + outId + '').text(Math.ceil(sTotal + eTotal / 2)+"/"+max);
        //无论英文汉子都算一个字符方式
        $('#' + outId + '').html((sTotal + eTotal)+"/"+max);
    }

}
