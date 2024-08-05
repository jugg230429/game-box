/**
 * 礼包详情弹窗
 */
  var giftdetail="";
   giftdetail +='  <div class="giftdetailspop js-pop">'+
		   ' <div class="giftdetailspop-con">'+
		   ' <img src="/themes/simpleboot3/sdkyy/assets/images/notice_ico_return.png" class="pop-icon-return js-btn-return">'+
		   '<div class="giftpop-title">&nbsp;<img src="/themes/simpleboot3/sdkyy/assets/images/icon_close.png" class="btn-close js-btn-close"></div>'+
		   ' <div class="gift-name"></div>'+
		   ' <div  class="gift-detail"></div>'+
		   '<div class="giftbox">'+
		   '<span hidden class="giftbox-prefix gift-novice">激活码</span><input type="" readonly value="" class="giftbox-input">'+
		   '<button class="copy giftbox-modals-btn"  data-clipboard-text="">复制</button>'+
		   '</div>'+
		   '<div class="giftbox—receive">'+
		   '<button class="giftbox——receive-btn get_gift" data-gift_id="">领取</button>'+
		   '</div>'+
		   '<div class="gift-item-title">有效期： </div>'+
		   '<div class="gift-item-time gift-time"></div>'+
		   '<div class="gift-item-title notice_text">注意事项：  </div>'+
		   '<div class="gift-item-time gift-notice"> </div>'+
		   '<div class="gift-item-title use_text">使用说明：</div>'+
		   '<div class="gift-item-time gift-desribe"></div>'+
		   '</div>  </div>';
     $('body').append(giftdetail);
		$("body").on('click','.get_gift',function () {
            var gift_id = $(this).data('gift_id');
            ajax_post('/sdkyy/game/getgift','gift_id='+gift_id, function (result) {
                if (result.user_id == 0) {
                    top.location.reload();
                    return false;
                }else{
                    layer.msg(result.msg);
                    if(result.status ==1){
                        $(".giftdetailspop").find(".giftbox").show();
                        $(".giftdetailspop").find(".giftbox—receive").hide();
                        $(".giftdetailspop").find(".giftbox-input").val(result.novice);
                        $(".giftdetailspop").find(".copy").attr('data-clipboard-text',result.novice);
                    }
                }
            })
        })
     
	     
		  
		 
		 
		  
		      
		      
		  
		  
		 
		  
		 
		 
		  
		  
	  

   
    
	     
		 
