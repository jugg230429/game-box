/**
 * 个人信息弹窗
 */
  var userinfo = [];
	ajax_post('/sdkyy/game/get_user_info','game_id='+game_id, function (result) {
		if(result.user_id == 0){
			top.location.reload();
		}else{
            userinfo = result;
            vip_level = result.vip_level;
            age_status = result.age_status;
		}
    });
	 // 打开公告
	$(".js-news").click(function(){
		$(".js-pop").hide();
		$(".newspop").show();
		$(this).addClass('highBlcokFour')
		$(this).siblings().removeClass('highBlcok')
		$(this).siblings().removeClass('highBlcokTwo')
		$(this).siblings().removeClass('highBlcokThree')
	});
	 // 打开礼包
	$(".js-gift").click(function(){
		$(".js-pop").hide();
		$(".giftpop").show();
		$(this).addClass('highBlcokThree')
		$(this).siblings().removeClass('highBlcokFour')
		$(this).siblings().removeClass('highBlcok')
		$(this).siblings().removeClass('highBlcokTwo')
	});
	//打开换服
	$(".js-server").click(function(){
		$(".js-pop").hide();
		$(".serverpop").show();
		$(this).addClass('highBlcokTwo')
		$(this).siblings().removeClass('highBlcokThree')
		$(this).siblings().removeClass('highBlcokFour')
		$(this).siblings().removeClass('highBlcok')
	});
	//打开我的
	$(".js-my").click(function(){
		$(".js-pop").hide();
		$(".mypop").show();
		$(this).addClass('highBlcok')
		$(this).siblings().removeClass('highBlcokTwo')
		$(this).siblings().removeClass('highBlcokThree')
		$(this).siblings().removeClass('highBlcokFour')
	});
