<?php 
class ContactsAction extends Action {

	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('checklistdialog','revert', 'mdelete','add_dialog','qrcode','excelimport','validate','reltobusiness','changetofirstcontact')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	
	/**
	*Ajax检测联系人唯一字段
	*
	**/
	public function validate() {
		if($this->isAjax()){
            if(!$this->_request('clientid','trim') || !$this->_request($this->_request('clientid','trim'),'trim')){
				$this->ajaxReturn("","",3);
			}
            $field = M('Fields')->where('model = "contacts" and field = "%s"',$this->_request('clientid','trim'))->find();
            $m_contacts = $field['is_main'] ? D('contacts') : D('ContactsData');
            $where[$this->_request('clientid','trim')] = array('eq',$this->_request($this->_request('clientid','trim'),'trim'));
            if($this->_request('id','intval',0)){
                $where[$m_contacts->getpk()] = array('neq',$this->_request('id','intval',0));
            }
			if($this->_request('clientid','trim')) {
				if ($m_contacts->where($where)->find()) {
					$this->ajaxReturn("","",1);
				} else {
					$this->ajaxReturn("","",0);
				}
			}else{
				$this->ajaxReturn("","",0);
			}
		}
	}
	
	public function add(){
		if ($_GET['r'] && $_GET['module'] && $_GET['id']) {
			$this -> r = $_GET['r'];
			$this -> module = $_GET['module'];
			$this -> id = $_GET['id'];
			$this->display('Contacts:add_dialog');
		}elseif($this->isPost()){
			$name = trim($_POST['name']);
			$customer_id = trim($_POST['customer_id']);
			if ($name == '' || $name == null) {
				$this->error(L('CONTACT NAME CANNOT BE EMPTY'));
			}
			if ($customer_id == '' || $customer_id == null) {
				$this->error(L('CUSTOMER NAME CANNOT BE EMPTY'));
			}
			$contacts = D('Contacts');
			$contacts_data = D('ContactsData');
			//自定义字段
			$field_list = M('Fields')->where('model = "contacts" and in_add = 1')->order('order_id')->select();
			foreach ($field_list as $v){
				switch($v['form_type']) {
					case 'address':
						$_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
					break;
					case 'datetime':
						$_POST[$v['field']] = strtotime($_POST[$v['field']]);
					break;
					case 'box':
						eval('$field_type = '.$v['setting'].';');
						if($field_type['type'] == 'checkbox'){
							$a =array_filter($_POST[$v['field']]);
							$_POST[$v['field']] = !empty($a) ? implode(chr(10),$a) : '';
						}
					break;
				}
			}

			if ($contacts->create()) {
				$contacts->create_time = time();
				$contacts->update_time = time();
				$contacts->creator_role_id = session('role_id');
				if($contacts_id = $contacts->add()){
					if($contacts_id){
						$RContactsCustomer['contacts_id'] = $contacts_id;
						$RContactsCustomer['customer_id'] = $_POST['customer_id'];
						if(M('RContactsCustomer') ->add($RContactsCustomer)){
							//商机关联联系人
							if($_POST['redirect'] == 'business'){
								$RBusinessContavts = array();
								$RBusinessContavts['business_id'] = intval($_POST['redirect_id']);
								$RBusinessContavts['contacts_id'] = $contacts_id;
								$res = M('RBusinessContacts')->add($RBusinessContavts);
							}
							M('ContactsData')->create();
							M('ContactsData')->contacts_id = $contacts_id;
							if(M('ContactsData')->add()){
								if($_POST['refer_url']){
									alert('success', '添加联系人成功!', $_POST['refer_url'].'#tab3');
								}else{
									alert('success',L('ADD A SUCCESS'),U('contacts/view','id='.$contacts_id));
								}
							}
						}
					}else{
						alert('success',L('ADD A SUCCESS'),U('contacts/view','id='.$contacts_id));
					}
				}else{
					$this->error(L('ADD FAILURE'));
				}
			} else {
				$this->error($contacts->getError());
			}
		}else{
			$m_customer = M('Customer');
			if($_GET['redirect']){
				$this->redirect_id = intval($_GET['redirect_id']);
				if(trim($_GET['redirect']) == 'customer'){
					$customer_id = $this->redirect_id;
				}elseif(trim($_GET['redirect']) == 'business'){
					$customer_id = M('Business')->where('business_id = %d',intval($_GET['redirect_id']))->getField('customer_id');
				}
				// $d_module = $_GET['redirect'] == 'customer' ? array('customer_id'=>$this->redirect_id) : array();
				$d_module = array('customer_id'=>$customer_id);
				$this->redirect = trim($_GET['redirect']);
			}
			if(!empty($_GET['redirect_id'])){
				if(trim($_GET['redirect']) == 'customer'){
					$this->customer = $m_customer->where('customer_id =%d', intval($_GET['redirect_id']))->find();
				}elseif(trim($_GET['redirect']) == 'business'){
					$customer_id = M('Business')->where('business_id = %d',intval($_GET['redirect_id']))->getField('customer_id');
					$this->customer = $m_customer->where('customer_id =%s',$customer_id)->find();
				}
			}
			if($_GET['customer_id']){
				$this->customer_id = $customer_id = intval($_GET['customer_id']);
				$this->customer_name = $m_customer->where('customer_id =%d',$customer_id)->getField('name');
			}
			$this->refer_url = $_SERVER['HTTP_REFERER'];
			$this->field_list = field_list_html('add','contacts',$d_module);
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	//联系人编辑
	public function edit(){
		$d_contacts = D('ContactsView');
		$RContactsCustomer = M('RContactsCustomer');
		$contacts_id = $_GET['id'] ? intval($_GET['id']) : intval($_POST['contacts_id']);
		if(empty($contacts_id)){
			alert('error',L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}
		//检查联系人date表中无关联时创建
		$contacts_data = M('ContactsData');
		$contactsid = $contacts_data->where('contacts_id = %d',$contacts_id)->find();
		if(!$contactsid){
			$data_a['contacts_id'] = $contacts_id;
			$contacts_data->add($data_a);
		}
		$field_list = M('Fields')->where('model = "contacts"')->order('order_id')->select();

		//检查权限
		$customer_id = M('RContactsCustomer')->where('contacts_id = %d', $contacts_id)->getField('customer_id');
		//判断联系人所在客户是否在客户池，如果在则不判断权限
		$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
		$m_config = M('Config');
		$outdays = $m_config->where('name="customer_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

		$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
		$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
		$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
		$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
		if ($openrecycle == 2) {
			if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
				if (!in_array($customer_info['owner_role_id'], $this->_permissionRes) && !session('?admin')) {
					$this->error(L('HAVE NOT PRIVILEGES'));
				}
			}
		} 
		$contacts = $d_contacts->where(array('contacts_id'=>$contacts_id))->find();
		if(empty($contacts)){
			alert('error', L('RECORD_NOT_EXIST_OR_HAVE_BEEN_DELETED',array(L('CONTACTS'))),U('contacts/index'));
		} 
		if ($this->isPost()) {
			$m_contacts = D('Contacts');
			$m_contacts_data = D('ContactsData');
			foreach ($field_list as $v){
				switch($v['form_type']) {
					case 'address':
						$_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
					break;
					case 'datetime':
						$_POST[$v['field']] = strtotime($_POST[$v['field']]);
					break;
					case 'box':
						eval('$field_type = '.$v['setting'].';');
						if($field_type['type'] == 'checkbox'){
							$_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
						}
					break;
				}
			}
			if($m_contacts->create()){
				if($m_contacts_data->create() !== false){
					$m_contacts->update_time = time();
					if (!empty($_POST['customer_id'])) {
						if (empty($customer_id)) {
							$data['contacts_id'] = $_POST['contacts_id'];
							$data['customer_id'] = $_POST['customer_id'];
							$RContactsCustomer ->where('contacts_id = %d', $_POST['contacts_id'])->delete();
							$RContactsCustomer -> add($data);
						}elseif ($_POST['customer_id'] != $customer_id) {
							M('RContactsCustomer') -> where('contacts_id = %d' , $_POST['contacts_id']) -> setField('customer_id',$_POST['customer_id']);
						}	
					}else{
						alert('error', L('NOT NULL',array(L('CUSTOMER'))), $_SERVER['HTTP_REFERER']);
					}
					$a = $m_contacts->where('contacts_id= %d',$contacts['contacts_id'])->save();
					$contacts_field = M('Fields')->where('model = "%s" and is_main = 0','contacts')->find();
					if($contacts_field){
						$b = $m_contacts_data->where('contacts_id= %d', $contacts['contacts_id'])->save();
					}else{
						$b = 0;
					}
					if ($a !== false && $b !== false) {
						if($_POST['refer_url']){
							alert('success', '联系人信息修改成功!', $_POST['refer_url'].'#tab3');
						}else{
							alert('success',L('THE CONTACT INFORMATION OF SUCCESS'),U('contacts/view') . "&id=" . $_POST['contacts_id']);
						}
					} else {
						$this->error(L('THE CONTACT INFORMATION CHANGE FAILED'));
					}
				}else{
					alert('error', $m_contacts_data->getError());
					$this->alert = parseAlert();				
					$this->error();
				}
			}else{
				$this->error($m_contacts->getError(), U('contacts/edit')."&id=".$_POST['contacts_id']);
			}
		}else{
			$this->contacts = $contacts;
			$this->refer_url = $_SERVER['HTTP_REFERER'];
			$this->alert = parseAlert();
			$this->field_list = field_list_html("edit","contacts",$contacts);
			$this->display();
		}
	}
	
	//联系人详情
	public function view(){
		$contacts_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$RContactsCustomer = M('RContactsCustomer');
		$d_contacts = D('ContactsView');

		if (empty($contacts_id)) {
			alert('error', L('PARAMETER_ERROR'), U('contacts/index'));
		} else {
			//检查联系人date表中无关联时创建
			$contacts_data = M('ContactsData');
			$contactsid = $contacts_data->where('contacts_id = %d',$contacts_id)->find();
			if(!$contactsid){
				$data['contacts_id'] = $contacts_id;
				$contacts_data->add($data);
			}

			$customer_id = $RContactsCustomer->where('contacts_id = %d', $contacts_id)->getField('customer_id');

			//判断联系人所在客户是否在客户池，如果在则不判断权限
			$customer_info = M('Customer')->where('customer_id = %d', $customer_id)->find();
			$m_config = M('Config');
			$outdays = $m_config->where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

			$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config->where('name="openrecycle"')->getField('value');

			if ($openrecycle == 2) {
				if($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)){
					if(!in_array($customer_info['owner_role_id'], getPerByAction('contacts','view')) && !session('?admin')){
						$this->error(L('HAVE NOT PRIVILEGES'));
					}
				}
			}

			//查询关联商机
			$m_r_business = M('RBusinessContacts');
			$business_list = $m_r_business->where('contacts_id = %d', $contacts_id)->select();
			$m_business = M('Business');
			$m_business_status = M('BusinessStatus');
			foreach ($business_list as $k => $v) {
				$business_info = $m_business->where('business_id = %d', $v['business_id'])->field('code,status_id,status_type_id')->find();
				if ($business_info) {
					$business_list[$k]['status'] = $m_business_status->where(array('status_id'=>$business_info['status_id'],'type_id'=>$business_info['status_type_id']))->getField('name');
					$business_list[$k]['code'] = $business_info['code'];
				} else {
					unset($business_list[$k]);
				}
			}
			//自定义字段显示
			$field_list = M('Fields')->where('model = "contacts"')->order('order_id')->select();
			$contacts = $d_contacts->where('contacts.contacts_id = %d' , $contacts_id)->find();
			//日程信息 
			$m_event = M('Event');
			$m_user = M('User');
			$event_list = $m_event ->where('module ="contacts" and module_id =%d',$contacts_id)->select();
			foreach($event_list as $k=>$v){
				$user_info = $m_user ->where('role_id =%d',$v['creator_role_id'])->field('full_name,img')->find();
				$event_list[$k]['create_role_name'] = $user_info['full_name'];
				$event_list[$k]['img'] = $user_info['img'];
			}
			$this->event_list = $event_list;
			$this->contacts = $contacts;
            $this->field_list = $field_list;	
            $this->business_list = $business_list;	
			$this->alert = parseAlert();
			$this->display();
		}		
	}

	//联系人和商机解除关联
	public function relToBusiness(){
		$act_n = $_GET['act_n'];
		$business_id = $_GET['business_id'];
		$contacts_id = $_GET['contacts_id'];
		if (!intval($business_id) || !$contacts_id) {
			$this->ajaxReturn(array('error','参数错误！',$_SERVER['HTTP_REFERER']),'JSON');
		}
		if ($act_n == 1) {//关联商机
			$contacts_id_array = explode(',',$contacts_id);
			if (count($contacts_id_array)>1) {
				$data = array();	
				foreach ($contacts_id_array as $k => $v) {
					$data['business_id'] = $business_id;
					$data['contacts_id'] = $v;
					$is_rel = M('RBusinessContacts')->where('business_id = %d and contacts_id = %d',$business_id,$v)->find();
					if ($is_rel) {
						continue;
					}
					$ret = M('RBusinessContacts')->add($data);
					if (!$ret) {
						$this->ajaxReturn(array('error','批量绑定出错！',$_SERVER['HTTP_REFERER']),'JSON');
					}
				}
				$this->ajaxReturn(array('success','绑定成功！',$_SERVER['HTTP_REFERER']),'JSON');
			}
			$data = array();
			$data['business_id'] = $business_id;
			$data['contacts_id'] = $contacts_id;
			$is_rel = M('RBusinessContacts')->where('business_id = %d and contacts_id = %d',$business_id,$contacts_id)->find();
			if ($is_rel) {
				$this->ajaxReturn(array('error','联系人已绑定该商机！',$_SERVER['HTTP_REFERER']),'JSON');
			}
			$ret = M('RBusinessContacts')->add($data);
			if ($ret) {
				$this->ajaxReturn(array('success','绑定成功！',$_SERVER['HTTP_REFERER']),'JSON');
			} else {
				$this->ajaxReturn(array('error','绑定失败！',$_SERVER['HTTP_REFERER']),'JSON');
			}
			
		} else {//解绑关联商机
			$ret = M('RBusinessContacts')->where('business_id = %d and contacts_id = %d',$business_id,$contacts_id)->delete();
			if ($ret) {
				$this->ajaxReturn(array('success','解绑商机成功！',$_SERVER['HTTP_REFERER']),'JSON');
			} else {
				$this->ajaxReturn(array('error','解绑商机失败！',$_SERVER['HTTP_REFERER']),'JSON');
			}
		}
	}

	public function index(){
		$d_contacts = D('ContactsView');
		$m_customer = M('Customer');
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		$below_ids = getPerByAction('contacts',ACTION_NAME);
		$where = array();
		$where_customer = array();
		$b_where = array();
		$c_where = array();
		$params = array();
		$order = "contacts.update_time desc,contacts.contacts_id asc";
		
		if ($_GET['desc_order']) {
			$order = 'contacts.'.trim($_GET['desc_order']).' desc,contacts.contacts_id asc';
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif ($_GET['asc_order']) {
			$order = 'contacts.'.trim($_GET['asc_order']).' asc,contacts.contacts_id asc';
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}
		switch ($by) {
			case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
			case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
			case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
			case 'add' : $order = 'contacts.create_time desc,contacts.contacts_id asc'; break;
			case 'update' : $order = 'contacts.update_time desc,contacts.contacts_id asc'; break;
			case 'deleted' : $where['is_deleted'] = 1; break;
			default : $where['customer.owner_role_id'] = array('in', $below_ids); break;
		}
		if (!isset($where['customer.owner_role_id'])) {
			$where['customer.owner_role_id'] = array('in', $below_ids);
		}
		if (!isset($where['is_deleted'])) {
			$where['is_deleted'] = 0;
		}
		
		if ($_REQUEST["field"]) {
			$field = $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if ($this->_request('state')){
				$state = $this->_request('state', 'trim');
				$address_where[] = '%'.$state.'%';
				if($this->_request('city')){
					$city = $this->_request('city', 'trim');
					$address_where[] = '%'.$city.'%';
					if($this->_request('area')){
						$area = $this->_request('area', 'trim');
						$address_where[] = '%'.$this->_request('area', 'trim').'%';
					}
				}
				if($search){
					$address_where[] = '%'.$search.'%';
				}
				$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'state='.$this->_request('state','trim'), 'city='.$this->_request('city','trim'),'area='.$this->_request('area','trim'),'search='.$this->_request('search','trim'));
				$where[$field] = array('like', $address_where, 'AND');
			}else{ 
				$field_date = M('Fields')->where('is_main=1 and (model="" or model="contacts") and form_type="datetime"')->select();
				foreach($field_date as $v){
					if($field == $v['field'] || $field == 'customer.create_time' || $field == 'customer.update_time') $search = is_numeric($search)?$search:strtotime($search);
				}
				if($field =="customer_id"){
					//所属客户
					$b_where['name'] = array('like','%'.$where['customer_id'][1].'%');
					$c_where['is_deleted'] = 0;
					$c_where['customer_id'] = array('in',$owner_customer_ids); //过滤权限
					$customer_str = M('Customer')->where($c_where)->getField('customer_id',true);
					unset($where['customer_id']);
				}else{
					switch ($condition) {
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
				$params = array('field='.$field, 'condition='.$condition, 'search='.$_REQUEST["search"]);
			}
		}
		//多选类型字段
		$check_field_arr = M('Fields')->where(array('model'=>'contacts','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
		//高级搜索
		if(!$_GET['field']){
			foreach($_GET as $k=>$v){
				if($k != 'act' && $k != 'content' && $k != 'p' && $k !='condition' && $k != 'listrows' && $k != 'asc_order' && $k != 'desc_order'){
					if(is_array($v)){
						 if ($v['state']){
							$address_where[] = '%'.$v['state'].'%';

							if($v['city']){
								$address_where[] = '%'.$v['city'].'%';

								if($v['area']){
									$address_where[] = '%'.$v['area'].'%';
								}
							}
							if($v['search']) $address_where[] = '%'.$v['search'].'%';

							if($v['condition'] == 'not_contain'){
								$where[$k] = array('notlike', $address_where, 'OR');
							}else{
								$where[$k] = array('like', $address_where, 'AND');
							}
						} elseif (($v['start'] != '' || $v['end'] != '')) {
							if($k == 'create_time'){
								$k = 'contacts.create_time';
							}elseif($k == 'update_time'){
								$k = 'contacts.update_time';
							}
							//时间段查询
							if ($v['start'] && $v['end']) {
								$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
							} elseif ($v['start']) {
								$where[$k] = array('egt',strtotime($v['start']));
							} else {
								$where[$k] = array('elt',strtotime($v['end'])+86399);
							}
						}elseif(($v['value']) != ''){
							if (in_array($k,$check_field_arr)) {
								$where[$k] = field($v['value'],'contains');
							} else {
								$where[$k] = field($v['value'],$v['condition']);
							}
						}
					}else{
						if(!empty($v)){
							$where[$k] = field($v);
						}
				    }
				}
				if(is_array($v)){
					foreach ($v as $key => $value) {
						$params[] = $k.'['.$key.']='.$value;
					}
				}else{
					$params[] = $k.'='.$v;
				}
			}	
			//所属客户
			if(isset($where['customer_id'])){
				$c_where['name'] = array('like','%'.$where['customer_id'][1].'%');
				$c_where['is_deleted'] = 0;
				$c_where['owner_role_id'] = array('in',$below_ids); //过滤权限
				$customer_str = M('Customer')->where($c_where)->getField('customer_id',true);
				unset($where['customer_id']);
			}
		}
		//高级搜索字段
		$fields_list_data = M('Fields')->where(array('model'=>array('in',array('','contacts')),'is_main'=>1))->field('field,form_type')->select();
		foreach($fields_list_data as $k=>$v){
			$fields_data_list[$v['field']] = $v['form_type'];
		}

		$fields_search = array();
		foreach($params as $k=>$v){
			if(strpos($v,'[condition]=') || strpos($v,'[value]=') || strpos($v,'[state]=') || strpos($v,'[city]=') || strpos($v,'[area]=') || strpos($v,'[start]=') || strpos($v,'[end]=')){
				$field = explode('[',$v);

				if(strpos($field[0],'.')){
					$ex_field = explode('.',$field[0]);
					$field[0] = $ex_field[1];
				}
				if(strpos($v,'[condition]=')){
					$condition = explode('=',$v);
					$fields_search[$field[0]]['field'] = $field[0];
					$fields_search[$field[0]]['condition'] = $condition[1];
				} elseif (strpos($v,'[state]=')) {
					$state = explode('=',$field[1]);
					$fields_search[$field[0]]['state'] = $state[1];
				} elseif (strpos($v,'[city]=')) {
					$city = explode('=',$field[1]);
					$fields_search[$field[0]]['city'] = $city[1];
				} elseif (strpos($v,'[area]=')) {
					$area = explode('=',$field[1]);
					$fields_search[$field[0]]['area'] = $area[1];
				} elseif (strpos($v,'[start]=')) {
					$start = explode('=',$field[1]);
					$fields_search[$field[0]]['field'] = $field[0];
					$fields_search[$field[0]]['start'] = $start[1];
				} elseif (strpos($v,'[end]=')) {
					$end = explode('=',$field[1]);
					$fields_search[$field[0]]['end'] = $end[1];
				}else{
					$value = explode('=',$v);
					if($fields_search[$field[0]]['field']){
						$fields_search[$field[0]]['value'] = $value[1];
					}else{
						$fields_search[$field[0]]['field'] = $field[0];
						$fields_search[$field[0]]['condition'] = 'eq';
						$fields_search[$field[0]]['value'] = $value[1];
					}
				}
				$fields_search[$field[0]]['form_type'] = $fields_data_list[$field[0]];
			}
		}
		$this->fields_search = $fields_search;

		//所属客户
		if ($customer_str) {
			$where['rContactsCustomer.customer_id'] = array('in',$customer_str);
		}
		if(trim($_GET['act']) == 'excel'){
			if(checkPerByAction('contacts','excelexport')){
				$contactsList = $d_contacts->where($where)->order($order)->select();		
				$this->excelExport($contactsList);
			}else{
				alert('error',  L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}
		}else{
			if($_GET['listrows']){
				$listrows = intval($_GET['listrows']);
				$params[] = "listrows=" . intval($_GET['listrows']);
			}else{
				$listrows = 15;
				$params[] = "listrows=".$listrows;
			}
			if (!empty($_GET['by'])) {
				$params[] = "by=".trim($_GET['by']);
			}
			$count = $d_contacts->where($where)->count();
			$p_num = ceil($count/$listrows);
			if ($p_num < $p) {
				$p = $p_num;
			}
			$contacts_list = $d_contacts->where($where)->order($order)->page($p.','.$listrows)->select();
			foreach ($contacts_list as $k => $v) {		
				$contacts_list[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
			}
			import("@.ORG.Page");
			$Page = new Page($count,$listrows);	
			$Page->parameter = implode('&', $params);
			$this->assign('page',$Page->show());
			$this->assign("count",$count);
			$this->listrows = $listrows;
			$this->assign('contactsList',$contacts_list);
			$this->field_array = getIndexFields('contacts');
			$this->field_list = getMainFields('contacts');
			$this->alert = parseAlert();
			$this->display();
		}
	}

	//弹出框列表
	public function listDialog(){
		$d_contacts = D('ContactsView');
		$m_customer = M('Customer');
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$where = array();
		$params = array();
		$order = "contacts.update_time desc,contacts.contacts_id asc";
		if (!isset($where['is_deleted'])) {
			$where['is_deleted'] = 0;
		}
		
		if ($_REQUEST["field"]) {
			$field = $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			$field_date = M('Fields')->where('is_main=1 and (model="" or model="contacts") and form_type="datetime"')->select();
			foreach($field_date as $v){
				if($field == $v['field'] || $field == 'customer.create_time' || $field == 'customer.update_time') $search = is_numeric($search)?$search:strtotime($search);
			}
			if($field =="customer_id"){
				$c_where['name'] = array('like','%'.$search.'%');
				$customer_ids = $m_customer->where($c_where)->getField('customer_id',true);
				$where[$field] = array('in',$customer_ids);
			}else{
				switch ($condition) {
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
			$params = array('field='.$field, 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}

		//权限控制(根据联系人列表权限)
		$below_ids = getPerByAction('contacts','index');
		$where_customer['is_deleted'] = 0;
		$where_customer['owner_role_id'] = array('in', $below_ids);
		//权限范围内的customer_id
		$owner_customer_ids = $m_customer->where($where_customer)->getField('customer_id',true);
		$customer_str = $owner_customer_ids;
		$where['RContactsCustomer.customer_id'] = array('in',$customer_str);

		import("@.ORG.DialogListPage");
		$contactsList = $d_contacts->where($where)->order($order)->page($p.',10')->select();
		foreach ($contactsList as $k => $v) {		
			$contactsList[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
			$contactsList[$k]["customer_name"] = $m_customer ->where('customer_id =%d',$v['customer_id'])->getField('name');
		}
		$count = $d_contacts->where($where)->count();
		$this->search_field = $_REQUEST;//搜索信息
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());
		$this->assign('contactsList',$contactsList);
		$this->display();
	}
	
	public function delete(){
		$m_contacts = M('contacts');
		$RContactsCustomer = M('RContactsCustomer');

		$m_config = M('Config');
		$outdays = $m_config->where('name="customer_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

		$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
		$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
		$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
		$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
		
		if ($_POST['contacts_id']) {
			if (!session('?admin')) {
				foreach ($_POST['contacts_id'] as $value) {
					//检查权限
					$customer_id = $RContactsCustomer->where('contacts_id = %d', $value)->getField('customer_id');
					//判断联系人所在客户是否在客户池，如果在则不判断权限
					$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
					if ($openrecycle == 2 && ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1))) {
						if (!in_array($customer_info['owner_role_id'], $this->_permissionRes) && !session('?admin')) {
							$this->ajaxReturn('','您没有此权利！',0);
						}
					}
				}
			}
			if ($m_contacts->where('contacts_id in (%s)', implode(',', $_POST['contacts_id']))->delete()) {
				$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
			} else {
				$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
			}
		}elseif($_GET['id']){
			$contacts_id = intval($_GET['id']);
			$contacts = $m_contacts->where('contacts_id = %d', $contacts_id)->find();
			if (!$contacts) {
				$this->ajaxReturn('',L('YOU WANT TO DELETE THE RECORD DOES NOT EXIST'),0);
			}
			//检查权限
			$customer_id = $RContactsCustomer->where('contacts_id = %d', $contacts_id)->getField('customer_id');
			//判断联系人所在客户是否在客户池，如果在则不判断权限
			$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
			if ($openrecycle == 2) {
				if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
					if (!in_array($customer_info['owner_role_id'], $this->_permissionRes) && !session('?admin')) {
						$this->ajaxReturn('','您没有此权利！',0);
					}
				}
			}
			if($m_contacts->where('contacts_id = %d', $contacts_id)->delete()){
				$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
			} else {
				$this->ajaxReturn('',L('DELETE FAILED'),0);
			}
		}else{
			$this->ajaxReturn('',L('PLEASE CHOOSE TO DELETE THE CONTACT'),0);
		}
	}

	//当联系人为首要联系人时调用的删除方法
	public function mDelete(){
		$contacts_id = intval($_GET['id']);
		$module_id = intval($this->_get('module_id'));
		$m_customer = M('Customer');
		$m_contacts = M('Contacts');
		$RContactsCustomer = M('RContactsCustomer');

		//检查权限
		$customer_id = $RContactsCustomer->where('contacts_id = %d', $contacts_id)->getField('customer_id');
		
		//判断联系人所在客户是否在客户池，如果在则不判断权限
		$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
		$m_config = M('Config');
		$outdays = $m_config->where('name="customer_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

		$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
		$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
		$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
		$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
		if ($openrecycle == 2) {
			if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
				if (!in_array($customer_info['owner_role_id'], $this->_permissionRes)) {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			}
		}
		
		if($m_customer->where("customer_id = %d and contacts_id = %d", $module_id, $contacts_id)->setField('contacts_id', 0)){
			if($m_contacts->where('contacts_id = %d', $contacts_id)->delete()){
				$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
			} else {
				$this->ajaxReturn('',L('DELETE FAILED'),0);
			}
		}else{
			$this->ajaxReturn('',L('DELETE FAILED'),0);
		}
	}

	//商机详情下关联联系人
	public function checkListDialog(){
		if(empty($_GET['id']) || empty($_GET['business_id'])){
			echo '<div class="alert alert-error">参数错误！</div>';die();
		}
		$customer_id = intval($_GET['id']);
		//权限控制(根据联系人列表权限)
		$below_ids = getPerByAction('contacts','index');
		$where_customer = array();
		$where_customer['is_deleted'] = 0;
		$where_customer['owner_role_id'] = array('in', $below_ids);
		//权限范围内的customer_id
		$owner_customer_ids = M('Customer')->where($where_customer)->getField('customer_id',true);
		if(in_array($customer_id,$owner_customer_ids)){
			$contacts_ids = M('RContactsCustomer') ->where('customer_id = %d', $customer_id)->getField('contacts_id', true);
		}else{
			$contacts_ids = array();
		}
		
		$contacts_ids[] = '-1';
		$m_contacts = M('Contacts');
		$where = array();
		$business_id = intval($_GET['business_id']);

		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']);
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);

			if ('create_time' == $field || 'update_time' == $field) {
				$search = is_numeric($search)?$search:strtotime($search);
			}
			switch ($condition) {
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
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}
		$params[] = 'id='.$customer_id;
		$params[] = 'business_id='.$business_id;
		//过滤已关联的联系人
		$relation_contacts_ids = M('RBusinessContacts')->where('business_id = %d',$business_id)->getField('contacts_id',true);
		//数组差集
		if($relation_contacts_ids){
			$diff_contacts_ids = array_diff($contacts_ids, $relation_contacts_ids);
		}else{
			$diff_contacts_ids = $contacts_ids;
		}
		$where['contacts_id'] = array('in',$diff_contacts_ids);
		$where['is_deleted'] = array('neq',1);

		import("@.ORG.DialogListPage");
		$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
		$contactsList = $m_contacts->where($where)->order('create_time desc')->page($p.',10')->select();
		$count = $m_contacts->where($where)->count();
		$this->contactsList = $contactsList;
		$this->search_field = $_REQUEST;//搜索信息

		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());

		$this->customer_id = $customer_id;
		$this->business_id = $business_id;
		$this->display();
	}
	
	//设为首要联系人
	public function changeToFirstContact(){
		$id = $_GET['id'];
		$customer_id = $_GET['customer_id'];
		if(isset($id) && isset($customer_id)){
			$m_customer = M('Customer');
			$data['contacts_id'] = $id;
			if($m_customer->where('customer_id = %d',$customer_id)->save($data)){
				alert('success', L('SET THE FIRST CONTACT SUCCESS') ,$_SERVER['HTTP_REFERER'].'#tab3');
			}else{
				alert('error', L('NO CHANGE INFORMATION') ,$_SERVER['HTTP_REFERER'].'#tab3');
			}
		}else{
			alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}
	
	//弹出框
	public function radioListDialog(){
		$customer_id = $_GET['id'] ? intval($_GET['id']) : '';
		$rcc =  M('RContactsCustomer');
		$m_contacts = D('ContactsTempView');
		$m_customer = M('Customer');
		$where = array();
		$where['customer.owner_role_id'] = array('in', getPerByAction('customer','index'));
		$where['is_deleted'] = 0;
		if($customer_id){
			$contacts_id = $rcc->where('customer_id = %d', $customer_id)->getField('contacts_id', true);
			$where['contacts_id'] = array('in', implode(',', $contacts_id));
			$this->customer_id = $customer_id;
		}
		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']);
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);

			if ('create_time' == $field || 'update_time' == $field) {
				$search = is_numeric($search)?$search:strtotime($search);
			}
			if($field =="customer_id"){
				$c_where['name'] = array('like','%'.$search.'%');
				$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true);
				$contacts_ids = M('RContactsCustomer')->where(array('customer_id'=>array('in',$contacts_ids)))->getField('contacts_id',true);
				$where['contacts_id'] = array('in',$contacts_ids);
			}else{
				switch ($condition) {
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
		$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
		import("@.ORG.DialogListPage");

		$list = $m_contacts->where($where)->order('update_time desc')->page($p.',10')->select();
		$count = $m_contacts->where($where)->order('update_time desc')->count();
		foreach ($list as $k=>$value) {
			$customer_id = '';
			$customer_id = $rcc->where('contacts_id = %d', $value['contacts_id'])->getField('customer_id');
			$list[$k]['customer'] = $m_customer->where('customer_id = %d', $customer_id)->field('name,customer_id')->find();
		}
		$this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
		$this->count_num = $count;
		//获取下级和自己的岗位列表,搜索用
		$below_ids = getSubRoleId(false);
		$d_role_view = D('RoleView');
		$this->role_list = $d_role_view->where('role.role_id in (%s)', implode(',', $below_ids))->select();
		$this->contactsList = $list;

		$this->search_field = $_REQUEST;//搜索信息
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());

        $this->field_array = getIndexFields('contacts');
		$this->display();
	}
	
	//联系人二维码
	public function qrcode(){
		$contacts_id = intval($_GET['contacts_id']);
		//判断权限
		$r_contacts_customer = M('RContactsCustomer');
		$below_ids = getPerByAction('contacts','view');
		$customer_idArr = M('Customer')->where(array('owner_role_id'=>array('in', $below_ids)))->getField('customer_id', true);
		$customer_id = $r_contacts_customer->where('contacts_id = %d', $contacts_id)->getField('customer_id');
		
		//判断联系人所在客户是否在客户池，如果在则不判断权限
		//查询客户数据
		$customer = D('CustomerView')->where('customer.customer_id = %d', $customer_id)->find();
		$outdays = M('Config') -> where('name="customer_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

		if($customer['owner_role_id'] != 0 && ($customer['update_time'] > $outdate || $customer['is_locked'] == 1) && !in_array($customer_id, $customer_idArr)){
			echo 3;$this->error('您没有此权利！');
		}
		if($contacts = M('Contacts')->where('contacts_id = %d', $contacts_id)->find()){
			$customer_id = M('RContactsCustomer')->where('contacts_id = %d',$contacts_id)->getField('customer_id');
			$contacts['customer'] = M('Customer')->where('customer_id = %d', $customer_id)->getField('name');
			$qrOpt = '';
			$qrOpt = "BEGIN:VCARD\nVERSION:3.0\n";
			$qrOpt .= $contacts['name'] ? ("N:".$contacts['name']."\n") : "";
			$qrOpt .= $contacts['telephone'] ? ("TEL:".$contacts['telephone']."\n") : "";
			$qrOpt .= $contacts['email'] ? ("EMAIL;PREF;INTERNET:".$contacts['email']."\n") : "";
			$qrOpt .= $contacts['customer'] ? ("ORG:".$contacts['customer']."\n") : "";	
			$qrOpt .= $contacts['post'] ? ("TITLE:".$contacts['post']."\n") : "";
			$qrOpt .= $contacts['address'] ? ("ADR;WORK;POSTAL:".$contacts['address']."\n") : "";
			$qrOpt .= "END:VCARD";
			
			$png_temp_dir = UPLOAD_PATH.'/qrpng/';
			$filename = $png_temp_dir.$contacts['contacts_id'].'.png';
			if (!is_dir($png_temp_dir) && !mkdir($png_temp_dir, 0777, true)) { echo 3;$this->error('二维码保存目录不可写'); }

			import("@.ORG.QRCode.qrlib");
			QRcode::png($qrOpt, $filename, 'M', 4, 2);
			header('Content-type: image/png');	
			header("Content-Disposition: attachment; filename=".$contacts['contacts_id'].'.png');
			echo file_get_contents($filename);
			unlink($filename);
		}
	}
}