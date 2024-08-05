//管理中心公用js

var url = window.location.pathname + "?s=/Home/" + "Promote" + "/" + "index" + ".html";
			url = url.replace(/(\/(p)\/\d+)|(&p=\d+)|(\/(id)\/\d+)|(&id=\d+)|(\/(group)\/\d+)|(&group=\d+)/, "");
			$('.nav .menu').find("a[href='" + url + "']").addClass("active").closest('.wrap').addClass('active');
			//      左侧菜单折叠
			$(function() {
				$('.jsnavContent').find('.active').closest('.jsnavContent').css('display', 'block');
				$('.jsnavContent').find('.active').closest('.jsnavContent').prev('.jssubNav').addClass('currentDd');
				// $(".jssubNav").click(function() {
				// 	$(this).toggleClass("currentDd").siblings(".jssubNav").removeClass("currentDd");
				// 	$(this).next(".jsnavContent").slideToggle(300).siblings(".jsnavContent").slideUp(500);
				// });
			});
//			菜单切换
			$(".tab-menu").click(function() {
				var index = $(this).index();
				$(this).addClass("tab-menu-active").siblings().removeClass("tab-menu-active");
				$(".tabpan-con").eq(index).addClass("tabpan-show").siblings().removeClass("tabpan-show")
			})