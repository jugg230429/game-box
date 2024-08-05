/**
 * Created by yyh on 2020/6/10.
 */
;(function (w) {
    var sdk = w.XG_H5_GAME_SDK || {};
    sdk.clientVersion = "9.0 build 20200610";
    sdk.config = function (params) {
        config(params);
    };
    //加载配置信息
    function config(params) {
        window.addEventListener("message", function (event) {
            switch (event.data.operation) {
                case "pullShare": {
                    params.share.success(event.data.param);
                    break
                }
                case "pullPay": {
                    params.pay.success(event.data.param);
                    break
                }
                case "pullRole": {
                    params.role.success(event.data.param);
                    break
                }
            }
        }, false);
    };

//唤起支付
    sdk.pay = function (order) {
        sdk.postMsgToParent({
            operation: "pay",
            param: order
        });
    };
//上传角色信息
    sdk.role = function (roleInfo) {
        sdk.postMsgToParent({
            operation: "role",
            param: roleInfo
        });
    };
//调起支付
    sdk.payurl = function (url) {
        sdk.postMsgToParent({
            operation: "payurl",
            param: url
        });
    }

//唤起分享引导页
    sdk.showShare = function (shareInfo) {
        sdk.postMsgToParent({
            operation: "share",
            param: {
                title: shareInfo.title,
                desc: shareInfo.desc,
                imgUrl: shareInfo.imgUrl
            }
        });
    };

    sdk.postMsgToParent = function (post) {

        window.parent.postMessage(post, '*');
    };


    sdk.getParameter = function(name) {
        var reg = new RegExp("[&?](" + name + ")=([^&?#]*)", "i");
        var r = window.location.search.match(reg);
        return r ? r[2] : null;
    };


    w.XG_H5_GAME_SDK = sdk;
})(window);