function getXfqSetDiv(flag) {
	flag = flag || false
	/**
	 * 个人信息弹窗
	 */
	var userinfo = [];
	ajax_post('/sdkh5/game/get_user_info','game_id='+game_id, function (result) {
		if(result.user_id == 0){
			top.location.reload();
		}else{
			userinfo = result;
			vip_level = result.vip_level;
			age_status = result.age_status;
			task_id = result.task_id;
			guess_you_like_switch = result.guess_you_like_switch;  // 增加猜你喜欢显示开关
		}
	});
	var perinfo="";
	perinfo +='<div class="shezhiPop" >'+
		'<div class="popinfo-con landPopInfoChange computerSetBox">'+
		'<img src="/themes/simpleboot3/sdkh5/assets/images/icon_close.png" class="s-btn-close">'+
		'<div class="popinfo-top">'+
		'<a class="my-pic fl"> <img src="'+userinfo.head_img+'">';
	if(userinfo.vip_level == 0){
		perinfo += '<span class="vip vip-disabled">V'+userinfo.vip_level+'</span>';
	}else{
		perinfo += '<span class="vip">V'+userinfo.vip_level+'</span>';
	}
	perinfo +=		'<a>'+
		'<div class="fl">'+
		'<div class="user-nickname fl">'+userinfo.account+'</div>';
	// '<a class="btn-change fl">[切换]</a>';


	perinfo +=   '<a class="js_waprenturn fl" id="logOutGameBtn">[退出游戏]</a>';
	perinfo +=  	'<a class="btn-shezhi fl" id="btn-shezhi">[设置]</a>';
	perinfo +=	'<div class="clear"></div>'+
		'<div class="fl currency">平台币:'+userinfo.balance+'</div>'+
		'<div class="fl currency">绑币:'+userinfo.bind_balance+'</div>'+
		'<div class="fl currency">积分:'+userinfo.point+'</div>'+
		' </div>'+
		'</div>'+
		'<div class="info-listAll">';
	if(userinfo.age_status == 1 || userinfo.age_status == 0){
		perinfo +=	'<a href="javascript:;" onclick="goregistered()">'+
			'<p class="s-line"><span class="s-left">我的实名</span>';
	}else{
		perinfo +=	'<a href="javascript:;">'+
			'<p class="s-line"><span class="s-left">我的实名</span>'
	}
	if(userinfo.age_status ==0){
		//未实名
		perinfo += '<img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_into.png" alt="" class="goto_icon fg"><span class="s-right fg">未实名</span></p>';
	}else if(userinfo.age_status ==4){
		// 审核中
		perinfo += '<span class="shenhe fg">审核中</span></p>';
	}else if(userinfo.age_status ==1){
		// 审核失败
		perinfo += '<img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_into.png" alt="" class="goto_icon fg"><span class="s-fail fg">审核失败</span></p>';
	}else if(userinfo.age_status ==2){
		// 已实名
		perinfo += '<span class="y-finish fg">'+userinfo.real_name+'，'+userinfo.idcard+'</span></p>';
	}else if(userinfo.age_status ==3){
		// 未成年
		perinfo += '<span class="shenhe fg" style="color: red">未成年<span style="color:#333">('+userinfo.real_name+','+userinfo.idcard+')</span></span></p>';
	}
	if(userinfo.age_status !=2 && userinfo.age_status !=3) {
		perinfo += '<p class="s-line2">*根据国家未成年防沉迷政策要求，<span class="redStyle">将限制未成年人每日游戏时间/充值等</span>，强烈推荐实名，我们承诺该信息仅作为实名认证所用，绝不会泄露给他人！</p>' ;

	}
	perinfo += '</a>';
	if(userinfo.phone == ''){
		perinfo += '<a href="javascript:;" onclick="gobindPhone()">'+
			'<p class="s-line"><span class="s-left">我的绑定手机</span><img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_into.png" alt="" class="goto_icon fg"><span class="s-right fg">未绑定</span></p>'+
			// '<span class="s-right fg">未绑定</span>'+
			'</a>';
	}else{
		perinfo += '<a href="javascript:;" onclick="phoneInfo()">'+
			'<p class="s-line"><span class="s-left">我的绑定手机</span><img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_into.png" alt="" class="goto_icon fg"><span class="y-finish fg">'+userinfo.phone+'</span></p>'+
			'</a>';
	}
	perinfo += '<a href="javascript:;"  onclick="goChangemima()">'+
		'<p class="s-line"><span class="s-left">修改密码</span><img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_into.png" alt="" class="goto_icon fg"></p>'+
		'</a>'+
		'<a href="javascript:;" class="hide-Xuanfuqiu">'+
		'<p class="s-line "><span class="s-left">隐藏浮球</span><img src="/themes/simpleboot3/mobilepublic/assets/images/common_btn_into.png" alt="" class="goto_icon fg"></p>'+
		'</a>'+

		'</div>'+
		'<div class="qiehuan-but s-btn-change">'+
		'<p>切换账号</p>'
	'</div>'+

	'</div></div>';
	$('body').append(perinfo);
	if (flag) {
		$('.shezhiPop').show()
	}
}

