/**
 * 礼包弹窗
 */
    var gift = '<div class="giftpop js-pop">'+
                    '<div class="biggiftBox">'+
                        '<div class="giftpop-con giftDetailAll">'+
                            '<div class="giftContainer">'+
                                '<span class="getGift">领取礼包</span>'+
                                '<img src="/themes/simpleboot3/sdkyy/assets/images/tc_close.png" class="btn-close js-btn-close changeClose">'+
                            '</div>'+
                            ' <ul class="game-bag-con"></ul>'+
                        '</div>'+
                    '</div>'+
                '</div>'
     $('body').append(gift);
     //获取礼包
	ajax_post('/sdkyy/game/get_gift','game_id='+game_id, function (result) {
        if(result.user_id == 0){
            top.location.reload();
        }else{
            var  html="";
            var data = result.gift;
            if(data && data.length) {
                for (var i in data) {
                    html += ' <li class="game-bag-con-item">' +
                        '<a href="/media/gift/detail/gift_id/'+data[i].gift_id+'.html" class="js_detail" target="_blank" data-gift_id="'+data[i].gift_id+'">' +
                        '<div class="fl game-bag-detail">' +
                        '<p class="game-bag-detail-title">' + data[i].giftbag_name + '</p>' +
                        '<p class="game-bag-detail-con">' + data[i].digest + '</p>' +
                        '</div>' +
                        '</a>' ;
                    if(data[i].received == 1){
                        html += '<button class="fg game-bag-lingqu-btn copy" data-clipboard-text="'+data[i].novice+'">复制</button>';
                    }else{
                        if(data[i].vip > vip_level){
                            html += '<button class="fg game-bag-lingqu-btn disabled" >领取</button>';
                        }else{
                            html += '<button class="fg game-bag-lingqu-btn js-lingqu" data-gift_id="'+data[i].gift_id+'">领取</button>';
                        }
                    }
                    html += ' </li>';
                }
            }else{
            	html += '<div class="no-giftdata">暂无礼包</div>';
			}
            $('.game-bag-con').append(html);
        }
	});



  $("body").on('click','.js_detail',function () {
      getdetail($(this));
  })

// 打开礼包详情
$('.js-lingqu').bind('click',function(){
    var that = $(this);
    var gift_id = $(this).data('gift_id');
    ajax_post('/sdkyy/game/getgift','gift_id='+gift_id, function (result) {
        if (result.user_id == 0) {
            top.location.reload();
            return false;
        }else{
           layer.msg(result.msg);
            that.unbind('click');
		    that.removeClass('js-lingqu').addClass('copy').attr('data-clipboard-text',result.novice).text('复制');
		    if(result.status ==1 ){
                setTimeout(function () {
                    getdetail(that.siblings('.js_detail'));
                },6000)
            }
        }
    })
})

function getdetail(obj) {
    // var gift_id = obj.data('gift_id');
    // ajax_post('/sdkyy/game/get_gift_detail','gift_id='+gift_id, function (result) {
    //     if (result.user_id == 0) {
    //         top.location.reload();
    //         return false;
    //     }
    //     var gift = result.gift;
    //     $(".giftdetailspop").find(".gift-name").text(gift.giftbag_name);
    //     $(".giftdetailspop").find(".gift-detail").text(gift.digest);
    //     $(".giftdetailspop").find(".gift-time").text(gift.end_time);
    //     if(gift.notice != ''){
    //         $(".giftdetailspop").find(".notice_text").show();
    //         $(".giftdetailspop").find(".gift-notice").text(gift.notice);
    //     }else{
    //         $(".giftdetailspop").find(".notice_text").hide();
    //     }
    //     if(gift.desribe != ''){
    //         $(".giftdetailspop").find(".use_text").show();
    //         $(".giftdetailspop").find(".gift-desribe").text(gift.desribe);
    //     }else{
    //         $(".giftdetailspop").find(".use_text").hide();
    //     }
    //
    //     if(gift.received == 1){
    //         $(".giftdetailspop").find(".giftbox").show();
    //         $(".giftdetailspop").find(".giftbox—receive").hide();
    //         $(".giftdetailspop").find(".giftbox-input").val(gift.novice);
    //         $(".giftdetailspop").find(".copy").attr('data-clipboard-text',gift.novice);
    //     }else{
    //         $(".giftdetailspop").find(".giftbox").hide();
    //         $(".giftdetailspop").find(".giftbox—receive").show();
    //         if(gift.vip > vip_level){
    //             $(".giftdetailspop").find(".get_gift").addClass('disabled').attr('disabled',true);
    //         }else{
    //             $(".giftdetailspop").find(".get_gift").removeClass('disabled').attr('disabled',false);
    //             $(".giftdetailspop").find(".get_gift").attr('data-gift_id',gift.gift_id);
    //         }
    //
    //     }
    //     $(".giftdetailspop").show();
    // })
}
		 
