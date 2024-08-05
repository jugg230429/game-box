/**
 * 代金券弹窗
 */
    var couponpop="";
    couponpop +=' <div class="couponpop js-pop">'+
        '<div class="couponpop-con">'+
        '<img src="/themes/simpleboot3/sdkh5/assets/images/notice_ico_return.png" class="pop-icon-return js-btn-return">'+
        '<div class="couponpop-title"><div class="couponpop-menu fl couponpop-active">可领取</div>'+
        '<div class="couponpop-menu fl">可使用</div></div>'+
        '<img src="/themes/simpleboot3/sdkh5/assets/images/icon_close.png" class="btn-close js-btn-close">'+
        '<div>'+
        '<div class="coupon-game-list coupon-game-show">'+
        '<ul class="receive_list">'+
        '</ul>'+
        '</div>'+
        '<div class="coupon-game-list">'+
        '<ul class="unuse_list">'+
        '</ul></div></div></div></div>';
    $("body").append(couponpop) ;
    // 领取切换
    $(".couponpop-menu").click(function(){
        var index=$(this).index();
        $(this).addClass("couponpop-active").siblings().removeClass("couponpop-active");
        $(".coupon-game-list").eq(index).addClass("coupon-game-show").siblings().removeClass("coupon-game-show")
        if(index == 1){
            get_unuse();
        }else{
            get_receive();
        }

    })

    $("body").on('click','.receive_btn',function () {
        var that = $(this);
        var coupon_id = that.data('id');
        var num = that.data('num');
        if(num == 0){
            return false;
        }
        ajax_post('/sdkh5/game/reciver_coupon','coupon_id='+coupon_id, function (result) {
            if(result.user_id == 0){
                top.location.reload();
            }else{
                if(result.status == 1){
                   layer.msg("领取成功");
		            
                    that.attr('data-num',num-1);
                    that.siblings('.coupon-number').text('剩余'+(num-1)+'次');
                    if(num == 1){
                        that.parents('li').remove();
                    }
                }else{
                   layer.msg("领取失败");
					 
                }
            }
        });
    })

    function get_receive() {
        var amount = window.coupon_money||0;
        ajax_post('/sdkh5/game/get_receive_coupon','game_id='+game_id+'&amount='+amount, function (result) {
            if(result.user_id == 0){
                top.location.reload();
            }else{
                var html = '';
                var data = result.data;
                if(data && data.length) {
                    for (var i in data) {
                        html += '<li class="coupon-game-item fl">';
                        html +=  '<div class="coupon-date">'+data[i].start_time+'-'+data[i].end_time+'</div>';
                        html +=  '<div class="coupon-box">';
                        html +=  '<div class="coupon-num fl">';
                        html +=  '<p class="coupon-money"><span>￥</span>'+data[i].money+'</p>';
                        if(data[i].limit_money == 0){
                            html +=  '<p class="coupon-use-condition">无门槛</p>';
                        }else{
                            html +=  '<p class="coupon-use-condition">满'+data[i].limit_money+'可用</p>';
                        }

                        html +=  '</div>';
                        html +=  '<a href="javascript:;" data-num="'+data[i].limit_num+'" data-id="'+data[i].id+'" class="coupon-btn receive_btn fg">领取</a>';
                        html += '<div class="coupon-number fg">剩余'+data[i].limit_num+'次</div>';
                        html +=  '</div>';
                        if(data[i].mold == 0){
                            html += '<div class="coupon-scope">适用于全部游戏</div>';
                        }else{
                            html += '<div class="coupon-scope">适用于《'+data[i].coupon_game+'》</div>';
                        }
                        html += '</li>';
                    }
                }else{
                    html += '<div class="no-coupon" style="display:block"><img src="/themes/simpleboot3/sdkh5/assets/images/pg_wyhq.png" class=""><div class="no-giftdata">暂无优惠券~</div></div>';
                }
                $(".receive_list").html(html);
            }
        });
    }

    function get_unuse() {
        var amount = window.coupon_money||0;
        ajax_post('/sdkh5/game/get_unuse_coupon','game_id='+game_id+"&amount="+amount, function (result) {
            if(result.user_id == 0){
                top.location.reload();
            }else{
                var html = '';
                var data = result.data;
                if(data && data.length) {
                    for (var i in data) {
                        if(coupon_id == data[i].id) {
                            html += '<li data-money="' + data[i].money + '" data-id="' + data[i].id + '" class="coupon-game-item coupon_use coupon-choose fl">';
                        }else{
                            html += '<li data-money="' + data[i].money + '" data-id="' + data[i].id + '" class="coupon-game-item coupon_use fl">';
                        }
                        html +=  '<div class="coupon-date">'+data[i].start_time+'-'+data[i].end_time+'</div>';
                        html +=  '<div class="coupon-box">';
                        html +=  '<div class="coupon-num fl">';
                        html +=  '<p class="coupon-money"><span>￥</span>'+data[i].money+'</p>';
                        if(data[i].limit_money == 0){
                            html +=  '<p class="coupon-use-condition">无门槛</p>';
                        }else{
                            html +=  '<p class="coupon-use-condition">满'+data[i].limit_money+'可用</p>';
                        }

                        html +=  '</div>';
                        html +=  '<a href="javascript:;" data-id="'+data[i].id+'" class="coupon-btn fg">未使用</a>';
                        html +=  '</div>';
                        if(data[i].mold == 0){
                            html += '<div class="coupon-scope">适用于全部游戏</div>';
                        }else{
                            html += '<div class="coupon-scope">适用于《'+data[i].coupon_game+'》</div>';
                        }
                        if(coupon_id == data[i].id){
                            html += '<img src="/themes/simpleboot3/sdkh5/assets/images/ico_selected.png" class="coupon-select">';
                        }
                        html += '</li>';
                    }
                }else{
                    html += '<div class="no-coupon" style="display:block"><img src="/themes/simpleboot3/sdkh5/assets/images/pg_wyhq.png" class=""><div class="no-giftdata">暂无优惠券~</div></div>';
                }
                $(".unuse_list").html(html);
            }
        });
    }






							   
                                  
                                  
                               
							    
								
							 
							
						
						 
					
				
			    
			
		
	
