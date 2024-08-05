var addiction_is_show = 0

//倒计时
setInterval(function () {
    if (surplus_second <= 0 && addiction_is_show === 0) {
        var addiction = "";
        addiction += '<div class="realnamepop js-pop ">' +
            '<div class="realnamepop-con">' +
            '<div class="addiction-title" style="text-align: center;margin-top: 1rem;font-size: 1.5rem;font-weight: bold;">防沉迷提示</div>' +
            '<p class="realname_page_tap">根据国家关于《防止未成年人沉迷网络游戏的通知》要求，由于你是未成年玩家，仅可在周五/周六/周日/法定节假日每日20时至21时登录游戏，请注意休息，即将自动退出游戏。</p>' +
            '</div>';
        $(addiction).appendTo($('body')).css('display', 'block');

        addiction_is_show = 1;
        setTimeout(function () {
            $(".s-btn-change").click();
        }, 5000);
    }
    surplus_second--;
}, 1000);


