/**
 * 客服弹窗
 */

  var service="";
  service +='<div class="js-pop servicepop">'+
		   '<div class="servicepop-con">'+
		   '<img src="/themes/simpleboot3/sdkh5/assets/images/notice_ico_return.png" class="pop-icon-return js-btn-return">'+
		   '<div class="giftpop-title">客服<img src="/themes/simpleboot3/sdkh5/assets/images/icon_close.png" class="btn-close js-btn-close"></div>';
	if(service_info.qq != ''){
		    service +='<div class="service-box">'+
		   '<img src="/themes/simpleboot3/sdkh5/assets/images/ico_service.png" class="service-icon fl">'+
           '<div class="fl service-text">在线客服：'+service_info.qq+'</div>'+
            '<a href="javascript:;" data-qq="'+service_info.qq+'" data-href="'+service_info.qq_url+'" class="service-btn fg js_qq">联系我</a>'+
            '</div>';
	}
	if(service_info.qq_group !=''){
        service += '<div class="service-box">'+
            '<img src="/themes/simpleboot3/sdkh5/assets/images/ico_qq.png" class="service-icon fl">'+
            '<div class="fl service-text">玩家Q群：'+service_info.qq_group+'</div>'+
            '<a href="javascript:;" data-group="'+service_info.pc_qq_group_key+'" data-href="'+service_info.qq_group_url+'" class="service-btn fg js_group">加入</a>'+
            '</div>';
	}
	if(service_info.tel !=''){
        service += '<div class="service-box">'+
            '<img src="/themes/simpleboot3/sdkh5/assets/images/ico_kfdh.png" class="service-icon fl">'+
            '<div class="fl service-text">客服电话：'+service_info.tel+'</div>'+
            '<a href="tel://'+service_info.tel+'" class="service-btn fg">拨打</a>'+
            '</div>';
	}
	if(service_info.t_email !=''){
        service += '<div class="service-box">'+
            '<img src="/themes/simpleboot3/sdkh5/assets/images/ico_service.png" class="service-icon fl">'+
            '<div class="fl service-text">投诉邮箱：'+service_info.t_email+'</div>'+
            '<a href="javascript:;" class="service-btn fg copy js-emailcopy"  data-clipboard-text="'+service_info.t_email+'">复制</a>'+
            '</div>';
	}

service += '<img src="'+service_info.qrcode+'" class="service-code-img">'+
		   '<div class="service-code-text">扫码关注更多精彩活动</div>'+
		   '<div class="service-code-subtext">(截图保存二维码)</div>'+
		   '</div>'+
		   '</div></div>';
     $('body').append(service);
  
 
   
    
			    
				
				
			
			
			    
				
				
			
			
			
			
	   
  
