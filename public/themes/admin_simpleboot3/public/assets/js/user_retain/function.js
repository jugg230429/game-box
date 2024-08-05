function user_detail(url,data){
    $.ajax({
        type: 'POST',
        url: url,//发送请求
        data: {data:data},
        success: function(result) {
            var htmlCont = result;//返回的结果页面
            layer.open({
                type: 1,//弹出框类型
                title: '留存详情',
                shadeClose: false, //点击遮罩关闭层
                area: ['80%', '90%'],
                shift:1,
                content: htmlCont,//将结果页面放入layer弹出层中
                success: function(layero, index){

                }
            });
        }
    })
}