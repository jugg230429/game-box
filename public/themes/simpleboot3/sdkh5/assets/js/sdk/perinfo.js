/**
 * 个人信息弹窗
 */
  var userinfo = [];
	ajax_post('/sdkh5/game/get_user_info','game_id='+game_id, function (result) {
		if(result.user_id == 0){
			top.location.reload();
		}else{
            userinfo = result;
            vip_level = result.vip_level;
            age_status = result.age_status;
            task_id = result.task_id;
			guess_you_like_switch = result.guess_you_like_switch;  // 增加猜你喜欢显示开关
		}
    });
  var perinfo="";
  perinfo +='<div class="pop popinfo" >'+
            '<div class="popinfo-con landPopInfoChange comparePerinfo">'+
		    '<img src="/themes/simpleboot3/sdkh5/assets/images/icon_close.png" class="btn-close js-btn-close">'+
		    '<div class="popinfo-top">'+
			'<a class="my-pic fl"> <img src="'+userinfo.head_img+'">';
  			if(userinfo.vip_level == 0){
                perinfo += '<span class="vip vip-disabled">V'+userinfo.vip_level+'</span>';
			}else{
                perinfo += '<span class="vip">V'+userinfo.vip_level+'</span>';
			}
	perinfo +=		'<a>'+
			'<div class="fl">'+
			'<div class="user-nickname fl">'+userinfo.account+'</div>';
			// '<a class="btn-change fl">[切换]</a>';
		

    perinfo +=   '<a class="js_waprenturn fl" id="logOutGameBtn">[退出游戏]</a>';
    perinfo +=  	'<a class="btn-shezhi fl" id="btn-shezhi">[设置]</a>';
perinfo +=	'<div class="clear"></div>'+
			'<div class="fl currency">平台币:'+userinfo.balance+'</div>'+
			'<div class="fl currency">绑币:'+userinfo.bind_balance+'</div>'+
			'<div class="fl currency">积分:'+userinfo.point+'</div>'+
			' </div>'+
			'</div>'+
			'<div class="popinfo-bottom clearfix">'+
			'<ul>'+
			' <li class="fl popinfo-menu js-coupon">'+
			'<img src="/themes/simpleboot3/sdkh5/assets/images/icon_coupon.png" class="popinfo-menu-img">'+
			' <div class="popinfo-menu-text">优惠券</div>'+
			'</li>';
if(task_id > 0){
    perinfo += '<li class="fl popinfo-menu js-tplay">'+
        '<img src="/themes/simpleboot3/sdkh5/assets/images/redpaper.png" class="popinfo-menu-img">'+
        '<div class="popinfo-menu-text">拆红包</div>'+
        '</li>';
}
perinfo += '<li class="fl popinfo-menu js-news">'+
			'<img src="/themes/simpleboot3/sdkh5/assets/images/icon_news.png" class="popinfo-menu-img">'+
			'<div class="popinfo-menu-text">公告</div>'+
		    '</li>'+
			'<li class="fl popinfo-menu js-gift">'+
			'<img src="/themes/simpleboot3/sdkh5/assets/images/icon_gift.png" class="popinfo-menu-img">'+
			'<div class="popinfo-menu-text">礼包</div>'+
			'</li>'+
			'<li class="fl popinfo-menu js-service">'+
			' <img src="/themes/simpleboot3/sdkh5/assets/images/icon_servers.png" class="popinfo-menu-img">'+
			' <div class="popinfo-menu-text">客服</div>'+
			'</li>'+
// if(){
// 	perinfo += '<li class="fl popinfo-menu js-tplay">'+
// 				'<img src="/themes/simpleboot3/sdkh5/assets/images/redpaper.png" class="popinfo-menu-img">'+
// 				'<div class="popinfo-menu-text">拆红包</div>'+
// 				'</li>';
// }
			'</ul>'+
			'</div>';

			// 猜你喜欢模块
			if(guess_you_like_switch == 1){



				ajax_post('/sdkh5/game/likegame','game_id='+game_id, function (res) {
					var game_lists = res.data;

					if(game_lists.length>0){
						perinfo += '<div class="game_like">'+
							'<div class="fengeBottom"></div>'+
							'<div class="judgeLoveModal">'+
							'<div class="game_like-title">猜你喜欢</div>'+
							'<div class="swiper-container game_likeContainer">'+
							'<ul class="swiper-wrapper">';
						$.each(game_lists,function (index,ele) {
							perinfo += '<li class="fl game_likeContainer swiper-slide like_gameBox">'+
								'<a href="'+ele.url+'" class="link_like">'+
								'<img src="'+ele.icon+'" class="like-picture" />'+
								'</a>'+
								'</li>';
						});
						perinfo += '</ul>'+
							'</div>'+
							'</div>'+
							'</div>';
					}

				});


			}

			perinfo += '</div></div>';
  $('body').append(perinfo);
  // 打开优惠券
	$(".js-coupon").click(function(){
		console.log(888)
        coupon_id = 0;
        $(".couponpop-menu").eq(0).click();
	    $(".couponpop").show();
		$(".newspop").hide();
		$(".giftpop").hide();
		$(".servicepop").hide();
		$(".needPayDialog").css('display','none')

	})
	 // 打开公告
	$(".js-news").click(function(){
		console.log(666)
	    $(".newspop").show();
		$(".couponpop").hide();
		$(".giftpop").hide();
		$(".servicepop").hide();
		$(".needPayDialog").css('display','none')

	})
	 // 打开礼包
	$(".js-gift").click(function(){
		console.log(5555)
	    $(".giftpop").show();
		$(".couponpop").hide();
		$(".newspop").hide();
		$(".servicepop").hide();
		$(".needPayDialog").css('display','none')
	})
	 // 打开客服
	$(".js-service").click(function(){
	    $(".servicepop").show();
		$(".couponpop").hide();
		$(".newspop").hide();
		$(".giftpop").hide();
		$(".needPayDialog").css('display','none')

	})
var tplayIndex;
	$(".js-tplay").click(function () {
		tplayIndex = layer.open({
			type: 2,
			skin: 'lwx-layer',
			title: '',
			closeBtn: 0,
			area: [is_mobile ? '100%' : '640px', '100%'],
			content: task_url+"?game_id="+game_id
		});
		$('#layui-layer-shade1').css('display','none')
	});
function closeTplayPop() {
	layer.close(tplayIndex);
}
function isH5App(){
    var ua = window.navigator.userAgent.toLowerCase();
    if(ua.match(/app\/h5shell/i) == 'app/h5shell'){
        return true;
    }else{
        return false;
    }
}


var myswipers = new Swiper('.swiper-container.game_likeContainer', {
	slidesPerView: 'auto',
	spaceBetween: '3.7%',
	observeParents:true,
	observer:true
});

        // 判断手机还是电脑
function IsPC() {
    var userAgentInfo = navigator.userAgent;
    var Agents = ["Android", "iPhone",
                "SymbianOS", "Windows Phone",
                "iPad", "iPod"];
    var flag = true;
    for (var v = 0; v < Agents.length; v++) {
        if (userAgentInfo.indexOf(Agents[v]) > 0) {
            flag = false;
            break;
        }
    }
    return flag;
}

var flag = IsPC(); //true为PC端，false为手机端
if(flag) {
	$(".landPopInfoChange").css("width","32rem","height","23rem","margin","-5rem 0 0 -7rem");
}

