<?php
class ContractMobile extends Action {

	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('supplierlist','content_info')
		);
		B('AppAuthenticate',$action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
		Global $role;
		$this->role = $role;
		Global $roles;
		$this->roles = $roles;
	}
	//合同列表
	public function index(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		$contract_custom = M('config') -> where('name="contract_custom"')->getField('value');
		if(!$contract_custom)  $contract_custom = '5k_crm';
		if($this->isPost()){
			//获取权限
			$permission_list = apppermission(MODULE_NAME,ACTION_NAME);
			if($permission_list){
				$data['permission_list'] = $permission_list;
			}else{
				$data['permission_list'] = array();
			}
			$m_user = M('user');
			$last_read_time_js = $m_user->where('role_id = %d', session('role_id'))->getField('last_read_time');
			$last_read_time = json_decode($last_read_time_js, true);
			$last_read_time['contract'] = time();
			$m_user->where('role_id = %d', session('role_id'))->setField('last_read_time',json_encode($last_read_time));
			$d_contract = D('ContractView');
			$where = array();
			//按合同编号查询
			if(isset($_POST['search'])){
				$where['number'] = array('like','%'.trim($_POST['search']).'%');
			}
			//接收查询条件
			$searchfield = isset($_POST['searchfield']) ? trim($_POST['searchfield']) : '';
			$params_search = json_decode($searchfield,true);
			$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,$sub_role=true);
			$where['contract.owner_role_id'] = array('in', $this->_permissionRes);
			$order = 'contract.update_time desc,contract.contract_id asc';
			//查询条件
			switch ($_GET['by']){
				case 'create':
					$where['creator_role_id'] = session('role_id');
					break;
				case 'sub' :
					$where['contract.owner_role_id'] = array('in',implode(',', $below_ids));
					break;
				case 'subcreate' :
					$where['creator_role_id'] = array('in',implode(',', $below_ids));
					break;
				case 'today' :
					$where['due_time'] =  array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400));
					break;
				case 'week' :
					$week = (date('w') == 0)?7:date('w');
					$where['due_time'] =  array('between',array(strtotime(date('Y-m-d')) - ($week-1) * 86400 -1 ,strtotime(date('Y-m-d')) + (8-$week) * 86400));
					break;
				case 'month' :
					$next_year = date('Y')+1;
					$next_month = date('m')+1;
					$month_time = date('m') ==12 ? strtotime($next_year.'-01-01') : strtotime(date('Y').'-'.$next_month.'-01');
					$where['due_time'] = array('between',array(strtotime(date('Y-m-01')) -1 ,$month_time));
					break;
				case 'add' :
					$order = 'contract.create_time desc,contract.contract_id asc';
					break;
				case 'deleted' :
					$where['is_deleted'] = 1;
					break;
				case 'update' :
					$order = 'contract.update_time desc,contract.contract_id asc';
					break;
				case 'me' :
					$where['contract.owner_role_id'] = session('role_id');
					break;
			}
			//按分类 1销售 2采购
			switch ($_GET['type']){
				case '1':
					$where['contract.type'] = 1;
					break;
				case '2':
					$where['contract.type'] = 2;
					break;
			}
			if (!isset($where['is_deleted'])) {
				$where['is_deleted'] = 0;
			}
			if($params_search){
				$where[$params_search['field']] = array('like','%'.trim($params_search['val']).'%');
			}
			//商机下的合同
			if($_GET['business_id']){
				$contract_ids = M('rBusinessContract')->where('business_id = %d', $_GET['business_id'])->getField('contract_id', true);
				$where['contract.contract_id'] = array('in',$contract_ids);
			}
			//客户下的合同
			if($_GET['customer_id']){
				$where['contract.customer_id'] = $_GET['customer_id'];
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$list = $d_contract->where($where)->page($p.',10')->order($order)->field('number,price,customer_id,contract_id,type,supplier_id,owner_role_id')->select();
			foreach($list as $k=>$v){
				if($v['type'] == 1){
					$customer_name = M('Customer')->where('customer_id = %d',$v['customer_id'])->getField('name');
					if($customer_name){
						$list[$k]['customer_name'] = $customer_name;
					}else{
						$list[$k]['customer_name'] = '';
					}
				}elseif($v['type'] == 2){
					$supplier_name = M('Supplier')->where('supplier_id = %d',$v['supplier_id'])->getField('name');
					if($supplier_name){
						$list[$k]['customer_name'] = $supplier_name;
					}else{
						$list[$k]['customer_name'] = '';
					}
				}
				$owner_role_id = $v['owner_role_id'];
				//获取操作权限
				$list[$k]['permission'] = permissionlist(MODULE_NAME,$owner_role_id);
				//合同到期时间
				$end_date = 0;
				$end_date =  $d_contract->where('contract_id = %d', $v['contract_id'])->getField('end_date');
				if($end_date){
					$list[$k]['days'] = floor(($end_date-time())/86400);
				}else{
					$list[$k]['days'] = '';
				}
			}
			$count = $d_contract->where($where)->count();
			//获取查询条件信息
			$list = empty($list) ? array() : $list;
			$page = ceil($count/10);
			if($p == 1){
				$data['contract_custom'] = $contract_custom;
			}
			$data['list'] = $list;
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}else{
			$this->ajaxReturn('非法请求',"非法请求",2);
		}
	}
	//合同添加
	public function add(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$params = json_decode($_POST['params'],true);
			$number = $params['number'];
			if($number == '' || $number == null){
				$this->ajaxReturn('合同编号不能为空','合同编号不能为空',2);
			}
			$params['owner_role_id'] = $params['owner_role_id']?$params['owner_role_id']:session('role_id');
			$m_contract = M('Contract');
			if($m_contract->create($params)){
				$m_contract->create_time = time();
				$m_contract->create_role_id = session('role_id');
				$m_contract->status = '已创建';
				if($m_contract->add()){
					$this->ajaxReturn('添加成功','添加成功',1);
				}else{
					$this->ajaxReturn('添加失败','添加失败',2);
				}
			}else{
				$this->ajaxReturn('添加失败','添加失败',2);
			}
		}else{
			$this->ajaxReturn('非法请求',"非法请求",2);
		}
	}
	//合同详情
	public function view(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$id = $_POST['id'];
			$contract = D('ContractView');
			$m_user = M('User');
			$m_contacts = M('Contacts');
			$info = $contract->where('contract_id = %d',$id)->find();
			//权限判断
			if(empty($info) || empty($id)) {
				$this->ajaxReturn('合同不存在或已被删除！','合同不存在或已被删除！',2);
			}elseif(!in_array($info['owner_role_id'], $this->_permissionRes)) {
				$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
			}
			/* if($info['type'] == 1){ */
				unset($info['supplier_id']);
				$i = 0;
				foreach($info as $k=>$v){
					$contract_list[$i]['field'] = $k;
					$contract_list[$i]['name'] = '';
					if($k == 'content'){
						$contract_list[$i]['val'] = 'm=index&a=content_info&fields_m=contract&field_name='.$k.'&id='.$id;
						//$contract_list[$i]['val'] = '--暂不支持--';
						$contract_list[$i]['type'] = 13;
					}elseif($k == 'owner_role_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$owner = $m_user->where('role_id = %d',$v)->getField('name');
						$contract_list[$i]['val'] = $owner;
						$contract_list[$i]['type'] = 1;
					}elseif($k == 'creator_role_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$creator_name = $m_user->where('role_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						$contract_list[$i]['val'] = $creator_name;
						$contract_list[$i]['type'] = 1;
					}elseif($k == 'business_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$business_name = M('Business')->where('business_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						$contract_list[$i]['val'] = $business_name;
						$contract_list[$i]['type'] = 4;
					}elseif($k == 'customer_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$customer_name = M('Customer')->where('customer_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						$contract_list[$i]['val'] = $customer_name;
						$contract_list[$i]['type'] = 3;
					}elseif($k == 'type'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$customer_name = M('Customer')->where('customer_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						if($v == 1){
							$contract_list[$i]['val'] = '销售';
						}elseif($v == 2){
							$contract_list[$i]['val'] = '采购';
						}else{
							$contract_list[$i]['val'] = '销售合同';
						}
						$contract_list[$i]['type'] = 0;
					}elseif($k == 'contacts_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$contract_list[$i]['id'] = $v;
						$contract_list[$i]['val'] = $info['contacts_name'];
						$contract_list[$i]['type'] = 0;
					}else{
						$contract_list[$i]['val'] = $v;
						$contract_list[$i]['id'] = '';
						$contract_list[$i]['type'] = 0;
					}
					$i++;
				}
			/*} elseif($info['type'] == 2){
				unset($info['contacts_id']);
				unset($info['contacts_name']);
				unset($info['business_id']);
				unset($info['customer_id']);
				unset($info['customer_name']);
				unset($info['business_name']);
				$i = 0;
				foreach($info as $k=>$v){
					$contract_list[$i]['field'] = $k;
					$contract_list[$i]['name'] = '';
					$contract_list[$i]['val'] = $v;
					if($k == 'owner_role_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$owner = $m_user->where('role_id = %d',$v)->getField('name');
						$contract_list[$i]['val'] = $owner;
						$contract_list[$i]['type'] = 1;
					}elseif($k == 'creator_role_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$creator_name = $m_user->where('role_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						$contract_list[$i]['val'] = $creator_name;
						$contract_list[$i]['type'] = 1;
					}elseif($k == 'supplier_id'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$supplier_name = M('supplier')->where('supplier_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						$contract_list[$i]['val'] = $supplier_name;
						$contract_list[$i]['type'] = 0;
					}elseif($k == 'type'){
						$contract_list[$i]['id'] = $v;
						if($v){
							unset($contract_list[$i]['val']);
						}
						$customer_name = M('Customer')->where('customer_id = %d',$v)->getField('name');
						$contract_list[$i]['id'] = $v;
						if($v == 1){
							$contract_list[$i]['val'] = '销售';
						}elseif($v == 2){
							$contract_list[$i]['val'] = '采购';
						}
						$contract_list[$i]['type'] = 0;
					}else{
						$contract_list[$i]['id'] = '';
						$contract_list[$i]['type'] = 0;
					}
					$i++;
				}
			} */
			/* $supplier = M('supplier')->where('supplier_id = %d',$info['supplier_id'])->field('supplier_id,name,contact_id')->find();
			$contact_id = $supplier['contact_id'];
			$contact_name = M('supplierContact')->where('contact_id = %d',$supplier['contact_id'])->getField('contact_name');
			$info['supplier_name'] = $supplier['name'];
			$contract_list[$i]['field'] = 'contacts_id';
			$contract_list[$i]['name'] = '';
			$contract_list[$i]['val'] = $contact_name;
			$contract_list[$i]['id'] = $contact_id;
			$contract_list[$i]['type'] = 0; */

			$owner_role_id = $info['owner_role_id'];
			//获取权限
			$data['permission'] = permissionlist(MODULE_NAME,$owner_role_id);
			$data['data'] = $contract_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	//合同修改
	public function edit(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$params = json_decode($_POST['params'],true);
			$contract_id = $_POST['id'];
			$contract = D('ContractView');
			$m_contract = M('Contract');
			if(empty($contract_id)){
				$this->ajaxReturn('参数错误','参数错误',2);
			}
			$contract_info = $contract->where('contract_id = %d',$contract_id)->find();
			if($contract_info['type'] == 1){
				$customer = M('customer')->where('customer_id = %d',$contract_info['customer_id'])->find();
				$contract_info['contacts_name'] = M('contacts')->where('contacts_id = %d',$customer['contacts_id'])->getField('name');
				$contract_info['customer'] = $customer;
			}elseif($contract_info['type'] == 2){
				$supplier = M('supplier')->where('supplier_id = %d',$contract_info['supplier_id'])->find();
				$contract_info['contacts_name'] = M('supplierContact')->where('contact_id = %d',$supplier['contact_id'])->getField('contact_name');
				$contract_info['supplier_name'] = $supplier['name'];
			}
			if(!$contract_info){
				$this->ajaxReturn('合同不存在或已被删除！','合同不存在或已被删除！',2);
			}elseif($this->_permissionRes && !in_array($contract_info['owner_role_id'], $this->_permissionRes)){
				$this->akaxReturn('您没有此权利!','您没有此权利!',-2);
			}
			if(is_array($contract_info)){
				if($this->isPost()){
					$data['due_time'] = $params['due_time']?$params['due_time']:time();
					$data['type'] = trim($params['type']);
					$data['business_id'] = intval($params['business_id']);
					$data['supplier_id'] = intval($params['supplier_id']);
					$data['customer_id'] = intval($params['customer_id']);
					if($data['type'] == 1){
						if($data['customer_id'] == ''){
							$this->ajaxReturn('来源客户不能为空！','来源客户不能为空！',2);
						}
					}elseif($data['type'] == 2){
						if($data['supplier_id'] == ''){
							$this->ajaxReturn('供应商不能为空！','供应商不能为空！',2);
						}
					}
					$data['owner_role_id'] = $params['owner_role_id']?$params['owner_role_id']:session('role_id');
					$data['price'] = intval($params['price']);
					//$data['content'] = trim($params['content']);
					$data['description'] = trim($params['description']);
					$data['start_date'] = $params['start_date'];
					$data['end_date'] = $params['end_date'];
					$data['update_time'] = time();
					$data['status'] = $params['status'];

					if(M('contract')->where(array('contract_id'=>$contract_id))->save($data)){
						M('rBusinessContract')->where(array('contract_id'=>$contract_id))->save(array('business_id'=>$data['business_id']));
						$this->ajaxReturn('修改成功','修改成功',1);
					}else{
						$this->ajaxReturn('数据无变化','数据无变化',2);
					}
				}
			}else{
				$this->ajaxReturn('没有数据','没有数据',2);
			}
		}
	}
	//删除合同
	public function delete(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$contract_id = $_POST['contract_id'];
			if('' == $contract_id){
				$this->ajaxReturn('参数错误','参数错误',2);
			}else{
				$m_contract = M('Contract');
				$m_receivables = M('Receivables');
				$m_payables = M('Payables');
				$m_r_contract_product = M('rContractProduct');
				$m_r_contract_file = M('rContractFile');
				//权限判断
				$contracts = $m_contract->where('contract_id = %d', $contract_id)->find();
				if (!in_array($contracts['owner_role_id'], $this->_permissionRes)){
					$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
				}
				//如果合同下有产品，财务和文件信息，提示先删除产品，财务和文件数据。
				$data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
				$contract = $m_contract->where('contract_id = %d',$contract_id)->find();
				$contract_product = $m_r_contract_product->where('contract_id = %d',$contract_id)->select();//合同关联的产品记录
				$contract_file = $m_r_contract_file->where('contract_id = %d',$contract_id)->select();//合同关联的文件
				$contract_receivables = $m_receivables->where('is_deleted <> 1 and contract_id = %d',$contract_id)->select();//合同关联的应收款
				$contract_payables = $m_payables->where('is_deleted <> 1 and contract_id = %d',$contract_id)->select();//合同关联的应付款

				if(empty($contract_product) && empty($contract_file) && empty($contract_receivables) && empty($contract_payables)){
					if(!$m_contract->where('contract_id = %d', $contract_id)->save($data)){
						$this->ajaxReturn('删除失败，请联系管理员！','删除失败，请联系管理员！',2);
					}
				}else{
					if(!empty($contract_product)){
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同下的产品信息!','删除失败！请先删除'.$contract['number'].'合同下的产品信息!',2);
					}elseif(!empty($contract_file)){
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同下的文件信息!','删除失败！请先删除'.$contract['number'].'合同下的文件信息!',2);
					}elseif(!empty($contract_receivables)){
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!','删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!',2);
					}else{
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!','删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!',2);
					}
				}
				$this->ajaxReturn('删除成功','删除成功',1);
			}
		}
	}
}