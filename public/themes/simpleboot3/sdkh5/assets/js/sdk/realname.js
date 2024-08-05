/**
 * 实名认证弹窗
 */
    var realname="";
    realname += '<div class="realnamepop js-pop ">'+
        '<div class="realnamepop-con">';
    if(is_force_real != 1){
        realname += '<div  class="realnamepop-title"><img src="/themes/simpleboot3/sdkh5/assets/images/icon_close.png" class="btn-close js-btn-close"></div>';
    }
    realname +=  '<form>';
    if(auth_msg!=''){
        realname+='<p class="realname_page_tap">'+auth_msg;
    }else{
        realname+='<p class="realname_page_tap">根据国家关于《防止未成年人沉迷网络游戏的通知》要求，平台所有玩家必须完成实名认证，否则将会被禁止进入游戏。';
    }
    realname+='</p>'+
        '<div class="pr"><input type="text" id="real_name" name="real_name" maxlength="25" placeholder="请输入您的真实姓名"><img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_delet.png" alt="" class="clear_texts fristclear_texts  delet"></div>'+
        '<div class="pr"><input type="text" id="idcard" name="idcard"  maxlength="18" placeholder="输入身份证号，如有字母请小写" style="margin-top: 1.2rem"><img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_delet.png" alt="" class="clear_texts del1"></div>'+
        '<div class="tj_btn">'+
        '<p>立即认证</p>'+
        '</div>'+
        '</form>'+
        '</div>'+
        '</div>';
    // $('body').append(realname);
    $(realname).appendTo($('body')).css('display','block');

    $(function(){
        var u = navigator.userAgent;
        var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
        var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
        if(isiOS) {
           $('.realnamepop-title').css('height','1rem')
           $('.realnamepop-con').css({
            'min-height':'0%!important',
           })
           $('.tj_btn').css('margin-top','1rem')
        }
		
   })
    // input 右侧x号 点击清除
    $('body').on('focus', 'input[type=text],input[type=password]', function() {

        $(this).siblings('.clear_texts').css("display","block")

        return false;
    });
    $('body').on('blur', 'input[type=text]', function() {
        $(this).siblings('.clear_texts').css("display","none")
        return false;
    });
    $('body').on('mousedown', '.clear_texts', function() {
        $(this).css("display","none").siblings('input').val('').focus();
        return false;
    });
    $('body').on('click','.tj_btn',function () {
        var real_name = $("#real_name").val();
        if(real_name == ''){
           layer.msg("姓名不能为空");

            return false;
        }
        var idcard = $("#idcard").val();
        if(idcard == ''){
              layer.msg("身份证号不能为空");

            return false;
        }
        ajax_post('/sdkh5/game/user_auth','real_name='+real_name+'&idcard='+idcard+'&game_id='+game_id, function (result) {
            if(result.user_id == 0){
                top.location.reload();
            }else{
                layer.msg(result.msg);
                if(result.status == 1){
                    $('body').find(".realnamepop").css('display','none');
                    setTimeout(function () {
                        top.location.reload();
                    }, 100);
                } else {
                    setTimeout(function () {
                        layer.closeAll();
                    }, 2000);
                }
            }
        });
    })
