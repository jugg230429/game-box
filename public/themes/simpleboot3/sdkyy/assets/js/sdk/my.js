/**
 * 我的弹窗
 */

var myPop = '<div class="mypop js-pop myPerson">'    +
                '<div class="giftpop-con myBigBox">'+
                    '<img src="/themes/simpleboot3/sdkyy/assets/images/tc_close.png" class="btn-close js-btn-close myCloseBtn">'+
                    '<div class="myContainer">' +
                        '<div class="myHaeder"><img src="'+userinfo.head_img+'" onerror="this.src=\'/static/images/empty.jpg\';this.onerror=null" ></div>'+
                        '<div class="myDetail">'+
                            '<div class="myAccountNumber" id="accountNum">'+userinfo.account+'</div>'+
                            '<span class="integral">积分</span><span class="integralValue">'+userinfo.point+'</span>'+
                            '<span class="integral">平台币</span><span id="plantformNum">'+userinfo.balance+'</span>'+
                            '<div class="buttonChoose">' +
                                '<span class="recharge"><a class="do-recharge" href="'+pay_url+'" target="_blank">充值</a></span>';

                if(today_signed=='0'){
                    myPop += '<span class="recharge changeColor"><a href="#" id="signIn">签到</a></span>';
                }else{
                    myPop += '<span class="recharge changeColor successSignIn"><a href="#" id="signIn">签到</a></span>';
                }

                var ratio = (userinfo.cumulative/userinfo.next_level_all_num)*100;

    myPop += '</div>'+
                        '</div>' +
                    '</div>' +
                    '<div class="gradeNum">'+
                        '<span class="levelNum">LV '+userinfo.vip_level+'</span>'+
                        '<span class="gradeProgress">'+
                            '<span class="processLine" style="width: '+ratio+'%"></span>'+
                            '<div class="vipDialog" style="display:none">'+
                                '<div class="vipTopLevel">'+
                                    '<span>VIP等级：</span><span class="yelloVip">VIP'+userinfo.vip_level+'</span><span class="limitNum">('+userinfo.cumulative+'/'+userinfo.next_level_all_num+')</span>'+
                                '</div>'+
                                '<div class="consume">'+
                                    '再消费 <span class="yelloVip">'+userinfo.next_level_num+'</span> 即可升级'+
                                '</div>'+
                                '<button class="checkPrivilege js-btn-vip">查看特权</button>'+
                            '</div>'+
                        '</span>'+
                    '</div>'+
                    '<ul class="myGameBottom"> 推荐游戏：'+
                       // ' <li class="myGameBottom-item yellow-left"><a href="#">九州仙剑录</a><a href="#">双线23区</a><a href="#" class="startChange">开始游戏</a></li>'+
                       // '<li class="myGameBottom-item yellow-left"><a href="#">暗黑大天使</a><a href="#">战场23区</a><a href="#" class="startChange">开始游戏</a></li>'+
                       // '<li class="myGameBottom-item yellow-left"><a href="#">三国杀十周年关羽</a><a href="#">公测289区</a><a href="#" class="startChange">开始游戏</a></li>'+
                       // '<li class="myGameBottom-item yellow-left"><a href="#">三国杀十周年关羽</a><a href="#">首发公测289区</a><a href="#" class="startChange">开始游戏</a></li>'+
                       // '<li class="myGameBottom-item yellow-left"><a href="#">三国杀十周年关羽</a><a href="#">首发公测289区</a><a href="#" class="startChange">开始游戏</a></li>'+
                    '</ul>'+
                '</div>' +
            '</div>' ;

$('body').append(myPop);

