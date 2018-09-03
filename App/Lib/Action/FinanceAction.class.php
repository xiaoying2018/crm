<?php 
/**
*财务模块
*
**/
class FinanceAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('listdialog', 'revert', 'adddialog', 'analytics', 'checkout','getmonthlyreceive','getyearreceivecomparison','getreceivablesmoney','getpayablesmoney','receivablesplan','advance_search','getcurrentstatus')
		);
		$this->type = $_REQUEST['t'] ? trim($_REQUEST['t']) : 'receivables';
		if(!in_array($this->type,array('receivables','payables','receivingorder','paymentorder','receivablesplan'))){
			alert('error',L('PARAMETER_ERROR'),U('index/index'));
		}
		B('Authenticate', $action);
		$a = ACTION_NAME.'_'.$this->type;
		$this->_permissionRes = getPerByAction(MODULE_NAME,$a);
	}
	
	/**
	*应收款、应付款、收款单、付款单列表页面（默认页面）
	*
	**/
	public function index(){

		$where = array();
		$params = array();
		$order = "";
		
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$by = trim($_GET['by']) ? trim($_GET['by']) : 'me';
		$this->by = $by;
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type);

		switch ($by) {
			case 'create' : $where[$this->type . '.creator_role_id'] = session('role_id'); break;
			case 'sub' : $where[$this->type . '.owner_role_id'] = array('in',implode(',', $below_ids)); break;
			case 'subcreate' : $where[$this->type . '.creator_role_id'] = array('in',implode(',', $below_ids)); break;
			case 'none' : $where[$this->type . '.status'] = array('eq',0); break;
			case 'part' : $where[$this->type . '.status'] = array('eq',1); break;
			// case 'all' : $where[$this->type . '.status'] = array('eq',2); break;
			case 'today' : 
				$where[$this->type . '.pay_time'] = array('egt',strtotime(date('Y-m-d', time())));
				$where[$this->type . '.status'] = array('neq',2);
				break;
			case 'week' : 
				$where[$this->type . '.pay_time'] = array('gt',(strtotime(date('Y-m-d')) - (date('N', time()) - 1) * 86400));
				$where[$this->type . '.status'] = array('neq',2);
				break;
			case 'month' : 
				$where[$this->type . '.pay_time'] = array('gt',strtotime(date('Y-m-01', time())));
				$where[$this->type . '.status'] = array('neq',2);
				break;
			case 'deleted' : $where[$this->type . '.is_deleted'] = 1; break;
			case 'add' : $order = $this->type . '.create_time desc'; break;
			case 'update' : $order = $this->type . '.update_time desc'; break;
			case 'me' : $where[$this->type . '.owner_role_id'] = session('role_id'); break;
			default :
				$where[$this->type . '.owner_role_id'] = array('in',implode(',', $this->_permissionRes));
				break;
		}
		if (!isset($where[$this->type . '.owner_role_id']) && $by != 'deleted') {
			$where[$this->type . '.owner_role_id'] = array('in', $this->_permissionRes);
		}
		if (!isset($where[$this->type . '.is_deleted'])) {
			$where[$this->type . '.is_deleted'] = 0;
		}
		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']) == 'all' ? $this->type . '.name|'.$this->type .'.description' : $this->type .'.'. $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	($this->type . '.create_time' == $field || $this->type . '.update_time' == $field || $this->type . '.pay_time' == $field ) {
				$search = is_numeric($search)?$search:strtotime($search);
			}
			
			if($field =="receivables.customer_id"){
				$c_where['name'] = array('like','%'.$search.'%');
				$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true);
				$where[$field] = array('in',$customer_ids);
			}elseif($field !='t'){
				switch ($_REQUEST['condition']) {
					case "is" : $where[$field] = array('eq',$search);break;
					case "isnot" :  $where[$field] = array('neq',$search);break;
					case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
					case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
					case "start_with" :  $where[$field] = array('like',$search.'%');break;
					case "end_with" :  $where[$field] = array('like','%'.$search);break;
					case "is_empty" :  $where[$field] = array('eq','');break;
					case "is_not_empty" :  $where[$field] = array('neq','');break;
					case "gt" :  $where[$field] = array('gt',$search);break;
					case "egt" :  $where[$field] = array('egt',$search);break;
					case "lt" :  $where[$field] = array('lt',$search);break;
					case "elt" :  $where[$field] = array('elt',$search);break;
					case "eq" : $where[$field] = array('eq',$search);break;
					case "neq" : $where[$field] = array('neq',$search);break;
					case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
					case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
					case "tgt" :  $where[$field] = array('gt',$search+86399);break;
					default : $where[$field] = array('eq',$search);
				}
			}
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
			//过滤不在权限范围内的role_id
			if(trim($_REQUEST['field']) == 'owner_role_id'){
				if(!in_array(trim($search),$below_ids)){
					$where['owner_role_id'] = array('in',$below_ids);
				}
			}
		}
		$order = empty($order) ? $this->type . '.create_time desc' : $order;
		switch ($this->type) {
			case 'receivables' :
				//应收款
				$d_receivables = D('ReceivablesView');
				//应收款状态
				$where['receivables.status'] = array('lt',3);
				//高级搜索
				if(!$_GET['field']){
					$fields_search = array();
					foreach($_GET as $kd => $vd){
						$no_field_array = array('act','content','p','condition','listrows','daochu','this_page','current_page','export_limit','desc_order','asc_order','selectexcelxport','by','t','order_field','type','daochu','r_status','od');
						if(!in_array($kd,$no_field_array)){
							if(in_array($kd,array('create_time','update_time','pay_time'))){
								$where[$kd] = field($vd['value'], $vd['condition']);
								$fields_search[$kd]['field'] = $kd;
								$fields_search[$kd]['start'] = $vd['start'];
								$fields_search[$kd]['end'] = $vd['end'];
								$fields_search[$kd]['form_type'] = 'datetime';

								//时间段查询
								if ($vd['start'] && $vd['end']) {
									$where[$kd] = array('between',array(strtotime($vd['start']),strtotime($vd['end'])+86399));
								} elseif ($vd['start']) {
									$where[$kd] = array('egt',strtotime($vd['start']));
								} else {
									$where[$kd] = array('elt',strtotime($vd['end'])+86399);
								}
							}elseif($kd =='customer_name'){
								if(!empty($vd['value'])){
									$c_where['name'] = array('like','%'.$vd['value'].'%');
									$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true); 
									if($customer_ids){
										$where['customer_id'] = array('in',$customer_ids);
									}else{
										$where['customer_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['condition'] = $vd['condition'];
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif($kd =='contract_name'){
								if(!empty($vd['value'])){
									$c_where['number'] = array('like','%'.$vd['value'].'%');
									$contract_ids = M('Contract')->where($c_where)->getField('contract_id',true);
									if($contract_ids){
										$where['contract_id'] = array('in',$contract_ids);
									}else{
										$where['contract_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['condition'] = $vd['condition'];
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
								if(!empty($vd)){
									if ($kd == 'status' && $vd['value'] == 3) {
										$vd_value = 0;
									} else {
										$vd_value = $vd['value'];
									}
									$where[$this->type .'.'.$kd] = $vd['value'];
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['value'] = $vd_value;
								}
							}else{
								if(is_array($vd)) {
									if($kd =='price'){
										$fields_search[$kd]['form_type'] = 'number';
									}
									if(!empty($vd['value'])){
										$where[$kd] = field($vd['value'], $vd['condition']);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['condition'] = $vd['condition'];
										$fields_search[$kd]['value'] = $vd['value'];
									}
								}else{
									if(!empty($vd)){
										$where[$kd] = field($vd);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['value'] = $vd;
									} 
								}
							}
						}
						if($kd != 'search' && $kd != 'type'){
							if(is_array($vd)){
								foreach ($vd as $key => $value) {
									$params[] = $kd . '[' . $key . ']=' . $value;
								}
							}else{
								$params[] = $kd . '=' . $vd; 
							}
						} 
					}
					//权限
					if (!isset($where[$this->type . '.owner_role_id']) && $by != 'deleted') {
						$where[$this->type . '.owner_role_id'] = array('in', $this->_permissionRes);
					}
					//过滤不在权限范围内的role_id
					if(isset($where[$this->type . '.owner_role_id'])){
						if(!empty($where[$this->type . '.owner_role_id']['1']) && !in_array(intval($where[$this->type . '.owner_role_id']['1']),$below_ids)){
							$where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
						}
					}
				}
				
				//应收款状态
				if ($_GET['status']['value'] == 3) {
					$where['receivables.status'] = array('eq',0);
				}
				//列表搜索，有状态并且有普通搜索时，处理高级筛选数据
				if ($_GET['status']['value']) {
					$params[] = "status[value]=".$_GET['status']['value'];
					$fields_search['status']['field'] = 'status';
					$fields_search['status']['value'] = intval($_GET['status']['value']) == 3 ? 0 : intval($_GET['status']['value']);
				}
				$this->fields_search = $fields_search;

				//应收款提醒
				if($_GET['r_status'] == 1){
					$receivables_time = M('Config')->where('name="receivables_time"')->getField('value');
					$f_outdate = empty($receivables_time) ? 0 : time()-86400*$receivables_time;
					$where['pay_time'] = array('elt',time()+$f_outdate);
					$where['status'] = array('lt',2);
				}

				if($_GET['listrows']){
					$listrows = intval($_GET['listrows']);
					$params[] = "listrows=" . intval($_GET['listrows']);
				}else{
					$listrows = 15;
					$params[] = "listrows=15";
				}
				$count = $d_receivables->where($where)->count();
				$p_num = ceil($count/$listrows);
				if($p_num<$p){
					$p = $p_num;
				}
				if(!empty($_GET['od'])){
				    if($_GET['od']==2){
                       $order =  $this->type . '.pay_time desc';
                    }else if($_GET['od']==3){
                        $order =  $this->type . '.pay_time asc';
                    }
                }
//                echo $order;
//				die;
				$list = $d_receivables->where($where)->order($order)->page($p.','.$listrows)->select();
				$receivables_ids = $d_receivables->where($where)->getField('receivables_id',true);

				$m_business = M('Business');
				$m_receivingorder = M('Receivingorder');
				$m_contract = M('Contract');
				$m_user = M('User');
				foreach($list as $k=>$v){
					$list[$k]['owner'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->field('role_id','full_name')->find();
					//付款单
					$receivingorder_money = 0;
					$receivingorder_money = $m_receivingorder->where('is_deleted <> 1 and receivables_id = %d and status = 1', $v['receivables_id'])->sum('money');
					$un_payable = '0.00';
					if (($v['price']-$receivingorder_money) > 0) {
						$un_payable = $v['price']-$receivingorder_money;
					}
					$list[$k]['un_payable'] = $un_payable;
					//当前收款进度
					$schedule = 0;
					if($receivingorder_money){
						if($v['price'] == 0 || $v['price'] == ''){
							$schedule = 100;
						}else{
							$schedule = round(($receivingorder_money/$v['price'])*100,2);
						}
					}
					$list[$k]['schedule'] = $schedule;
					$list[$k]['contract_number'] = $m_contract->where('contract_id =%d',$v['contract_id'])->getField('number');
				}
				//应收总计
				$sum_money = 0.00;
				$sum_money = $d_receivables->where($where)->sum('receivables.price');
				//已收款总计
				$all_ysmoney = 0.00;
				$all_ysmoney = $m_receivingorder->where(array('is_deleted'=>array('neq',1),'receivables_id'=>array('in',$receivables_ids),'status'=>1))->sum('money');
				//未收总计
				$all_unmoney = number_format($sum_money - $all_ysmoney,2);
				$all_ysmoney = number_format($all_ysmoney,2);
				$sum_money = number_format($sum_money,2);
				$money_arr = array('all_unmoney'=>$all_unmoney,'all_ysmoney'=>$all_ysmoney,'sum_money'=>$sum_money);

				import("@.ORG.Page");
				$Page = new Page($count,$listrows);
				$params[] = 'by=' . trim($_GET['by']);
				$params[] = 't=' . $this->type;
				if ($_GET['desc_order']) {
					$params[] = "desc_order=" . trim($_GET['desc_order']);
				} elseif ($_GET['asc_order']){
					$params[] = "asc_order=" . trim($_GET['asc_order']);
				}

				$this->parameter = implode('&', $params);
				//by_parameter(特殊处理)
				$this->by_parameter = str_replace('by='.$_GET['by'], '', implode('&', $params));
				//status_parameter(特殊处理)
				$this->status_parameter = str_replace('status[value]='.$_GET['status']['value'], '', implode('&', $params));	
				$Page->parameter = implode('&', $params);
				$show = $Page->show();
				$this->alert = parseAlert();
				$this->listrows = $listrows;
				$this->assign('page',$show);
				$this->assign('count',$count);
				$this->assign('money_arr',$money_arr);
				$this->assign('list',$list);
				$this->display('receivables');
				break;
			case 'payables' :
				//应付款
				$d_payables = D('PayablesView');
				$m_paymentorder = M('Paymentorder');
				if($_GET['listrows']){
					$listrows = intval($_GET['listrows']);
					$params[] = "listrows=" . intval($_GET['listrows']);
				}else{
					$listrows = 15;
					$params[] = "listrows=15";
				}
				//应付款状态
				$where['payables.status'] = array('lt',3);
				//高级搜索
				if(!$_GET['field']){
					$fields_search = array();
					foreach($_GET as $kd => $vd){
						$no_field_array = array('act','content','p','condition','listrows','daochu','this_page','current_page','export_limit','desc_order','asc_order','selectexcelxport','by','t','order_field','type','daochu','r_status');
						if(!in_array($kd,$no_field_array)){
							if(in_array($kd,array('create_time','update_time','pay_time'))){
								$where[$kd] = field($vd['value'], $vd['condition']);
								$fields_search[$kd]['field'] = $kd;
								$fields_search[$kd]['start'] = $vd['start'];
								$fields_search[$kd]['end'] = $vd['end'];
								$fields_search[$kd]['form_type'] = 'datetime';

								//时间段查询
								if ($vd['start'] && $vd['end']) {
									$where[$kd] = array('between',array(strtotime($vd['start']),strtotime($vd['end'])+86399));
								} elseif ($vd['start']) {
									$where[$kd] = array('egt',strtotime($vd['start']));
								} else {
									$where[$kd] = array('elt',strtotime($vd['end'])+86399);
								}
							}elseif($kd =='customer_name'){
								if(!empty($vd['value'])){
									$c_where['name'] = array('like','%'.$vd['value'].'%');
									$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true); 
									if($customer_ids){
										$where['customer_id'] = array('in',$customer_ids);
									}else{
										$where['customer_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['condition'] = $vd['condition'];
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif($kd =='contract_name'){
								if(!empty($vd['value'])){
									$c_where['number'] = array('like','%'.$vd['value'].'%');
									$contract_ids = M('Contract')->where($c_where)->getField('contract_id',true);
									if($contract_ids){
										$where['contract_id'] = array('in',$contract_ids);
									}else{
										$where['contract_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['condition'] = $vd['condition'];
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
								if(!empty($vd)){
									if ($kd == 'status' && $vd['value'] == 3) {
										$vd_value = 0;
									} else {
										$vd_value = $vd['value'];
									}
									$where[$this->type .'.'.$kd] = $vd['value'];
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['value'] = $vd_value;
								}
							}else{
								if(is_array($vd)) {
									if($kd =='price'){
										$fields_search[$kd]['form_type'] = 'number';
									}
									if(!empty($vd['value'])){
										$where[$kd] = field($vd['value'], $vd['condition']);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['condition'] = $vd['condition'];
										$fields_search[$kd]['value'] = $vd['value'];
									}
								}else{
									if(!empty($vd)){
										$where[$kd] = field($vd);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['value'] = $vd;
									} 
								}
							}
						}
						if($kd != 'search' && $kd != 'type'){
							if(is_array($vd)){
								foreach ($vd as $key => $value) {
									$params[] = $kd . '[' . $key . ']=' . $value;
								}
							}else{
								$params[] = $kd . '=' . $vd; 
							}
						} 
					}
					//权限
					if (!isset($where[$this->type . '.owner_role_id']) && $by != 'deleted') {
						$where[$this->type . '.owner_role_id'] = array('in', $this->_permissionRes);
					}
					//过滤不在权限范围内的role_id
					if(isset($where[$this->type . '.owner_role_id'])){
						if(!empty($where[$this->type . '.owner_role_id']['1']) && !in_array(intval($where[$this->type . '.owner_role_id']['1']),$below_ids)){
							$where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
						}
					}
				}
				
				//应付款状态
				if ($_GET['status']['value'] == 3) {
					$where['payables.status'] = array('eq',0);
				}
				//列表搜索，有状态并且有普通搜索时，处理高级筛选数据
				if ($_GET['status']['value']) {
					$params[] = "status[value]=".$_GET['status']['value'];
					$fields_search['status']['field'] = 'status';
					$fields_search['status']['value'] = intval($_GET['status']['value']) == 3 ? 0 : intval($_GET['status']['value']);
				}
				$this->fields_search = $fields_search;

				$count = $d_payables->where($where)->count();	
				$p_num = ceil($count/$listrows);
				if($p_num<$p){
					$p = $p_num;
				}
				$list = $d_payables->where($where)->order($order)->page($p.','.$listrows)->select();
				//付款单总计
				$sum_money = $d_payables->where($where)->sum('payables.price');
				$m_user = M('User');
				foreach($list as $k=>$v){
					$list[$k]['owner'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->field('role_id','full_name')->find();
					//已付款金额
					$paymentorder_sum_money = 0;
					$paymentorder_sum_money = $m_paymentorder->where(array('payables_id'=>$v['payables_id'],'status'=>1,'is_deleted'=>0))->sum('money');
					//未付款金额
					$list[$k]['sub_money'] = number_format($v['price']-$paymentorder_sum_money,2);
					//当前收款进度
					$schedule = 0;
					if($paymentorder_sum_money){
						if($v['price'] == 0 || $v['price'] == ''){
							$schedule = 100;
						}else{
							$schedule = round(($paymentorder_sum_money/$v['price'])*100,2);
						}
					}
					$list[$k]['schedule'] = $schedule;
					$list[$k]['price'] = number_format($v['price'],2);
				}
				$sum_money = number_format($sum_money,2);
				$money_arr = array('sum_money'=>$sum_money);

				import("@.ORG.Page");
				$Page = new Page($count,$listrows);
				$params[] = 'by=' . trim($_GET['by']);
				$params[] = 't=' . $this->type;
				if ($_GET['desc_order']) {
					$params[] = "desc_order=" . trim($_GET['desc_order']); 
				} elseif($_GET['asc_order']){
					$params[] = "asc_order=" . trim($_GET['asc_order']);
				}
				$this->parameter = implode('&', $params);
				$Page->parameter = implode('&', $params);
				//by_parameter(特殊处理)
				$this->by_parameter = str_replace('by='.$_GET['by'], '', implode('&', $params));
				//status_parameter(特殊处理)
				$this->status_parameter = str_replace('status[value]='.$_GET['status']['value'], '', implode('&', $params));

				$show = $Page->show();
				$this->listrows = $listrows;
				$this->alert = parseAlert();
				$this->assign('page',$show);
				$this->assign('money_arr',$money_arr);
				$this->assign('list',$list);
				$this->display('payables');
				break;
			case 'receivingorder' :
				//回款单
				$d_receivingorder = D('ReceivingorderView');
				$m_receivingorder = M('Receivingorder');
				$m_customer = M('Customer');
				$m_contract = M('Contract');
				$m_receivables = M('Receivables');
				$m_user = M('User');
				//高级搜索
				if(!$_GET['field']){
					$fields_search = array();
					foreach($_GET as $kd => $vd){
						$no_field_array = array('act','content','p','condition','listrows','daochu','this_page','current_page','export_limit','desc_order','asc_order','selectexcelxport','by','t','order_field','type','daochu','od');
						if(!in_array($kd,$no_field_array)){
							if(in_array($kd,array('create_time','update_time','pay_time'))){
								$where[$kd] = field($vd['value'], $vd['condition']);
								$fields_search[$kd]['field'] = $kd;
								$fields_search[$kd]['start'] = $vd['start'];
								$fields_search[$kd]['end'] = $vd['end'];
								$fields_search[$kd]['form_type'] = 'datetime';

								//时间段查询
								if ($vd['start'] && $vd['end']) {
									$where[$kd] = array('between',array(strtotime($vd['start']),strtotime($vd['end'])+86399));
								} elseif ($vd['start']) {
									$where[$kd] = array('egt',strtotime($vd['start']));
								} else {
									$where[$kd] = array('elt',strtotime($vd['end'])+86399);
								}
							}elseif($kd =='customer_name'){
								if(!empty($vd['value'])){
									$c_where['name'] = array('like','%'.$vd['value'].'%');
									$customer_ids = M('customer')->where($c_where)->getField('customer_id',true); 
									if($customer_ids){
										$where['customer_id'] = array('in',$customer_ids);
									}else{
										$where['customer_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['condition'] = $vd['condition'];
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
								if(!empty($vd)){
									$where[$this->type .'.'.$kd] = $vd['value'];
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif($kd =='code'){
								if(!empty($vd['value'])){
									$b_where['code'] = array('like','%'.$vd['value'].'%');
									$business_ids = M('business')->where($b_where)->getField('business_id',true); 
									if($business_ids){
										$where['business_id'] = array('in',$business_ids);
									}else{
										$where['business_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}else{
								if(is_array($vd)) {
									if($kd =='money'){
										$fields_search[$kd]['form_type'] = 'number';
									}
									if(!empty($vd['value'])){
										$where[$kd] = field($vd['value'], $vd['condition']);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['condition'] = $vd['condition'];
										$fields_search[$kd]['value'] = $vd['value'];
									}
								}else{
									if(!empty($vd)){
										$where[$kd] = field($vd);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['value'] = $vd;
									} 
								}
							}
						}
						if($kd != 'search'){
							if(is_array($vd)){
								foreach ($vd as $key => $value) {
									$params[] = $kd . '[' . $key . ']=' . $value;
								}
							}else{
								$params[] = $kd . '=' . $vd; 
							} 
						} 
					} 
				}
                
				//权限
				if (!isset($where[$this->type . '.owner_role_id']) && $by != 'deleted') {
					$where[$this->type . '.owner_role_id'] = array('in', $this->_permissionRes);
				}

				// 过滤不在权限范围内的role_id
				if(isset($where[$this->type . '.owner_role_id'])){
					if(!empty($where[$this->type . '.owner_role_id']) && !in_array(intval($where[$this->type . '.owner_role_id']),$below_ids)){
						$where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
					}
				}

                // 校区筛选 获取组织架构中的校区
                $jiagou = M('RoleDepartment')->where(['department_id'=>['IN','200,300,400,500']])->select();
                $this->jiagou = $jiagou;
                
                unset($where['xq']);
                if ($xq = $_GET['xq'])
                {
                    $this->xq = $xq;
                    // 获取校区负责人的position_id
                    $position = M('Position')->where(['department_id'=>['eq',$xq],'parent_id'=>['eq',1]])->find()['position_id'];
                    // 通过负责人的position_id 获取role_id
                    $role = M('Role')->where(['position_id'=>['eq',$position]])->find()['role_id'];
                    // 通过负责人的 role_id 获取下属 role_ids
                    $sub_ids = getAppointSubRoleId($role);
                    $where[$this->type . '.owner_role_id'] = array('in',implode(',', $sub_ids));

                    if ($by == 'me') $where[$this->type . '.owner_role_id'] = array('eq',session('role_id'));

                }
                // 校区筛选 END

				$this->fields_search = $fields_search;
				if($_GET['listrows']){
					$listrows = intval($_GET['listrows']);
					$params[] = "listrows=" . intval($_GET['listrows']);
				}else{
					$listrows = 15;
					$params[] = "listrows=15";
				}
				$count = $d_receivingorder->where($where)->count();
				$p_num = ceil($count/$listrows);
				if($p_num<$p){
					$p = $p_num;
				}
				//导出
				if(trim($_GET['act']) == 'excel'){
					if(!checkPerByAction('finance','export_receivingorder')){
						alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
					}else{
						$dc_id = explode(',',trim($_GET['daochu']));
						if(!empty($_GET['daochu'])){
							$where['receivingorder.receivingorder_id'] = array('in',$dc_id);
						}
						$current_page = intval($_GET['current_page']);
						$export_limit = intval($_GET['export_limit']);
						$limit = ($export_limit*($current_page-1)).','.$export_limit;
						$list = $d_receivingorder->where($where)->limit($limit)->select();
					}
				}else{
                    if(!empty($_GET['od'])){
                        if($_GET['od']==2){
                            $order =  $this->type . '.pay_time desc';
                        }else if($_GET['od']==3){
                            $order =  $this->type . '.pay_time asc';
                        }
                    }
					$list = $d_receivingorder->where($where)->order($order)->page($p.','.$listrows)->select();
				}
				//总计金额
				$sum_money = $d_receivingorder->where($where)->sum('money');
				$m_r_contract_sales = M('RContractSales');
				$m_sales_product = M('SalesProduct');
				$m_product = M('Product');
				foreach($list as $k=>$v){
					$list[$k]['owner'] = $m_user->where('role_id =%d',$v['owner_role_id'])->field('full_name,role_id')->find();
					//合同
					$contract_info = $m_contract->where('contract_id =%d',$v['contract_id'])->field('number,contract_id,price')->find();
					$list[$k]['contract'] = $contract_info;
					//已收款金额
					$receivingorder_price_total = 0;
					$receivingorder_price_total = $m_receivingorder->where(array('receivables_id'=>$v['receivables_id'],'status'=>1))->sum('money');
					//剩余金额
					$list[$k]['un_receivingorder_price'] = $contract_info['price']-$receivingorder_price_total;

					$receivables_info = $m_receivables->where('receivables_id =%d',$v['receivables_id'])->find();
					if($receivables_info['type'] == 1){
						$list[$k]['customer_name'] = $m_customer->where('customer_id =%d',$receivables_info['customer_id'])->getField('name');
						$list[$k]['customer_id'] = $receivables_info['customer_id'];
					}
					//审核状态
					$status_name = '待审';
					if ($v['status'] == 1) {
						$status_name = '通过';
					}elseif($v['status'] == 2){
						$status_name = '拒绝';
					}
					$list[$k]['status_name'] = $status_name;
					//相关产品
					$sales_id = $m_r_contract_sales->where(array('contract_id'=>$v['contract_id']))->getField('sales_id');
					$product_names = array();
					if ($sales_id) {
						$product_ids = $m_sales_product->where(array('sales_id'=>$sales_id))->getField('product_id',true);
						$product_names = $m_product->where(array('product_id'=>array('in',$product_ids)))->getField('name',true);
					}
					$list[$k]['product_name'] = implode(',',$product_names);
				}
				$sum_money = number_format($sum_money,2);
				$money_arr = array('sum_money'=>$sum_money);

				//导出
				if(trim($_GET['act']) == 'excel'){
					session('export_status', 1);
					$this->excelExport($list,'receivingorder');
				}

				import("@.ORG.Page");
				$Page = new Page($count,$listrows);
				$params[] = 'by=' . trim($_GET['by']);
				$params[] = 't=' . $this->type;
				$params[] = 'type=' . trim($_GET['type']); 
				if ($_GET['desc_order']) {
					$params[] = "desc_order=" . trim($_GET['desc_order']);
				} elseif($_GET['asc_order']){
					$params[] = "asc_order=" . trim($_GET['asc_order']);
				}

				$this->parameter = implode('&', $params);
				$Page->parameter = implode('&', $params);
				$show = $Page->show();
				$this->listrows = $listrows;
				$this->alert = parseAlert();
				$this->assign('page',$show);
				$this->assign('list',$list);
				$this->assign('count',$count);
				$this->assign('money_arr',$money_arr);
				$this->display('receivingorder');
				break;
			case 'paymentorder' :
				//付款单
				$d_paymentorder = D('PaymentorderView');
				$m_customer = M('Customer');
				$m_user = M('User');
				//高级搜索
				if(!$_GET['field']){
					$fields_search = array();
					foreach($_GET as $kd => $vd){
						$no_field_array = array('act','content','p','condition','listrows','daochu','this_page','current_page','export_limit','desc_order','asc_order','selectexcelxport','by','t','order_field','type','daochu');
						if(!in_array($kd,$no_field_array)){
							if(in_array($kd,array('create_time','update_time','pay_time'))){
								$where[$kd] = field($vd['value'], $vd['condition']);
								$fields_search[$kd]['field'] = $kd;
								$fields_search[$kd]['start'] = $vd['start'];
								$fields_search[$kd]['end'] = $vd['end'];
								$fields_search[$kd]['form_type'] = 'datetime';

								//时间段查询
								if ($vd['start'] && $vd['end']) {
									$where[$kd] = array('between',array(strtotime($vd['start']),strtotime($vd['end'])+86399));
								} elseif ($vd['start']) {
									$where[$kd] = array('egt',strtotime($vd['start']));
								} else {
									$where[$kd] = array('elt',strtotime($vd['end'])+86399);
								}
							}elseif($kd =='customer_name'){
								if(!empty($vd['value'])){
									$c_where['name'] = array('like','%'.$vd['value'].'%');
									$customer_ids = M('customer')->where($c_where)->getField('customer_id',true); 
									if($customer_ids){
										$where['customer_id'] = array('in',$customer_ids);
									}else{
										$where['customer_id'] = -1;
									}
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['condition'] = $vd['condition'];
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
								if(!empty($vd)){
									$where[$this->type .'.'.$kd] = $vd['value'];
									$fields_search[$kd]['field'] = $kd;
									$fields_search[$kd]['value'] = $vd['value'];
								}
							}else{
								if(is_array($vd)) {
									if($kd =='money'){
										$fields_search[$kd]['form_type'] = 'number';
									}
									if(!empty($vd['value'])){
										$where[$kd] = field($vd['value'], $vd['condition']);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['condition'] = $vd['condition'];
										$fields_search[$kd]['value'] = $vd['value'];
									}
								}else{
									if(!empty($vd)){
										$where[$kd] = field($vd);
										$fields_search[$kd]['field'] = $kd;
										$fields_search[$kd]['value'] = $vd;
									} 
								}
							}
						}
						if($kd != 'search'){
							if(is_array($vd)){
								foreach ($vd as $key => $value) {
									$params[] = $kd . '[' . $key . ']=' . $value;
								}
							}else{
								$params[] = $kd . '=' . $vd; 
							} 
						} 
					} 
				}
				//权限
				if (!isset($where[$this->type . '.owner_role_id']) && $by != 'deleted') {
					$where[$this->type . '.owner_role_id'] = array('in', $this->_permissionRes);
				}
				//过滤不在权限范围内的role_id
				if(isset($where[$this->type . '.owner_role_id'])){
					if(!empty($where[$this->type . '.owner_role_id']) && !in_array(intval($where[$this->type . '.owner_role_id']),$below_ids)){
						$where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
					}
				}
				if($_GET['listrows']){
					$listrows = intval($_GET['listrows']);
					$params[] = "listrows=" . intval($_GET['listrows']);
				}else{
					$listrows = 15;
					$params[] = "listrows=15";
				}
				$count = $d_paymentorder->where($where)->count();
				$p_num = ceil($count/$listrows);
				if($p_num<$p){
					$p = $p_num;
				}
				$list = $d_paymentorder->where($where)->order($order)->page($p.','.$listrows)->select();
				//金额总计
				$sum_money = $d_paymentorder->where($where)->sum('money');
				foreach($list as $k=>$v){
					$list[$k]['owner_name'] = $m_user->where('role_id =%d',$v['owner_role_id'])->getField('full_name');
					$list[$k]['customer_name'] = $m_customer->where('customer_id =%d',$v['customer_id'])->getField('name');
				}
				$sum_money = number_format($sum_money,2);
				$money_arr = array('sum_money'=>$sum_money);

				import("@.ORG.Page");
				$Page = new Page($count,$listrows);
				$params[] = 'by=' . trim($_GET['by']);
				$params[] = 't=' . $this->type;
				$params[] = 'type=' . trim($_GET['type']); 
				if ($_GET['desc_order']) {
					$params[] = "desc_order=" . trim($_GET['desc_order']);
				} elseif($_GET['asc_order']){
					$params[] = "asc_order=" . trim($_GET['asc_order']);
				}

				$this->parameter = implode('&', $params);
				$Page->parameter = implode('&', $params);
				$show = $Page->show();
				$this->listrows = $listrows;
				$this->alert = parseAlert();
				$this->assign('page',$show);
				$this->assign('money_arr',$money_arr);
				$this->assign('list',$list);
				$this->display('paymentorder');
				break;
		}
	}
	
	/**
	*增加应收款、应付款、收款单、付款单页面
	*
	**/
	public function add(){
		switch ($this->type) {
			case 'receivables' :
				//应收款编号前缀
				$receivables_custom = M('Config')->where('name="receivables_custom"')->getField('value');
				$m_receivables = M('Receivables');
				if($this->isPost()){
					if ($m_receivables->create()) {
						if(empty($_POST['customer_id'])){
							$this->error(L('PLEASE_SELECT_CUSTOMERS'));
						} 
						$m_receivables->type = 1;
						$m_receivables->price = round($_POST['price'], 2);

						$receivables_max_id = $m_receivables->max('receivables_id');
						$receivables_max_id = $receivables_max_id+1;
						$receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
						$m_receivables->name = $receivables_custom.date('Ymd').'-'.$receivables_max_code;
						$m_receivables->prefixion = $receivables_custom;

						$m_receivables->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_receivables->creator_role_id = session('role_id');
						$m_receivables->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
						$m_receivables->create_time = time();
						$m_receivables->update_time = time();
						$m_receivables->status = 0;

						if($id = $m_receivables->add()){
							//创建应收款同时创建收款单		
							if(!empty($id)){
								actionLog($id,'t=receivables');

								//发送站内信给审核人
								$check_position_ids =  M('Permission') -> where('url = "%s"','finance/check')->getField('position_id',true);
								if($check_position_ids){
									$receivables_check_role_ids = D('RoleView')->where(array('role.position_id'=>array('in',$check_position_ids),'user.status'=>array('neq',2)))->getField('role_id');
								}
								if($receivables_check_role_ids){
									$receivables_check_role_ids = $receivables_check_role_ids;
								}else{
									//管理员
									$receivables_check_role_ids = M('User')->where(array('category_id'=>1,'status'=>1))->getField('role_id',true);
								}
								$url = U('finance/view','t=receivables&id='.$id);
								$form_role_info = M('User')->where('role_id = %d',session('role_id'))->field('role_id,full_name')->find();
								foreach($receivables_check_role_ids as $k=>$v){
									sendMessage($v,$_SESSION['name'].'&nbsp;&nbsp;创建了新的收款单《<a href="'.$url.'">'.$_POST['name'].'</a>》<font style="color:green;">需要进行审核</font>！',1);
								}
								if($_POST['refer_url'] == 2){
									alert('success',L('ADD SUCCESS',array('')),U('contract/view', 'id='.$_POST['contract_id']));
								}else{
									if(checkPerByAction('finance','view_receivables')){
										alert('success',L('ADD SUCCESS',array('')),U('finance/view','t=receivables'.'&id='.$id));
									}
									if($_POST['contract_id']){
										alert('success',L('ADD SUCCESS',array('')),U('contract/view', 'id='.$_POST['contract_id']));
									}else{
										
										alert('success',L('ADD SUCCESS',array('')),U('finance/index', 't=receivables'));
									}
								}
							}
						}else{
							$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
						}
					} else {
						$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
					}
				}else{
					if($_REQUEST['contract_id'] > 0){
						$data = M('Contract')->where('contract_id = %d',intval($_REQUEST['contract_id']))->find();
						$data['customer'] = M('Customer')->where('customer_id = %d',$data['customer_id'])->getField('name');
						$data['money'] = $data['price']-(M('Receivingorder')->where('contract_id = %d',intval($_REQUEST['contract_id']))->sum('money'));
						if($data['money'] <= 0){
							alert('error','该合同的应收款项已回款完毕！', $_SERVER['HTTP_REFERER']);
						}
					}
					// $this->finance_category = M('FinanceCategory')->where('type=1 and is_pause=0')->select();
					$receivables_max_id = $m_receivables->max('receivables_id');
					$receivables_max_id = $receivables_max_id+1;
					$receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
					$this->number = $receivables_custom.date('Ymd').'-'.$receivables_max_code;
					$this->owner_role_info = getUserByRoleId(session('role_id'));
 					$this->assign('data',$data);
					$this->alert = parseAlert();
					$this->display('receivablesadd');
				}
				break;
			case 'payables' :
				if($this->isPost()){
					$m_payables = M('Payables');
					//判断编号是否唯一
					if ($m_payables->where(array('name'=>trim($_POST['name'])))->find()) {
						$this->error('应付款编号已存在，请修改后重新提交');
					}
					if ($m_payables->create()) {
						$m_payables->name = $_POST['name'] ? trim($_POST['name']) : $this->error(L('PLEASE_FILL_IN_THE_NAME'));
						$m_payables->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_payables->creator_role_id = session('role_id');
						$m_payables->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
						$m_payables->create_time = time();
						$m_payables->update_time = time();
						$m_payables->status = 0;
						if($id = $m_payables->add()){
							actionLog($id,'t=payables');
							alert('success',L('ADD SUCCESS',array('')),U('finance/index', 't=payables'));
						}else{
							$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
						}
					} else {
						$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
					}
				}else{
					$this->alert = parseAlert();
					$this->finance_category = M('FinanceCategory')->where('type=2 and is_pause=0')->select();
					$payables = M('payables');
					$payables_max_id = $payables->max('payables_id');
					$payables_max_id = $payables_max_id+1;
					$payables_max_code = str_pad($payables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
					$payables_custom = M('config') -> where('name="payables_custom"')->getField('value');
					$this->number = $payables_custom.date('Ymd').'-'.$payables_max_code;

					//类型
					$this->type_list = M('FinanceType')->where(array('field'=>'payables'))->select();
					$this->display('payablesadd');
				}
				break;
			case 'receivingorder' :
				//回款单
				$m_receivingorder = M('Receivingorder');
				$m_receivables = M('Receivables');
				if($this->isPost()){

					// 回款凭证 如果没有上传文件直接阻止提交 6-28 dragon
                    if (!$_FILES['file']['size']) $this->error('File is not uploaded.');

                    $pingzheng = $this->uploadFile();// 文件上传

                    if (!$pingzheng) $this->error("File type error..(allow: 'word','docx','gif','jpg','jpeg','bmp','png','swf','pdf')");

                    $_POST['file'] = $pingzheng['savename'];// 赋值
                    $_POST['filename'] = $pingzheng['name'];// 赋值
                    // 回款凭证 END

					$receivables_id = intval($_POST['receivables_id']);
					if (!$receivables_id) {
						$this->error(L('PLEASE_SELECT_RECEIVABLES'));
					}
					//应收款
					$receivables_info = $m_receivables->where(array('receivables_id'=>$receivables_id))->find();
					//已回款金额不能大于应收款总金额
					$receivingorder_sum = $m_receivingorder->where(array('receivables_id'=>$receivables_id,'status'=>1))->sum('money');
					$receivingorder_sum = !empty($receivingorder_sum) ? $receivingorder_sum : 0;
					$money_total = $receivingorder_sum + $_POST['money'];
					if ($money_total > $receivables_info['price']) {
						$this->error(L('EXCEED THE AMOUNT OF RECEIVING'));
					}

					//收款单编号
					$receivingorder_max_id = $m_receivingorder->max('receivingorder_id');
					$receivingorder_max_id = $receivingorder_max_id+1;
					$receivingorder_max_code = str_pad($receivingorder_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
					$code = date('Ymd').'-'.$receivingorder_max_code;
					if ($m_receivingorder->create()) {
						$m_receivingorder->name = $code;
						$owner_role_id = intval($_POST['owner_role_id']);
						if (!$owner_role_id) {
							$this->error(L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'));
						}
						$m_receivingorder->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_receivingorder->create_time = time();
						$m_receivingorder->update_time = time();
						$m_payables->creator_role_id = session('role_id');
						$m_payables->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
						$m_receivingorder->status = 0;
						$m_receivingorder->invoice = intval($_POST['invoice']);

						//查询开户行信息
						if(intval($_POST['account_id'])){
							$bank_account_info = M('BankAccount')->where('account_id = %d',intval($_POST['account_id']))->find();
							$m_receivingorder->bank_account_id = $bank_account_info['account_id'];
							$m_receivingorder->receipt_account = $bank_account_info['bank_account'];
							$m_receivingorder->receipt_bank = $bank_account_info['open_bank'];
							$m_receivingorder->company = $bank_account_info['company'];
						} else {
							$this->error(L('Bank Account Error'));
						}
						//合同ID
						if($_POST['contract_id']){
							$m_receivingorder->contract_id = intval($_POST['contract_id']);
						}else{
							$m_receivingorder->contract_id = $receivables_info['contract_id'];
						}

						if($id = $m_receivingorder->add()){
							//操作记录
							actionLog($id,'t=receivingorder');

							//发送站内信给审核人
							$check_position_ids = M('Permission')->where('url = "%s"','finance/check')->getField('position_id',true);
							if($check_position_ids){
								$receivables_check_role_ids = D('RoleView')->where(array('role.position_id'=>array('in',$check_position_ids),'user.status'=>array('neq',2)))->getField('role_id');
							}
							if($receivables_check_role_ids){
								$receivables_check_role_ids = $receivables_check_role_ids;
							}else{
								//管理员
								$receivables_check_role_ids = M('User')->where(array('category_id'=>1,'status'=>1))->getField('role_id',true);
							}
							$url = U('finance/view','t=receivingorder&id='.$id);
							$form_role_info = M('User')->where('role_id = %d',session('role_id'))->field('role_id,full_name')->find();
							foreach($receivables_check_role_ids as $k=>$v){
								sendMessage($v,$form_role_info['full_name'].'&nbsp;&nbsp;创建了新的回款单《<a href="'.$url.'">'.$data['name'].'</a>》<font style="color:green;">需要进行审核</font>！',1);
							}

							//已回款总金额
							$money_sum = $m_receivingorder->where(array('receivables_id'=>$receivables_id,'status'=>1))->sum('money');
							//修改回款单状态
							if($money_sum >= $receivables_info['price']){
								if($receivables_info['status'] != 2){
									$m_receivables->where(array('receivables_id'=>$receivables_id))->setField('status',2);
								}
							}elseif($money_sum > 0){
								$m_receivables->where(array('receivables_id'=>$receivables_id))->save(array('status'=>1));
							}
							if($_POST['submit'] == L('SAVE')){
								if($_POST['refer_url']){
									alert('success', L('ADD SUCCESS',array('')), $_POST['refer_url']);
								}else{
									alert('success', L('ADD SUCCESS',array('')), U('finance/index','t='.$this->type));
								}
							}else{
								alert('success',L('ADD SUCCESS',array('')),$_SERVER['HTTP_REFERER']);
							}
						}else{
							$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
						}
					} else {
						$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
					}
				}else{
					$this->bank_list = M('bank_account')->select();
					$this->alert = parseAlert();
					$this->display('receivingorderadd');
				}
				break;
			case 'paymentorder' :
				if($this->isPost()){
					$m_paymentorder = M('Paymentorder');
					$m_payables = M('Payables');
					$payables_id = intval($_POST['payables_id']);
					if (!$payables_id) {
						$this->error(L('PLEASE_SELECT_PAYABLES'));
					}
					//应付款
					$payables_info = $m_payables->where(array('payables_id'=>$payables_id))->find();
					//已付款金额不能大于应付款总金额
					$paymentorder_sum = $m_paymentorder->where(array('payables_id'=>$payables_id,'status'=>1))->sum('money');
					$paymentorder_sum = !empty($paymentorder_sum) ? $paymentorder_sum : 0;
					$money_total = $paymentorder_sum + $_POST['money'];
					if ($money_total > $payables_info['price']) {
						$this->error(L('EXCEED THE AMOUNT OF PAYMENT'));
					}

					if ($m_paymentorder->create()) {
						$m_paymentorder->name = 'FKD'.date('Ymd').mt_rand(1000,9999);
						$m_paymentorder->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_paymentorder->creator_role_id = session('role_id');
						$m_paymentorder->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
						$m_paymentorder->create_time = time();
						$m_paymentorder->update_time = time();
						$m_paymentorder->status = 0;
						$m_paymentorder->type = 0;
						//银行账户
						$bank_account = M('BankAccount')->where('account_id=%d', intval($_POST['account_id']))->find();
						if (is_array($bank_account) && !empty($bank_account)) {
							$m_paymentorder->bank_account_id = $bank_account['account_id'];
							$m_paymentorder->bank_account = $bank_account['bank_account'];
							$m_paymentorder->open_bank = $bank_account['open_bank'];
							$m_paymentorder->company = $bank_account['company'];
						} else {
							$this->error(L('Bank Account Error'));
						}

						if($id = $m_paymentorder->add()){
							actionLog($id,'t=paymentorder');
							
							$money_sum = $m_paymentorder->where(array('payables_id'=>$payables_id,'status'=>1))->sum('money');
							//应付款状态
							if($money_sum >= $payables_info['price']){
								if($payables_info['status'] != 2){
									$m_payables ->where(array('payables_id'=>$payables_id))->setField('status',2);
								}
							}elseif($money_sum > 0){
								$m_payables->where(array('payables_id'=>$data['payables_id']))->setField('status',1);
							}
							if($_POST['submit'] == L('SAVE')){
								alert('success', L('ADD SUCCESS',array('')),  U('finance/index','t='.$this->type));
							}else{
								alert('success',L('ADD SUCCESS',array('')),$_SERVER['HTTP_REFERER']);
							}
						}else{
							$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
						}
					} else {
						$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR',array('')));
					}
				}else{
					$this->alert = parseAlert();
					$this->bank_list = M('bank_account')->select();
					$this->display('paymentorderadd');
				}
				break;
			case 'receivablesplan' :
				$receivables_status = M('Receivables')->where('receivables_id = %d',$_REQUEST['receivables_id'])->getField('status');
				if($receivables_status == 2){
					alert('error','该应收款项已结束！', $_SERVER['HTTP_REFERER']);
				}
				if($this->isPost()){
					$m_receivables_plan = M('ReceivablesPlan');
					$contract_id = M('receivables')->where('receivables_id = %d',$_POST['receivables_id'])->getField('contract_id');
					foreach($_POST['receivable'] as $v){
						if(!empty($v['price']) && $v['price'] != 0){
							$data['pay_time'] = strtotime($v['pay_time']);
							$data['creator_role_id'] = session('role_id');
							$data['owner_role_id'] = $v['owner_role_id'];
							$data['receivables_id'] = $_POST['receivables_id'];
							$data['price'] = $v['price'];
							$data['description'] = $v['description'];
							$res = $m_receivables_plan->add($data);
						}
						if(!$res){
							alert('error','添加失败！', $_SERVER['HTTP_REFERER']);
						}
					}
					if($res){
						alert('success','添加成功！',U('contract/view','id='.$contract_id));
					}
				}else{
					$data['receivables_id'] = $_GET['receivables_id'];
					$data['receivables'] = M('receivables')->where('receivables_id = %d',$data['receivables_id'])->getField('name'); 
					$this->assign('data',$data);
					$this->alert = parseAlert();
					$this->display('receivables_plan_add');
				}
				break;
		}
	}

	public function uploadFile()
    {
        // 如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
        $upload->allowExts = array('word','docx','gif','jpg','jpeg','bmp','png','swf','pdf');// 设置附件上传类型
        $upload->thumbRemoveOrigin = false;//是否删除原文件
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
        }
        $upload->savePath = $dirname;

        if (!$upload->upload()) {// 上传错误提示错误信息
            $this->error($upload->getErrorMsg());
        } else {// 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if (is_array($info[0]) && !empty($info[0])) {
                $res['savename'] = $dirname . $info[0]['savename'];
                $res['name'] = $info[0]['name'];
            } else {
                $this->error('file is not uploaded.');
            }
            // 返回文件路径
            return $res ?: [];
        }
    }
	
	/**
	*修改应收款、应付款、收款单、付款单页面
	*
	**/
	public function edit(){
		$id = intval($_REQUEST['id']);
		if($id == 0) {
			$this->error(L('PARAMETER_ERROR'),U('finance/index','t='.$this->type));
		}
		switch ($this->type) {
			case 'receivables' :
				//应收款
				$m_receivables = M('Receivables');
				$info = $m_receivables->where(array('receivables_id'=>$id))->find();
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
				}
				$info['owner_name'] = M('User')->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
				if($this->isPost()){
					if (!intval($_POST['owner_role_id'])) {
						$this->error(L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'));
					}
					//判断编号是否唯一
					if ($m_receivables->where(array('name'=>trim($_POST['name']),'receivables_id'=>array('neq',$id)))->find()) {
						$this->error('应收款编号已存在，请修改后重新提交');
					}
					if ($m_receivables->create()) {
						$m_receivables->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_receivables->update_time = time();
						if($m_receivables->where(array('receivables_id'=>$id))->save()){
							actionLog($id,'t=receivables');
							alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
						}else{
							$this->error(L('EDIT FAILED',array('')));
						}
					} else {
						$this->error(L('EDIT FAILED',array('')));
					}
				}else{
					// $receivables_info['type_name'] = M('FinanceCategory')->where('id=%d', $receivables_info['type'])->getField('name');
					$info['contract_number'] = M('Contract')->where(array('contract_id'=>$info['contract_id']))->getField('number');
					$info['customer_name'] = M('Customer')->where(array('customer_id'=>$info['customer_id']))->getField('name');
					$this->assign('info',$info);
					$this->refer_url = $_SERVER['HTTP_REFERER'];
					$this->alert = parseAlert();
					$this->display('receivablesedit');
				}
				break;
			case 'payables' :
				//应付款
				$m_payables = M('Payables');
				$info = $m_payables->where(array('payables_id'=>$id))->find();
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				$info['owner_name'] = M('User')->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
				if ($info['customer_id']) {
					$info['customer_name'] = M('Customer')->where(array('customer_id'=>$info['customer_id']))->getField('name');
				}
				if($this->isPost()){
					$name = trim($_POST['name']);
					$owner_role_id = intval($_POST['owner_role_id']);
					if (!$owner_role_id) {
						$this->error(L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'));
					}
					if (!$name) {
						$this->error(L('PLEASE_FILL_IN_THE_NAME'));
					}
					//判断编号是否唯一
					if ($m_payables->where(array('name'=>trim($_POST['name']),'payables_id'=>array('neq',$id)))->find()) {
						$this->error('应付款编号已存在，请修改后重新提交');
					}
					if ($m_payables->create()) {
						$m_payables->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_payables->update_time = time();
						if($m_payables->where(array('payables_id'=>$id))->save()){
							actionLog($id,'t=payables');
							if($_POST['refer_url']){
								alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
							}else{
								alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
							}
						}else{
							$this->error(L('EDIT FAILED',array('')));
						}
					}else{
						$this->error(L('EDIT FAILED',array('')));
					}
				}else{
					//财务类型
					$this->type_list = M('FinanceType')->where(array('field'=>'payables'))->select();
					$this->assign('info',$info);
					$this->refer_url = $_SERVER['HTTP_REFERER'];
					$this->alert = parseAlert();
					$this->display('payablesedit');
				}
				break;
			case 'receivingorder' :
				//回款单
				$m_receivingorder = M('Receivingorder');
				$m_receivables = M('Receivables');
				$info = $m_receivingorder->where(array('receivingorder_id'=>$id))->find();
				$info['receivables'] = $m_receivables->where(array('receivables_id'=>$info['receivables_id']))->field('name')->find();
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				if($info['status'] == 1) {
					$this->error(L('THE RECEIVABLES ORDER HAS BEEN CLOSING'),U('finance/index','t='.$this->type));
				}
				$info['owner_name'] = M('User')->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
				if($this->isPost()){
					$receivables_id = intval($_POST['receivables_id']);
					if (!$receivables_id) {
						$this->error(L('PLEASE_SELECT_PAYABLES'));
					}

					//应收款
					$receivables_info = $m_receivables->where(array('receivables_id'=>$receivables_id))->find();
					//已回款金额不能大于应收款总金额
					$receivingorder_sum = $m_receivingorder->where(array('receivables_id'=>$receivables_id,'status'=>1))->sum('money');
					$receivingorder_sum = !empty($receivingorder_sum) ? $receivingorder_sum : 0;
					$money_total = $receivingorder_sum + $_POST['money'];
					if ($money_total > $receivables_info['price']) {
						$this->error(L('EXCEED THE AMOUNT OF PAYMENT'));
					}
					
					if (!intval($_POST['owner_role_id'])) {
						$this->error(L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'));
					}
					if(!intval($_POST['account_id'])){
						$this->error(L('Bank Account Error'));
					}
					//判断编号是否唯一
					if ($m_receivingorder->where(array('name'=>trim($_POST['name']),'receivingorder_id'=>array('neq',$id)))->find()) {
						$this->error('收款单号已存在，请修改后重新提交');
					}
					if ($m_receivingorder->create()) {
						$m_receivingorder->status = 0;
						$m_receivingorder->check_des = '';
						$m_receivingorder->update_time = time();
						$m_receivingorder->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();

						$bank_account_info = M('BankAccount')->where('account_id = %d',intval($_POST['account_id']))->find();
						$m_receivingorder->bank_account_id = $bank_account_info['account_id'];
						$m_receivingorder->receipt_account = $bank_account_info['bank_account'];
						$m_receivingorder->receipt_bank = $bank_account_info['open_bank'];
						$m_receivingorder->company = $bank_account_info['company'];

						if($m_receivingorder->where(array('receivingorder_id'=>$id))->save()){
							actionLog($id,'t=receivingorder');
							//应收款状态
							$receivables_info = $m_receivables->where(array('receivables_id'=>$receivables_id))->find();
							$money_sum = $m_receivingorder->where(array('receivables_id'=>$receivables_id))->sum('money');
							if($money_sum >= $receivables_info['price']){
								$m_receivables->where(array('receivables_id'=>$receivables_id))->save(array('status'=>2));
							}elseif($money_sum > 0){
								$m_receivables->where(array('receivables_id'=>$receivables_id))->save(array('status'=>1));
							}
							if($_POST['refer_url']){
							   alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
							}else{
								alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
							}
						}else{
							$this->error(L('EDIT FAILED',array('')));
						}
					} else {
						$this->error(L('EDIT FAILED',array('')));
					}
				}else{
					$this->bank_list = M('bank_account')->select();
					$this->assign('info',$info);
					$this->refer_url = $_SERVER['HTTP_REFERER'];
					$this->alert = parseAlert();
					$this->display('receivingorderedit');
				}
				break;
			case 'paymentorder' :
				//付款单
				$m_paymentorder = M('Paymentorder');
				$m_payables = M('Payables');
				$info = $m_paymentorder->where(array('paymentorder_id'=>$id))->find();
				$info['payables'] = $m_payables->where(array('payables_id'=>$info['payables_id']))->field('name')->find();
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				if($info['status'] == 1) {
					$this->error(L('THE PAYMENT ORDER HAS BEEN CLOSING'),U('finance/index','t='.$this->type));
				}
				$info['owner_name'] = M('User')->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
				if($this->isPost()){
					$payables_id = intval($_POST['payables_id']);
					if (!$payables_id) {
						$this->error(L('PLEASE_SELECT_PAYABLES'));
					}
					
					//应付款
					$payables_info = $m_payables->where(array('payables_id'=>$payables_id))->find();
					//已回款金额不能大于应收款总金额
					$paymentorder_sum = $m_paymentorder->where(array('payables_id'=>$payables_id,'status'=>1))->sum('money');
					$paymentorder_sum = !empty($paymentorder_sum) ? $paymentorder_sum : 0;
					$money_total = $paymentorder_sum + $_POST['money'];
					if ($money_total > $payables_info['price']) {
						$this->error(L('EXCEED THE AMOUNT OF PAYMENT'));
					}

					if (!intval($_POST['owner_role_id'])) {
						$this->error(L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'));
					}
					//判断编号是否唯一
					if ($m_paymentorder->where(array('name'=>trim($_POST['name']),'paymentorder_id'=>array('neq',$id)))->find()) {
						$this->error('付款单号已存在，请修改后重新提交');
					}
					if ($m_paymentorder->create()) {
						$m_paymentorder->pay_time = $_POST['pay_time'] ? strtotime($_POST['pay_time']) : time();
						$m_paymentorder->update_time = time();
						$m_paymentorder->status = 0;
						if($m_paymentorder->where(array('paymentorder_id'=>$id))->save()){
							actionLog($id,'t=paymentorder');
							$payables_info = $m_payables->where(array('payables_id'=>$payables_id))->find();
							$money_sum = $m_paymentorder->where(array('payables_id'=>$payables_id))->sum('money');
							if($money_sum >= $payables_info['price']){
								$m_payables->where(array('payables_id'=>$payables_id))->save(array('status'=>2));
							}elseif($money_sum > 0){
								$m_payables->where(array('payables_id'=>$payables_id))->save(array('status'=>1));
							}
							if($_POST['refer_url']){
							    alert('success',L('EDIT SUCCESS',array('')),$_POST['refer_url']);
							}else{
								alert('success',L('EDIT SUCCESS',array('')),U('finance/view','id='.$id.'&t='.$this->type));
							}
						}else{
							$this->error(L('EDIT FAILED',array('')));
						}
					} else {
						$this->error(L('EDIT FAILED',array('')));
					}
				}else{
					$this->bank_list = M('bank_account')->select();
					$this->assign('info',$info);
					$this->refer_url=$_SERVER['HTTP_REFERER'];
					$this->alert = parseAlert();
					$this->display('paymentorderedit');
				}
				break;
		}
	}
	
	/**
	*应收款、应付款、收款单、付款单详情页面
	*
	**/
	public function view(){
		$id = intval($_GET['id']);
		$m_user = M('User');
		if($id == 0) {
			alert('error',L('PARAMETER_ERROR'),U('finance/index','t='.$this->type));
		}
		switch ($this->type) {
			case 'receivables' :
				//应收款
				$d_receivables = D('ReceivablesView');
				$m_receivingorder = M('Receivingorder');
				
				$info = $d_receivables->where(array('receivables_id'=>$id))->find();
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				$receivingorder_list = $m_receivingorder->where(array('is_deleted'=>array('neq',1),'receivables_id'=>$id))->select();
				$receivingorder_money = 0; //已收款金额
				$un_receivables_money = 0; //未收款金额
				foreach($receivingorder_list as $k=>$v){
					$receivingorder_list[$k]['owner_name'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->getField('full_name');
					if($v['status'] == 1){
						//计算已结账状态的金额
						$receivingorder_money += $v['money'];
					}
				}
				$info['receivingorder'] = $receivingorder_list;
				$un_receivables_money = ($info['price'] - $receivingorder_money) < 0 ? 0 : ($info['price'] - $receivingorder_money);
				$info['contract'] = M('Contract')->where('contract_id = %d', $info['contract_id'])->field('contract_id,number')->find();
				$info['un_receivables_money'] = $un_receivables_money;
				$info['owner_name'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');

				//循环提醒下次收款时间
				$cycel_info = M('Cycel')->where(array('module'=>'receivables','module_id'=>$id,'end_time'=>array('egt',strtotime(date('Y-m-d',time())))))->find();
				//type 1周 2月 3年 4仅一次
				if ($cycel_info) {
					if ($cycel_info['type'] == 1) {
						$cycel_name = '星期'.$cycel_info['num'];
						//获取最近7天的时间戳数组
						$start_week_time = strtotime(date('Y-m-d',time()));
						$end_week_time = strtotime(date('Y-m-d',time()))+86400*7;
						$week_arr = dateList($start_week_time,$end_week_time);
						foreach ($week_arr as $k=>$v) {
							$week_name = getTimeWeek($v['sdate']); //星期
							if ($week_name == $cycel_name) {
								$cycel_time = date('Y-m-d',$v['sdate']);
								break;
							}
						}
					} elseif ($cycel_info['type'] == 2) {
						$now_day = date('d',time());
						$now_month = date('m',time());
						if (strtotime(date('Y-m-d',time())) == strtotime('Y-m-'.$cycel_info['num'])) {
							$cycel_time = date('Y-m-d',time());
						} else {
							if (strtotime(date('Y-m-d',time())) > strtotime('Y-m-'.$cycel_info['num'])) {
								//获取下月开始时间
								$next_month = strtotime(date('Y-m-01', strtotime('+1 month')));
								$cycel_time = date('Y-m-d',strtotime(date('Y',$next_month).date('m',$next_month).$cycel_info['num']));
							} else {
								$cycel_time = date('Y-m-',time()).$cycel_info['num'];
							}
						}
					} elseif ($cycel_info['type'] == 3) {
						if (strtotime(date('Y-m-d',time())) == strtotime('Y-'.$cycel_info['num'])) {
							$cycel_time = date('Y-m-d',time());
						} else {
							if (strtotime(date('Y-m-d',time())) > strtotime('Y-'.$cycel_info['num'])) {
								//获取下年年份
								$year = date("Y",time());
							    $year_next = $year+1;
							    $cycel_time = $year_next.'-'.$cycel_info['num'];
							} else {
								$cycel_time = date("Y-",time()).$cycel_info['num'];
							}
						}
					} else {
						$cycel_time = $cycel_info['num'];
					}
				}
				$info['cycel_time'] = $cycel_time;

				$this->assign('info',$info);
				$this->alert = parseAlert();
				$this->display('receivablesview');
				break;
			case 'payables' :
				//应付款
				$d_payables = D('PayablesView');
				$m_paymentorder = M('Paymentorder');
				$info = $d_payables->where(array('payables_id'=>$id))->find();
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				$paymentorder_list = $m_paymentorder->where('is_deleted <> 1 and payables_id = %d', $id)->select();
				$sum_money = 0; //已付款金额
				$un_payment = 0; //还剩多少金额未付款
				foreach($paymentorder_list as $k=>$v){
					$paymentorder_list[$k]['owner_name'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->getField('full_name');
					if($v['status'] == 1 ){
						//计算已结账状态的金额
						$sum_money += $v['money'];
					}
				}
				$info['paymentorder'] = $paymentorder_list;
				$info['un_payment'] = ($info['price'] - $sum_money) < 0 ? 0 : ($info['price'] - $sum_money);
				$info['owner_name'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
				$info['creator_name'] = $m_user->where(array('role_id'=>$info['creator_role_id']))->getField('full_name');
				//应付款类型
				$info['type_name'] = M('FinanceType')->where(array('id'=>$info['type_id']))->getField('name');
				//相关客户
				$info['customer_name'] = M('Customer')->where(array('customer_id'=>$info['customer_id']))->getField('name');
				$this->assign('info',$info);
				$this->alert = parseAlert();
				$this->display('payablesview');
				break;
			case 'receivingorder' :
				//收款单
				$d_receivingorder = D('ReceivingorderView');
				$m_receivables = M('Receivables');
				$info = $d_receivingorder->where(array('receivingorder_id'=>$id))->find();
				$info['examine'] = getUserByRoleId($info['examine_role_id']);
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				$info['owner'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->field('full_name,thumb_path')->find();
				$this->assign('info',$info);
				$this->alert = parseAlert();
				$this->display('receivingorderview');
				break;
			case 'paymentorder' :
				//付款单
				$d_paymentorder = D('PaymentorderView');
				$info = $d_paymentorder->where(array('paymentorder_id'=>$id))->find();
				$info['examine'] = getUserByRoleId($info['examine_role_id']);
				if(empty($info)){
					$this->error(L('RECORD NOT EXIST'));
				}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
				$info['owner'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->field('full_name,thumb_path')->find();
				$this->assign('info',$info);
				$this->alert = parseAlert();
				$this->display('paymentorderview');
				break;
		}
	}
	
	/**
	*删除应收款、应付款、收款单、付款单
	*
	**/
	public function delete(){
		switch ($this->type) {
			case 'receivables' :
				$receivables_ids = is_array($_REQUEST['receivables_id']) ? implode(',', $_REQUEST['receivables_id']) : $_REQUEST['id'];
				if($receivables_ids == ''){
					$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
				} 
				$m_receivables = M('Receivables');
				$m_receivingorder = M('Receivingorder');
				//如果应收款下有收款单记录，提示先删除收款单
				$error_tip = '';
				$receivables_list = $m_receivables->where('is_deleted <> 1 and receivables_id in ('.$receivables_ids.')')->select();
				if(checkPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type)){
					foreach($receivables_list as $k=>$v){
						if(!$m_receivingorder->where(array('receivables_id'=>$v['receivables_id'],'owner_role_id'=>array('in',implode(',', $this->_permissionRes))))->find()){
							if(!$m_receivables->where('receivables_id = %d', $v['receivables_id'])->delete()){
								$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
							} else {
								actionLog($v['receivables_id'],'t=receivables');
							}
						}else{
							$error_tip .= $v['name'].',';
						}		
					}
				}else{
					$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),0);
				}
				if($error_tip){
					$this->ajaxReturn('',L('PARTIAL DELETION FAILED',array($error_tip)),0);
				}else{
					$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
				}
				break;
			case 'payables' :
				$payables_ids = is_array($_REQUEST['payables_id']) ? implode(',', $_REQUEST['payables_id']) : $_REQUEST['id'];
				if($payables_ids == ''){
					$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
				}
				$m_payables = M('Payables');
				$m_paymentorder = M('Paymentorder');
				//如果应付款下有付款单记录，提示先删除付款单
				$error_tip = '';
				$payables_list = $m_payables->where('is_deleted <> 1 and payables_id in ('.$payables_ids.')')->select();
				if(checkPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type)){
					foreach($payables_list as $k=>$v){
						if(!$m_paymentorder->where(array('payables_id'=>$v['payables_id'],'owner_role_id'=>array('in',implode(',', $this->_permissionRes))))->find()){
							if(!$m_payables->where('payables_id = %d', $v['payables_id'])->delete()){
								$this->ajaxReturn('','删除失败，请联系管理员！',0);
							}else{
								actionLog($v['payables_id'],'t=payables');
							}
						}else{
							$error_tip .= $v['name'].',';
						}
					}
				}else{
					$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),0);
				}
				if($error_tip){
					$this->ajaxReturn('',L('PARTIAL DELETION FAILED',array($error_tip)),0);
				}else{
					$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
				}
				break;
			case 'receivingorder' :
				$receivingorder_ids = is_array($_REQUEST['receivingorder_id']) ? implode(',', $_REQUEST['receivingorder_id']) : $_REQUEST['id'];
				$m_receivingorder = M('Receivingorder');
				$m_receivables = M('Receivables');
				$receivingorder_info = $m_receivingorder->where('receivingorder_id in (%s)',$receivingorder_ids)->field('status,receivables_id')->find();
				if($receivingorder_ids == '') {
					$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
				}elseif($receivingorder_info['status'] ==1 ){
					$this->ajaxReturn('','收款单已结账，不能删除',0);
				}
				if(checkPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type)){					
					//改变应收款状态
					$receivables_price = $m_receivables->where('receivables_id = %d',$receivingorder_info['receivables_id'])->getField('price');
					$sum_receivingorder_price = $m_receivingorder->where(array('receivables_id'=>$receivingorder_info['receivables_id'],'is_deleted'=>0,'status'=>array('eq',1)))->sum('money');
					if(empty($sum_receivingorder_price)){
						$m_receivables->where('receivables_id = %d',$receivingorder_info['receivables_id'])->setField('status',0);
					}else{
						if(intval($receivables_price) > intval($sum_receivingorder_price)){
							$m_receivables->where('receivables_id = %d',$receivingorder_info['receivables_id'])->setField('status',1);
						}
					}
					$receivingorder_res = $m_receivingorder->where('receivingorder_id in (%s) and owner_role_id in (%s)', $receivingorder_ids,implode(',', $this->_permissionRes))->delete();
					if($receivingorder_res){
						$receivingorder_idsArr = explode(',',$receivingorder_ids);
						foreach($receivingorder_idsArr as $v){
							actionLog($v,'t=receivingorder');
						}
						$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
					}else{
						$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
					}
				}else{
					$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),0);
				}
				break;
			case 'paymentorder' :
				$paymentorder_ids = is_array($_REQUEST['paymentorder_id']) ? implode(',', $_REQUEST['paymentorder_id']) : intval($_REQUEST['id']);
				$m_paymentorder = M('Paymentorder');
				$m_payables = M('Payables');
				$paymentorder_info = $m_paymentorder->where('paymentorder_id in (%s)',$paymentorder_ids)->field('status,payables_id')->find();
				if($paymentorder_ids == '') {
					$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
				}elseif($paymentorder_info['status'] ==1 ){
					$this->ajaxReturn('','付款单已结账，不能删除',0);
				}
				if(checkPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type)){
					
					//改变应付款状态
					$payables_price = $m_payables->where('payables_id = %d',$paymentorder_info['payables_id'])->getField('price');
					$sum_paymentorder_price = $m_paymentorder->where(array('payables_id'=>$paymentorder_info['payables_id'],'is_deleted'=>0,'status'=>array('eq',1)))->sum('money');
					if(empty($sum_paymentorder_price)){
						$m_payables->where('payables_id = %d',$paymentorder_info['payables_id'])->setField('status',0);
					}else{
						if(intval($payables_price) > intval($sum_paymentorder_price)){
							$m_payables->where('payables_id = %d',$paymentorder_info['payables_id'])->setField('status',1);
						}
					}
					$paymentorder_res = $m_paymentorder->where('paymentorder_id in (%s) and owner_role_id in (%s)', $paymentorder_ids,implode(',', $this->_permissionRes))->delete();
					if($paymentorder_res){
						$paymentorder_idsArr = explode(',',$paymentorder_ids);
						foreach($paymentorder_idsArr as $v){
							actionLog($v,'t=paymentorder');
						}
						$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
					}else{
						$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
					}
				}else{
					$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),0);
				}
				break;
		}
	}
	
	/**
	*财务弹出框列表页
	*
	**/
	public function listdialog(){
		//权限判断
		if(!checkPerByAction('finance', 'index_'.$this->type)){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}
		$where = array();
		$params = array();
		$order = "";
		$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
		$below_ids = getPerByAction('finance', 'index_'.$this->type);
		$where[$this->type . '.is_deleted'] = 0;
		$where[$this->type . '.owner_role_id'] = array('in',implode(',', $below_ids)); 
		$where[$this->type.'.status'] = array('neq',2);
		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']) == 'all' ? $this->type . '.name|'.$this->type .'.description' : $this->type .'.'. $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('create_time' == $field || 'update_time' == $field ) {
				$search = is_numeric($search)?$search:strtotime($search);
			}
			if(trim($_REQUEST["field"]) == "customer_id"){
				$c_where['name'] = array('like','%'.$search.'%');
				$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true);
				$where[$field] = array('in',$customer_ids);
			}else{
				switch ($_REQUEST['condition']) {
					case "is" : $where[$field] = array('eq',$search);break;
					case "isnot" :  $where[$field] = array('neq',$search);break;
					case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
					case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
					case "start_with" :  $where[$field] = array('like',$search.'%');break;
					case "end_with" :  $where[$field] = array('like','%'.$search);break;
					case "is_empty" :  $where[$field] = array('eq','');break;
					case "is_not_empty" :  $where[$field] = array('neq','');break;
					case "gt" :  $where[$field] = array('gt',$search);break;
					case "egt" :  $where[$field] = array('egt',$search);break;
					case "lt" :  $where[$field] = array('lt',$search);break;
					case "elt" :  $where[$field] = array('elt',$search);break;
					case "eq" : $where[$field] = array('eq',$search);break;
					case "neq" : $where[$field] = array('neq',$search);break;
					case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
					case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
					case "tgt" :  $where[$field] = array('gt',$search+86400);break;
					default : $where[$field] = array('eq',$search);
				}
			}
			
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}
		$params[] = 't='.$this->type;
		$order = empty($order) ? $this->type . '.update_time desc' : $order;
		import("@.ORG.DialogListPage");
		switch ($this->type) {
			case 'receivables' :
				$receivables = D('ReceivablesView');
				
				$list = $receivables->where($where)->page($p.',10')->order('receivables.update_time desc')->select();
				$count = $receivables->where($where)->count();
				$this->search_field = $_REQUEST;//搜索信息

				$Page = new Page($count,10);
				$Page->parameter = implode('&', $params);
				$this->assign('page',$Page->show());

				$this->assign('receivablesList',$list);
				$this->display('receivableslistdialog');
				break;
			case 'payables' :
				$payables = D('PayablesView');
				$payablesList = $payables->where($where)->page($p.',10')->order('payables.update_time desc')->select();
				$this->payablesList = $payablesList;
				$count = $payables->where($where)->count();
				$this->search_field = $_REQUEST;//搜索信息

				$Page = new Page($count,10);
				$Page->parameter = implode('&', $params);
				$this->assign('page',$Page->show());
				$this->display('payableslistdialog');
				break;
		}
	}
	
	/**
	*  增加财务弹出框页
	*
	**/
	public function adddialog(){
		$contract_id = $this->_get('contract_id','intval',0);
		if($this->type == 'receivablesplan'){
			if(!checkPerByAction('finance', 'add_'.$this->type)){
				echo '<div class="alert alert-error">您没有此权利！</div>';die;
			}
		}else{
			if(!checkPerByAction('finance', 'add_'.$this->type)){
				echo '<div class="alert alert-error">您没有此权利！</div>';die;
			}
		}
		if($contract_id == 0){
			$id = $this->_get('id','intval',0);
			$this->assign('id',$id);
		}else{
			$contract_id = intval($_GET['contract_id']);
			$this->assign('contract_id',$contract_id);
			$business_id = M('contract')->where(array('contract_id'=>$contract_id))->getField('business_id');
			$customer_id = M('business')->where(array('business_id'=>$business_id))->getField('customer_id');
			$this->assign('customer_id',$customer_id);
		}
		
		switch ($this->type) {
			case 'receivables' :
				//应收款
				$contract_info = M('contract')->where('contract_id = %d',$contract_id)->field('contract_id,number,price,customer_id')->find();
				$contract_info['customer_name'] = M('customer')->where('customer_id =%d',$contract_info['customer_id'])->getField('name');
				$receivables_custom = M('config') -> where('name="receivables_custom"')->getField('value');
				$receivables = M('receivables');
				$receivables_max_id = $receivables->max('receivables_id');
				$receivables_max_id = $receivables_max_id+1;
				$receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
				$this->number = $receivables_custom.date('Ymd').'-'.$receivables_max_code;
				$this->contract_info = $contract_info;
				$this->display('receivablesadddialog');
				break;
			case 'payables' :
				//应付款
				$this->display('payablesadddialog');
				break;
			case 'receivingorder' :
				//回款单
				$m_receivables = M('Receivables');
				$m_receivingorder = M('Receivingorder');
				$receivables_id = $this->_get('receivables_id','intval',0);
				if($receivables_id){
					$receivables = $m_receivables->where('is_deleted <> 1 and receivables_id = %d',$receivables_id)->find();
				}else{
					if($contract_id){
						$receivables = $m_receivables->where('is_deleted <> 1 and contract_id = %d',$contract_id)->find();
					}else{
						$receivables = $m_receivables->where('is_deleted <> 1 and receivables_id = %d',$id)->find();
					}
				}
				$receivingorder_money = $m_receivingorder->where(array('is_deleted'=>array('neq',1),'receivables_id'=>$receivables['receivables_id'],'status'=>array('neq',2)))->sum('money');
				$add_money = $receivables['price'] - $receivingorder_money;
				//开户行
				$this->bank_list = M('bank_account')->select();
				
				$this->assign('add_money',$add_money);
				$this->assign('receivingorder_money',$receivingorder_money);
				$this->assign('receivables',$receivables);
				$this->display('receivingorderadddialog');
				break;
			case 'paymentorder' :
				//付款单
				$m_payables = M('Payables');
				$m_paymentorder = M('Paymentorder');
				$payables = $m_payables->where('is_deleted <> 1 and payables_id = %d',$id)->find();
				$paymentorder_money = $m_paymentorder->where('is_deleted <> 1 and payables_id = %d',$payables['payables_id'])->sum('money');
				//未付款金额
				$sub_money = $payables['price']-$paymentorder_money;
				//开户行
				$this->bank_list = M('bank_account')->select();
				$this->assign('paymentorder_money',$paymentorder_money);
				$this->assign('sub_money',$sub_money);
				$this->assign('payables',$payables);
				$this->display('paymentorderadddialog');
				break;
			case 'receivablesplan' :
				//应收款计划
				$receivables_id = intval($_GET['receivables_id']);
				$m_receivables = M('Receivables');
				$price = $m_receivables->where('is_deleted <> 1 and receivables_id = %d',$receivables_id)->getField('price');
				//已回款金额
				$sum_money = M('Receivingorder')->where(array('receivables_id'=>$receivables_id,'status'=>array('eq',1),'is_deleted'=>0))->sum('money');
				$sub_price = $price-$sum_money;
				$data['receivables_id'] = $receivables_id;
				$data['price'] = $sub_price;
				$this->assign('data',$data);
				$this->display('receivables_plan_adddialog');
				break;
		}
	}
	//审核
	public function check(){
		$type = $this->_post('t','trim');
		$submit = $this->_post('submit1','trim');
		$description = $this->_post('description','trim');
		if($type == 'receivingorder'){
			$receivingorder_id = $this->_post('receivingorder_id','intval');
			$m_receivingorder = M('receivingorder');
			$m_receivables = M('Receivables');
			if(!$receivingorder_id){
				alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
			}
			if(!$receivingorder = $m_receivingorder->where('is_deleted = 0 and receivingorder_id = %d', $receivingorder_id)->find()){
				alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
			}
			if($receivingorder['status'] == 0){
				if($submit == 'agree'){
					$data['status'] = 1;
				}elseif($submit == 'deny'){
					$data['status'] = 2;
				}else{
					alert('error', '请求错误!', $_SERVER['HTTP_REFERER']);
				}
				$data['examine_role_id'] = session('role_id');
				$data['check_des'] = $description;
				$data['update_time'] = time();
				$data['check_time'] = time();
				$result = $m_receivingorder->where('receivingorder_id = %d', $receivingorder_id)->save($data);
				if($result){
					$r_money = $m_receivingorder->where('is_deleted <> 1 and status = 1 and receivables_id =%d',$receivingorder['receivables_id'])->sum('money');
					$price = $m_receivables->where('receivables_id =%d',$receivingorder['receivables_id'])->getField('price');
					if($r_money >= $price){
						$m_receivables->where('receivables_id =%d',$receivingorder['receivables_id'])->setField('status',2);
					}else{
						$m_receivables->where('receivables_id =%d',$receivingorder['receivables_id'])->setField('status',1);
					}
					
					//发送站内信
					$url = U('finance/view','t=receivingorder&id='.$receivingorder_id);
					sendMessage($receivingorder['creator_role_id'],'您创建的回款单《<a href="'.$url.'">'.$receivingorder['name'].'</a>》<font style="color:green;">已审核</font>！',1);
					alert('success', L('CHECK_SUCCESS'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', L('CHECK_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '审核失败，该单已审核过了', $_SERVER['HTTP_REFERER']);
			}
		}elseif($type == 'paymentorder'){
			$paymentorder_id = $this->_post('paymentorder_id','intval');
			$m_paymentorder = M('paymentorder');
			$m_payables = M('Payables');
			if(!$paymentorder_id){
				alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
			}
			if(!$paymentorder = $m_paymentorder->where('is_deleted = 0 and paymentorder_id = %d', $paymentorder_id)->find()) {
				alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
			}
			if($paymentorder['status'] == 0){
				if($submit == 'agree'){
					$data['status'] = 1;
				}elseif($submit == 'deny'){
					$data['status'] = 2;
				}else{
					alert('error', '请求错误!', $_SERVER['HTTP_REFERER']);
				}
				$data['examine_role_id'] = session('role_id');
				$data['check_des'] = $description;
				$data['update_time'] = time();
				$data['check_time'] = time();
				$result = $m_paymentorder->where('paymentorder_id = %d', $paymentorder_id)->save($data);
				if($result){
					//相关应付款状态
					$r_money = $m_paymentorder->where('is_deleted <> 1 and status = 1 and payables_id =%d',$paymentorder['payables_id'])->sum('money');
					$price = $m_payables->where('payables_id =%d',$paymentorder['payables_id'])->getField('price');
					if($r_money >= $price){
						$m_payables->where('payables_id =%d',$paymentorder['payables_id'])->setField('status',2);
					}else{
						$m_payables->where('payables_id =%d',$paymentorder['payables_id'])->setField('status',1);
					}
					
					//发送站内信
					$url = U('finance/view','t=paymentorder&id='.$paymentorder_id);
					sendMessage($paymentorder['creator_role_id'],'您创建的付款单《<a href="'.$url.'">'.$paymentorder['name'].'</a>》<font style="color:green;">已审核</font>！',1);
					alert('success', L('CHECK_SUCCESS'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', L('CHECK_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '审核失败，该单已审核过了', $_SERVER['HTTP_REFERER']);
			}
		}
	}

	/**
	*  撤销审核
	*
	**/
	public function revokeCheck(){
		$type = $this->_get('t','trim');
		if($type == 'receivingorder'){
			$receivingorder_id = $this->_get('id','intval');
			$m_receivingorder = M('receivingorder');
			$m_receivables = M('Receivables');
			if(!$receivingorder_id){
				$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
			}
			if(!$receivingorder = $m_receivingorder->where('receivingorder_id = %d', $receivingorder_id)->find()){
				$this->ajaxReturn('',L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),0);
			}

			if($receivingorder['status'] != 0){
				$result = $m_receivingorder->where('receivingorder_id = %d', $receivingorder_id)->setField('status',0);
				//相关应收款状态改变
				$sum_receivingorder_money = $m_receivingorder->where(array('receivables_id'=>$receivingorder['receivables_id'],'is_deleted'=>0,'status'=>1))->sum('money'); //收款单总金额
				$receivables_price = $m_receivables->where('receivables_id = %d',$receivingorder['receivables_id'])->getField('price'); //应收款金额
				if(empty($sum_receivingorder_money)){
					$m_receivables->where('receivables_id = %d',$receivingorder['receivables_id'])->setField('status',0);
				}else{
					if($receivables_price > $sum_receivingorder_money){
						$m_receivables->where('receivables_id = %d',$receivingorder['receivables_id'])->setField('status',1);
					}
				}
				
				if($result){
					//发送站内信
					$url = U('finance/view','t=receivingorder&id='.$receivingorder_id);
					sendMessage($receivingorder['creator_role_id'],'您创建的回款单《<a href="'.$url.'">'.$receivingorder['name'].'</a>》<font style="color:red;">已被撤销审核</font>！',1);
					$this->ajaxReturn('',L('REVOKE_CHECK_SUCCESS'),1);
				}else{
					$this->ajaxReturn('',L('REVOKE_CHECK_FAILED'),0);
				}
			}else{
				$this->ajaxReturn('','该单无须撤销审核！',0);
			}
		}elseif($type == 'paymentorder'){
			$paymentorder_id = $this->_get('id','intval');
			$m_paymentorder = M('Paymentorder');
			$m_payables = M('Payables');
			if(!$paymentorder_id){
				$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
			}
			if(!$paymentorder = $m_paymentorder->where('paymentorder_id = %d', $paymentorder_id)->find()){
				$this->ajaxReturn('',L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),0);
			}

			if($paymentorder['status'] != 0){
				$result = $m_paymentorder->where('paymentorder_id = %d', $paymentorder_id)->setField('status',0);
				
				//改变应付款状态
				$payables_price = $m_payables->where('payables_id = %d',$paymentorder['payables_id'])->getField('price');
				$sum_paymentorder_price = $m_paymentorder->where(array('payables_id'=>$paymentorder['payables_id'],'is_deleted'=>0,'status'=>array('eq',1)))->sum('money');
				if(empty($sum_paymentorder_price)){
					$m_payables->where('payables_id = %d',$paymentorder['payables_id'])->setField('status',0);
				}else{
					if(intval($payables_price) > intval($sum_paymentorder_price)){
						$m_payables->where('payables_id = %d',$paymentorder['payables_id'])->setField('status',1);
					}
				}
				
				if($result){
					//发送站内信
					$url = U('finance/view','t=paymentorder&id='.$paymentorder_id);
					sendMessage($paymentorder['creator_role_id'],'您创建的付款单《<a href="'.$url.'">'.$paymentorder['name'].'</a>》<font style="color:red;">已被撤销审核</font>！',1);
					$this->ajaxReturn('',L('REVOKE_CHECK_SUCCESS'),1);
				}else{
					$this->ajaxReturn('',L('REVOKE_CHECK_FAILED'),0);
				}
			}else{
				$this->ajaxReturn('','该单无须撤销审核！',0);
			}
		}else{
			$this->ajaxReturn('','参数错误！',0);
		}
	}

	/**
	 * 根据receivables_id获取应收金额
	 *
	 **/
	public function getReceivablesMoney(){
		$receivables_id = intval($_GET['id']);
		if ($receivables_id) {
			//应收款总额
			$receivables_price = M('Receivables')->where('receivables_id = %d', $receivables_id)->getField('price');
			if (empty($receivables_price)) {
				$receivables_price = 0;
			}
			//已收款金额
			$receivingorder = M('Receivingorder')->where('receivables_id = %d and status = 1', $receivables_id)->sum('money');
			if (empty($receivingorder)) {
				$receivingorder = 0;
			}
			$this->ajaxReturn(array('total'=>$receivables_price, 'receivingorder'=>$receivingorder),'',1);
		}
	}
	
	/**
	 * 根据payables_id获取应付金额
	 *
	 **/
	public function getPayablesMoney(){
		$payables_id = intval($_GET['id']);
		if ($payables_id) {
			//应收款总额
			$payables_price = M('Payables')->where('payables_id = %d', $payables_id)->getField('price');
			if (empty($payables_price)) {
				$payables_price = 0;
			}
			//已收款金额
			$paymentorder = M('Paymentorder')->where('payables_id = %d and status = 1', $payables_id)->sum('money');
			if (empty($paymentorder)) {
				$paymentorder = 0;
			}
			$this->ajaxReturn(array('total'=>$payables_price, 'paymentorder'=>$paymentorder),'',1);
		}
	}
	
	/**
	*财务统计页面
	*
	**/
	public function analytics(){
		if(!checkPerByAction(MODULE_NAME, ACTION_NAME)){
			alert('error',L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
		}
	
		$m_shoukuan = M('receivables');
		$m_shoukuandan = M('receivingorder');
		$m_fukuan = M('payables');
		$m_fukuandan = M('paymentorder');
		
		if(intval($_GET['role'])){
			$role_id_array = array(intval($_GET['role']));
		}else{
			if(intval($_GET['department'])){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			}else{
				$role_array = getPerByAction(MODULE_NAME,ACTION_NAME,false);
				$role_id_array = $role_array;
				//$role_id_array = getSubRoleId(true, 1);
			}
		}
		//时间段搜索
		if($_GET['select_type'] == 1){
			$start_time = strtotime(date('Y-m-01 00:00:00'));
			$end_time = strtotime(date('Y-m-d H:i:s'));
		}elseif($_GET['select_type'] == 2){
			$month=date('m');
			if($month==1 || $month==2 ||$month==3){
				$start_time = strtotime(date('Y-01-01 00:00:00'));
				$end_time = strtotime(date("Y-03-31 23:59:59"));
			}elseif($month==4 || $month==5 ||$month==6){
				$start_time = strtotime(date('Y-04-01 00:00:00'));
				$end_time = strtotime(date("Y-06-30 23:59:59"));
			}elseif($month==7 || $month==8 ||$month==9){
				$start_time = strtotime(date('Y-07-01 00:00:00'));
				$end_time = strtotime(date("Y-09-30 23:59:59"));
			}else{
				$start_time = strtotime(date('Y-10-01 00:00:00'));
				$end_time = strtotime(date("Y-12-31 23:59:59"));
			}
		}elseif($_GET['select_type'] == 3){
			$start_time = strtotime(date('Y-01-01 00:00:00'));
			$end_time = time();
		}elseif($_GET['select_type'] == 4){
			if($_GET['start_time']){
				$start_time = strtotime(date('Y-m-d',strtotime($_GET['start_time'])));
			}
			$end_time = $_GET['end_time'] ?  strtotime(date('Y-m-d 23:59:59',strtotime($_GET['end_time']))) : strtotime(date('Y-m-d 23:59:59',time()));
		}elseif($_GET['select_type'] == 5){
			$year = date('Y')-1;
			$start_time = strtotime(date($year.'-01-01 00:00:00'));
			$end_time = strtotime(date('Y-01-01 00:00:00'));
		}else{
			if($_GET['start_time']){
				$start_time = strtotime(date('Y-m-d',strtotime($_GET['start_time'])));
			}
			$end_time = $_GET['end_time'] ? strtotime(date('Y-m-d 23:59:59',strtotime($_GET['end_time']))) : strtotime(date('Y-m-d 23:59:59',time()));
		}

		$where_shoukuan['is_deleted'] = array('eq', 0);
		$where_shoukuan['owner_role_id'] = array('in', $role_id_array);
		$year = date('Y');
		$moon = 1;
		$shoukuan_moon_count = array();
		$fukuan_moon_count = array();
		$shijishoukuan_moon_count = array();
		$shijifukuan_moon_count = array();
		while ($moon <= 12){
			if($moon == 12) {
				$where_shoukuan['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
			} else {
				$where_shoukuan['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
			}
			$shoukuanList = $m_shoukuan->where($where_shoukuan)->select();
			$fukuanList = $m_fukuan->where($where_shoukuan)->select();
			$total_shoukuan_money = 0;
			$total_shijishoukuan_money = 0;
			foreach($shoukuanList as $v){
				$total_shoukuan_money += $v['price'];
				$shoukuandan_list = $m_shoukuandan->where('receivables_id = %d and is_deleted = 0', $v['receivables_id'])->getField('money', true);
				foreach($shoukuandan_list as $v2) {
					$total_shijishoukuan_money += $v2;
				}
			}

			$total_fukuan_money = 0;
			$total_shijifukuan_money = 0;
			foreach($fukuanList as $v){
				$total_fukuan_money += $v['price'];
				$fukuandan_list = $m_fukuandan->where('payables_id = %d', $v['payables_id'])->getField('money', true);
				foreach($fukuandan_list as $v2) {
					$total_shijifukuan_money += $v2;
				}
			}

			$shoukuan_moon_count[] = $total_shoukuan_money;
			$shijishoukuan_moon_count[] = $total_shijishoukuan_money;
			$fukuan_moon_count[] = $total_fukuan_money;
			$shijifukuan_moon_count[] = $total_shijifukuan_money;
			$moon ++;
		}
		$moon_count['shoukuan'] = '['.implode(',', $shoukuan_moon_count).']';
		$moon_count['shijishoukuan'] = '['.implode(',', $shijishoukuan_moon_count).']';
		$moon_count['fukuan'] = '['.implode(',', $fukuan_moon_count).']';
		$moon_count['shijifukuan'] = '['.implode(',', $shijifukuan_moon_count).']';
		$this->moon_count = $moon_count;
		
		$previous_year = $year-1;
		$moon = 1;
		$shoukuan_thisyear_count = array();
		$shoukuan_previousyear_count = array();
		$fukuan_thisyear_count = array();
		$fukuan_previousyear_count = array();
		while ($moon <= 12){
			if($moon == 12) {
				$where_thisyear_shoukuan['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
				
				$where_previousyear_shoukuan['pay_time'] = array(array('egt', strtotime($previous_year.'-'.$moon.'-1')), array('lt', strtotime(($previous_year+1).'-1-1')), 'and');
			} else {
				$where_thisyear_shoukuan['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
				$where_previousyear_shoukuan['pay_time'] = array(array('egt', strtotime($previous_year.'-'.$moon.'-1')), array('lt', strtotime($previous_year.'-'.($moon+1).'-1')), 'and');
			}
			$where_thisyear_shoukuan['owner_role_id'] = array('in', $role_id_array);
			$where_previousyear_shoukuan['owner_role_id'] = array('in', $role_id_array);
			$thisyear_shoukuanList = $m_shoukuan->where($where_thisyear_shoukuan)->select();
			$previousyear_shoukuanList = $m_shoukuan->where($where_previousyear_shoukuan)->select();
			$thisyear_fukuanList = $m_fukuan->where($where_thisyear_shoukuan)->select();
			$previousyear_fukuanList = $m_fukuan->where($where_previousyear_shoukuan)->select();
			
			$total_thisyear_shoukuan_count = 0;
			$total_previousyear_shoukuan_count = 0;
			foreach($thisyear_shoukuanList as $v){
				$total_thisyear_shoukuan_count += $v['price'];
			}
			foreach($previousyear_shoukuanList as $v){
				$total_previousyear_shoukuan_count += $v['price'];
			}
			$shoukuan_thisyear_count[] = $total_thisyear_shoukuan_count;
			$shoukuan_previousyear_count[] = $total_previousyear_shoukuan_count;
			
			$total_thisyear_fukuan_count = 0;
			$total_previousyear_fukuan_count = 0;
			foreach($thisyear_fukuanList as $v){
				$total_thisyear_fukuan_count += $v['price'];
			}
			foreach($previousyear_fukuanList as $v){
				$total_previousyear_fukuan_count += $v['price'];
			}
			$fukuan_thisyear_count[] = $total_thisyear_fukuan_count;
			$fukuan_previousyear_count[] = $total_previousyear_fukuan_count;
			
			$moon ++; 
		}
		
		$year_count['shoukuan_previousyear'] = '['.implode(',', $shoukuan_previousyear_count).']';
		$year_count['shoukuan_thisyear'] = '['.implode(',', $shoukuan_thisyear_count).']';
		$year_count['fukuan_previousyear'] = '['.implode(',', $fukuan_previousyear_count).']';
		$year_count['fukuan_thisyear'] = '['.implode(',', $fukuan_thisyear_count).']';
		$this->year_count = $year_count;

		if($start_time){
			$create_time= array(array('elt',$end_time),array('egt',$start_time), 'and');
		}else{
			if($end_time){
				$create_time = array('elt',$end_time);
			}else{
				$create_time = array('egt',0);
			}
		}
		//合同数、合同总金额、已回款金额、剩余回款金额
		$reportList = array();
		$contract_count_total = 0;
		$contract_price_total = 0;
		$receivingorder_price_total = 0;
		$surplus_price_total = 0;
		$m_contract = M('Contract');
		foreach($role_id_array as $v){
			$user = getUserByRoleId($v);
			$contract_count = 0;
			$contract_price = 0;
			$receivingorder_price = 0;
			$surplus_price = 0;
			//合同
			$contract_list = array();
			$contract_list = $m_contract->where(array('owner_role_id'=>$v,'is_checked'=>1,'create_time'=>$create_time))->field('contract_id,price')->select();
			foreach($contract_list as $key=>$val){
				$contract_price += $val['price'];
			}
			$contract_count = $contract_list ? count($contract_list) : '0';
			// $contract_price = round($contract_price,2);
			//收款
			$receivables_id_array = array();
			$receivables_id_array = $m_shoukuan->where(array('is_deleted'=>0, 'owner_role_id'=>$v, 'pay_time'=>$create_time))->getField('receivables_id',true);
			//回款
			$receivingorder_price = $m_shoukuandan->where(array('status'=>1,'is_deleted'=>0,'receivables_id'=>array('in',$receivables_id_array)))->sum('money');
			// $receivingorder_price = round($receivingorder_price,2);
			//剩余回款
			$surplus_price = $contract_price-$receivingorder_price;
			$reportList[] = array("user"=>$user,"contract_count"=>$contract_count,"contract_price"=>$contract_price,"receivingorder_price"=>$receivingorder_price,"surplus_price"=>$surplus_price);
			//合计
			$contract_count_total += $contract_count;
			$contract_price_total += $contract_price;
			$receivingorder_price_total += $receivingorder_price;
			$surplus_price_total += $surplus_price;
		}
		$total_report = array("contract_count_total"=>$contract_count_total,"contract_price_total"=>$contract_price_total,"receivingorder_price_total"=>$receivingorder_price_total,"surplus_price_total"=>$surplus_price_total);

		$this->reportList = $reportList;
		$this->total_report = $total_report;
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		$idArray = $below_ids;
		//$idArray = getSubRoleId(true, 1);
		$roleList = array();
		foreach($idArray as $roleId){				
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		//$departmentList = M('roleDepartment')->select();
		$this->assign('departmentList', $departmentList);
		$this->alert = parseAlert();
		$this->type_id = intval($_GET['type_id']);
		$this->content_id = intval($_GET['content_id']);
		$this->display();
	}
	
	/**
	 * 首页应收款月度统计
	 * @ level 0:自己的数据  1:自己和下属的数据
	 **/
	public function getmonthlyreceive(){
		$m_receivables = M('receivables');
		$m_payables = M('payables');
		$dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widget = unserialize($dashboard);
		$id = intval($_GET['id']);
		foreach($widget['dashboard'] as $k=>$v){
			if($v['widget'] == 'Receivemonthly' && $v['id'] == $id){
				if($v['level'] == '1'){ 
					if(session('?admin')){
						$where['owner_role_id'] = array('in',getSubRoleId(true,1));
					}else{
						$where['owner_role_id'] = array('in',getSubRoleId());
					}
				}else{
					$where['owner_role_id'] = array('eq', session('role_id'));
				}
			}
		}
		
		$year = date('Y');
		$moon = 1;
		$not_receive = array();//应收款
		$have_received = array();//实际收款
		$not_pay = array();//应付款
		$have_paid = array();//实际付款
		$where['is_deleted'] = array('eq', 0);
		while ($moon <= 12){
			if($moon == 12) {
				$where['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
			} else {
				$where['pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
			}
	
			$not_receiveList = $m_receivables->where($where)->select();//应收款数组
			$monthly_not_receive = 0;
			foreach($not_receiveList as $v){
				$monthly_not_receive = floatval(bcadd($monthly_not_receive, $v['price'], 2));//单月应收款总额
			}
			$not_receive[] = $monthly_not_receive;
			
			$condition = $where;
			$condition['status'] = array('neq', 0);
			$have_receivedList = $m_receivables->where($condition)->select();//(部分)已收款数组
			$monthly_have_received = 0;
			foreach($have_receivedList as $v){
				$monthly_have_received += M('receivingorder')->where('receivables_id = %d and is_deleted = 0',$v['receivables_id'])->sum('money');//单月实收款总额
			}
			$have_received[] = $monthly_have_received;
			
			$not_payList = $m_payables->where($where)->select();//应付款数组
			$monthly_not_pay = 0;
			foreach($not_payList as $v){
				$monthly_not_pay = floatval(bcadd($monthly_not_pay, $v['price'], 2));//单月实收款总额
			}
			$not_pay[] = $monthly_not_pay;
			
			$have_paidList = $m_payables->where($condition)->select();//(部分)已收款数组
			$monthly_have_paid = 0;
			foreach($have_paidList as $v){
				$monthly_have_paid += M('paymentorder')->where('payables_id = %d and is_deleted = 0',$v['payables_id'])->sum('money');//单月实收款总额
			}
			$have_paid[] = $monthly_have_paid;
			
			$moon ++;
		}
		$financeDate['not_receive'] = $not_receive;
		$financeDate['have_received'] = $have_received;
		$financeDate['not_pay'] = $not_pay;
		$financeDate['have_paid'] = $have_paid;
		$this->ajaxReturn($financeDate,'success',1);
	}
	
	/**
	 * 首页应收款年度对比统计
	 * @ level 0:自己的数据  1:自己和下属的数据
	 **/
	public function getYearReceiveComparison(){
		//$m_receivables = M('receivables');
		$m_receivables = D('ReceivablesView');
		$dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widget = unserialize($dashboard);
		$id = intval($_GET['id']);
		foreach($widget['dashboard'] as $k=>$v){
			if($v['widget'] == 'Receiveyearcomparison' && $v['id'] == $id){
				if($v['level'] == '1'){
					if(session('?admin')){
						$where['receivables.owner_role_id'] = array('in',getSubRoleId(true, 1));
					}else{
						$where['receivables.owner_role_id'] = array('in',getSubRoleId());
					}
				}else{
					$where['receivables.owner_role_id'] = array('eq', session('role_id'));
				}
			}
		}
		$year = date('Y');
		$prev_year = $year-1;
		$moon = 1;
		$receive_this_year_money = array();
		$receive_prev_year_money = array();
		$where['receivables.is_deleted'] = array('eq', 0);
		$where_this_year = $where;
		$where_prev_year = $where;
		while ($moon <= 12){
			if($moon == 12) {
				$where_this_year['receivables.pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
				$where_prev_year['receivables.pay_time'] = array(array('egt', strtotime($prev_year.'-'.$moon.'-1')), array('lt', strtotime(($year).'-1-1')), 'and');
			}else{
				$where_this_year['receivables.pay_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
				$where_prev_year['receivables.pay_time'] = array(array('egt', strtotime($prev_year.'-'.$moon.'-1')), array('lt', strtotime($prev_year.'-'.($moon+1).'-1')), 'and');
			}

			$receive_this_year_price = $m_receivables->where($where_this_year)->sum('receivables.price');//今年月度收款金额总和
			$receive_prev_year_price = $m_receivables->where($where_prev_year)->sum('receivables.price');//去年月度收款金额总和
			$receive_this_year_money[] = empty($receive_this_year_price) ? 0 : round($receive_this_year_price,2);
			$receive_prev_year_money[] = empty($receive_prev_year_price) ? 0 : round($receive_prev_year_price,2);
			$moon ++; 
		}
		$total_money = array('this_year'=>$receive_this_year_money, 'prev_year'=>$receive_prev_year_money);
		$this->ajaxReturn($total_money,'success',1);
	}
	
	//财务统计高级搜索
	public function advance_search(){
		$module_name = trim($_GET['module_name']);
		$action_name = trim($_GET['action_name']);
		$idArray = getPerByAction($module_name,$action_name,false);
		//$idArray = getSubRoleId(true, 1);
		$roleList = array();
		foreach($idArray as $roleId){
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		$url = getCheckUrlByAction($module_name,$action_name);
		$per_type =  M('Permission') -> where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		$this->assign('departmentList', $departmentList);
		$this->type_id = intval($_GET['type_id']);
		$this->content_id = intval($_GET['content_id']);
		$this->display();
	}

	/**
	 * 财务导出
	 * @param 
	 * @author 
	 * @return 
	 */
	public function excelExport($financeList=false,$type){
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Konwledge");    
		$objProps->setSubject("mxcrm Konwledge Data");    
		$objProps->setDescription("mxcrm Konwledge Data");    
		$objProps->setKeywords("mxcrm Konwledge");    
		$objProps->setCategory("mxcrm");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 

		$type = $type ? trim($type) : 'receivables';

		$excel_title = array();
		$field_arr = array();
		switch ($type) {
			case 'receivingorder': 
				$excel_title = array('回款单编号','客户名称','合同编号','合同金额(元)','回款金额(元)','剩余金额(元)','回款时间','负责人','状态','相关产品');
				break;
		}
		$j = 0;
		foreach($excel_title as $title){
			$pCoordinate = PHPExcel_Cell::stringFromColumnIndex($j); //生成Excel
			$objActSheet->setCellValue($pCoordinate.'1', $title);
			$j++;
        }
		$list = $financeList;
		
		$i = 1;
		switch ($type) {
			case 'receivingorder' :
				foreach ($list as $k => $v) {
					$i++;
					$objActSheet->setCellValue('A'.$i, $v['name']);
					$objActSheet->setCellValue('B'.$i, $v['customer_name']);
					$objActSheet->setCellValue('C'.$i, $v['contract']['number']);
					$objActSheet->setCellValue('D'.$i, $v['contract']['price']);
					$objActSheet->setCellValue('E'.$i, $v['money']);
					$objActSheet->setCellValue('F'.$i, $v['un_receivingorder_price']);
					$objActSheet->setCellValue('G'.$i, date("Y-m-d", $v['pay_time']));
					$objActSheet->setCellValue('H'.$i, $v['owner']['full_name']);
					$objActSheet->setCellValue('I'.$i, $v['status_name']);
					$objActSheet->setCellValue('J'.$i, $v['product_name']);
				}
			break;
		}
		
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_receivingorder_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
		session('export_status', 0);
	}
	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);
		
	}
}