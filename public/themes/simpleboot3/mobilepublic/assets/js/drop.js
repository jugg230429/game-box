var lwx = window.lwx || {};
lwx.ready = true;
lwx.winHeight = $(window).height();
lwx.number = {new:1,rec:1,hot:1,gift:1};
lwx.row = 20;
lwx.http = {
	getJSON:function(url,callback){
		$.getJSON(url + '&v=' + Date.now(),function(data){
			callback(data);
		});
	},
};
lwx.url ={
	game:'{:url("Game/lists")}',
};
lwx.page = {
	hot:function(){
		if(lwx.number.hot) {
			lwx.http.getJSON(lwx.url.game+'?type=2&limit='+lwx.row+'&p='+lwx.number.hot,function(data) {
				var hotload = $('#hotload');
				var data = data.data;

				if(data && data.length) {
					var result = '';
					for (var i in data) {
						// result += '<li><div class="item clearfix"><div class="iconbox"><span class="font table"><span class="table-cell">{:C('BITMAP')}</span></span><img src="'+data[i].icon+'" class="icon"></div><div class="butnbox"><span class="table"><span class="table-cell"><a href="'+data[i].play_url+'" class="butn">开始</a></span></span></div><div class="textbox"><div class="title"><span class="name">'+data[i].game_name+'</span>';
						// if(data[i].gift_id){
						// 	result += '<span class="mark gift-mark">礼包</span>';
						// }
						// result += '</div><p class="info"><span class="type">'+data[i].game_type_name+'</span><span class="description">'+data[i].features+'</span></p></div></div></li>';
					
						result +='<div class="rec_hot_new_cons_item clear">'
			                result +='<div class="rec_hot_new_cons_item_img fl"><img src="/themes/simpleboot3/mobilepublic/assets/images/hot_ico_game.png" alt=""></div>'
			                result +='<div class="fl rec_hot_new_info">'
			                 result +='   <p class="rec_hot_new_info_one">盗墓笔记</p>'
			                 result +='   <p class="rec_hot_new_info_two"><span>角色</span><span class="rec_hot_new_fgx">|</span><span class="rec_hot_new_download_num">2343</span><span>下载</span><span class="rec_hot_new_fgx">|</span><span>258MB</span></p>'
			                  result +='  <div class="rec_hot_new_info_three">'
			                  result +='      <div class="fl bq_one">标签一标签一标签一</div>'
			                  result +='      <div class="fl bq_two">标签二二</div>'
			                   result +='     <div class="fl bq_three">标签三</div>'
			                  result +='  </div>'
			               result +=' </div>'
			               result +=' <div class="fl rec_hot_new_download ">'
			                result +='    <p>下载</p>'
			               result +=' </div>'
			           result +=' </div>' 

					}

					hotload.append(result);


					if(data && data.length >= lwx.row) {
						lwx.number.hot++;
						lwx.ready = true;

					} else {
						lwx.number.hot = false;

					}
				} else {
					if(lwx.number.hot==1){
						hotload.append('<li class="end"><div class="empty s-categroy emptypb10"><img src="__IMG__/no_date.png" class="empty-icon"><p class="empty-text">~ 空空如也 ~</p></div></li>');
					} else{

					}
					lwx.number.hot = false;
				}


				if ($(document).height()-$(window).scrollTop()-lwx.winHeight<50) {
					lwx.page.hot();
				}



			});


		}
	},
	gift:function(){
		if(lwx.number.gift) {
			lwx.http.getJSON(lwx.url.gift,function(data) {
				var gamegiftlist = $('#gamegiftlist');
				var othergiftlist = $('#othergiftlist');
				data = data.data;
				var detail = data.detail.data;
                var other = data.other.data;
				if(detail && detail.length) {
					var result = '';
					for (var i in detail) {
						all = detail[i].novice_all;
							wei = detail[i].novice_surplus;
							baifen = (wei/all*100).toFixed(2);
							result += '<li><div class="item"><div class="butnbox"><span class="table"><span class="table-cell"><a href="javascript:;" ';
							if(detail[i].received!=1){
								result +='class="butn jsgetgift" data-game_id="'+detail[i].game_id+'" data-gift_id="'+detail[i].gift_id+'">领取</a>';
							}else{
								/*result +='class="butn jsgetgift " data-game_id="'+detail[i].game_id+'" data-gift_id="'+detail[i].gift_id+'">已领取</a>';*/
								result +='class="butn jsgetgift " data-game_id="'+detail[i].game_id+'" data-gift_id="'+detail[i].gift_id+'">复制</a>';
							}
							result +='</span></span></div><div class="textbox"><div class="title">['+detail[i].game_name+']'+detail[i].giftbag_name+'</div><div class="surplusbox"><span class="surplus"><i style="width:'+baifen+'%"></i></span><span class="number">剩余<i>'+baifen+'%</i></span></div><p class="info">'+detail[i].desribe+'</p></div></div></li>';
					}

					gamegiftlist.append(result);

				} else {

					gamegiftlist.append('<li class="end"><div class="empty s-categroy emptypb10"><img src="__IMG__/no_date.png" class="empty-icon"><p class="empty-text">~ 空空如也 ~</p></div></li>');

				}

				if(other && other.length) {
					var result2 = '';
					for (var i in other) {
						var od = other[i].gblist;
						for(var j=0;j<od.length;j++) {
							all = od[j].novice_all;
							wei = od[j].novice_surplus;
							baifen = (wei/all*100).toFixed(2);
							result2 += '<li><div class="item"><div class="butnbox"><span class="table"><span class="table-cell"><a href="javascript:;" ';
							if(od[j].geted!=1){
								result2 += 'class="butn jsgetgift" data-game_id="'+other[i].game_id+'" data-gift_id="'+od[j].gift_id+'">领取</a>';
							}else{
								/*result2 += 'class="butn jsgetgift " data-game_id="'+other[i].game_id+'" data-gift_id="'+od[j].gift_id+'">已领取</a>';*/
								result2 += 'class="butn jsgetgift " data-game_id="'+other[i].game_id+'" data-gift_id="'+od[j].gift_id+'">复制</a>';
							}
							result2 += '</span></span></div><div class="textbox"><div class="title">['+other[i].game_name+']'+od[j].giftbag_name+'</div><div class="surplusbox"><span class="surplus"><i style="width:'+baifen+'%"></i></span><span class="number">剩余<i>'+baifen+'%</i></span></div><p class="info">'+od[j].desribe+'</p></div></div></li>';

						}
					}
					othergiftlist.append(result2);

				} else {

					othergiftlist.append('<li class="end"><div class="empty s-categroy emptypb10"><img src="__IMG__/no_date.png" class="empty-icon"><p class="empty-text">~ 空空如也 ~</p></div></li>');

				}


				lwx.number.gift = false;


			});


		}

		new Swiper('#giftscroll',{direction:'vertical',scrollbar: '#giftscrollbar',slidesPerView:'auto',freeMode:true,roundLengths:true,paginationClickable: true,observer:true,observeParents:true});

	},


};