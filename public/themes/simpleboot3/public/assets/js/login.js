/**
 * 新增验证方法
 */
$.validator.addMethod("numOrLetter", function(value, element) {
    return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
}, '只能是字母或数字');

// 登录验证
$("#loginForm").validate({
	errorPlacement: function(error, element) {
      error.appendTo(element.parent(".input-format").next(".input-status"));
	},
	//每个控件成功验证后的处理
		success: function(label,element) {
            label.text('').addClass('success');
			$(element).parents(".input-format").removeClass("has-error has-feedback").addClass("has-success has-feedback");
		},
		//验证错误时处理
		highlight:function(element) {
            $(element).parents(".input-format").nextAll(".input-status").text('');
			$(element).parents(".input-format").removeClass("has-success has-feedback").addClass("has-error has-feedback");
		},
    onkeyup:function(element) {
        $(element).valid()
    },
    //定义规则
    rules:{
        account:{
            required:true,
            rangelength:[6,15],
            numOrLetter:true,
            remote:{
                url: checkAccounturl,     //后台处理程序
                type: "post",               //数据发送方式
                data: {validate:1,account:function() {return $(".account").val();}},
            }
        },
        password:{
            required:true,
            minlength:6
        },
        yzm:{
            required:true,
            rangelength:[4,4]
        }
    },
    //定义错误消息
    messages:{
        account:{
            required:"请输入登录账号",
            rangelength:"账号必须是6-15位字符串",
            remote:"账号不存在或被锁定",
        },
        password:{
            required:"请输入登录密码",
            minlength:'密码错误，请重新输入',
        },
        yzm:{
            required:"请输入验证码",
            rangelength:"验证码必须是4位字符串"
        }
    },
    submitHandler:function(form){
        data = $('#loginForm').serialize();
        $.ajax({
            type:'post',
            url:loginurl,
            data:data,
            success:function(data){
                if(data.code==1){
                    layer.msg(data.msg,{icon: 1,time:1000},function(){
                        location.href=data.url;
                    });
                }else if(data.code== -2){
                    $('.js-password .input-status').html('<label id="password-error" class="error" for="password">密码错误</label>');
                    setTimeout('location.href=location.href',500);
                }else{

                    layer.msg(data.msg,{icon: 2,time:1000},function(){
                        location.href=data.url;
                    });
                }
            },error:function(){

            }
        });
    }
});
