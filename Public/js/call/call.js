/*
 * 呼叫中心逻辑处理
 * （呼入、呼出）
 */
$(document).on('click','.call',function(){
	var tel = $(this).attr('phone');
	var model_id = $(this).attr('rel');
	var model = $(this).attr('model');
	var width = $('#wrapper').width() * 0.9;
	var title = '客户信息';
	if (model == 'leads') {
		title = '线索信息';
	}
	layer.open({
		type: 2,
		title: false,
		closeBtn: 0, //不显示关闭按钮
		shade: [0],
		area: ['340px', '215px'],
		offset: 'rb', //右下角弹出
		time: 2000, //2秒后自动关闭
		anim: 2,
		content: ['./index.php?m=call&a=call_content&tel='+tel+'&model_id='+model_id+'&model='+model, 'no'], //iframe的url，no代表不显示滚动条
		end: function(){
			layer.open({
				type: 2,
				title: title,
				shadeClose: true,
				shade: false,
				maxmin: true, //开启最大化最小化按钮
				area: [width+'px', '600px'],
				content: './index.php?m=call&a=data&tel='+tel+'&model_id='+model_id+'&model='+model
			});
		}
	});
	$('#call_list').modal('hide');
});