function getXfqSetDivReload(flag) {
	$('.shezhiPop').remove();
	getXfqSetDiv(flag);//再加载一遍设置div
}

//方法============================================
getXfqSetDiv();//先加载一遍设置div
var tplayIndex;
$(".js-tplay").click(function () {
	tplayIndex = layer.open({
		type: 2,
		skin: 'lwx-layer',
		title: '',
		closeBtn: 0,
		area: [is_mobile ? '100%' : '640px', '100%'],
		content: task_url+"?game_id="+game_id
	});
	$('#layui-layer-shade1').css('display','none')
});
function closeTplayPop() {
	layer.close(tplayIndex);
}
function isH5App(){
	var ua = window.navigator.userAgent.toLowerCase();
	if(ua.match(/app\/h5shell/i) == 'app/h5shell'){
		return true;
	}else{
		return false;
	}
}


var myswipers = new Swiper('.swiper-container.game_likeContainer', {
	slidesPerView: 'auto',
	spaceBetween: '3.7%',
	observeParents:true,
	observer:true
});

function goregistered(){
	$(".realnamepop2").css("display","block");
}

//设置--修改密码
//设置--修改密码 关闭
$(".change-top .js-btn-close").click(function(){
	$(".changeMima").css("display","none");
})
function goChangemima(){
	$(".changeMima").css("display","block");
	if (flag) {
		$(".shezhiPop").css("display","none");
	}

}
//未进行任何输入点击”确认
function checkpas2(){
	var pas1=document.getElementById("last-pwd").value;
	var pas2=document.getElementById("new-pwd").value;
	var pas3=document.getElementById("confirm-pwd").value;
	if(pas1==''){
		layer.msg("原密码不能为空！");
		return false;
	}
	if(pas2==''){
		layer.msg("新密码不能为空！");
		return false;
	}
	if(pas1=='' && pas2==''&& pas3==''){
		layer.msg("确认密码不能为空！");
		return false;
	}
	ajax_post('/sdkh5/game/modifyPassword','pas1='+pas1+'&pas2='+pas2+'&pas3='+pas3, function (res) {
		if(res.code == 1){
			layer.msg(res.msg);
			$(".changeMima").css("display","none");
			getXfqSetDivReload(true)
		}else{
			layer.msg(res.msg);
			return
		}
	});

}
//绑定手机 未绑定
function gobindPhone(){
	$(".gobindPhone").css("display","block");
	if(flag) {
		$(".shezhiPop").css("display","none");
	}
}
$(".gobindPhone .js-btn-close").click(function(){
	$(".gobindPhone").css("display","none");
})
//绑定手机 绑定成功
function confirm(){
	//绑定手机号请求
	var phone = $("#phone-num").val();
	var yzm = $("#yzm").val();
	if(phone == ''){
		layer.msg('请输入手机号');
		return false;
	}
	if(yzm == ''){
		layer.msg('请输入验证码');
		return false;
	}
	ajax_post('/sdkh5/game/modifyPhone','phone='+phone+'&yzm='+yzm+'&type='+1, function (res) {
		if(res.code == 1){
			layer.msg(res.msg);
			$(".gobindPhone").css("display","none")
			$(".gobindSucess").css("display","block");
			getXfqSetDivReload(true)
		}else{
			layer.msg(res.msg);
			return
		}
	});
}
$(".back").click(function(){
	$(".yanzhengPhone").css("display","none")
})
function fanhui(){
	$(".gobindSucess").css("display","none")
	$(".gobindPhone").css("display","none")
	getXfqSetDivReload(true)
}
// 设置绑定手机 已绑定 解绑手机号
// 设置绑定手机 已绑定 验证原手机
function phoneInfo() {
	$(".jiebang").css("display","block");
	$(".user_phone").html(userinfo.phone)
	if(flag){
	$(".shezhiPop").css("display","none");
}
}
function jiebang(){

	$(".jiebang").css("display","none")
	$(".yanzhengPhone").css("display","block")
	$("#phone-num1").val(userinfo.phone)
}
$(".back").click(function(){
	$(".yanzhengPhone").css("display","none")
	getXfqSetDiv();//再加载一遍设置div
})
$(".y-back").click(function(){
	$(".yanzhengPhone").css("display","none")
	$(".jiebang").css("display","block")
})
$(".back-old").click(function(){
	$(".bind-newPhone").css("display","none")
	$(".yanzhengPhone").css("display","block")
})
$(".change-top .btn-close").click(function(){
	$(".jiebang").css("display","none")
})
function contact(){
	$(".servicepop").show();
	$(".servicepop").css('z-index','999999');

}



