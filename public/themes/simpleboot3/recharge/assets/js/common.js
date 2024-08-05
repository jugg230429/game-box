/**
 * 模块行为
 * @author 鹿文学[lwx]<fyj301415926@126.com>
 */
var withdrawRecordIndex;
$(function () {
    // 切换
    $('.js-tabItem').on('click', function () {
        $(this).addClass('active').siblings().removeClass('active');
        $($(this).attr('data-id')).show().siblings().hide();
    });

    // 关闭
    $('#backBtn').on('click', function () {
        try{
            if(mt==2){
                window.webkit.messageHandlers.xgsdk_finishPage.postMessage(1);
            }else{
                window.xgsdk.finishPage()
            }
        }catch (e) {
            top.window.closeTplayPop();
        }
        return false;
    });

    // 刷新
    $('#refresh').on('click', function () {console.log('ddd')
        location.reload();
        return false;
    });

    // 打开提现页
    $('#withdrawBtn').on('click', function () {
         var url = $(this).attr('data-url');
         layer.open({
            type: 2,
            skin: 'lwx-layer',
            title: '',
            closeBtn: 0,
            area: ['100%', '100%'],
            content: url
         });
        return false;
    });

    // 打开拆红包页
    $('#taskBtn').on('click', function () {
        parent.window.location.reload();
        return false;
    });

    // 打开提现明细页
    $('#detailBtn').on('click', function () {
        var url = $(this).attr('data-url');
        withdrawRecordIndex = layer.open({
            type: 2,
            skin: 'lwx-layer',
            title: '',
            closeBtn: 0,
            area: ['100%', '100%'],
            content: url
        });
        return false;
    });

});

/**
 * 关闭提现明细页
 */
function withdrawRecordClose() {
    layer.close(withdrawRecordIndex);
}


