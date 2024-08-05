$(function () {
    //显示历史账号
    $('.js-drop-down').click(function () {

        $(this).toggleClass("rotate");
        $("#account-list").slideToggle()
    });
    $("#account-list li").click(function () {
        var value =$.trim($(this).text());
        $('#account').val(value);
        $("#account-list").slideUp();
    });

});



