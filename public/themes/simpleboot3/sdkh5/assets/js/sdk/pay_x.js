 /* 悬浮按钮移动 鹿文学 2017-11-07 */
	  var flag = 0;//判断点击和拖拽
      var s = document.getElementById('jssuspensionbtn');
      var t =  mobile_flag?{evt1:'touchstart',evt2:'touchmove',evt3:'touchend'}:{evt1:'mousedown',evt2:'mousemove',evt3:'mouseup'};
      var h,f,l,r,c,d;
      s.addEventListener(t.evt1,function(event){
        f = !0;
		flag = 0;
		 console.log(flag);
        var e = event || window.event;
        var g = e.touches ? e.touches[0]:{clientX:e.clientX,clientY:e.clientY};
        l = g.clientX - s.offsetLeft;
        r = g.clientY - s.offsetTop;
        document.addEventListener(t.evt2,function(a){a.preventDefault();},!1),
        document.addEventListener(t.evt2,function(a){
		 flag =1;
          var a = a || window.event;
          if (f) {
            h = !1;
            var b = a.touches?a.touches[0]:{clientX:a.clientX,clientY:a.clientY};
            c = b.clientX - l;
            d = b.clientY - r;
            0>c?c=0:c>document.documentElement.clientWidth-s.offsetWidth && (c = document.documentElement.clientWidth-s.offsetWidth);
            0>d?d=0:d>document.documentElement.clientHeight-s.offsetHeight && (d = document.documentElement.clientHeight-s.offsetHeight);
            s.style.left = c + 'px';
            s.style.top = d + 'px';
            $(s).addClass('open');
          }
        },!1)

      },!1);
      s.addEventListener(t.evt3,function(event){
	  
        f = !1;
        var e = event || window.event;
        s.style.left = '-1rem';
        s.style.right = 'auto';
        s.style.top = d + 'px';
        $(s).removeClass('open');
        document.addEventListener(t.evt2,function(a){a.preventDefault();},!1);
        document.removeEventListener(t.evt2,function(a){a.preventDefault(); },!1);
        setTimeout(function() {h = !0;},15);
        return false;
      },!1);
     
	    /* 悬浮出现 */
	
      $(s).on('click',function() {
	  if(flag ==0){
          $(".pop").toggle();
		  return false;
		  }
      });
	 