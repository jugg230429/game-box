/**
 * 拖动图片验证码，中、英
 * 传参说明
 * "id": ""
 "verify_url": false, // 这里设置默认 滑块验证地址
 "source_url": false, // 资源地址
 "form_id": false, // 表单id
 "submit_url": false, // 表单提交地址
 "auto_submit": false, // 是否自动提交表单
 "crypt_func": false, // 是否启用加密函数，一般用RSA，若启用https，请忽略此项
 "post_field":"x_value", // 用于验证的字段名
 "layer_time": 1000 // 错误信息[除验证码] 弹出窗口显示时间，单位 ms
 */
;
(function($) {
    $.fn.y_drag = function(options) {
        var x,
            moving = false,
            //  外部传参
            initial = $.extend({
                "id":"#captchar",
                "if_hide": false, // 是否将验证码图片先隐藏，响应事件时才显示[验证成功继续隐藏]
                "verify_url": false, // 这里设置默认 滑块验证地址
                "source_url": false, // 资源地址
                "form_id": false, // 表单id
                "submit_url": false, // 表单提交地址
                "auto_submit": false, // 是否自动提交表单
                "crypt_func": false, // 是否启用加密函数，一般用RSA
                "post_field": "h", // 用于验证的字段名
                "success": false,
                "key":'',
                "layer_time": 1000 // 错误信息[除验证码] 弹出窗口显示时间，单位 ms
            }, options);
        var that = $(initial.id);
        var drag = that.find('.drag');
        var getnow_xy_img = that.find('.sliding_block');
        var base64 = new Base64();
        var sh = new H();
        //添加背景，文字，滑块
        if (initial.if_hide) {}
        //放验证码的容器
        var now_container = drag.parent().eq(0).parent().eq(0),
            handler = drag.find('.handler'),
            drag_bg = drag.find('.drag_bg'),
            text = drag.find('.drag_text'),
            maxWidth = drag.outerWidth() - handler.outerWidth();


        var track=[];

        var s = handler.get(0);
        var maxHeight = handler.outerHeight();
        var offset = drag.offset();

        var t = 'ontouchstart' in window?{evt1:'touchstart',evt2:'touchmove',evt3:'touchend'}:{evt1:'mousedown',evt2:'mousemove',evt3:'mouseup'};
        var h,f,l,c;
        var mk = 'ontouchstart' in window?{ passive: false }:!1;
        s.addEventListener(t.evt1,function(event){
            getnow_xy_img.show();
            if(drag.hasClass('drag_checking')) {return false;}
            drag.addClass('drag_checking').removeClass('drag_success drag_error');
            f = !0;
            var e = event || window.event;
            var g = e.changedTouches ? e.changedTouches[0]:{clientX:e.clientX,clientY:e.clientY};
            l = g.clientX - s.offsetLeft;

            track.push([g.clientX,g.clientY,(new Date()).getTime()]);
            document.addEventListener(t.evt2,function(a){a.preventDefault();},mk),
                document.addEventListener(t.evt2,function(a){
                    var a = a || window.event;
                    if (f) {
                        h = !1;
                        var b = a.changedTouches?a.changedTouches[0]:{clientX:a.clientX,clientY:a.clientY};
                        c = b.clientX - l;

                        track.push([b.clientX,b.clientY,(new Date()).getTime()]);
                        0>c?c=0:c>maxWidth && (c=maxWidth);

                        s.style.left = c + 'px';

                        getnow_xy_img.css({
                            'left': c
                        });

                        drag_bg.css({
                            'width': c
                        });

                    } else {
                        return false;
                    }
                },mk),

                document.addEventListener(t.evt3,function (a) {
                    var a = a || window.event;
                    if (f) {

                        var b = a.changedTouches?a.changedTouches[0]:{clientX:a.clientX,clientY:a.clientY};

                        if(b.clientX < offset.left || b.clientX > offset.left + maxWidth ||
                            b.clientY < offset.top || b.clientY > offset.top + maxHeight ) {
                            h = !0;
                            f = !1;
                            submit_verify(c);
                        }

                    }
                }, mk)

        },!1);
        s.addEventListener(t.evt3,function(event){
            f = !1;
            var e = event || window.event;
            var b = e.changedTouches?e.changedTouches[0]:{clientX:e.clientX,clientY:e.clientY};
            track.push([b.clientX,b.clientY,(new Date()).getTime()]);

            document.addEventListener(t.evt2,function(a){a.preventDefault();},!1);
            document.removeEventListener(t.evt2,function(a){a.preventDefault();},!1);
            setTimeout(function() {
                h = !0;
                submit_verify(c);
            },5);

        },mk);


        function H() {
            var hexcase = 0;
            var b64pad = "";
            var chrsz = 8;

            this.hex_sha1 = function (s) {
                return binb2hex(core_sha1(str2binb(s), s.length * chrsz));
            }

            this.b64_sha1 = function (s) {
                return binb2b64(core_sha1(str2binb(s), s.length * chrsz));
            }

            function str_sha1(s) {
                return binb2str(core_sha1(str2binb(s), s.length * chrsz));
            }

            function hex_hmac_sha1(key, data) {
                return binb2hex(core_hmac_sha1(key, data));
            }

            function b64_hmac_sha1(key, data) {
                return binb2b64(core_hmac_sha1(key, data));
            }

            function str_hmac_sha1(key, data) {
                return binb2str(core_hmac_sha1(key, data));
            }

            function sha1_vm_test() {
                return hex_sha1("abc") == "a9993e364706816aba3e25717850c26c9cd0d89d";
            }

            function core_sha1(x, len) {
                x[len >> 5] |= 0x80 << (24 - len % 32);
                x[((len + 64 >> 9) << 4) + 15] = len;

                var w = Array(80);
                var a = 1732584193;
                var b = -271733879;
                var c = -1732584194;
                var d = 271733878;
                var e = -1009589776;

                for (var i = 0; i < x.length; i += 16) {
                    var olda = a;
                    var oldb = b;
                    var oldc = c;
                    var oldd = d;
                    var olde = e;

                    for (var j = 0; j < 80; j++) {
                        if (j < 16) w[j] = x[i + j];
                        else w[j] = rol(w[j - 3] ^ w[j - 8] ^ w[j - 14] ^ w[j - 16], 1);
                        var t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)), safe_add(safe_add(e, w[j]), sha1_kt(j)));
                        e = d;
                        d = c;
                        c = rol(b, 30);
                        b = a;
                        a = t;
                    }

                    a = safe_add(a, olda);
                    b = safe_add(b, oldb);
                    c = safe_add(c, oldc);
                    d = safe_add(d, oldd);
                    e = safe_add(e, olde);
                }
                return Array(a, b, c, d, e);

            }

            function sha1_ft(t, b, c, d) {
                if (t < 20) return (b & c) | ((~b) & d);
                if (t < 40) return b ^ c ^ d;
                if (t < 60) return (b & c) | (b & d) | (c & d);
                return b ^ c ^ d;
            }

            function sha1_kt(t) {
                return (t < 20) ? 1518500249 : (t < 40) ? 1859775393 : (t < 60) ? -1894007588 : -899497514;
            }

            function core_hmac_sha1(key, data) {
                var bkey = str2binb(key);
                if (bkey.length > 16) bkey = core_sha1(bkey, key.length * chrsz);

                var ipad = Array(16),
                    opad = Array(16);
                for (var i = 0; i < 16; i++) {
                    ipad[i] = bkey[i] ^ 0x36363636;
                    opad[i] = bkey[i] ^ 0x5C5C5C5C;
                }

                var hash = core_sha1(ipad.concat(str2binb(data)), 512 + data.length * chrsz);
                return core_sha1(opad.concat(hash), 512 + 160);
            }

            function safe_add(x, y) {
                var lsw = (x & 0xFFFF) + (y & 0xFFFF);
                var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
                return (msw << 16) | (lsw & 0xFFFF);
            }

            function rol(num, cnt) {
                return (num << cnt) | (num >>> (32 - cnt));
            }

            function str2binb(str) {
                var bin = Array();
                var mask = (1 << chrsz) - 1;
                for (var i = 0; i < str.length * chrsz; i += chrsz)
                    bin[i >> 5] |= (str.charCodeAt(i / chrsz) & mask) << (24 - i % 32);
                return bin;
            }

            function binb2str(bin) {
                var str = "";
                var mask = (1 << chrsz) - 1;
                for (var i = 0; i < bin.length * 32; i += chrsz)
                    str += String.fromCharCode((bin[i >> 5] >>> (24 - i % 32)) & mask);
                return str;
            }

            function binb2hex(binarray) {
                var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
                var str = "";
                for (var i = 0; i < binarray.length * 4; i++) {
                    str += hex_tab.charAt((binarray[i >> 2] >> ((3 - i % 4) * 8 + 4)) & 0xF) + hex_tab.charAt((binarray[i >> 2] >> ((3 - i % 4) * 8)) & 0xF);
                }
                return str;
            }

            function binb2b64(binarray) {
                var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
                var str = "";
                for (var i = 0; i < binarray.length * 4; i += 3) {
                    var triplet = (((binarray[i >> 2] >> 8 * (3 - i % 4)) & 0xFF) << 16) | (((binarray[i + 1 >> 2] >> 8 * (3 - (i + 1) % 4)) & 0xFF) << 8) | ((binarray[i + 2 >> 2] >> 8 * (3 - (i + 2) % 4)) & 0xFF);
                    for (var j = 0; j < 4; j++) {
                        if (i * 8 + j * 6 > binarray.length * 32) str += b64pad;
                        else str += tab.charAt((triplet >> 6 * (3 - j)) & 0x3F);
                    }
                }
                return str;
            }
        }

        function ens(str) {
            //定义密钥，36个字母和数字
            var key = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            var l = key.length;  //获取密钥的长度
            var a = key.split("");  //把密钥字符串转换为字符数组
            var s = "",b, b1, b2, b3;  //定义临时变量
            for (var i = 0; i <str.length; i ++) {  //遍历字符串
                b = str.charCodeAt(i);  //逐个提取每个字符，并获取Unicode编码值
                b1 = b % l;  //求Unicode编码值得余数
                b = (b - b1) / l;  //求最大倍数
                b2 = b % l;  //求最大倍数的于是
                b = (b - b2) / l;  //求最大倍数
                b3 = b % l;  //求最大倍数的余数
                s += a[b3] + a[b2] + a[b1];  //根据余数值映射到密钥中对应下标位置的字符
            }
            return s;  //返回这些映射的字符
        }

        function submit_verify(now_x) {
            var t = (new Date()).getTime();
            var r = random().toString();
            var post_data = initial.post_field + "=" + base64.encode(r+(1000+now_x).toString());
            var data = post_data;
            data += '&g=' + JSON.stringify(track) + '&t='+t;
            post_data += '&b=' + ens(base64.encode(data)) + '&j='+t+'&d='+base64.encode(r.toString());
            post_data += '&e='+ sh.hex_sha1(base64.encode(data)+''+r);
            post_data += '&f='+initial.key;
            $.ajax({
                type: "post",
                url: initial.verify_url,
                dataType: "json",
                async: false,
                data: post_data,
                success: function(result) {
                        for (var i = 1; 4 >= i; i++) {
                            getnow_xy_img.animate({
                                left: now_x - (30 - 10 * i)
                            }, 50);
                            getnow_xy_img.animate({
                                left: now_x + 2 * (30 - 10 * i)
                            }, 50, function() {
                                getnow_xy_img.css({
                                    'left': now_x
                                })
                            })
                        }
                        handler.css({
                            'left': maxWidth
                        });
                        drag_bg.css({
                            'width': maxWidth
                        });
                    if (200 === result.code) {
                        drag.removeClass('drag_checking').addClass('drag_success');
                        remove_listener();
                        if(initial.success) {
                            if(initial.crypt_func) {initial.success(result, crypt_data);} else {initial.success(result);}
                        }
                    } else {
                        drag.removeClass('drag_checking').addClass('drag_error');
                        drag.find('.drag_text').text(result.msg);
                        setTimeout(function () {
                            get_src()
                        },800)
                    }
                }
            })
        }

        function msg(msg, time) {
            time = time || 10000000000000;
            var html= '<div class="drag-msg" style="position: absolute;top:0;left:0;width:100%;height:100%;z-index:999999999999999;background:rgba(0,0,0,.75);">' +
                '<div style="display:table;width:100%;height:100%;"><div style="display:table-cell;vertical-align: middle;text-align: center;width:100%;">' +
                '<span style="display: inline-block;background:rgba(255,255,255,.75);color:#000;font-size:1em;padding:5px 10px;">'+msg+'</span></div></div></div>';
            $('body').append(html);
            setTimeout(function () {
                $('body .drag-msg').remove();
            },time);
        }

        function Base64() {
            // private property
            _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
            // public method for encoding
            this.encode = function (input) {
                var output = "";
                var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                var i = 0;
                input = _utf8_encode(input);
                while (i < input.length) {
                    chr1 = input.charCodeAt(i++);
                    chr2 = input.charCodeAt(i++);
                    chr3 = input.charCodeAt(i++);
                    enc1 = chr1 >> 2;
                    enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                    enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                    enc4 = chr3 & 63;
                    if (isNaN(chr2)) {
                        enc3 = enc4 = 64;
                    } else if (isNaN(chr3)) {
                        enc4 = 64;
                    }
                    output = output +
                        _keyStr.charAt(enc1) + _keyStr.charAt(enc2) +
                        _keyStr.charAt(enc3) + _keyStr.charAt(enc4);
                }
                return output;
            }
            // public method for decoding
            this.decode = function (input) {
                var output = "";
                var chr1, chr2, chr3;
                var enc1, enc2, enc3, enc4;
                var i = 0;
                input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
                while (i < input.length) {
                    enc1 = _keyStr.indexOf(input.charAt(i++));
                    enc2 = _keyStr.indexOf(input.charAt(i++));
                    enc3 = _keyStr.indexOf(input.charAt(i++));
                    enc4 = _keyStr.indexOf(input.charAt(i++));
                    chr1 = (enc1 << 2) | (enc2 >> 4);
                    chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                    chr3 = ((enc3 & 3) << 6) | enc4;
                    output = output + String.fromCharCode(chr1);
                    if (enc3 != 64) {
                        output = output + String.fromCharCode(chr2);
                    }
                    if (enc4 != 64) {
                        output = output + String.fromCharCode(chr3);
                    }
                }
                output = _utf8_decode(output);
                return output;
            }
            // private method for UTF-8 encoding
            _utf8_encode = function (string) {
                string = string.replace(/\r\n/g,"\n");
                var utftext = "";
                for (var n = 0; n < string.length; n++) {
                    var c = string.charCodeAt(n);
                    if (c < 128) {
                        utftext += String.fromCharCode(c);
                    } else if((c > 127) && (c < 2048)) {
                        utftext += String.fromCharCode((c >> 6) | 192);
                        utftext += String.fromCharCode((c & 63) | 128);
                    } else {
                        utftext += String.fromCharCode((c >> 12) | 224);
                        utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                        utftext += String.fromCharCode((c & 63) | 128);
                    }
                }
                return utftext;
            }
            // private method for UTF-8 decoding
            _utf8_decode = function (utftext) {
                var string = "";
                var i = 0;
                var c = c1 = c2 = 0;
                while ( i < utftext.length ) {
                    c = utftext.charCodeAt(i);
                    if (c < 128) {
                        string += String.fromCharCode(c);
                        i++;
                    } else if((c > 191) && (c < 224)) {
                        c2 = utftext.charCodeAt(i+1);
                        string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                        i += 2;
                    } else {
                        c2 = utftext.charCodeAt(i+1);
                        c3 = utftext.charCodeAt(i+2);
                        string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                        i += 3;
                    }
                }
                return string;
            }
        }

        function random() {
            return parseInt(Math.random() * 10000) + (new Date()).valueOf();
        }



        function crypt_data() {
            var params = $("#" + initial.form_id).serialize(),
                arr = params.split("&"),    // 分割为单个数据
                bulid_data = {},    // 待组装的数据
                temp,
                temp_encode_data;
            for (var i = 0; i < arr.length; i++) {
                temp = arr[i].split('=');
                // 获取加密数据
                temp_encode_data = eval( initial.crypt_func + "('" + temp[1]  + "');" );
                // 组装为json，【利用jquery中json解析机制，防止传送过程中，个别字符因直接序列化发送导致的丢失】
                eval( "bulid_data." + temp[0] + "='" + temp_encode_data+"';");
            }
            return bulid_data;
        }
        // GET 新资源

        function get_src() {
            $.ajax({
                type: "get",
                url: initial.source_url,
                dataType: 'html',
                async: false,
                success: function(get_html) {
                    remove_listener();
                    now_container.eq(0).html(get_html);
                    $(this).y_drag(initial)
                }
            })
        }
        // 清空监听事件

        function remove_listener() {
            handler.removeClass('handler_bg').addClass('handler_ok_bg');
            if (client_lang()) {
                text.text("验证码正确!")
            } else {
                text.text("Verification code correct!")
            }
        }
        // 浏览器语言判断，中英

        function client_lang() {
            var type = navigator.appName;
            if (type == "Netscape") {
                var lang = navigator.language
            } else {
                var lang = navigator.userLanguage
            }
            var lang = lang.substr(0, 2);
            if (lang == "zh") {
                return true
            } else {
                return false
            }
        }
    }
})(jQuery);
