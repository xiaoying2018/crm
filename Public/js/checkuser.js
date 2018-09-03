/*
* 类型 ( require 不能为空   password 密码（防止以后有特殊的要求所以单独写出） email 邮箱  phone 手机)
* @description   js判断 input
* input_msg = false 标示 全局变量
* input_require input_pwd  input_doublepwd input_email  input_phone = 0 全局变量 （要求每一个类型对应一个input_XX  做到全面判断）
* 该 input name属性应和下面的提示div的id的前半部分保持一致
* <div class="col-sm-4 error_msg" id="telephoneTip"></div>  提示信息的div   class id 必须
*col-sm-4 随便定义
*error_msg 必须
 */
var input_msg = false;//标示 全局变量
var input_require = 0, input_pwd = 0, input_doublepwd = 0, input_email = 0, input_phone = 0;//全局变量 判断是否符合条件
function checkform(obj){
	var item_val = $(obj).val();//当前对象的值
	var item_type = $(obj).attr('rel');//类型
	var item_name = $(obj).attr('rell');//该对象的名字
	var item = $(obj).attr('id');//该对象的 表单 id属性的值
	if(item_type == 'require'){
		var myreg = /\S/;
		if(!myreg.test(item_val)){ 
			$('#'+item+'Tip').removeClass("hide");
			$('#'+item+'Tip').addClass('show');
		   	$('#'+item+'Tip').addClass("onFocus");
		   	$('#'+item+'Tip').html('请输入'+item_name+'！');
			return false;
		}else{
			$('#'+item+'Tip').removeClass("onFocus");
			$('#'+item+'Tip').removeClass('show');
			$('#'+item+'Tip').addClass('hide');
			$('#'+item+'Tip').html('');
			input_require = 1;
		}
	}else if(item_type == 'password'){
		var myreg = /\S/;
		if(!myreg.test(item_val)){ 
			$('#'+item+'Tip').removeClass("hide");
			$('#'+item+'Tip').addClass('show');
		   	$('#'+item+'Tip').addClass("onFocus");
		   	$('#'+item+'Tip').html('请输入'+item_name+'！');
		    return false; 
		}else{
			$('#'+item+'Tip').removeClass("onFocus");
			$('#'+item+'Tip').removeClass('show');
			$('#'+item+'Tip').addClass('hide');
			$('#'+item+'Tip').html('');
			input_pwd = 1;
		}
	}else if(item_type == 'confirmpd'){
		if(item_val == ''){
			$('#'+item+'Tip').removeClass("hide");
			$('#'+item+'Tip').addClass('show');
		   	$('#'+item+'Tip').addClass("onFocus");
		   	$('#'+item+'Tip').html('请填写'+item_name+'！');
			return false;
		}else{
			var pwd = $('#password').val();
			if(item_val != pwd){
				$('#'+item+'Tip').removeClass("hide");
				$('#'+item+'Tip').addClass('show');
			   	$('#'+item+'Tip').addClass("onFocus");
			   	$('#'+item+'Tip').html('两次密码输入不一致！');
				return false;
			}else{
				$('#'+item+'Tip').removeClass("onFocus");
				$('#'+item+'Tip').removeClass('show');
				$('#'+item+'Tip').addClass('hide');
				$('#'+item+'Tip').html('');
				input_doublepwd = 1;
			}
		}
	}else if(item_type == 'email'){
		var myreg = /\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/;
		if(item_val == ''){
			$('#'+item+'Tip').removeClass("hide");
			$('#'+item+'Tip').addClass('show');
		   	$('#'+item+'Tip').addClass("onFocus");
		   	$('#'+item+'Tip').html('请填写'+item_name+'！');
			return false;
		}else{
			if(!myreg.test(item_val)){
				$('#'+item+'Tip').removeClass("hide");
				$('#'+item+'Tip').addClass('show');
			   	$('#'+item+'Tip').addClass("onFocus");
			   	$('#'+item+'Tip').html(item_name+'格式不正确！');
				return false;
			}else{
				$('#'+item+'Tip').removeClass("onFocus");
				$('#'+item+'Tip').removeClass('show');
				$('#'+item+'Tip').addClass('hide');
				$('#name').val(item_val);
				$('#'+item+'Tip').html('');
				input_email = 1;
			}
		}
	}else if(item_type == 'phone'){
 		var myreg = /^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/;
		if(item_val == ''){
			$('#'+item+'Tip').removeClass("hide");
			$('#'+item+'Tip').addClass('show');
		   	$('#'+item+'Tip').addClass("onFocus");
		   	$('#'+item+'Tip').html('请填写'+item_name+'！');
			return false;
		}else{
			if(!myreg.test(item_val)){
				$('#'+item+'Tip').removeClass("hide");
				$('#'+item+'Tip').addClass('show');
			   	$('#'+item+'Tip').addClass("onFocus");
			   	$('#'+item+'Tip').html(item_name+'格式不正确！');
				return false;
			}else{
				$('#'+item+'Tip').removeClass("onFocus");
				$('#'+item+'Tip').removeClass('show');
				$('#'+item+'Tip').addClass('hide');
				$('#'+item+'Tip').html('');
				input_phone = 1;
			}
		}
	}
	if(input_require || (input_pwd || input_doublepwd || input_email || input_phone)){
		input_msg = true;
		return true;
	}
}
/*
* 提交表单时 做最后验证
*/
function before_submit(){
	var error_html = ''; //div中的提示信息  以便验证
	$('.error_msg').each(function(k,v){
		error_html += ($(v).html());
	});
	if(error_html == ''){
		return true;
	}else{
		return false;
	}
}
// 验证表单 end