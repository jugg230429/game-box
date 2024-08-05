// $(document).ready(function () {
//     var counttime=60
//     var can_click=true
//     $('.get_yanzhengma').click(function(){
//         if(counttime!==60){
//
//         }else{
//             var phone = $("").val();
//             var isok = false;
//
//             $.ajax({
//                 url:'',
//                 type:'post',
//                 dataType:'json',
//                 async:false,
//                 data:{phone:phone,reg:1},
//                 success:function(res){
//                     if(res.code!=1){
//                         isok = true;
//                         layer.msg(res.msg);
//                     }else{
//                         layer.msg(res.msg);
//                         $('.get_yanzhengma').css('cursor','not-allowed');
//                         $('.get_yanzhengma').css('background-color','#CCCCCC');
//                     }
//                 },error:function(){
//                     layer.msg('服务器错误');
//                 }
//             })
//             if(!isok){
//                 if(can_click){
//                     can_click=false
//                     var send_num=setInterval(function(){
//                         counttime--
//                         $('.get_yanzhengma p').text('正在发送('+counttime+')');
//                         if(counttime===0){
//                             clearInterval(send_num)
//                             can_click=true
//                             counttime=60
//                             $('.get_yanzhengma p').text('获取验证码');
//                             $('.get_yanzhengma').css('cursor','pointer');
//                             $('.get_yanzhengma').css('background-color','#018FFF');
//                         }
//                     },1000)
//                 }
//             }
//         }
//
//     })
//
//
//
// });

$(function () {
    $('body').on('click', '.js-user-qq-update', function () {
        updateQQ();
    });
});

function updateQQ() {
    layer.prompt({
        formType: 0,
        title: '绑定QQ号',
        value: '',
        skin: 'updateQQ',
		btn:['保存','取消'],
        success: function (layero) {
            layero.find('input').attr('placeholder', '输入QQ号');
        }
    }, function(value, index, elem){
        $.ajax({
            url:qq_url,
            type:'post',
            dataType:'json',
            async:false,
            data:{qq:value},
            success:function(res){
                layer.msg(res.msg);
                if(res.code==1){
                    setTimeout(function () {
                        window.location.reload();
                    },1000)

                }
            },error:function(){
                layer.msg('服务器错误');
            }
        })
    });
}
