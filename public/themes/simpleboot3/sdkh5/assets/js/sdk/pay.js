/**
 * Created by yyh on 2020/6/10.
 */
if(real_pay_status == 1 && age_status == 0){
    var realnamepop2="";
    realnamepop2 +='<div class="realnamepop3 js-pop" style="display: none">'+
        ' <div class="realnamepop-con">'+
        '<div class="realnamepop-title">'+
        '<img src="/themes/simpleboot3/sdkh5/assets/images/icon_close.png" class="btn-close js-btn-close">'+
        '</div>'+
        '<form>';
	if(real_pay_msg == ''){
        realnamepop2 +='<p class="realname_page_tap">根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！</p>';
    }else{
        realnamepop2 +='<p class="realname_page_tap">'+real_pay_msg+'</p>';
    }
     realnamepop2 += '<div class="pr">'+
        '<input type="text" id="real_name3" name="real_name3" maxlength="25" placeholder="请输入您的真实姓名">'+
        '<img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_delet.png" alt="" class="clear_texts fristclear_texts delet">'+
        '</div>'+
        '<div class="pr">'+
        ' <input type="text" id="idcard3" name="idcard3" maxlength="18" placeholder="输入身份证号，如有字母请小写" style="margin-top: 1.2rem">'+
        '<img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_delet.png" alt="" class="clear_texts del1">'+
        '</div>'+
        '<div class="tj_btn3"><p>立即认证</p></div>'+
        '</form>'+
        '</div>'+
        '</div>';
    $('body').append(realnamepop2);
    $('body').on('click','.tj_btn3',function () {
        var real_name = $("#real_name3").val();
        if(real_name == ''){
           layer.msg("姓名不能为空");
			
            return false;
        }
        var idcard = $("#idcard3").val();
        if(idcard == ''){
               layer.msg("请输入身份证号码");
			
            return false;
        }
        ajax_post('/sdkh5/game/user_auth','real_name='+real_name+'&idcard='+idcard, function (result) {
            if(result.user_id == 0){
                top.location.reload();
            }else{
                layer.msg(result.msg);
                if(result.status == 1){
                    $('body').find(".realnamepop").css('display','none');
                    top.location.reload();
                }
            }
        });
    })
}

window.addEventListener("message", function (event) {
    switch (event.data.operation) {
        case "pay": {
            var cporder = event.data.param;
            ajax_post('/sdkh5/pay/pay_init', cporder, function (result) {
                console.log(result);
                if (result.status == '200') {
                    $('body').append(result.data.paysdk);
                    $(".paypop").show();
                } 
				else if(result.status == '-100'){
					 $(".realnamepop3").show();
				}
				else {
                    layer.msg(result.message);
                }
				
            });
            break;
        }
    }
}, false);
// input 右侧x号 点击清除
$('body').on('focus', 'input[type=text],input[type=password],input[type=number]', function() {

    $(this).siblings('.clear_texts').show()

    return false;
});
$('body').on('blur', 'input[type=text],input[type=password],input[type=number]', function() {
    $(this).siblings('.clear_texts').hide()
    return false;
});
$('body').on('mousedown', '.clear_texts', function() {
    $(this).hide().siblings('input').val('').focus();
    parent = $(this).closest('.jsclear').data('value');
    if(parent!=undefined){
        change_but = "{$if_next}">0?'registered_modal_next_btn':'registered_modal_pub_btn';
        if(parent='registered_modal_real'){
            change_but = 'registered_modal_pub_btn';
        }
        change_submit_btn($('.'+parent),$('.'+parent).find('.'+change_but))
    }
    return false;
});