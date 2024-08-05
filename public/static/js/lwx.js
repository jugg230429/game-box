/**
 * 浏览器端存储数据
 * author 鹿文学
 */
(function() {
	var lwx = window.LWX || {};
	lwx.init = function(){
		
	};
	lwx.setItem = function(key, value, year) {
		if (window.localStorage) {
			try {
					window.localStorage.setItem("lwx_" + key, value)
			} catch (err) {}
		} else {
			var exp = new Date;var year = year || 1;
			exp.setTime(exp.getTime() + 365 * 24 * 60 * 60 * 1e3 * year);
			document.cookie = "lwx_" + key + "=" + escape(value) + ";expires=" + exp.toGMTString()
		}
	};
	lwx.getItem = function(key) {
		if (window.localStorage) {
			return window.localStorage.getItem("lwx_" + key)
		} else {
			var arr = document.cookie.match(new RegExp("(^| )lwx_" + key + "=([^;]*)(;|$)"));
			if (arr != null) {
					return unescape(arr[2])
			}
		}
		return null
	};
	lwx.removeItem = function(key) {
		if (window.localStorage) {
			window.localStorage.removeItem("lwx_" + key)
		} else {
			var exp = new Date;
			exp.setTime(exp.getTime() - 1);
			var cval = lwx.getItem(key);
			if (cval != null) {
				document.cookie = "lwx_" + key + "=" + cval + ";expires=" + exp.toGMTString()
			}
		}
	};
	lwx.setSession = function(key, value) {
		if (window.sessionStorage) {
			window.sessionStorage.setItem("lwx_" + key, value)
		}
	};
	lwx.getSession = function(key) {
		if (window.sessionStorage) {
			return window.sessionStorage.getItem("lwx_" + key)
		}
		return null
	};
	lwx.removeSession = function(key) {
		if (window.sessionStorage) {
			window.sessionStorage.removeItem("lwx_" + key)
		}
	};
	lwx.init();
	window.LWX = lwx;
})();
var lwx = window.LWX;