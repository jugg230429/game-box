/**
 *  选择小号
 */

var service = "";

if (is_open_sub_account == '1' && small_id=='0') {

    service += '<div class="js-pop chooseNum">' +
        '<div class="servicepop-con hj">' +
        '<div class="ch_head">' +
        '<a href="javascript:;" class="js_waprenturn returnBackOut">注销</a>' +
        '<span style="float:right;"><button class="ch_shuoming">小号说明</button><button class="ch_add">增加小号</button></div></span>' +
        '<div class="ch_title"><p class="ch_choose">选择小号进入游戏</p></div>' +
        '<div class="ch_content">' +
        '<ul>';


    //获取小号列表
    ajax_post('/sdkh5/game/smallLists','game_id='+game_id+'&pid='+pid+'&user_token='+user_token, function (res) {

        var last_choose_id = localStorage.getItem('last_login_id');

        $.each(res.data,function (index,ele) {

            if(last_choose_id==ele.id){
                service += '<a class="choose_small_account" data-id="'+ele.id+'" href="'+ele.url+'"><li>' +
                    '<span class="list_xiaohao">'+ele.nickname+'</span>' +
                    '<span style="float:left">'+ele.account_tag+'</span>' +
                    '<span class="last_login">(上次登录)</span>'+
                    '<span class="ch_go"><img src="/themes/simpleboot3/sdkh5/assets/images/sdk_choose_btn_goin.png"></span>' +
                    '</li></a>'
            }
        });

        $.each(res.data,function (index,ele) {
            if(last_choose_id!=ele.id){
                service += '<a class="choose_small_account" data-id="'+ele.id+'" href="'+ele.url+'"><li>' +
                    '<span class="list_xiaohao">'+ele.nickname+'</span>' +
                    '<span style="float:left">'+ele.account_tag+'</span>' +
                    '<span class="ch_go"><img src="/themes/simpleboot3/sdkh5/assets/images/sdk_choose_btn_goin.png"></span>' +
                    '</li></a>'
            }
        })

    });

    service +='</ul>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';

    $('body').append(service);

}


















