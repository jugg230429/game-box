/**
 * 公告弹窗
 */
//   var news="";
//     news +=' <div class="newspop js-pop">'+
// 		       '<div class="newspop-con">'+
// 	           '<img src="/themes/simpleboot3/sdkyy/assets/images/notice_ico_return.png" class="pop-icon-return js-btn-return">'+
// 		       '<div class="newspop-title">'+
// 		       '<div class="newspop-title-con">'+
// 		       '<div class="fl newspop-menu newspop-active">公告</div>'+
// 			   '<div class="fl newspop-menu">活动</div>'+
// 			   '<div class="fl newspop-menu">攻略</div>'+
// 			   ' </div>'+
// 		       '</div>'+
// 		       '<img src="/themes/simpleboot3/sdkyy/assets/images/icon_close.png" class="btn-close js-btn-close">'+
// 		       '<div>'+
// 		       '<div class="gonggao news-arc-list news-arc-show" >'+
// 		       '</div>'+
// 		       '<div class="huodong news-arc-list">'+
// 				'</div>'+
// 				'<div class="gonglue news-arc-list">'+
// 				'</div>'+
// 				'</div></div></div>';

	var news = ' <div class="newspop js-pop">'+
					'<div class="biggiftBox">'+
						'<div class="newspop-con newAnnouncementDetail">'+
							'<div class="newspop-title announcement">'+
								'<div class="newspop-title-con">'+
									'<div class="fl newspop-menu newspop-active">公告</div>'+
									'<div class="fl newspop-menu">活动</div>'+
									'<div class="fl newspop-menu">攻略</div>'+
								' </div>'+
								
							'</div>'+
							'<img src="/themes/simpleboot3/sdkyy/assets/images/tc_close.png" class="btn-close js-btn-close changeNewClose">'+
							'<div>'+
								'<div class="gonggao news-arc-list news-arc-show" >'+
								'</div>'+
								'<div class="huodong news-arc-list">'+
								'</div>'+
								'<div class="gonglue news-arc-list">'+
								'</div>'+
							'</div>'+
						'</div>'+
					'</div>'+
				'</div>'
    $("body").append(news) ;
    //获取文章数据
	ajax_post('/sdkyy/game/get_article','game_id='+game_id, function (result) {
			var html = '';
			var gonggao = result.gonggao;
			if(gonggao && gonggao.length) {
				for (var i in gonggao) {
					html += '<a href="/media/article/detail/id/'+gonggao[i].id+'/.html" data-id="'+gonggao[i].id+'" target="_blank" class="news-list js-news-list">';
					html +=  '<div class="fl newspop-arctitle">'+gonggao[i].post_title+'</div> <div class="fg newspop-time">'+gonggao[i].update_time+'</div>';
					html +=  '</a>';
				}
			}else{
				html += '<div class="no-giftdata">暂无公告</div>';
			}
			$(".gonggao").html(html);
        var html = '';
        var huodong = result.huodong;
        if(huodong && huodong.length) {
            for (var i in huodong) {
                html += '<a href="/media/article/detail/id/'+huodong[i].id+'/.html" data-id="'+huodong[i].id+'" target="_blank" class="news-list  js-news-list">';
                html +=  '<div class="fl newspop-arctitle">'+huodong[i].post_title+'</div> <div class="fg newspop-time">'+huodong[i].update_time+'</div>';
                html +=  '</a>';
            }
        }else{
            html +=  '<div class="no-giftdata">暂无活动</div>';
        }
        $(".huodong").html(html);
        var html = '';
        var gonglue = result.gonglue;
        if(gonglue && gonglue.length) {
            for (var i in gonglue) {
                html += '<a href="/media/article/detail/id/'+gonglue[i].id+'/.html" data-id="'+gonglue[i].id+'" target="_blank" class="news-list  js-news-list">';
                html +=  '<div class="fl newspop-arctitle">'+gonglue[i].post_title+'</div> <div class="fg newspop-time">'+gonglue[i].update_time+'</div>';
                html +=  '</a>';
            }
        }else{
            html += '<div class="no-giftdata">暂无攻略</div>';
        }
        $(".gonglue").html(html);
	});
	// 领取切换
    $(".newspop-menu").click(function(){
		var index=$(this).index();
		$(this).addClass("newspop-active").siblings().removeClass("newspop-active");
		$(".news-arc-list").eq(index).addClass("news-arc-show").siblings().removeClass("news-arc-show")
	})
	
	$(".js-news-list").click(function(){
		// var id = $(this).data('id');
        // ajax_post('/sdkyy/game/get_article_detail','id='+id, function (result) {
        //     $(".newsdetailpop").show();
        //     $(".newsdetailpop").find('.news-main').html(result.post_content);
		// 	$(".newsdetailpop").find('.news-title').text(result.post_title);
        //     $(".newsdetailpop").find('.news-time').text(result.update_time);
        // });

	})	
	
	$(".news-list").mouseover(function() {
		$('.news-list').addClass('newsColorChange')
	})
     
				    
					  
						    
							
							   
                                  
                                  
                               
							    
								
							 
							
						
						 
					
				
			    
			
		
	
