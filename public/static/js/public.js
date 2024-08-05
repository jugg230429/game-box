$(document).ready(function () {
    if($('.fast_login_qq').length===0){
        $('.fast_login_wx').css('margin-left','0px')
    }
    // 首页推荐游戏悬浮下拉效果
    $(".other_remgame_img").hover(function () {
        $(this).children('.togdiv').stop().slideDown()
    }, function () {
        $(this).children('.togdiv').stop().slideUp()
    });
    function checkBrowser() {
        var ua = navigator.userAgent.toLocaleLowerCase();
        var browserType = null;
        if (ua.match(/msie/) != null || ua.match(/trident/) != null) {
            browserType = "IE";
            browserVersion = ua.match(/msie ([\d.]+)/) != null ? ua.match(/msie ([\d.]+)/)[1] : ua.match(/rv:([\d.]+)/)[1];
        } else if (ua.match(/firefox/) != null) {
            browserType = "火狐";
            $('.game_type_img').css('top','41px')
        } else if (ua.match(/ubrowser/) != null) {
            browserType = "UC";
        } else if (ua.match(/opera/) != null) {
            browserType = "欧朋";
        } else if (ua.match(/bidubrowser/) != null) {
            browserType = "百度";
        } else if (ua.match(/metasr/) != null) {
            browserType = "搜狗";
        } else if (ua.match(/tencenttraveler/) != null || ua.match(/qqbrowse/) != null) {
            browserType = "QQ";
        } else if (ua.match(/maxthon/) != null) {
            browserType = "遨游";
        } else if (ua.match(/chrome/) != null) {
            var is360 = _mime("type", "application/vnd.chromium.remoting-viewer");

            function _mime(option, value) {
                var mimeTypes = navigator.mimeTypes;
                for (var mt in mimeTypes) {
                    if (mimeTypes[mt][option] == value) {
                        return true;
                    }
                }
                return false;
            }
            if (is360) {
                browserType = '360';
                $('.game_detail_hotgame_type img').css('top','65px')
                $('.game_detail_hotgame_type img.one').css('top','60px')
            } else {
                browserType = "谷歌";
                $('.game_detail_hotgame_type img.one').css('top','61px')
                $('.game_type_img').css('top','39px')
            }
        } else if (ua.match(/safari/) != null) {
            browserType = "Safari";
        }
        return browserType;
    }
    var broType = checkBrowser();
    $('.toggle_forgetpwd_email span').click(function(){
        layer.closeAll()
        var email_modal=$('.retrieve_pwd_email_modal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '346px'],
            offset: 'auto',
            content: email_modal,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.retrieve_pwd_email_modal').hide()
            }
        })
    })
    $('.toggle_forgetpwd_mobile span').click(function(){
        layer.closeAll()
        var mobile_modal=$('.retrieve_pwd_algin_modal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '346px'],
            offset: 'auto',
            content: mobile_modal,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.retrieve_pwd_algin_modal').hide()
            }
        })
    })
    // 切换不同注册方式
    $('.zhuce_type_item').click(function () {
        $('.zhuce_type_item').children('p').removeClass('active_zhuce_type_title')
        $('.zhuce_type_item').children('div').removeClass('active_zhuce_type')
        $(this).children('p').addClass('active_zhuce_type_title')
        $(this).children('div').addClass('active_zhuce_type')
        if ($(this).children('p').text().indexOf('手机') !== -1) {
            $('.zhuce_mobile_con').show()
            $('.zhuce_zhanghao_con').hide()
            $('.zhuce_email_con').hide()
        }
        if ($(this).children('p').text().indexOf('账号') !== -1) {
            $('.zhuce_mobile_con').hide()
            $('.zhuce_zhanghao_con').show()
            $('.zhuce_email_con').hide()
        }
        if ($(this).children('p').text().indexOf('邮箱') !== -1) {
            $('.zhuce_mobile_con').hide()
            $('.zhuce_zhanghao_con').hide()
            $('.zhuce_email_con').show()
        }
    })
    $('.forget_password').click(function(){
        layer.closeAll()
        var consa = $('.retrieve_pwd_modal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '336px'],
            offset: 'auto',
            content: consa,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.retrieve_pwd_modal').hide()
            }
        })
    })
    //游客弹窗
    $('.login_youke').click(function(){
        layer.closeAll()
        var consa = $('.youkePop')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['296px', '224px'],
            offset: 'auto',
            content: consa,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.youkePop').hide()
            }
        })
    })
    
    $('.retrieve_pwd_onclickbtn').click(function(){
        var account  = $("#forget_account").val();
        var isok = false;
        var consaa = '';
        $.ajax({
            url:forget_url,
            type:'post',
            dataType:'json',
            async:false,
            data:{account:account},
            success:function(res){
                if(res.code==0){
                    isok = true;
                    layer.msg(res.msg,{skin: 'demo-red'});
                }else{
                    if(res.code == 1 ){
                        consaa = $('.retrieve_pwd_algin_modal');
                    }else if(res.code == 3) {
                        consaa = $('.retrieve_pwd_algin_modal');
                        $(".toggle_forgetpwd_email").hide();
                        $(".toggle_forgetpwd_mobile").hide();
                    }else{
                        consaa = $('.retrieve_pwd_email_modal');
                        $(".toggle_forgetpwd_email").hide();
                        $(".toggle_forgetpwd_mobile").hide();
                    }
                }
            },
            error :function(){
                isok = true;
                layer.msg('服务器错误',{skin: 'demo-red'});
            }
        });
        if(isok == true){
            return false;
        }
        layer.closeAll();
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '346px'],
            offset: 'auto',
            content: consaa,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.retrieve_pwd_algin_modal').hide()
            }
        })
    })
    // 手机找回密码--two
    $('.retrieve_pwd_algin_onclickbtn').click(function(){
        var phone = $("#forget_phone").val();
        var code = $("#forget_code").val();
        var verify_tag = $('#verify_pop3').siblings('.verify_tag').val();
        var verify_token = $('#verify_pop3').siblings('.verify_token').val();
        var account = $("#forget_account").val();
        if(verify_tag == '' || verify_token==''){
            layer.msg('请先进行图形验证',{skin: 'demo-red'});
            return false;
        }
        var isok = false;
        $.ajax({
            url:forget_sms_url,
            type:'post',
            dataType:'json',
            async:false,
            data:{phone:phone,verify_tag:verify_tag,verify_token:verify_token,code:code},
            success:function(res){
                if(res.code==0){
                    isok = true;
                    layer.msg(res.msg,{skin: 'demo-red'});
                }
            },error:function(){
                isok = true;
                layer.msg('服务器错误',{skin: 'demo-red'});
            }
        });
        if(isok == true){
            return false;
        }
        layer.closeAll()
        var consaa = $('.retrieve_pwd_last_modal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '346px'],
            offset: 'auto',
            content: consaa,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.retrieve_pwd_last_modal').hide()
            }
        })
    })
    // 邮箱找回密码 --two
    $('.retrieve_pwd_algin_onclickbtns').click(function(){
        var email = $("#forget_email").val();
        var code = $("#forget_email_code").val();
        var isok = false;
        $.ajax({
            url:forget_email_url,
            type:'post',
            dataType:'json',
            async:false,
            data:{email:email,code:code},
            success:function(res){
                if(res.code==0){
                    isok = true;
                    layer.msg(res.msg,{skin: 'demo-red'});
                }
            },error:function(){
                isok = true;
                layer.msg('服务器错误',{skin: 'demo-red'});
            }
        });
         if(isok == true){
             return false;
         }
        layer.closeAll()
        var consaaa = $('.retrieve_pwd_last_modals')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '346px'],
            offset: 'auto',
            content: consaaa,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.retrieve_pwd_last_modals').hide()
            }
        })
    })
    $('.retrieve_pwd_algin_last_line span').click(function(){
        // 没有绑定邮箱
        // layer.msg('当前账号未绑定邮箱，无法找回密码')
        // 绑定邮箱
        $('.twocol').text('邮箱验证')
        $('.retrieve_pwd_algin_last_line_noemail').show()
        $('.retrieve_pwd_algin_last_line').hide()
        $('.retrieve_pwd_username input').attr('placeholder','绑定邮箱')
    })
    $('.retrieve_pwd_algin_last_line_noemail span').click(function(){
        // 没有绑定手机
        // layer.msg('当前账号未绑定手机，可联系客服找回密码')
        // 绑定邮箱
        $('.twocol').text('短信验证')
        $('.retrieve_pwd_algin_last_line_noemail').hide()
        $('.retrieve_pwd_algin_last_line').show()
        $('.retrieve_pwd_username input').attr('placeholder','绑定手机号')
    })
    $('.retrieve_pwd_title img').click(function(){
        layer.closeAll()
    })
    // 打开注册弹窗
    $('.zhuce').click(function () {
        if(is_blank == 1){
            layer.msg('暂时无法注册，请联系客服');
            return false;
        }
        var cons = $('#zhucemodal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '575'],
            offset: 'auto',
            content: cons,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('#zhucemodal').hide()
            }
        })
    })
    $('.login').click(function () {
        var conss = $('.login_modals')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['305px', '320px'],
			skin:'loginclass',
            offset: 'auto',
            content: conss,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.login_modals').hide()
            }
        })
    })
    $('#search').bind('input propertychange', function() {searchinfo()});
    function searchinfo(){
             if($('#search').val()!==''||$('#search').val()!=='输入关键词'){
            $('.inputdowndiv').show()
        }
        if($('#search').val()===''||$('#search').val()==='输入关键词'){
            $('.inputdowndiv').hide()
        }
    }
    // $('#search').blur(function(event){
    //     $('.inputdowndiv').hide()
    // })
    // 获取验证码button效果
    var counttime=60
    var can_click=true
    $('.getyanzhengma').click(function(){
        if($('#register_phone').siblings('.error_text').text()!=''){
            return false;
        }
        if(counttime!==60){

        }else{
            var phone = $("#register_phone").val();
            var verify_tag = $('#verify_pop2').siblings('.verify_tag').val();
            var verify_token = $('#verify_pop2').siblings('.verify_token').val();
            if(verify_tag == '' || verify_token==''){
                layer.msg('请先进行图形验证',{skin: 'demo-red'});
                return false;
            }
            var isok = false;

            $.ajax({
                url:send_sms_url,
                type:'post',
                dataType:'json',
                async:false,
                data:{phone:phone,verify_tag:verify_tag,verify_token:verify_token,reg:1},
                success:function(res){
                    if(res.code!=1){
                        isok = true;
                        if(res.msg != '该手机号已被注册或绑定过'){
                            layer.msg(res.msg,{skin: 'demo-red'});
                            $("#getcaptcha").click();
                        }
                    }else{
                        layer.msg(res.msg,{skin: 'demo-blue'});
                        $('.getyanzhengma').css('cursor','not-allowed');
                        $('.getyanzhengma').css('background-color','#CCCCCC');
                    }
                },error:function(){
                    layer.msg('服务器错误',{skin: 'demo-red'});
                }
            })
            if(!isok){
                if(can_click){
                    can_click=false
                    var send_num=setInterval(function(){
                        counttime--
                        $('.getyanzhengma').text('正在发送('+counttime+')');
                        if(counttime===0){
                            clearInterval(send_num)
                            can_click=true
                            counttime=60
                            $('.getyanzhengma').text('获取验证码');
                            $('.getyanzhengma').css('cursor','pointer');
                            $('.getyanzhengma').css('background-color','#018FFF');
                        }
                    },1000)
                }
            }
        }

    })
    // 获取验证码button效果 --找回密码
    var counttimes=60
    var can_clicks=true
    $('#forget').on('click','.getyam_btn',function(){
        if(counttimes!==60){

        }else{
            var phone = $("#forget_phone").val();
            var verify_tag = $('#verify_pop3').siblings('.verify_tag').val();
            var verify_token = $('#verify_pop3').siblings('.verify_token').val();
            var account = $("#forget_account").val();
            if(verify_tag == '' || verify_token==''){
                layer.msg('请先进行图形验证',{skin: 'demo-red'});
                return false;
            }
            var isoka = false;
            $.ajax({
                url:send_sms_url,
                type:'post',
                dataType:'json',
                async:false,
                data:{account:account,phone:phone,verify_tag:verify_tag,verify_token:verify_token,reg:2},
                success:function(res){
                    if(res.code!=1){
                        isoka = true;
                        layer.msg(res.msg,{skin: 'demo-red'});
                    }else{
                        layer.msg(res.msg,{skin: 'demo-blue'});
                        $('.getyam_btn').css('cursor','not-allowed')
                        $('.getyam_btn').css('background-color','#CCCCCC')
                    }
                },error:function(){
                    layer.msg('服务器错误',{skin: 'demo-red'});
                }
            })
            if(!isoka){
                if(can_clicks){
                    can_clicks=false
                    var send_nums=setInterval(function(){
                        counttime--
                        $('.getyam_btn').text('正在发送('+counttime+')');
                        if(counttime===0){
                            clearInterval(send_nums)
                            can_clicks=true
                            counttime=60
                            $('.getyam_btn').text('获取验证码');
                            $('.getyam_btn').css('cursor','pointer');
                            $('.getyam_btn').css('background-color','#018FFF');
                        }
                    },1000)
                }
            }
        }
    })
    //邮箱注册发送验证码
    var counttimea=60
    var can_clicke=true
    $('.zhuce_email_con').on('click','.getyanzhengma_email',function(){
        if(counttimea!==60){
        }else{
            var email = $("#register_email").val();
            var isoks = false;
            $.ajax({
                url:send_email_url,
                type:'post',
                dataType:'json',
                async:false,
                data:{email:email,code_type:1},
                success:function(res){
                    if(res.code!=1){
                        isoks = true;
                        layer.msg(res.msg,{skin: 'demo-red'});
                    }else{
                        layer.msg(res.msg,{skin: 'demo-blue'});
                        $('.getyanzhengma_email').css('cursor','not-allowed')
                        $('.getyanzhengma_email').css('background-color','#CCCCCC')
                    }
                },error:function(){
                    layer.msg('服务器错误',{skin: 'demo-red'});
                }
            })
            if(!isoks){
                if(can_clicke){
                    can_clicke=false
                    var send_numa=setInterval(function(){
                        counttimea--
                        $('.getyanzhengma_email').text('正在发送('+counttimea+')');
                        if(counttimea===0){
                            clearInterval(send_numa)
                            can_clicke=true
                            counttimea=60
                            $('.getyanzhengma_email').text('获取验证码');
                            $('.getyanzhengma_email').css('cursor','pointer');
                            $('.getyanzhengma_email').css('background-color','#018FFF');
                        }
                    },1000)
                }
            }
        }
    })
    var counttimea=60
    var can_clicke=true
    $('#forget_ema').on('click','.getyam_btns',function(){
        if(counttimea!==60){

        }else{
            var email = $("#forget_email").val();
            var account = $("#forget_account").val();
            var isoks = false;
            $.ajax({
                url:send_email_url,
                type:'post',
                dataType:'json',
                async:false,
                data:{account:account,email:email,code_type:2},
                success:function(res){
                    if(res.code!=1){
                        isoks = true;
                        layer.msg(res.msg,{skin: 'demo-red'});
                    }else{
                        layer.msg(res.msg,{skin: 'demo-blue'});
                        $('.getyam_btns').css('cursor','not-allowed')
                        $('.getyam_btns').css('background-color','#CCCCCC')
                    }
                },error:function(){
                    layer.msg('服务器错误',{skin: 'demo-red'});
                }
            })
            if(!isoks){
                if(can_clicke){
                    can_clicke=false
                    var send_numa=setInterval(function(){
                        counttimea--
                        $('.getyam_btns').text('正在发送('+counttimea+')');
                        if(counttimea===0){
                            clearInterval(send_numa)
                            can_clicke=true
                            counttimea=60
                            $('.getyam_btns').text('获取验证码');
                            $('.getyam_btns').css('cursor','pointer');
                            $('.getyam_btns').css('background-color','#018FFF');
                        }
                    },1000)
                }
                // setInterval(function() {
                //     if(counttime!==0){
                //         counttime--
                //         $('.getyam_btns').text('正在发送('+counttime+')')
                //     }else{
                //         $('.getyam_btns').text('获取验证码')
                //         counttime = 60;
                //     }
                // }, 1000);
            }
        }
    })
    // 关闭注册弹窗
    $('.zhucemodal_title img').click(function(){
        layer.closeAll()
    })
    $('.closemodals img').click(function(){
        layer.closeAll()
    })
    $('.closemodalss img').click(function(){
        layer.closeAll()
    })
    $('.zhuce_newuser').click(function(){
        if(is_blank == 1){
            layer.msg('暂时无法注册，请联系客服');
            return false;
        }
        layer.closeAll()
        var cons = $('#zhucemodal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', 'auto'],
            offset: 'auto',
            content: cons,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('#zhucemodal').hide()
            }
        })
    })
    $('.zhuce_modal_tap span').click(function(){
        layer.closeAll()
        var conss = $('.login_modals')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['300px', '320px'],
            offset: 'auto',
            content: conss,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('.login_modals').hide()
            }
        })
    })
    $(document).ready(function () {
        $('.zhuce_zhanghao_con').hide()
        $('.zhuce_email_con').hide()
    })
    $('.mianfei_zhuce').click(function(){
        var cons = $('#zhucemodal')
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['459px', '515px'],
            offset: 'auto',
            content: cons,
            btnAlign: 'c',
            shadeClose: true,
            end: function () {
                $('#zhucemodal').hide()
            }
        })
    })
    // 控制登录弹窗
    $('.closemodal').click(function() {
        $('.login_modal').hide()
    })
    // $('.login').click(function() {
    //     $('.login_modal').show()
    // })
    // 首页-客服中心-微信客服 悬浮样式
    $('#wx_sale').hover(function () {
        $('.hover_wx_sale').show()
    }, function () {
        $('.hover_wx_sale').hide()
    })
    // 页面右侧固定悬浮框 样式
    $('.comment').hover(function(){
        $(this).show()
    },function(){
        $(this).hide()
    })
    $('.comment2').hover(function(){
        $(this).show()
    },function(){
        $(this).hide()
    })
    $('.comment3 ').hover(function(){
        $(this).show()
    },function(){
        $(this).hide()
    })
    $('.hover_modal_item4').click(function() {
        $('html,body').animate({ scrollTop: 0 }, 'slow');
    })
    
});
 