var count = userinfo.point;
$(".recharge").on('click','#signIn',function () {

    if(age_status == 0){

        layer.confirm('您还未实名<br>需认证后才可以签到哦~',{
            title: false,
            closeBtn:0 ,
            skin: 'attestation-class',
            btn: ['取消','去认证'],

        }, function(index, layero){
            layer.close(index);
        }, function(index){
            var realname="";
            realname+='<div class="realnamepop js-pop ">'+
                '<div class="realnamepop-con approve">';
            if(is_force_real != 1){
                realname+=  '<div  class="realnamepop-title">实名认证<img src="/themes/simpleboot3/sdkyy/assets/images/icon_close.png" class="btn-close js-btn-close realnameIcon"></div>';
            }
            realname+=   '<form>';
            if(auth_msg!=''){
                realname+='<p class="realname_page_tap">'+auth_msg;
            }else{
                realname+='<p class="realname_page_tap">根据国家关于《防止未成年人沉迷网络游戏的通知》要求,平台所有玩家必须完成实名认证,否则将会被禁止进入游戏。';
            }
            realname+='</p>'+
                '<div class="pr"><input type="text" id="real_name2" name="real_name2" maxlength="25" placeholder="姓名"><img src="__TMPL__/mobilepublic/assets/images/common_btn_delet.png" alt="" class="clear_texts fristclear_texts"></div>'+
                '<div class="pr"><input type="text" id="idcard2" name="idcard2"  maxlength="18" placeholder="身份证号"><img src="__TMPL__/mobilepublic/assets/images/common_btn_delet.png" alt="" class="clear_texts"></div>'+
                '<div class=" tj_btn0" style="width: 340px;\n' +
                '    border-radius: 3.5px;\n' +
                '    color: #ffffff;\n' +
                '    text-align: center;\n' +
                '    height: 40px;\n' +
                '    margin-left: 3.5%;\n' +
                '    background-color: #018FFF;\n' +
                '    display: flex;\n' +
                '    align-items: center;\n' +
                '    justify-content: center;\n' +
                '\tmargin-bottom: 3rem;\n' +
                '\tcursor:pointer;\n' +
                '\tmargin-left: 65px;">'+
                '<p>立即认证</p>'+
                '</div>'+
                '</form>'+
                '</div>'+
                '</div>';
            $('body').append(realname);
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
            $('body').on('click','.tj_btn0',function () {
                var real_name2 = $("#real_name2").val();
                if(real_name2 == ''){
                    layer.msg("姓名不能为空");

                    return false;
                }
                var idcard2 = $("#idcard2").val();
                if(idcard2 == ''){
                    layer.msg("身份证号不能为空");

                    return false;
                }
                ajax_post('/sdkyy/game/user_auth','real_name='+real_name2+'&idcard='+idcard2+'&game_id='+game_id, function (result) {
                    if(result.user_id == 0){
                        top.location.reload();
                    }else{
                        console.log(result);
                        layer.msg(result.msg);
                        if(result.status == 1){
                            $('body').find(".realnamepop").css('display','none');
                            location.reload();
                        }
                    }
                });
            })
        });
        return false;

    }



    if($(".changeColor").hasClass('signin_disabled')){
        return false;
    }else{
        $.ajax({
            url:do_sign_url,
            type:"post",
            success:function (res) {
                if(res.code==1){
                    layer.open({
                        title: '签到'
                        ,content: '签到成功'
                    });
                    $('.changeColor').addClass('successSignIn');
                    count++;
                    $('.integralValue').html(count)
                }else{
                    layer.msg(res.msg);
                }
            },error:function () {
                layer.msg('服务器错误，请稍后再试');
            }
        })
    }
});

$('.gradeProgress').mouseover(function() {
    $('.vipDialog').show()
})
$('.gradeProgress').mouseout(function() {
    $('.vipDialog').hide()
})


$(".do-recharge").click(function () {
    var url = $(this).attr('href');
    $.ajax({
        url: url,
        async: false,
        type: "get",
        cache: false,
        success: function (res) {
            if (res.code == '0') {
                layer.msg(res.msg);
                ret = false
            } else {
                ret  = true;
            }
        }
    });
    return ret;
});


//获取推荐游戏
ajax_post('/sdkyy/game/recommend_game','game_id='+game_id, function (result) {
    var html = "推荐游戏";
    var data = result.data;
    if(data && data.length) {
        for (var i in data) {
            html += '<li class="myGameBottom-item yellow-left">' +
                '<a href="'+data[i].play_url+'">'+data[i].game_name+'</a>' +
                '<a href="'+data[i].play_url+'">'+data[i].server_name+'</a>' +
                '<a href="'+data[i].play_url+'" class="startChange">开始游戏</a></li>';
        }
    }
    $(".myGameBottom").html(html);
});

