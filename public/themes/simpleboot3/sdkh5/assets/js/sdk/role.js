/**
 * Created by yyh on 2020/6/10.
 */
window.addEventListener("message", function (event) {
    switch (event.data.operation) {
        case "role": {
            var cporder = event.data.param;
            ajax_post('/sdkh5/role/createRole', cporder, function (result) {
                console.log(result);
            });
            break;
        }
    }
}, false);