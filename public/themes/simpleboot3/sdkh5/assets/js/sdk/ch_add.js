/**
 *  选择小号
 */

  var service="";
  service +='<div class="addPop" hidden>'+
            '<div class="servicepop-con2">'+
                '<div class="ch_head">'+
                   '<p>增加小号</p>'+
                '</div>'+
                '<div class="add_content">'+
                '<form action="" method="get">'+
                '   <input type="text" name="small_account" id="small_account" placeholder="请输入小号名" autocomplete="off" class="ch_input"' +
                    ' onkeyup="this.value=this.value.replace(/\\s+/g,\'\')" maxlength="20">'+
                '</form>'+
                '</div>'+
                 '<div class="add_bottom">'+
                   '<button class="add_qx">取消</button><button class="add_confir">确定</button>'
                '</div>'+

            '</div>'+

           '</div>';

     $('body').append(service);



















