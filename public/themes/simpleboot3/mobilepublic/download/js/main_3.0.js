/**
 * 刷新页面
 */
function refresh() {
    window.location.reload();
}

/**
 * 判断是否Safari浏览
 * @returns {boolean}
 */
function isSafari(){
    var userAgent = navigator.userAgent; //取得浏览器的userAgent字符串
    if (userAgent.indexOf("Safari") > -1) { //判断是否Safari浏览器
        return true;
    }else{
        return false;
    }
}

//=======================================================================

/**
 * 判断是否微信浏览
 * @returns {boolean}
 */
function isWeixin() {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == "micromessenger") {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断是否IOS
 */
function isIOS(){
    var u = navigator.userAgent;
    var isIOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);
    if(isIOS){
        return true;
    }else{
        return false;
    }
}

function closeBtn(){
    $(".user_center").css("display","none");
    $(".cover").css("display","none");
	$("body").css("overflow","visible");
}

/**
 * 浏览器状态
 */
function brower(){
	if (isWeixin()){
		$(".cover,.img_tishi").css("display","block");
		$("body").css("overflow","hidden");
        $(".cover,.img_tishi").on("click",function(){
            $(".cover,.img_tishi").css("display","none");
			$("body").css("overflow","visible");
        })
        $(".game_down a,.game_anzhuang a").attr("href","##");
        $(".game_down a,.game_anzhuang a").on("click",function(){
            $(".cover,.img_tishi").css("display","block");
			$("body").css("overflow","hidden");
        })
	} else {
        if(isIOS()){
			if (!config.is_ios){
                alert('IOS暂未开启,敬请期待!');
                window.location.href=""+config.web_url+"";
                return false;
            }
			if (!isSafari()){
				$(".cover,.img_tishi").css("display","block");
				$("body").css("overflow","hidden");
				$(".cover,.img_tishi").on("click",function(){
					$(".cover,.img_tishi").css("display","none");
					$("body").css("overflow","visible");
				})
				$(".game_down a,.game_anzhuang a").attr("href","##");
				$(".game_down a,.game_anzhuang a").on("click",function(){
					$(".cover,.img_tishi").css("display","block");
					$("body").css("overflow","hidden");
				})
			}else{
				$(".cover,.user_center,.register").css("display","block");
				$("body").css("overflow","hidden");
				$(".game_down a,.game_anzhuang a").on("click",function(){
					$(".login").css("display","none");
					$(".cover,.user_center,.register").css("display","block");
					$("body").css("overflow","hidden");
				})
				$(".go_reg").on("click",function(){
					$(".login").css("display","none");
					$(".register").css("display","block");
					$(".tishi span").html("");
				});
				$(".go_login").on("click",function(){
					$(".register").css("display","none");
					$(".login").css("display","block");
					$(".tishi span").html("");
				});
			}

        }else {
           /* var gameLink = config.android_url;
            $(".game_down a,.game_anzhuang a").attr("href",gameLink);*/
        }
    }
}

$(function(){
    //游戏简介
    var flag = 1;
    $(".see a").on("click",function(){
        if(flag){
            $(this).text("收起");
            $(".game_jianjie_content").css({"pverflow":"inherit","height":"auto"});
            flag = 0;
        }else{
            $(this).text("显示全部");
            $(".game_jianjie_content").css({"pverflow":"hidden","height":"2rem"});
            flag = 1;
        }
    })

    //分享
    $(".share,.footer_share").on("click",function(){
        $(".share_tip").css("display","block");
        setTimeout(function(){
            $(".share_tip").css("display","none");
        },3000);
    })

})



