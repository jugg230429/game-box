/**
 * 基础JS
 */
$(document).ready(function(){
	$('[data-toggle="tooltip"]').tooltip({"trigger":"hover", "html":true});

	// 更换图片验证码
	$("img[name='changeCaptcha']").click(function(){
        flushCaptcha(this);
	});

	// JS检索
	$("a[href='#filter']").click(function(event){
        event.preventDefault();
        var type = $(this).data("type");
        var value = $(this).data("value");

        var href = window.location.href;
        if (href.indexOf("?") != -1) {
        	var p = new RegExp("(&)?" + type + "=([^&]*)", "g");
            href = href.replace(p, "");
            href = href.replace(/(&)?pageId=(\d+)?/g, "");
            window.location.href = href + "&" + type + "=" + value;
        } else {
        	window.location.href = href + "?" + type + "=" + value;
        }
    });

	/* 表单验证设置全局方法 */
	if($.validator)$.validator.setDefaults({
		errorElement:"small",
		//设置错误消息的位置
		errorPlacement: function(error, element) {
			element.parents(".input-format").nextAll(".input-status").append(error);
		},
		//每个控件成功验证后的处理
		success: function(label,element) {

			$(element).parents(".input-format").removeClass("has-error has-feedback").addClass("has-success has-feedback");
		},
		//验证错误时处理
		highlight:function(element) {

			$(element).parents(".input-format").removeClass("has-success has-feedback").addClass("has-error has-feedback");
		},
		onfocusout: function(element) { $(element).valid();}
	});


});


/* 验证码刷新 */
function flushCaptcha(that) {
    var url = that.src.replace(/((\.html)?|(\/t\/.+)?)$/g,'');
    that.src = url+'/t/'+(new Date()).getTime()+'.html'; 
}


function logout() {
    $.ajax({
        url:logoutUrl,
        type: 'POST',
        dataType: 'json',
        success:function(data) {
            if (data.url) {
                window.location.href = data.url
            } else {
                window.location.reload();                
            }
        }
    });
}



