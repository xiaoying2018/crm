$(function() {
    window.submitObj = {
        afterDomReady: function() {

            $("select").niceSelect();

            $(".chooselist li").click(function() {
                $(this).addClass("active").siblings().removeClass("active");
            });
        },
        beforeValidate: function() {
            return true;
        },
        //公共组件验证完成之后会触发该方法，返回true往下执行
        afterValidate: function() {
            return true;
        },
        //提交之前触发该方法，个性项目提交参数对象转换
        before: function(_obj, cb) {
            cb(_obj);
        },
        after: function(res) {
            Cookies.remove('xiaoying.net_evaluation_res', { path: '' });
            Cookies.set('xiaoying.net_evaluation_res', res);
            // alert("提交成功");
            window.location.href = "/evaluation_res"
        }
    }

    $(".itemFill input").keyup(function(){
        $(this).closest(".itemFill").removeClass("correct wrong")
    })

    $(".itemFill").click(function(){
        $(this).removeClass("correct wrong")
    })
});