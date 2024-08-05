/**
 * 新增验证方法
 */
$.validator.addMethod("numOrLetter", function(value, element) {
        return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
    }, '只能是字母或数字');
    $.validator.addMethod("ismobile", function(value, element) {
        var length = value.length;
        var mobile = /^[1]([3-9])[0-9]{9}$/;
        return (length == 11 && mobile.exec(value))? true:false;
    }, "请输入正确手机号");

//检测用户姓名是否为汉字
$.validator.addMethod("isChar", function(value, element) { var length = value.length; var regName = /[^\u4e00-\u9fa5]/g; return this.optional(element) || !regName.test( value ); }, "姓名格式错误(暂支持汉字)");


  // 注册验证
    $("#regForm").validate({
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
            reaccount:{
                required:true,
                rangelength:[6,30],
                numOrLetter:true,
                remote:{
					url: checkAccounturl,     //后台处理程序
					type: "post",               //数据发送方式
					data: {validate:2,account:function() {return $("input[name=reaccount]").val();}}
				}

            },
           regpassword:{
                required:true,
                rangelength:[6,30],
            },
            regrepassword:{
                required:true,
                //minlength:6,
                equalTo: "#regpassword"
            },

            // remember:{
            //     required:true,
            // }
        },
        //定义错误消息
        messages:{
            reaccount:{
                required:"账号不能为空",
                rangelength:"账号为6-30位字母或数字组合",
                remote:"账号已存在，可直接登录",
            },
            regpassword:{
                required:"密码不能为空",
                rangelength:"密码为6-30位字母或数字组合"
            },
            regrepassword:{
                required:"确认密码不能为空",
                //minlength:"登录密码不能小于6位字符",
                equalTo:"两次输入密码不一致！"
            },

            // remember:{
            //     required:"请勾选用户协议",
            // }
        },
        submitHandler:function(form){
            if(!$('#accpet').siblings('i').hasClass('on')){
                layer.msg('请阅读用户协议');
                return false;
            }
            $('.nextregister-box').show();
            $('.register-box').hide();

        }
    });
 // 注册验证下一步
    $("#nextregForm").validate({
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

            real_name:{
                required:true,
				isChar:true,
                rangelength:[2,25],

            },
            mobile_phone:{
                required:true,
                ismobile:true,

            }

        },
        //定义错误消息
        messages:{

            mobile_phone:{
                required:"请输入正确手机号",
				rangelength:"请输入11位手机号码",


            },
			 real_name:{
                required:"请输入联系人姓名",
                rangelength:"姓名长度需要在2-25个字符之间",


            }
        },
        submitHandler:function(form){
            data = $('#regForm').serialize();
            nextdata = $('#nextregForm').serialize();
            data = data+'&'+nextdata;
            $.ajax({
                type:'post',
                url:registerurl,
                data:data,
                success:function(data){
                    if(data.code==1){
                        var msg = ""; var status = promote_auto_audit;
                        msg = status == 1?"账号注册成功":"账号注册成功，请等待审核通过后再登录";
                        layer.msg(msg, {icon: 1});
                        setTimeout(function(){
                            window.location.href=data.url;
                        },1500);
                    }else{
                        setTimeout(function(){
                            layer.msg(data.msg, {icon: 2});
                        },1500);
                    }
                },error:function(){
                    layer.msg('服务器错误，请稍后再试');
                }
            });
        }
    });
