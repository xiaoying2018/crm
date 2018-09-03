function changeCondition(){
	var a = $("#field option:selected").attr('class');
	var b = $("#field option:selected").val();
	var c = $("#field option:selected").attr('rel');
	if(a == 'role'){
		role_id = $('#search').val();
	}
	if(a == 'number') {
		$("#conditionContent").html('<select class="form-control input-sm" id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="gt">  '+CrmLang.GT+'  </option>'
							+'<option value="lt">  '+CrmLang.LT+'  </option>'
							+'<option value="eq">  '+CrmLang.EQ+'  </option>'
							+'<option value="neq">  '+CrmLang.NEQ+'  </option>'
							+'</select>&nbsp;&nbsp; ');
		$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search"/>&nbsp;&nbsp;');
	} else if ((a == 'word') || (a == 'text') || (a == 'textarea') || (a == 'editor') || (a == 'mobile') || (a == 'email')) {
		$("#conditionContent").html('<select class="form-control input-sm" id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="contains">'+CrmLang.CONTAINS+'</option>'
							+'<option value="not_contain">'+CrmLang.NOT_CONTAIN+'</option>'
							+'<option value="is">'+CrmLang.IS+'</option>'
							+'<option value="isnot">'+CrmLang.ISNOT+'</option>'							
							+'<option value="start_with">'+CrmLang.START_WITH+'</option>'
							+'<option value="end_with">'+CrmLang.END_WITH+'</option>'
							+'<option value="is_empty">'+CrmLang.IS_EMPTY+'</option>'
							+'<option value="is_not_empty">'+CrmLang.IS_NOT_EMPTY+'</option></select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search"/>&nbsp;&nbsp;');
	} else if (a == 'date' || a== 'datetime') {
		$("#conditionContent").html('<select class="form-control input-sm" id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="tgt">  '+CrmLang.BEHIND+'  </option>'
							+'<option value="lt">  '+CrmLang.BEFORE+'  </option>'
							+'<option value="between">  '+CrmLang.EXIST+'  </option>'
							+'<option value="nbetween">  '+CrmLang.ABSENT+'  </option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search" onclick="WdatePicker()"/>&nbsp;&nbsp;');
	} else if (a == 'bool') {
		$("#conditionContent").html('<select class="form-control input-sm" id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="1">'+CrmLang.IS+'</option>'
							+'<option value="0">'+CrmLang.ISNOT+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search"/>&nbsp;&nbsp;');
	} else if (a == 'sex') {
		$("#searchContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value="1">'+CrmLang.MAN+'</option>'
							+'<option value="0">'+CrmLang.WOMAN+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	} else if (a == 'role') {
		var module = getUrlParam("m");
		var action = getUrlParam("a");
		$.ajax({
			type:'get',
			url:'index.php?m=user&a=getrolelist&module='+module+'&action='+action,
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					var select ='';
					if(v.role_id == role_id){
						select = 'selected';
					}
					options += '<option value="'+v.role_id+'" '+select+'>'+v.user_name+' ['+v.department_name+'-'+v.role_name+'] </option>';
				});
				//$("#searchContent").html('<select id="search" class="form-control input-sm" style="width:auto;max-width: 200px;" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#searchContent").html('<select class="selectpicker show-tick form-control input-sm" data-live-search="true" id="search" name="search" style="width:auto">' + options + '</select>&nbsp;&nbsp;');
				$('#search').selectpicker('render');
                $('#search').selectpicker('refresh');
				
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	} else if (a == 'business_status') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=getbusinessstatuslist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.status_id+'">'+v.name+'</option>';
				});

				$("#searchContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	}else if (a == 'customer') {
		$("#conditionContent").html('');
		$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search"/>&nbsp;&nbsp;');
	}else if (a == 'stock') {
		$.ajax({
			type:'get',
			url:'index.php?m=stock&a=getwarehouselist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.warehouse_id+'">'+v.name+'</option>';
				});
				$("#searchContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	}else if (a == 'contract') {
		$.ajax({
			type:'get',
			url:'index.php?m=contract&a=getcontractlist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.contract_id+'">'+v.number+'--'+v.customer_name+'</option>';
				});
				$("#searchContent").html('<select class="selectpicker show-tick form-control input-sm" id="search" style="width:auto" data-live-search="true" name="search">' + options + '</select>&nbsp;&nbsp;');
				$('#search').selectpicker('render');
                $('#search').selectpicker('refresh');
				$("#conditionContent").html('');
			},
			dataType:'json'
		});		
	}else if (a == 'contract_check') {
		$("#searchContent").html('<select id="search" style="width:auto" name="search">'
							+'<option value="1">通过</option>'
							+'<option value="0">待审</option>'
							+'<option value="2">拒绝</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	}else if (a == 'sales_status') {
		var options = '';
		if(c == 'index'){
			options += '<option value="97">未出库</option>';
			options += '<option value="98">已出库</option>';
		}else if(c == 'salesreturn'){
			options += '<option value="99">未入库</option>';
			options += '<option value="100">已入库</option>';
		}else{
			options += '<option value="97">未出库</option>';
			options += '<option value="98">已出库</option>';
			options += '<option value="99">未入库</option>';
			options += '<option value="100">已入库</option>';
		}
		$("#searchContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	} else if(a=='all') {
		$("#conditionContent").html('<select class="form-control input-sm" id="condition" style="width:auto" name="condition" onchange="changeSearch()">'
							+'<option value="contains">'+CrmLang.CONTAINS+'</option>'
							+'<option value="is">'+CrmLang.IS+'</option>'
							+'<option value="start_with">'+CrmLang.START_WITH+'</option>'
							+'<option value="end_with">'+CrmLang.END_WITH+'</option>'
							+'<option value="is_empty">'+CrmLang.IS_EMPTY+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search"/>&nbsp;&nbsp;');
	} else if (a == 'task_status') {
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value='+CrmLang.NOT_STARTED+'>'+CrmLang.NOT_STARTED+'</option>'
							+'<option value='+CrmLang.RETARDATION+'>'+CrmLang.RETARDATION+'</option>'
							+'<option value='+CrmLang.UNDERWAY+'>'+CrmLang.UNDERWAY+'</option>'
							+'<option value='+CrmLang.COMPLETED+'>'+CrmLang.COMPLETED+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'task_priority') {
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value='+CrmLang.HIGH+'>'+CrmLang.HIGH+'</option>'
							+'<option value='+CrmLang.GENERAL+'>'+CrmLang.GENERAL+'</option>'
							+'<option value='+CrmLang.LOW+'>'+CrmLang.LOW+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	}else if (a == 'payables_status') {
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value="0">'+CrmLang.NOT_PAYING+'</option>'
							+'<option value="1">'+CrmLang.PART_OF_THE_PREPAID+'</option>'
							+'<option value="2">'+CrmLang.ACCOUNT_PAID+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	}else if (a == 'order_status') {
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value="0">'+CrmLang.NOT_CHECK+'</option>'
							+'<option value="1">'+CrmLang.HAS_THE_INVOICING+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'receivables_status') {
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value="0">'+CrmLang.NOT_RECEIVE_PAYMENT+'</option>'
							+'<option value="1">'+CrmLang.PART_OF_THE_RECEIVED+'</option>'
							+'<option value="2">'+CrmLang.HAS_BEEN_RECEIVING+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'customer_ownership') {	
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value='+CrmLang.HIGH+'>'+CrmLang.HIGH+'</option>'
							+'<option value='+CrmLang.NO+'>'+CrmLang.NO+'</option>'
							+'<option value='+CrmLang.STATE_OWNED_ENTERPRISES+'>'+CrmLang.STATE_OWNED_ENTERPRISES+'</option>'
							+'<option value='+CrmLang.FOREIGN_CAPITAL_ENTERPRISE+'>'+CrmLang.FOREIGN_CAPITAL_ENTERPRISE+'</option>'
							+'<option value='+CrmLang.PRIVATE_ENTERPRISE+'>'+CrmLang.PRIVATE_ENTERPRISE+'</option>'
							+'<option value='+CrmLang.COLLECTIVE_ENTERPRISE+'>'+CrmLang.COLLECTIVE_ENTERPRISE+'</option>'
							+'<option value='+CrmLang.JOINT_STOCK_COMPANY+'>'+CrmLang.JOINT_STOCK_COMPANY+'</option>'
							+'<option value='+CrmLang.JOINT_VENTURE+'>'+CrmLang.JOINT_VENTURE+'</option>'
							+'<option value='+CrmLang.SOLE_PROPRIETORSHIP_ENTERPRISE+'>'+CrmLang.SOLE_PROPRIETORSHIP_ENTERPRISE+'</option>'
							+'<option value='+CrmLang.OTHER+'>'+CrmLang.OTHER+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	} else if (a == 'customer_type') {	
		$("#conditionContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">'
							+'<option value='+CrmLang.ANALYSTS+'>'+CrmLang.ANALYSTS+'</option>'
							+'<option value='+CrmLang.COMPETITOR+'>'+CrmLang.COMPETITOR+'</option>'
							+'<option value='+CrmLang.CUSTOMER+'>'+CrmLang.CUSTOMER+'</option>'
							+'<option value='+CrmLang.INTEGRATORS+'>'+CrmLang.INTEGRATORS+'</option>'
							+'<option value='+CrmLang.INVESTORS+'>'+CrmLang.INVESTORS+'</option>'
							+'<option value='+CrmLang.PARTNERS+'>'+CrmLang.PARTNERS+'</option>'
							+'<option value='+CrmLang.PUBLISHERS+'>'+CrmLang.PUBLISHERS+'</option>'
							+'<option value='+CrmLang.TARGET+'>'+CrmLang.TARGET+'</option>'
							+'<option value='+CrmLang.SUPPLIER+'>'+CrmLang.SUPPLIER+'</option>'
							+'<option value='+CrmLang.OTHER+'>'+CrmLang.OTHER+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent").html('');
	}else if (a == 'box') {
		$.ajax({
			type:'get',
			url:'index.php?m=setting&a=boxfield&model='+c+'&field='+b,
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v+'">'+v+'</option>';
				});
				$("#searchContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
                if(data.info == 'checkbox'){
                    $("#conditionContent").html('<input type="hidden" name="condition" value="contains">');
                }else{
                    $("#conditionContent").html('');
                }
			},
			dataType:'json'
		});		
	} else if (a == 'address') {
        $("#conditionContent").html('<select class="form-control input-sm" id="condition" style="width:auto" name="condition">'
							+'<option value="contains">'+CrmLang.EXIST+'</option>'
							+'<option value="not_contain">'+CrmLang.ABSENT+'</option></select>&nbsp;&nbsp;');
        $("#searchContent").html('<select class="form-control input-sm" name="state" id="state" style="width:80px;margin-bottom:10px;"></select>&nbsp;'
							+'<select class="form-control input-sm" name="city" id="city" style="width:80px;margin-bottom:10px;"></select>&nbsp;'
							+'<select class="form-control input-sm" name="area" id="area" style="width:80px;margin-bottom:10px;"></select>&nbsp;'
							+'<input type="text" id="search" class="form-control input-sm" style="width:100px;margin-bottom:10px;" name="search" placeholder='+CrmLang.STREET_INFORMATION+' class="input-large">&nbsp;&nbsp;');
        new PCAS("state","city","area","","","");
	} else if (a == 'is_examine') {
		var is_search = $('#is_search').val();
		var options = '<option value="">全部</option>';
		var a = new Array('待审', '通过', '拒绝');
		for(var i=0;i<3;i++){
			if(is_search == ''){
				options += '<option value="'+i+'">'+a[i]+'</option>';
			}else if(is_search == i){
				options += '<option value="'+i+'" selected >'+a[i]+'</option>';
			}else{
				options += '<option value="'+i+'">'+a[i]+'</option>';
			}
		}
        $("#searchContent").html('<select class="form-control input-sm" name="search" id="search" style="width:auto">'+options+'</select>&nbsp;&nbsp;');
        $("#conditionContent").html('');
	} else if (a == 'is_read') {
		var options = '';
		options += '<option value="2">未读</option>';
		options += '<option value="1">已读</option>';
		$("#searchContent").html('<select class="form-control input-sm" id="search" style="width:auto" name="search">' + options + '</select>&nbsp;&nbsp;');
		$("#conditionContent").html('');
	}
}
function checkSearchForm() {
    search = $("#searchForm #search").val();
    field = $("#searchForm #field").val();
    if($("#searchForm #state").length>0){
        if($("#searchForm #state").val() == '' && search == ''){
            alert(CrmLang.SELECT_REGION);return false;
        }else{
        	return true;
        }
    }else{
        if (search == "") {
			if(field == 'is_examine'){
				return true; 
			}else{
				return true;
			}
        }else if(field == ""){
			 alert(CrmLang.SELECT_FILTER_CONDITION);return false;
		}
    }
    return true;
}
$(function(){
	$('form').find('input[type="submit"]').removeAttr("disabled");
	$(document).on('click', 'input[type="submit"]', function(){
		if($(this).parent().find('.form_submit').length > 0){
			$(this).parent().find('.form_submit').val($(this).attr('value'));
		}else{
			$(this).after('<input class="form_submit" type="hidden" name="'+$(this).attr('name')+'" value="'+$(this).attr('value')+'">');
		}
		return true;
	});
	$(document).on('submit', 'form', function(){
		$(this).find('input[type="submit"]').attr("disabled",true);
		return true;
	});
});

function changeSearch() {
	a = $("#field option:selected").attr('class');
	b = $("#condition option:selected").val();
	if(b == 'is_empty' || b == 'is_not_empty') {
		$("#searchContent").html('');
	} else {
		if(a == "date") {
			$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search" onclick="WdatePicker()"/>&nbsp;&nbsp;');	
		}  else if (a == "number" || a == "word" || a == "date") {
			$("#searchContent").html('<input id="search" type="text" class="form-control input-sm search-query" name="search"/>&nbsp;&nbsp;');
		}
	}
}
$(function(){
	if($('.table_thead_fixed thead').length>0){
		var b=30;
		var c=$(".table_thead_fixed").offset();
		var a=$(window).scrollTop();
		var default_w_width = $(window).width();
		var default_width = new Array();
		$.each($(".table_thead_fixed tbody tr:first td"),function(key,val){
			$('.table_thead_fixed thead tr:first th:eq('+key+')').width($(val).width());
			$(val).width($(val).width());
			default_width[key] = $(val).width();
		});
		if(a>c.top-b){
			$(".table_thead_fixed thead").addClass("fixed");
		}else{
			$(".table_thead_fixed thead").removeClass("fixed");
		};
		$(window).scroll(
			function(){
				var a=$(window).scrollTop();
				$.each($(".table_thead_fixed tbody tr:first td"),function(key,val){
					$('.table_thead_fixed thead tr:first th:eq('+key+')').width($(val).width());
					$(val).width($(val).width());
				});
				if(a>c.top-b){
					$(".table_thead_fixed thead").addClass("fixed");
				}else{
					$(".table_thead_fixed thead").removeClass("fixed");
				}
			}
		);
		$(window).resize(
			function(){
				$.each($(".table_thead_fixed tbody tr:first td"),function(key,val){
					if(default_w_width == $(window).width()){
						$(val).css({width:default_width[key]});
						$('.table_thead_fixed thead tr:first th:eq('+key+')').width(default_width[key]);
					}else{
						$(val).css({width:''});
						$('.table_thead_fixed thead tr:first th:eq('+key+')').width($(val).width());
					}
				});
			}
		)
	}
	
	/*删除提示*/
	$('.del_confirm').click(function(){
		if(confirm(CrmLang.CONFIRM_DELETE)){
			return true;
		}else{
			return false;
		}
	});
});

/*alert 显示优化*/
function alert_crm(msg){
	swal({
		title: "温馨提示",
		text: msg,
		type: "warning",
		// showCancelButton: true,
		confirmButtonColor: "#DD6B55",
		confirmButtonText: "确认",
		// cancelButtonText: "取消",
		closeOnConfirm: true});
	return false;
}

function confirm_crm(url,msg){
	msg = msg || '确认删除?';
	swal({
		title: msg,
		type: "warning",
		showCancelButton: true,
		confirmButtonColor: "#DD6B55",
		confirmButtonText: "确认",
		cancelButtonText: "取消",
		closeOnConfirm: false,
		closeOnCancel:  true
	}, function(isConfirm){
		if (isConfirm) {
			window.location.href = url;
		} else {
			return false;
		} 
	});
	return false;

}
//下载 方法
function filedown(obj){
	var file_path = $(obj).attr('file');
	var file_name = $(obj).attr('filename');
	if(file_path && file_name){
		var url = "index.php?m=file&a=filedownload"+"&file_path="+file_path+"&file_name="+file_name;
		window.location.href = url;
	}else{
		alert_crm('该文件不存在，请选择其他文件！');
	}
}

//获取URL参数
function getUrlParam(name){
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return  unescape(r[2]); return null;
}

//jquery控制input只能输入数字和两位小数
function num_input(obj){
	obj.value = obj.value.replace(/[^\d.]/g,""); //清除"数字"和"."以外的字符
	obj.value = obj.value.replace(/^\./g,""); //验证第一个字符是数字
	obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个, 清除多余的
	obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
	obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3'); //只能输入两个小数
}

//默认两位小数
function bu(txtObj) {
	txtObj.value = Number(txtObj.value).toFixed(2);
}

//附件查看
$(document).on('click','.file_view',function(){
	var path = $(this).attr('rel');
	var title = $(this).attr('title');
	layer.open({
		type: 2,
		title: title,
		shadeClose: true,
		shade: false,
		maxmin: true, //开启最大化最小化按钮
		area: ['800px', '600px'],
		content: path
	});
});

// yyyy-MM-dd h:m:s
Date.prototype.format = function(format) {
    var date = {
        "M+": this.getMonth() + 1,
        "d+": this.getDate(),
        "h+": this.getHours(),
        "m+": this.getMinutes(),
        "s+": this.getSeconds(),
        "q+": Math.floor((this.getMonth() + 3) / 3),
        "S+": this.getMilliseconds()
    };
    if (/(y+)/i.test(format)) {
        format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    for (var k in date) {
        if (new RegExp("(" + k + ")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1
                ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
        }
    }
    return format;
};

function timestampToFormat (timestamp, format)
{
    var newData     =   new Date();
    newData.setTime(timestamp*1000);
    return newData.format(format);
}


// 获取浏览器参数
function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]); return null;
  }