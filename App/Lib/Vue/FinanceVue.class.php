<?php 
/**
*财务模块
*
**/
class FinanceVue extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('getcode','check_list')
		);
		$this->type = $_POST['t'] ? trim($_POST['t']) : 'receivables';
		if (!in_array($this->type,array('receivables','receivingorder'))) {
			$this->ajaxReturn('','参数错误！',0);
		}
		B('VueAuthenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type);

		Global $role;
		$this->role = $role;
		Global $roles;
		$this->roles = $roles;

		if($roles == 2){
			$this->ajaxReturn('','您没有此权限！',-2);
		}

		if($roles == 3){
			$this->ajaxReturn('','请先登录！',-1);
		}
	}

	/**
	 * 获取应收款、回款添加时相关信息
	 * @param 
	 * @author 
	 * @return 
	 */
	public function getCode() {
		if ($this->isPost()) {
			switch ($this->type) {
				case 'receivables' :
					//应收款编号
					$m_config = M('Config');
					$m_receivables = M('Receivables');
					$receivables_custom = $m_config->where(array('name'=>'receivables_custom'))->getField('value');
					$receivables_max_id = $m_receivables->max('receivables_id');
					$receivables_max_id = $receivables_max_id+1;
					$receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
					$number = $receivables_custom.date('Ymd').'-'.$receivables_max_code;
					$data['data'] = $number ? $number : '';
					$data['status'] = 1;
					$data['info'] = 'success';
					$this->ajaxReturn($data,'JSON');
				break;
				case 'receivingorder' :
					//收款账户信息
					$m_bank_account = M('bank_account');
					$bank_list = $m_bank_account ->select();
					$data['list'] = $bank_list ? $bank_list : array();
					$data['status'] = 1;
					$data['info'] = 'success';
					$this->ajaxReturn($data,'JSON');
				break;
			}
		}
	}
	
	/**
	 * 添加应收款、回款
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			switch ($this->type) {
				case 'receivables' :
					$m_config = M('Config');
					$receivables_custom = $m_config->where(array('name'=>'receivables_custom'))->getField('value');
					$fc_custom = $m_config->where(array('name'=>'fc_custom'))->getField('value');
					$m_receivables = M('Receivables');

					if ($m_receivables->create()) {
						if(empty($_POST['customer_id'])){
							$this->ajaxReturn('','请选择客户',0);
						} 
						$m_receivables->type = 1;
						$m_receivables->price = round($_POST['price'], 2);

						$receivables_max_id = $m_receivables->max('receivables_id');
						$receivables_max_id = $receivables_max_id+1;
						$receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
						$m_receivables->name = $receivables_custom.date('Ymd').'-'.$receivables_max_code;
						$m_receivables->prefixion = $receivables_custom;

						$m_receivables->pay_time = $_POST['pay_time'] ? $_POST['pay_time'] : time();
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
								$this->ajaxReturn('','创建成功！',1);
							}
						}else{
							$this->ajaxReturn('','创建失败，请重试！',0);
						}
					} else {
						$this->ajaxReturn('','创建失败，请重试！',0);
					}
					break;
				case 'receivingorder' :
					$m_receivingorder = M('Receivingorder');
					$m_receivables = M('Receivables');
					//收款单编号
					$receivingorder_max_id = $m_receivingorder->max('receivingorder_id');
					$receivingorder_max_id = $receivingorder_max_id+1;
					$receivingorder_max_code = str_pad($receivingorder_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
					$code = date('Ymd').'-'.$receivingorder_max_code;
					if ($m_receivingorder->create()) {
						$m_receivingorder->name = $code;
						$receivables_id = intval($_POST['receivables_id']);
						$owner_role_id = intval($_POST['owner_role_id']);
						if (!$receivables_id) {
							$this->ajaxReturn('',L('PLEASE_SELECT_RECEIVABLES'),0);
						}
						if (!$owner_role_id) {
							$this->ajaxReturn('',L('PLEASE_SELECT_THE_PERSON_IN_CHARGE'),0);
						}
						$m_receivingorder->pay_time = $_POST['pay_time'] ? $_POST['pay_time'] : time();
						$m_receivingorder->creator_role_id = session('role_id');
						$m_receivingorder->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
						$m_receivingorder->create_time = time();
						$m_receivingorder->update_time = time();
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
							$this->ajaxReturn('',L('Bank Account Error'),0);
						}
						//合同ID
						if($_POST['contract_id']){
							$m_receivingorder->contract_id = intval($_POST['contract_id']);
						}else{
							$m_receivingorder->contract_id = (int)$m_receivables->where('receivables_id = %d', $receivables_id)->getField('contract_id');
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
								sendMessage($v,$form_role_info['full_name'].'&nbsp;&nbsp;创建了新的回款单《<a href="'.$url.'">'.$_POST['name'].'</a>》<font style="color:green;">需要进行审核</font>！',1);
							}

							//应收款
							$receivables_info = $m_receivables->where(array('receivables_id'=>$receivables_id))->find();
							$money_sum = $m_receivingorder->where(array('receivables_id'=>$receivables_id,'status'=>1))->sum('money');
							//修改回款单状态
							if($money_sum >= $receivables_info['price']){
								if($receivables_info['status'] != 2){
									$m_receivables->where(array('receivables_id'=>$receivables_id))->setField('status',2);
								}
							}elseif($money_sum > 0){
								$m_receivables->where(array('receivables_id'=>$receivables_id))->save(array('status'=>1));
							}
							$this->ajaxReturn('','创建成功！',1);
						}else{
							$this->ajaxReturn('','创建失败，请重试！',0);
						}
					} else {
						$this->ajaxReturn('','创建失败，请重试！',0);
					}
				break;
			}
		}		
	}

	/**
	 * 应收款、回款列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			$where = array();
			$order = "";
			if ($_POST['desc_order']) {
				$order = trim($_POST['desc_order']).' desc';
			} elseif ($_POST['asc_order']) {
				$order = trim($_POST['asc_order']).' asc';
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$by = trim($_POST['by']) ? trim($_POST['by']) : '';
			$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type,true);
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
				
				if($field == "receivables.customer_id"){
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
				//过滤不在权限范围内的role_id
				if(trim($_REQUEST['field']) == 'owner_role_id'){
					if(!in_array(trim($search),$below_ids)){
						$where['owner_role_id'] = array('in',$below_ids);
					}
				}
			}
			$order = empty($order) ? $this->type . '.create_time desc' : $order;

			//列表返回权限（添加）
			$permission_list = apppermission(MODULE_NAME,$this->type);
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}
			switch ($this->type) {
				case 'receivables' :
					$d_receivables = D('ReceivablesView');
					//高级搜索
					if(!$_POST['field']){
						$fields_search = array();
						foreach($_POST as $kd => $vd){
							if ($kd != 'act' && $kd != 'content' && $kd != 'p' && $kd != 'search' && $kd != 't' && $kd != 'type' && $kd != 'by' && $kd != 'listrows' && $kd != 'r_status' && $kd != 'token') {
								if(in_array($kd,array('create_time','update_time','pay_time'))){
									$where[$kd] = field($vd['value'], $vd['condition']);

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
									}
								}elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
									if(!empty($vd)){
										$where[$this->type .'.'.$kd] = $vd['value'];
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
									}
								}else{
									if(is_array($vd)) {
										if(!empty($vd['value'])){
											$where[$kd] = field($vd['value'], $vd['condition']);
										}
									}else{
										if(!empty($vd)){
											$where[$kd] = field($vd);
										} 
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
							if(is_array($where[$this->type . '.owner_role_id']) && !empty($where[$this->type . '.owner_role_id']['1']) && !in_array(intval($where[$this->type . '.owner_role_id']['1']),$below_ids)){
								$where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
							}
						}
					}
					if($_POST['type'] == '3' || $_POST['type'] == ''){
						$where['receivables.status'] = array('lt',2); 
					}else if($_POST['type'] == '2'){
						$where['receivables.status'] = 2; 
					}
					unset($where['type']);
					$count = $d_receivables->where($where)->count();
					//应收款提醒
					if($_POST['r_status'] == 1){
						$receivables_time = M('config')->where('name="receivables_time"')->getField('value');
						$f_outdate = empty($receivables_time) ? 0 : time()-86400*$receivables_time;
						$where['pay_time'] = array('elt',time()+$f_outdate);
						$where['status'] = array('lt',2);
					}
					$list = $d_receivables->where($where)->order($order)->page($p.',10')->field('receivables_id,name,pay_time,price')->select();
					$m_receivingorder = M('Receivingorder');
					foreach($list as $k=>$v){
						$money += $v['price'];
						$done_money = $m_receivingorder->where('is_deleted <> 1 and receivables_id = %d and status = 1', $v['receivables_id'])->sum('money');
						// $list[$k]['un_payable'] = $v['price'] - $done_money;
						$list[$k]['un_payable'] = $done_money ? $done_money : '0.00';
						//当前收款进度
						$schedule = 0;
						if($done_money){
							if($v['price'] == 0 || $v['price'] == ''){
								$schedule = 100;
							}else{
								$schedule = round(($done_money/$v['price'])*100,2);
							}
						}
						$list[$k]['schedule'] = $schedule;
					}

					$page = ceil($count/10);
					$data['list'] = $list ? $list : array();
					$data['page'] = $page;
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
					break;
				
				case 'receivingorder' :
					$d_receivingorder = D('ReceivingorderView');
					//高级搜索
					if(!$_POST['field']){
						foreach($_POST as $kd => $vd){
							if ($kd != 'act' && $kd != 'content' && $kd != 'p' && $kd != 'search' && $kd != 't' && $kd != 'type' && $kd != 'by' && $kd != 'listrows' && $kd != 'token') {
								if(in_array($kd,array('create_time','update_time','pay_time'))){
									$where[$kd] = field($vd['value'], $vd['condition']);

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
									}
								}elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
									if(!empty($vd)){
										$where[$this->type .'.'.$kd] = $vd['value'];
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
									}
								}else{
									if(is_array($vd)) {
										if(!empty($vd['value'])){
											$where[$kd] = field($vd['value'], $vd['condition']);
										}
									}else{
										if(!empty($vd)){
											$where[$kd] = field($vd);
										} 
									}
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
						if(is_array($where[$this->type . '.owner_role_id']) && !empty($where[$this->type . '.owner_role_id']['1']) && !in_array(intval($where[$this->type . '.owner_role_id']['1']),$below_ids)){
							$where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
						}
					}
					//商机下回款
					if ($_POST['business_id']) {
						$contract_ids = M('RBusinessContract')->where('business_id = %d',intval($_POST['business_id']))->getField('contract_id',true);
						$where['contract_id'] =  $contract_ids ? array('in',$contract_ids) : '';
						unset($where['business_id']);
					}
					//客户下回款
					if ($_POST['customer_id']) {
						$contract_ids = M('Contract')->where(array('customer_id'=>intval($_POST['customer_id'])))->getField('contract_id',true);
						$where['contract_id'] =  array('in',$contract_ids);
						unset($where['customer_id']);
					}
					//应收款下回款
					if ($_POST['receivables_id']) {
						$where['receivables_id'] = intval($_POST['receivables_id']);
						$receivables_price = M('Receivables')->where(array('receivables_id'=>intval($_POST['receivables_id'])))->getField('price');
					}
					$count = $d_receivingorder->where($where)->count();
					$list = $d_receivingorder->where($where)->order($order)->page($p.',10')->field('receivingorder_id,name,pay_time,money,receivables_id,status,owner_role_id,receipt_account,receipt_bank,company,description')->select();
					$m_user = M('User');

					$receivingorder_money = 0; //已收款金额
					$un_receivables_money = 0; //未收款金额

					foreach($list as $k=>$v){
						switch ($v['status']) {
							case 0 : $status_name = '待审'; break;
							case 1 : $status_name = '通过'; break;
							case 2 : $status_name = '驳回'; break;
							default : $status_name = '待审'; break;
						}
						$list[$k]['status_name'] = $status_name;
						$list[$k]['owner_name'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->getField('full_name');
						if($v['status'] == 1){
							//计算已结账状态的金额
							$receivingorder_money += $v['money'];
						}
					}
					//查询应收款下回款，返回合计
					if ($_POST['receivables_id']) {
						$un_receivables_money = ($receivables_price - $receivingorder_money) < 0 ? 0 : ($receivables_price - $receivingorder_money);
					}
					$data['receivingorder_money'] = $receivingorder_money;
					$data['un_receivables_money'] = $un_receivables_money;
					
					$page = ceil($count/10);
					$data['list'] = $list ? $list : array();
					$data['page'] = $page;
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
				break;
			}
		}
	}

	/**
	 * 应收款、回款详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$m_user = M('User');
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			if($id == 0) {
				$this->ajaxReturn('','参数错误！',0);
			}
			switch ($this->type) {
				case 'receivables' :
					//应收款
					$d_receivables = D('ReceivablesView');
					$m_receivingorder = M('Receivingorder');
					
					$info = $d_receivables->where(array('receivables_id'=>$id))->find();
					if(empty($info)){
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					// $receivingorder_list = $m_receivingorder->where(array('is_deleted'=>array('neq',1),'receivables_id'=>$id))->select();
					// $receivingorder_money = 0; //已收款金额
					// $un_receivables_money = 0; //未收款金额
					// foreach($receivingorder_list as $k=>$v){
					// 	$receivingorder_list[$k]['owner_name'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->getField('full_name');
					// 	if($v['status'] == 1){
					// 		//计算已结账状态的金额
					// 		$receivingorder_money += $v['money'];
					// 	}
					// }
					// $info['receivingorder'] = $receivingorder_list;
					// $un_receivables_money = ($info['price'] - $receivingorder_money) < 0 ? 0 : ($info['price'] - $receivingorder_money);
					// $info['un_receivables_money'] = $un_receivables_money;
					$info['contract'] = M('Contract')->where('contract_id = %d', $info['contract_id'])->field('contract_id,number')->find();
					$info['owner_name'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
					//获取操作权限
					$data['permission'] = permissionlist(MODULE_NAME,$info['owner_role_id'],'receivingorder');

					$data['data'] = $info ? $info : array();
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
					break;
				case 'payables' :
					//应付款
					$d_payables = D('PayablesView');
					$m_paymentorder = M('Paymentorder');
					$info = $d_payables->where(array('payables_id'=>$id))->find();
					if(empty($info)){
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					// $paymentorder_list = $m_paymentorder->where('is_deleted <> 1 and payables_id = %d', $id)->select();
					// $sum_money = 0; //已付款金额
					// $un_payment = 0; //还剩多少金额未付款
					// foreach($paymentorder_list as $k=>$v){
					// 	$paymentorder_list[$k]['owner_name'] = $m_user->where(array('role_id'=>$v['owner_role_id']))->getField('full_name');
					// 	if($v['status'] == 1 ){
					// 		//计算已结账状态的金额
					// 		$sum_money += $v['money'];
					// 	}
					// }
					// $info['paymentorder'] = $paymentorder_list;
					// $info['un_payment'] = ($info['price'] - $sum_money) < 0 ? 0 : ($info['price'] - $sum_money);
					$info['owner_name'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
					$info['creator_name'] = $m_user->where(array('role_id'=>$info['creator_role_id']))->getField('full_name');
					//应付款类型
					$info['type_name'] = M('FinanceType')->where(array('id'=>$info['type_id']))->getField('name');
					//相关客户
					$info['customer_name'] = M('Customer')->where(array('customer_id'=>$info['customer_id']))->getField('name');
					//获取操作权限
					$data['permission'] = permissionlist(MODULE_NAME,$info['owner_role_id'],'receivingorder');

					$data['data'] = $info ? $info : array();
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
					break;
				case 'receivingorder' :
					//收款单
					$d_receivingorder = D('ReceivingorderView');
					$m_receivables = M('Receivables');
					$info = $d_receivingorder->where(array('receivingorder_id'=>$id))->find();
					$info['examine'] = getUserByRoleId($info['examine_role_id']);
					if(empty($info)){
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					$info['owner_name'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
					//获取操作权限
					$data['permission'] = permissionlist(MODULE_NAME,$info['owner_role_id'],'receivingorder');
					//审核权限
					$finance_check = M('Permission')->where(array('position_id'=>session('position_id'),'url'=>'finance/check'))->find();
					$data['permission']['check'] = 0;
					if ($finance_check || session('?admin')) {
						$data['permission']['check'] = 1;
					}
					$data['data'] = $info ? $info : array();
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
					break;
				case 'paymentorder' :
					//付款单
					$d_paymentorder = D('PaymentorderView');
					$info = $d_paymentorder->where(array('paymentorder_id'=>$id))->find();
					$info['examine'] = getUserByRoleId($info['examine_role_id']);
					if(empty($info)){
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					$info['owner'] = $m_user->where(array('role_id'=>$info['owner_role_id']))->field('full_name,thumb_path')->find();
					//获取操作权限
					$data['permission'] = permissionlist(MODULE_NAME,$info['owner_role_id'],'receivingorder');
					//审核权限
					$finance_check = M('Permission')->where(array('position_id'=>session('position_id'),'url'=>'finance/check'))->find();
					if ($finance_check || session('?admin')) {
						$data['permission']['check'] = 1;
					} else {
						$data['permission']['check'] = 0;
					}

					$data['data'] = $info ? $info : array();
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
					break;
			}
		}
	}

	/**
	 * 应收款、回款编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->isPost()) {
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			if ($id == 0) {
				$this->ajaxReturn('','参数错误！',0);
			}
			switch ($this->type) {
				case 'receivables' :
					//应收款
					$m_receivables = M('Receivables');
					$info = $m_receivables->where(array('receivables_id'=>$id))->find();
					if (empty($info)) {
						$this->ajaxReturn('','数据不存在或已删除！',0);
					} elseif (!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}

					if (!intval($_POST['owner_role_id'])) {
						$this->ajaxReturn('','请选择负责人！',0);
					}
					//判断编号是否唯一
					if ($m_receivables->where(array('name'=>trim($_POST['name']),'receivables_id'=>array('neq',$id)))->find()) {
						$this->ajaxReturn('','应收款编号已存在，请修改后重新提交！',0);
					}
					if ($m_receivables->create()) {
						$m_receivables->pay_time = $_POST['pay_time'] ? $_POST['pay_time'] : time();
						$m_receivables->update_time = time();
						if ($m_receivables->where(array('receivables_id'=>$id))->save()) {
							actionLog($id,'t=receivables');
							$this->ajaxReturn('','修改成功！',1);
						} else {
							$this->ajaxReturn('','修改失败！',0);
						}
					} else {
						$this->ajaxReturn('','修改失败！',0);
					}
					break;
				case 'payables' :
					//应付款
					$m_payables = M('Payables');
					$info = $m_payables->where(array('payables_id'=>$id))->find();
					if (empty($info)) {
						$this->ajaxReturn('','数据不存在或已删除！',0);
					} elseif (!in_array($info['owner_role_id'], $this->_permissionRes)) {
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					$info['owner_name'] = M('User')->where(array('role_id'=>$info['owner_role_id']))->getField('full_name');
					if ($info['customer_id']) {
						$info['customer_name'] = M('Customer')->where(array('customer_id'=>$info['customer_id']))->getField('name');
					}
					if (!intval($_POST['owner_role_id'])) {
						$this->ajaxReturn('','请选择负责人！',0);
					}
					//判断编号是否唯一
					if ($m_payables->where(array('name'=>trim($_POST['name']),'payables_id'=>array('neq',$id)))->find()) {
						$this->ajaxReturn('','应付款编号已存在，请修改后重新提交',0);
					}
					if ($m_payables->create()) {
						$m_payables->pay_time = $_POST['pay_time'] ? $_POST['pay_time'] : time();
						$m_payables->update_time = time();
						if($m_payables->where(array('payables_id'=>$id))->save()){
							actionLog($id,'t=payables');
							$this->ajaxReturn('','修改成功！',1);
						}else{
							$this->ajaxReturn('','修改失败！',0);
						}
					}else{
						$this->ajaxReturn('','修改失败！',0);
					}
					break;
				case 'receivingorder' :
					//回款单
					$m_receivingorder = M('Receivingorder');
					$m_receivables = M('Receivables');
					$info = $m_receivingorder->where(array('receivingorder_id'=>$id))->find();
					if(empty($info)){
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					if($info['status'] == 1) {
						$this->ajaxReturn('',L('THE RECEIVABLES ORDER HAS BEEN CLOSING'),0);
					}
					$receivables_id = intval($_POST['receivables_id']);
					if (!$receivables_id) {
						$this->ajaxReturn('',L('PLEASE_SELECT_PAYABLES'),0);
					}
					if (!intval($_POST['owner_role_id'])) {
						$this->ajaxReturn('','请选择负责人！',0);
					}
					if(!intval($_POST['account_id'])){
						$this->ajaxReturn('',L('Bank Account Error'),0);
					}
					//判断编号是否唯一
					if ($m_receivingorder->where(array('name'=>trim($_POST['name']),'receivingorder_id'=>array('neq',$id)))->find()) {
						$this->ajaxReturn('','收款单号已存在，请修改后重新提交',0);
					}
					if ($m_receivingorder->create()) {
						$m_receivingorder->status = 0;
						$m_receivingorder->check_des = '';
						$m_receivingorder->pay_time = $_POST['pay_time'] ? $_POST['pay_time'] : time();

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
							$this->ajaxReturn('','修改成功！',1);
						}else{
							$this->ajaxReturn('','修改失败，请重试！',0);
						}
					} else {
						$this->ajaxReturn('','修改失败，请重试！',0);
					}
					break;
				case 'paymentorder' :
					//付款单
					$m_paymentorder = M('Paymentorder');
					$m_payables = M('Payables');
					$info = $m_paymentorder->where(array('paymentorder_id'=>$id))->find();
					if(empty($info)){
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)){
						$this->ajaxReturn('','您没有此权利！',-2);
					}
					if($info['status'] == 1) {
						$this->ajaxReturn('',L('THE PAYMENT ORDER HAS BEEN CLOSING'),0);
					}
					$payables_id = intval($_POST['payables_id']);
					if (!$payables_id) {
						$this->ajaxReturn('',L('PLEASE_SELECT_PAYABLES'),0);
					}
					if (!intval($_POST['owner_role_id'])) {
						$this->ajaxReturn('','请选择负责人！',0);
					}
					//判断编号是否唯一
					if ($m_paymentorder->where(array('name'=>trim($_POST['name']),'paymentorder_id'=>array('neq',$id)))->find()) {
						$this->ajaxReturn('','付款单号已存在，请修改后重新提交',0);
					}
					if ($m_paymentorder->create()) {
						$m_paymentorder->pay_time = $_POST['pay_time'] ? $_POST['pay_time'] : time();
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
							$this->ajaxReturn('','修改成功！',1);
						}else{
							$this->ajaxReturn('','修改失败，请重试！',0);
						}
					} else {
						$this->ajaxReturn('','修改失败，请重试！',0);
					}
				break;
			}
		}
	}

	/**
	 * 应收款、回款、应付、付款（删除）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete(){
		if ($this->isPost()) {
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			if ($id == 0) {
				$this->ajaxReturn('','参数错误！',0);
			}
			switch ($this->type) {
				case 'receivables' :
					$m_receivables = M('Receivables');
					$m_receivingorder = M('Receivingorder');
					//如果应收款下有收款单记录，提示先删除收款单
					$error_tip = '';
					$receivables_info = $m_receivables->where(array('receivables_id'=>$id))->find();
					if (!$receivables_info) {
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}
					if(checkPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type)){
						if (!in_array($receivables_info['owner_role_id'],$this->_permissionRes)) {
							$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),-2);
						}
						if(!$m_receivingorder->where('receivables_id = %d ',$id)->find()){
							if(!$m_receivables->where('receivables_id = %d', $id)->delete()){
								$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
							} else {
								$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
								actionLog($id,'t=receivables');
							}
						}else{
							$this->ajaxReturn('','请先删除该应收款下回款单',0);
						}
					}else{
						$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),-2);
					}
					break;
				case 'payables' :					
					$m_payables = M('Payables');
					$m_paymentorder = M('Paymentorder');
					//如果应付款下有付款单记录，提示先删除付款单
					$error_tip = '';
					$payables_info = $m_payables->where(array('payables_id'=>$id))->find();
					if (!$payables_info) {
						$this->ajaxReturn('','数据不存在或已删除！',0);
					}
					if(checkPerByAction(MODULE_NAME,ACTION_NAME.'_'.$this->type)){
						if (!in_array($payables_info['owner_role_id'],$this->_permissionRes)) {
							$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),-2);
						}
						if(!$m_paymentorder->where('payables_id = %d',$id)->find()){
							if(!$m_payables->where('payables_id = %d',$id)->delete()){
								$this->ajaxReturn('','删除失败，请联系管理员！',0);
							} else {
								actionLog($id,'t=payables');
								$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
							}
						}else{
							$this->ajaxReturn('','请先删除该应付款下付款单',0);
						}
					}else{
						$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),-2);
					}
					break;
				case 'receivingorder' :
					$m_receivingorder = M('Receivingorder');
					$m_receivables = M('Receivables');
					$receivingorder_info = $m_receivingorder->where(array('receivingorder_id'=>$id))->field('status,receivables_id')->find();
					if($receivingorder_info['status'] ==1 ){
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
						$receivingorder_res = $m_receivingorder->where(array('receivingorder_id'=>$id,'owner_role_id'=>array('in',implode(',', $this->_permissionRes))))->delete();
						if($receivingorder_res){
							actionLog($id,'t=receivingorder');
							$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
						}else{
							$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
						}
					}else{
						$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),-2);
					}
					break;
				case 'paymentorder' :
					$m_paymentorder = M('Paymentorder');
					$m_payables = M('Payables');
					$paymentorder_info = $m_paymentorder->where(array('paymentorder_id'=>$id))->field('status,payables_id')->find();
					if($paymentorder_info['status'] ==1 ){
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
						$paymentorder_res = $m_paymentorder->where(array('paymentorder_id'=>$id,'owner_role_id'=>array('in',implode(',', $this->_permissionRes))))->delete();
						if($paymentorder_res){
							actionLog($id,'t=paymentorder');
							$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
						}else{
							$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
						}
					}else{
						$this->ajaxReturn('',L('HAVE NOT PRIVILEGES'),-2);
					}
					break;
			}
		}
	}

	/**
	 * 审核记录(收款单、付款单)
	 * @param 
	 * @author 
	 * @return 
	 */
	public function check_list(){
		if ($this->isPost()) {
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			if ($id == 0) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_user = M('User');
			switch ($this->type) {
				case 'receivingorder' : 
					$res_info = M('Receivingorder')->where(array('receivingorder_id'=>$id))->field('owner_role_id,examine_role_id,status,check_des,update_time,create_time')->find();
					if ($res_info['status'] && empty($res_info['check_time'])) {
						$res_info['check_time'] = $res_info['update_time'];
					}
				break;
				case 'paymentorder' :
					$res_info = M('Paymentorder')->where(array('paymentorder_id'=>$id))->field('owner_role_id,examine_role_id,status,check_des,check_time,create_time')->find();
				break;
			}
			if (!$res_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			$owner_info = $m_user->where(array('role_id'=>$res_info['owner_role_id']))->field('full_name,thumb_path,role_id')->find();
			if ($res_info['examine_role_id']) {
				$examine_info = $m_user->where(array('role_id'=>$res_info['examine_role_id']))->field('full_name,thumb_path,role_id')->find();
			} else {
				$examine_info = array();
			}
			
			$info = array();
			$info['res_info'] = $res_info;
			$info['owner_info'] = $owner_info;
			$info['examine_info'] = $examine_info;
			$data['data'] = $info ? $info : array();
			$data['status'] = 1;
			$data['info'] = 'success';
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 审核(收款单、付款单)
	 * @param 
	 * @author 
	 * @return 
	 */
	public function check(){
		if ($this->isPost()) {
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			$is_checked = $_POST['is_checked'] ? intval($_POST['is_checked']) : '';
			if (!$id || !$is_checked) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_user = M('User');
			$data = array();
			switch ($this->type) {
				case 'receivingorder' : 
					$m_receivingorder = M('Receivingorder');
					$m_receivables = M('Receivables');
					$receivingorder_info = $m_receivingorder->where(array('receivingorder_id'=>$id))->find();
					if ($receivingorder_info['status']) {
						$this->ajaxReturn('','已审核，请勿重复操作！',0);
					}
					if ($is_checked == 1) {
						$data['status'] = 1;
					} elseif ($is_checked == 2) {
						$data['status'] = 2;
					}
					$data['examine_role_id'] = session('role_id');
					$data['check_des'] = trim($_POST['check_des']);
					$data['update_time'] = time();
					$data['check_time'] = time();
					$result = $m_receivingorder->where('receivingorder_id = %d', $id)->save($data);
					if ($result) {
						$r_money = $m_receivingorder->where('is_deleted <> 1 and status = 1 and receivables_id =%d',$receivingorder_info['receivables_id'])->sum('money');
						$price = $m_receivables->where('receivables_id =%d',$receivingorder_info['receivables_id'])->getField('price');
						if($r_money >= $price){
							$m_receivables->where('receivables_id =%d',$receivingorder_info['receivables_id'])->setField('status',2);
						}else{
							$m_receivables->where('receivables_id =%d',$receivingorder_info['receivables_id'])->setField('status',1);
						}
						//发送站内信
						$url = U('finance/view','t=receivingorder&id='.$id);
						sendMessage($receivingorder_info['creator_role_id'],'您创建的回款单《<a href="'.$url.'">'.$receivingorder_info['name'].'</a>》<font style="color:green;">已审核</font>！',1);
						$this->ajaxReturn('','审核成功！',1);
					}else{
						$this->ajaxReturn('','审核失败，请重试！',0);
					}
				break;
				case 'paymentorder' :
					$m_paymentorder = M('Paymentorder');
					$m_payables = M('Payables');
					$paymentorder_info = $m_paymentorder->where(array('paymentorder_id'=>$id))->find();
					if ($paymentorder_info['status']) {
						$this->ajaxReturn('','已审核，请勿重复操作！',0);
					}

					if ($is_checked == 1) {
						$data['status'] = 1;
					} elseif ($is_checked == 2) {
						$data['status'] = 2;
					}
					$data['examine_role_id'] = session('role_id');
					$data['check_des'] = trim($_POST['check_des']);
					$data['update_time'] = time();
					$data['check_time'] = time();
					$result = $m_paymentorder->where('paymentorder_id = %d', $id)->save($data);
					if ($result) {
						//相关应付款状态
						$r_money = $m_paymentorder->where('is_deleted <> 1 and status = 1 and payables_id =%d',$paymentorder_info['payables_id'])->sum('money');
						$price = $m_payables->where('payables_id =%d',$paymentorder_info['payables_id'])->getField('price');
						if($r_money >= $price){
							$m_payables->where('payables_id =%d',$paymentorder_info['payables_id'])->setField('status',2);
						}else{
							$m_payables->where('payables_id =%d',$paymentorder_info['payables_id'])->setField('status',1);
						}
						//发送站内信
						$url = U('finance/view','t=paymentorder&id='.$id);
						sendMessage($paymentorder['creator_role_id'],'您创建的付款单《<a href="'.$url.'">'.$paymentorder['name'].'</a>》<font style="color:green;">已审核</font>！',1);
						$this->ajaxReturn('','审核成功！',1);
					} else {
						$this->ajaxReturn('','审核失败，请重试！',0);
					}
				break;
			}
		}
	}

	/**
	*  撤销审核
	*
	**/
	public function revokeCheck(){
		if ($this->isPost()) {
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			if (!$id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_user = M('User');
			$data = array();
			switch ($this->type) {
				case 'receivingorder' :
					$m_receivingorder = M('Receivingorder');
					$m_receivables = M('Receivables');
					$receivingorder_info = $m_receivingorder->where(array('receivingorder_id'=>$id))->find();
					if ($receivingorder_info['status']) {
						$result = $m_receivingorder->where('receivingorder_id = %d', $id)->setField('status',0);
						//相关应收款状态改变
						$sum_receivingorder_money = $m_receivingorder->where(array('receivables_id'=>$receivingorder_info['receivables_id'],'is_deleted'=>0,'status'=>1))->sum('money'); //收款单总金额
						$receivables_price = $m_receivables->where('receivables_id = %d',$receivingorder_info['receivables_id'])->getField('price'); //应收款金额
						if (empty($sum_receivingorder_money)) {
							$m_receivables->where('receivables_id = %d',$receivingorder_info['receivables_id'])->setField('status',0);
						} else {
							if($receivables_price > $sum_receivingorder_money){
								$m_receivables->where('receivables_id = %d',$receivingorder_info['receivables_id'])->setField('status',1);
							}
						}
						if ($result) {
							//发送站内信
							$url = U('finance/view','t=receivingorder&id='.$id);
							sendMessage($receivingorder_info['creator_role_id'],'您创建的回款单《<a href="'.$url.'">'.$receivingorder_info['name'].'</a>》<font style="color:red;">已被撤销审核</font>！',1);
							$this->ajaxReturn('','撤销成功！',1);
						} else {
							$this->ajaxReturn('','撤销失败，请重试！',0);
						}
					} else {
						$this->ajaxReturn('','已撤销审核，请勿重复操作！',0);
					}
				break;
				case 'paymentorder' :
					$m_paymentorder = M('Paymentorder');
					$m_payables = M('Payables');
					$paymentorder_info = $m_paymentorder->where(array('paymentorder_id'=>$id))->find();
					if ($paymentorder_info['status']) {
						$result = $m_paymentorder->where('paymentorder_id = %d', $id)->setField('status',0);
						//改变应付款状态
						$payables_price = $m_payables->where('payables_id = %d',$paymentorder_info['payables_id'])->getField('price');
						$sum_paymentorder_price = $m_paymentorder->where(array('payables_id'=>$paymentorder_info['payables_id'],'is_deleted'=>0,'status'=>array('eq',1)))->sum('money');
						if (empty($sum_paymentorder_price)) {
							$m_payables->where('payables_id = %d',$paymentorder_info['payables_id'])->setField('status',0);
						} else {
							if (intval($payables_price) > intval($sum_paymentorder_price)) {
								$m_payables->where('payables_id = %d',$paymentorder_info['payables_id'])->setField('status',1);
							}
						}
						if ($result) {
							//发送站内信
							$url = U('finance/view','t=paymentorder&id='.$id);
							sendMessage($paymentorder_info['creator_role_id'],'您创建的付款单《<a href="'.$url.'">'.$paymentorder_info['name'].'</a>》<font style="color:red;">已被撤销审核</font>！',1);
							$this->ajaxReturn('','撤销成功！',1);
						} else {
							$this->ajaxReturn('','撤销失败，请重试！',0);
						}
					} else {
						$this->ajaxReturn('','已撤销审核，请勿重复操作！',0);
					}
				break;
			}
		}
	}
}