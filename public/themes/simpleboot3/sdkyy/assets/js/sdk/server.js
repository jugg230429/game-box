/**
 * 区服弹窗
 */
var serverPop = '<div class="serverpop js-pop">' +
                    '<div class="biggiftBox">' +
                        '<div class="giftpop-con serverBox">'+
                            '<img src="/themes/simpleboot3/sdkyy/assets/images/tc_close.png" class="btn-close js-btn-close serviceBtn">'+
                            '<ul class="playedArea"><div class="playedAreaTitle">最近玩过的区服</div>'+
                                '<li class="playedAreaName">'+
                                    // '<span class="area-item"><a href="#" class="routerToArea">首发公测256服</a></span>'+
                                    // '<span class="area-item"><a href="#" class="routerToArea">首发公测256服</a></span>'+
                                    // '<span class="area-item"><a href="#" class="routerToArea">首发公测256服</a></span>'+
                                '</li>'+
                            '</ul>'+
                            '<ul class="currentArea"><div class="playedAreaTitle">最新区服</div>'+
                            '</ul>'+
                        '</div>'+
                    '</div>'+
                '</div>';

$('body').append(serverPop);

//获取区服登录记录
ajax_post('/sdkyy/game/get_server_record','game_id='+game_id, function (result) {
    var html = "";
    var data = result.data;
    if(data && data.length) {
        for (var i in data) {
            html += '<span class="area-item"><a href="/media/game/open_ygame/game_id/'+game_id+'/server_id/'+data[i].server_id+'.html" class="routerToArea">'+data[i].server_name+'</a></span>';
        }
    }
    $(".playedAreaName").html(html);
});


//获取区服列表
ajax_post('/sdkyy/game/get_server_lists','game_id='+game_id, function (result) {
    var html = "<div class='playedAreaTitle'>最新区服</div>";
    var data = result.data;
    if(data && data.length) {
        for (var i in data) {
            html += '<li class="currentAreaItem">'+
            '<div class="gameReady"><span class="gameReadyItem">'+data[i].server_name+'</span><span class="gameReadyStart">' +
            '<a href="'+data[i].play_url+'" class="startChange">'+data[i].start_status+'</a></span></div>'+
            '</li>';
        }
    }
    $(".currentArea").html(html);
});