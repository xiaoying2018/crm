function changeCondition($i){
	var a = $("#field_"+$i+" option:selected").attr('class');
	var b = $("#field_"+$i+" option:selected").val();
	var c = $("#field_"+$i+" option:selected").attr('rel');
	var name = b+'['+'condition'+']';
	$('#condition_'+$i+'').attr("name",name);
	var search = b+'['+'value'+']';
	
	if(a == 'number') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="condition_'+$i+'" style="width:auto" onchange="changeSearch()" name="'+name+'">'
							+'<option value="gt">  '+CrmLang.GT+'  </option>'
							+'<option value="lt">  '+CrmLang.LT+'  </option>'
							+'<option value="eq">  '+CrmLang.EQ+'  </option>'
							+'<option value="neq">  '+CrmLang.NEQ+'  </option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'"/>');
	} else if ((a == 'word') || (a == 'text') || (a == 'textarea') || (a == 'editor') || (a == 'mobile') || (a == 'email')) {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="condition_'+$i+'" style="width:auto" onchange="changeSearch()" name="'+name+'">'
							+'<option value="contains">'+CrmLang.CONTAINS+'</option>'
							+'<option value="not_contain">'+CrmLang.NOT_CONTAIN+'</option>'
							+'<option value="is">'+CrmLang.IS+'</option>'
							+'<option value="isnot">'+CrmLang.ISNOT+'</option>'							
							+'<option value="start_with">'+CrmLang.START_WITH+'</option>'
							+'<option value="end_with">'+CrmLang.END_WITH+'</option>'
							+'<option value="is_empty">'+CrmLang.IS_EMPTY+'</option>'
							+'<option value="is_not_empty">'+CrmLang.IS_NOT_EMPTY+'</option></select>&nbsp;&nbsp;');
		$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'"/>');
	} else if (a == 'date' || a== 'datetime') {
		$("#conditionContent_"+$i+"").html("");
		//$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="condition_'+$i+'" style="width:auto" onchange="changeSearch()" name="'+name+'">'
		//					+'<option value="tgt">  '+CrmLang.BEHIND+'  </option>'
		//					+'<option value="lt">  '+CrmLang.BEFORE+'  </option>'
		//					+'<option value="between">  '+CrmLang.EXIST+'  </option>'
		//					+'<option value="nbetween">  '+CrmLang.ABSENT+'  </option>'
		//					+'</select>&nbsp;&nbsp;');
		$("#searchContent_"+$i+"").html('<input id="start_'+$i+'" type="text" class="form-control input-sm search-query" name="'+b+'['+'start'+']'+'" onclick="WdatePicker()"/> 至 <input id="end_'+$i+'" type="text" class="form-control input-sm search-query" name="'+b+'['+'end'+']'+'" onclick="WdatePicker()"/>');
	} else if (a == 'bool') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="condition_'+$i+'" style="width:auto"  onchange="changeSearch()" name="'+name+'">'
							+'<option value="1">'+CrmLang.IS+'</option>'
							+'<option value="0">'+CrmLang.ISNOT+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'"/>');
	} else if (a == 'sex') {
		$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value="1">'+CrmLang.MAN+'</option>'
							+'<option value="0">'+CrmLang.WOMAN+'</option>'
							+'</select>');
		$("#conditionContent_"+$i+"").html('');
	}else if (a == 'contract_check') {
		$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value="1">通过</option>'
							+'<option value="0">待审</option>'
							+'<option value="2">拒绝</option>'
							+'<option value="3">审批中</option>'
							+'</select>&nbsp;&nbsp;');
		$("#conditionContent_"+$i+"").html('');
	} else if (a == 'role') {
		var module = getUrlParam("m");
		var action = getUrlParam("a");
		var t = '';
		if(getUrlParam("t") != '' || getUrlParam("t") !== null){
			t = getUrlParam("t");
		}
		$.ajax({
			type:'get',
			url:'index.php?m=user&a=getrolelist&module='+module+'&action='+action+'&t='+t,
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.role_id+'">'+v.user_name+' ['+v.department_name+'-'+v.role_name+'] </option>';
				});
				//$("#searchContent_"+$i+"").html('<select id="search_'+$i+'" class="form-control input-sm" style="width:auto;max-width: 200px;" name="'+search+'">' + options + '</select>');
				$("#searchContent_"+$i+"").html('<select class="selectpicker show-tick form-control input-sm" data-live-search="true" id="search_'+$i+'" name="'+search+'" style="width:auto">' + options + '</select>');
				$('#search_'+$i).selectpicker('render');
                $('#search_'+$i).selectpicker('refresh');
				$("#conditionContent_"+$i+"").html('');
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

				$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
				$("#conditionContent_"+$i+"").html('');
			},
			dataType:'json'
		});		
	}else if (a == 'customer') {
		$("#conditionContent_"+$i+"").html('');
		$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'"/>');
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
				$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
				$("#conditionContent_"+$i+"").html('');
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
				$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
				$("#conditionContent_"+$i+"").html('');
			},
			dataType:'json'
		});		
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
		$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
		$("#conditionContent_"+$i+"").html('');
	} else if(a=='all') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="condition_'+$i+'" style="width:auto"  onchange="changeSearch()">'
							+'<option value="contains">'+CrmLang.CONTAINS+'</option>'
							+'<option value="is">'+CrmLang.IS+'</option>'
							+'<option value="start_with">'+CrmLang.START_WITH+'</option>'
							+'<option value="end_with">'+CrmLang.END_WITH+'</option>'
							+'<option value="is_empty">'+CrmLang.IS_EMPTY+'</option>'
							+'</select>&nbsp;&nbsp;');
		$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'"/>');
	} else if (a == 'task_status') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value='+CrmLang.NOT_STARTED+'>'+CrmLang.NOT_STARTED+'</option>'
							+'<option value='+CrmLang.RETARDATION+'>'+CrmLang.RETARDATION+'</option>'
							+'<option value='+CrmLang.UNDERWAY+'>'+CrmLang.UNDERWAY+'</option>'
							+'<option value='+CrmLang.COMPLETED+'>'+CrmLang.COMPLETED+'</option>'
							+'</select>');
		$("#searchContent_"+$i+"").html('');
	} else if (a == 'task_priority') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value='+CrmLang.HIGH+'>'+CrmLang.HIGH+'</option>'
							+'<option value='+CrmLang.GENERAL+'>'+CrmLang.GENERAL+'</option>'
							+'<option value='+CrmLang.LOW+'>'+CrmLang.LOW+'</option>'
							+'</select>');
		$("#searchContent_"+$i+"").html('');
	}else if (a == 'payables_status') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value="0">'+CrmLang.NOT_PAYING+'</option>'
							+'<option value="1">'+CrmLang.PART_OF_THE_PREPAID+'</option>'
							+'<option value="2">'+CrmLang.ACCOUNT_PAID+'</option>'
							+'</select>');
		$("#searchContent_"+$i+"").html('');
	}else if (a == 'order_status') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value="0">待审核</option>'
							+'<option value="1">已通过</option>'
							+'<option value="2">已拒绝</option>'
							+'</select>');
		$("#searchContent_"+$i+"").html('');
	} else if (a == 'receivables_status') {
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
							+'<option value="0">'+CrmLang.NOT_RECEIVE_PAYMENT+'</option>'
							+'<option value="1">'+CrmLang.PART_OF_THE_RECEIVED+'</option>'
							+'<option value="2">'+CrmLang.HAS_BEEN_RECEIVING+'</option>'
							+'</select>');
		$("#searchContent_"+$i+"").html('');
	} else if (a == 'customer_ownership') {	
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
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
							+'</select>');
		$("#searchContent_"+$i+"").html('');
	} else if (a == 'customer_type') {	
		$("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">'
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
							+'</select>');
		$("#searchContent_"+$i+"").html('');
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
				$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
                if(data.info == 'checkbox'){
                    $("#conditionContent_"+$i+"").html('<input type="hidden"  value="contains">');
                }else{
                    $("#conditionContent_"+$i+"").html('');
                }
			},
			dataType:'json'
		});		
	} else if (a == 'address') {
        $("#conditionContent_"+$i+"").html('<select class="form-control input-sm" id="condition_'+$i+'" style="width:auto;margin-bottom:10px;" >'
							+'<option value="contains">'+CrmLang.EXIST+'</option>'
							+'<option value="not_contain">'+CrmLang.ABSENT+'</option></select>');
        $("#searchContent_"+$i+"").html('<select class="form-control input-sm" name="'+b+'[state]" id="'+b+'_state" style="width:135px;margin-bottom:1px;"></select>&nbsp;&nbsp;'
							+'<select class="form-control input-sm" name="'+b+'[city]" id="'+b+'_city" style="width:110px;margin-bottom:1px;"></select>&nbsp;&nbsp;'
							+'<select class="form-control input-sm" name="'+b+'[area]" id="'+b+'_area" style="width:110px;margin-bottom:1px;"></select>&nbsp;&nbsp;'
							+'<input type="text" id="search_'+$i+'" class="form-control input-sm" style="width:130px;" name="'+search+'" placeholder='+CrmLang.STREET_INFORMATION+' class="input-large">');
        new PCAS(b+"[state]",b+"[city]",b+"[area]","","","");
	} else if (a == 'is_examine') {
		var is_search = $('#is_search').val();
		var options = '<option value="">全部</option>';
		var a = new Array('待审批','审批中','已通过','已拒绝');
		for(var i=0;i<4;i++){
			if(is_search == ''){
				options += '<option value="'+i+'">'+a[i]+'</option>';
			}else if(is_search == i){
				options += '<option value="'+i+'" selected >'+a[i]+'</option>';
			}else{
				options += '<option value="'+i+'">'+a[i]+'</option>';
			}
		}
        $("#searchContent_"+$i+"").html('<select class="form-control input-sm" name="'+search+'" id="search_'+$i+'" style="width:auto">'+options+'</select>');
		
        $("#conditionContent_"+$i+"").html('');
	} else if (a == 'is_read') {
		var options = '';
		options += '<option value="2">未读</option>';
		options += '<option value="1">已读</option>';
		$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
		$("#conditionContent_"+$i+"").html('');
	} else if (a == 'examine_type') {
		var is_search = $('#is_search').val();
		var options = '<option value="all">全部</option>';
		var a = new Array('','普通审批', '请假审批', '普通报销', '差旅报销', '出差申请', '借款申请');
		for(var i=1;i<7;i++){
			if(is_search == ''){
				options += '<option value="'+i+'">'+a[i]+'</option>';
			}else if(is_search == i){
				options += '<option value="'+i+'" selected >'+a[i]+'</option>';
			}else{
				options += '<option value="'+i+'">'+a[i]+'</option>';
			}
		}
        $("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
		$("#conditionContent_"+$i+"").html('');
    } else if (a == 'product_category') {
		$.ajax({
			type:'get',
			url:'index.php?m=product&a=categorylist',
			async:false,
			success:function(data){
				options = '';
				$.each(data.data, function(k, v){
					options += '<option value="'+v.category_id+'">'+v.name+'</option>';
				});
				$("#searchContent_"+$i+"").html('<select class="form-control input-sm" id="search_'+$i+'" style="width:auto" name="'+search+'">' + options + '</select>');
                if(data.info == 'checkbox'){
                    $("#conditionContent_"+$i+"").html('<input type="hidden"  value="contains">');
                }else{
                    $("#conditionContent_"+$i+"").html('');
                }
			},
			dataType:'json'
		});		
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
		$("#searchContent_"+$i+"").html('');
	} else {
		if(a == "date") {
			$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'" onclick="WdatePicker()"/>');	
		}  else if (a == "number" || a == "word" || a == "date") {
			$("#searchContent_"+$i+"").html('<input id="search_'+$i+'" type="text" class="form-control input-sm search-query" name="'+search+'"/>');
		}
	}
}
//获取URL参数
function getUrlParam(name){
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return  unescape(r[2]); return null;
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