function confirm2(){
	var yzm = $("#yzm1").val();
	if(yzm == ''){
		layer.msg('请输入验证码');
		return false;
	}
	ajax_post('/sdkh5/game/modifyPhone','yzm='+yzm+'&type='+2, function (res) {
		if(res.code == 1){
			layer.msg(res.msg);
			$(".yanzhengPhone ").css("display","none")
			$(".bind-newPhone").css("display","block")
			getXfqSetDivReload(true)
		}else{
			layer.msg(res.msg);
			return
		}
	});
}
//绑定手机 绑定成功
function newConfirm(){
	//绑定手机号请求
	var phone = $("#phone-num2").val();
	var yzm = $("#yzm2").val();
	if(phone == ''){
		layer.msg('请输入手机号');
		return false;
	}
	if(yzm == ''){
		layer.msg('请输入验证码');
		return false;
	}
	ajax_post('/sdkh5/game/modifyPhone','phone='+phone+'&yzm='+yzm+'&type='+1, function (res) {
		if(res.code == 1){
			layer.msg(res.msg);
			$(".bind-newPhone").css("display","none")
			$(".gobindSucess").css("display","block");
		}else{
			layer.msg(res.msg);
			return
		}
	});
}
$(".back").click(function(){
	$(".bind-newPhone").css("display","none")
})

function getSms(num,type,id){
	//ajax请求获取验证码 type 1=绑定,2=解绑
	ajax_post('/sdkh5/game/sendSms','phone='+num+'&type='+type, function (res) {
		if(res.code == 1){
			layer.msg(res.msg);
			daojishi(id);
		}else{
			layer.msg(res.msg);
			return
		}
	});
	return ;


}
function daojishi(id)
{
	let count = 60;
	const countDown = setInterval(() => {
		if (count === 0) {
			$('.huopqv'+id).text('重新发送').removeAttr('disabled');
			$('.huopqv'+id).css({
				background: '#018FFF',
				color: '#fff',
			});
			clearInterval(countDown);
		} else {
			$('.huopqv'+id).attr('disabled', true);
			$('.huopqv'+id).css({
				background: '#E3E3E3',
				color: '#999999',
				class:'disabled'
			});
			$('.huopqv'+id).text(count + 's');
		}
		count--;
	}, 1000);
}

// 点击获取验证码操作-绑定1
$('.getcode1').click(function() {
	//获取手机号
	var num = $("#phone-num").val();
	if(num == ''){
		layer.msg('请输入手机号');
		return false;
	}
	getSms(num,1,'');
});
// 点击获取验证码操作-解绑2
$('.getcode2').click(function() {
	getSms('',2,'');
});
// 点击获取验证码操作-绑定3
$('.getcode3').click(function() {
	//获取手机号
	var num = $("#phone-num2").val();
	if(num == ''){
		layer.msg('请输入手机号');
		return false;
	}
	getSms(num,1,'');
});
// 判断手机还是电脑
function IsPC() {
	var userAgentInfo = navigator.userAgent;
	var Agents = ["Android", "iPhone",
		"SymbianOS", "Windows Phone",
		"iPad", "iPod"];
	var flag = true;
	for (var v = 0; v < Agents.length; v++) {
		if (userAgentInfo.indexOf(Agents[v]) > 0) {
			flag = false;
			break;
		}
	}
	return flag;
}

var flag = IsPC(); //true为PC端，false为手机端
if(flag) {
	$(".landPopInfoChange").css("width","32rem","height","23rem","margin","-5rem 0 0 -7rem",);
}
$(".s-btn-close").click(function(){
	$(".shezhiPop").css("display","none")
})