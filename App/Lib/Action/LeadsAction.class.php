<?php
/**
*线索模块
*
**/
class LeadsAction extends Action
{
    protected $isMarket =   false;
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('transformeddialog','lead_move','detail','transform','checkinfo','changecontent','getaddchartbyroleid','getownchartbyroleid','check','receive','fenpei','batchreceive', 'assigndialog', 'batchassign', 'revert', 'validate', 'remove','excelimportdownload','getcurrentstatus','excelimportact','change_customer','field_save')
		);
		B('Authenticate', $action);
		$this->_permissionRes = array_merge( getPerByAction(MODULE_NAME,ACTION_NAME, true), [session('user_id')] ) ;

		// 市场部账号标记
		if( session( 'role_id' ) == 70 ){
            $this->isMarket     =   true;
        }
	}
	/**
	字段查重
	**/
	public function checkinfo(){
		if($this->isAjax()){
			$field_value = $_POST['field_value'];
			$field_name = $_POST['field_name'];
			$leads_id = intval($_POST['leads_id']);
			$m_leads = M('Leads');
			$m_customer = M('Customer');
			$where[$field_name] = $field_value;
			if($leads_id){
				$where['leads_id'] = $leads_id;
			}
			$where['is_deleted'] = 0;
			$info = $m_leads ->where($where)->field('owner_role_id,creator_role_id,update_time,leads_id')->find(); //判断是否存在，如存在获取负责人
			if($info){
				$outdays = M('config') -> where('name="leads_outdays"')->getField('value'); //获取自动回收时间
				$outdate = empty($outdays) ? time() : time()-86400*$outdays;
				$url = U('leads/view','id='.$info['leads_id']);
				if($info['owner_role_id'] == 0 || $info['update_time'] < $outdate){ //如果负责人为空或超时未跟进未线索池
					$create_role_name = M('user')->where('role_id =%d',$info['creator_role_id'])->getField('name');
					$message = '该线索已存在<a target="_blank" href="'.$url.'">线索池</a>中！创建人为:'.$create_role_name;
				}else{
					$owner_role_name = M('user')->where('role_id =%d',$info['owner_role_id'])->getField('name');
					$message = '该线索已存在<a target="_blank" href="'.$url.'">线索</a>中！负责人为:'.$owner_role_name;
				}
				$this->ajaxReturn($message,'线索重复！',1);
			}else{
				$this->ajaxReturn(0,'为空！',0);
			}
		}
	}
	/**
	*线索名验重
	*
	**/
	public function check(){
		if($_REQUEST['leads_id']){
			$where['leads_id'] = array('neq',$_REQUEST['leads_id']);
		}
		import("@.ORG.SplitWord");
		$sp = new SplitWord();
		$m_leads = M('Leads');
		$m_customer = M('Customer');
		//ignore words
		$useless_words = array(L('COMPANY'),L('LIMITED'),L('OF'),L('COMPANY_LIMITED'));
		if ($this->isAjax()) {
			$split_result = $sp->SplitRMM($_POST['name']);
			if(!is_utf8($split_result)) $split_result = iconv("GB2312//IGNORE", "UTF-8", $split_result) ;
			$result_array = explode(' ',trim($split_result));
            if(count($result_array) < 2){
                $this->ajaxReturn(0,'',0);
                die;
            }
			foreach($result_array as $k=>$v){
				if(in_array($v,$useless_words)) unset($result_array[$k]);
			}

			$leads_commpany_list = $m_leads->where($where)->getField('name', true);
			$customer_commpany_list = $m_customer->getField('name', true);

			$search_array = array();
			foreach($leads_commpany_list as $k=>$v){
				$search = 0;
				foreach($result_array as $k2=>$v2){
					if(strpos($v, $v2) > -1){
						$v = str_replace("$v2","<span style='color:red;'>$v2</span>", $v, $count);
						$search += $count;
					}
				}
				if($search > 2) $search_array[$k] = array('value'=>$v,'search'=>$search);
			}
			$seach_sort_result['leads'] = array_sort($search_array,'search','desc');

			$customer_search_array = array();
			foreach($customer_commpany_list as $k=>$v){
				$search = 0;
				foreach($result_array as $k2=>$v2){
					if(strpos($v, $v2) > -1){
						$v = str_replace("$v2","<span style='color:red;'>$v2</span>", $v, $count);
						$search += $count;
					}
				}
				if($search > 2) $customer_search_array[$k] = array('value'=>$v,'search'=>$search);
			}
			$seach_sort_result['customer'] = array_sort($customer_search_array,'search','desc');

			$leads_search = $seach_sort_result['leads'];
			$customer_search = $seach_sort_result['customer'];

			if(empty($leads_search) && empty($customer_search)){
				$this->ajaxReturn(0,L('YOU_CAN_ADD'),0);
			}else{
				$this->ajaxReturn($seach_sort_result,L('EXIST_SAME_LEADS_OR_COMPANY'),1);
			}
		}
	}

	/**
	*线索字段ajax验证
	*
	**/
	public function validate() {
		if($this->isAjax()){
            if(!$this->_request('clientid','trim') || !$this->_request($this->_request('clientid','trim'),'trim')) $this->ajaxReturn("","",3);
            $field = M('Fields')->where('model = "leads" and field = "%s"',$this->_request('clientid','trim'))->find();
            $m_leads = $field['is_main'] ? D('Leads') : D('LeadsData');
            $where[$this->_request('clientid','trim')] = array('eq',$this->_request($this->_request('clientid','trim'),'trim'));
            if($this->_request('id','intval',0)){
                $where[$m_leads->getpk()] = array('neq',$this->_request('id','intval',0));
            }
			if($this->_request('clientid','trim')) {
				if ($m_leads->where($where)->find()) {
					$this->ajaxReturn("","",1);
				} else {
					$this->ajaxReturn("","",0);
				}
			}else{
				$this->ajaxReturn("","",0);
			}

		}
	}

	/**
	*线索列表页面
	*
	**/
	public function index(){
		$by = isset($_GET['by']) ? trim($_GET['by']) : 'me';
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,true);
		$d_v_leads = D('LeadsView');
		$outdays = M('config') -> where('name="leads_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
		$where = array();
		$params = array();
		$order = "create_time desc";
		$todaywhere['have_time'] = $where['have_time'] = array('egt',$outdate);

		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}

		switch ($by) {
			case 'today' :
				$where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt',0), 'and');
				$where['owner_role_id'] = session('role_id');
				break;
			case 'week' :
				$w = date("w", time()); //获取当前周的第几天 周日是 0 周一 到周六是 1 -6
				$d = $w ? $w - 1 : 6; //如果是周日 -6天
				$start_week = strtotime("".date("Y-m-d")." -".$d." days"); //本周开始时间
				$end_week = strtotime("".date("Y-m-d",$start_week)." +7 days"); //本周结束时间

				$where['nextstep_time'] = array(array('gt',$start_week), array('lt', $end_week),'and');
				break;
			case 'month' :
				$where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-01', strtotime('+1 month')))), array('gt', strtotime(date('Y-m-01'))),'and');
				break;
			case 'd7' :
				$where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*6);
				break;
			case 'd15' :
				$where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*14);
				break;
			case 'd30' :
				$where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*29);
				break;
			case 'add' : $order = 'create_time desc';  break;
			case 'update' : $order = 'update_time desc';  break;
			case 'sub' : $where['owner_role_id'] = array('in',implode(',', $below_ids)); break;
			case 'subcreate' : $where['creator_role_id'] = array('in',implode(',', $below_ids)); break;
			case 'public' :
				unset($where['have_time']);
				$where['_string'] = "leads.owner_role_id=0 or leads.have_time < $outdate";
				break;
			case 'deleted': $where['is_deleted'] = 1;unset($where['have_time']); break;
			case 'transformed' : $where['is_transformed'] = 1; break;
            case 'me' : $where['_string'] = ['owner_role_id='.session('role_id').' OR merge='.session('role_id')]; break;
			default :
                $where['_string'] = ['owner_role_id='.session('role_id').' OR merge='.session('role_id')];
//				$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
				break;
		}
		$this->by = $by;
		if ($by != 'deleted') {
			$where['is_deleted'] = array('neq',1);
		}
		if ($by != 'transformed' && $by != 'deleted') {
			$where['is_transformed'] = array('neq',1);
		}
		//权限判断
		if ($this->_permissionRes && !isset($where['owner_role_id']) && $by != 'public') {
			if($by != 'deleted'){
				$where['owner_role_id'] = array('in', $this->_permissionRes);
			}else {
				$where['owner_role_id'] = array('in', '0,'.implode(',', $this->_permissionRes));
			}
		}

		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']);

			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);

            if ($this->_request('state')){

				$state = $this->_request('state', 'trim');
				$address_where[] = '%'.$state.'%';

				if($this->_request('city')){
					$city = $this->_request('city', 'trim');
					$address_where[] = '%'.$city.'%';

					if($this->_request('area')){
						$area = $this->_request('area', 'trim');
						$address_where[] = '%'.$area.'%';
					}
				}

				if($search) $address_where[] = '%'.$search.'%';

				$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'state='.$this->_request('state','trim'), 'city='.$this->_request('city','trim'),'area='.$this->_request('area','trim'),'search='.$this->_request('search','trim'));

				if($condition == 'not_contain'){
					$where[$field] = array('notlike', $address_where, 'OR');
				}else{
					$where[$field] = array('like', $address_where, 'AND');
				}
			}else{
				$field_date = M('Fields')->where('is_main=1 and (model="" or model="leads") and form_type="datetime"')->select();
				foreach($field_date as $v){
					if($field == $v['field'] || $field == 'customer.create_time' || $field == 'customer.update_time') $search = is_numeric($search)?$search:strtotime($search);
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
			//过滤不在权限范围内的role_id
			if(trim($_REQUEST['field']) == 'owner_role_id'){
				if(!in_array(trim($search),$below_ids)){
					$where['owner_role_id'] = array('in',$below_ids);
				}
			}
		}
		//多选类型字段
		$check_field_arr = M('Fields')->where(array('model'=>'leads','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
		//高级搜索
		if(!$_GET['field']){
			foreach($_GET as $k=>$v){
				if($k != 'act' && $k != 'content' && $k != 'p' && $k !='condition' && $k != 'listrows' && $k !='daochu' && $k !='this_page' && $k !='current_page' && $k !='export_limit' && $k !='desc_order' && $k !='asc_order' && $k !='by'){
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
								$k = 'leads.create_time';
							}elseif($k == 'update_time'){
								$k = 'leads.update_time';
							}
							//时间段查询
							if ($v['start'] && $v['end']) {
								$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
							} elseif ($v['start']) {
								$where[$k] = array('egt',strtotime($v['start']));
							} else {
								$where[$k] = array('elt',strtotime($v['end'])+86399);
							}
						} elseif (($v['value']) != '') {
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
			//过滤不在权限范围内的role_id
			if(isset($where['owner_role_id'])){
				if(is_array($where['owner_role_id']) && !empty($where['owner_role_id']['1']) && !in_array(intval($where['owner_role_id']['1']),$this->_permissionRes)){
					$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					// $where['owner_role_id'] = array('in',$below_ids);
				}
			}
		}
        if( array_key_exists( 'block', $where ) ){
		    unset( $where['block'] );
        }
		//高级搜索字段
		$fields_list_data = M('Fields')->where(array('model'=>array('in',array('','leads')),'is_main'=>1))->field('field,form_type')->select();
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

        /*** 线索校区 分类 start ***/
        $blockModel         =   M('Block');
        $blocks             =   $blockModel->field('id,name,person_id,person_name,department_id')->select();
        /*** 线索校区 分类 end ***/
        $clueCateList   =   [];
        $clueCateModel      =   M('LeadCategory');
        if( $blockId=I('get.block') ){
            $clueCateList       =   $clueCateModel->where('block_id='.$blockId)->select();
        }elseif( !session('?admin') ){
            $clueCateList       =   $clueCateModel->where('block_id='.session('block'))->select();
        }
        /*** 添加线索分类 start ***/
        if( $clueCateId = I('get.cluecate') ){
            $where['cluecate']      =   ['eq',(int)$clueCateId];
        }
        /*** 添加线索分类 end ***/
		if(trim($_GET['act'] == 'sms'))
		{
			if(!checkPerByAction('setting','sendsms')){
				alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}else{
				$leadsList = $d_v_leads->where($where)->select();
				$contacts = array();
				foreach ($leadsList as $k => $v) {
					$contacts[] = array('name'=>$v['contacts_name'], 'customer_name'=>$v['name'], 'telephone'=>trim($v['mobile']));
				}
				$this->contacts = $contacts;
				$this->alert = parseAlert();
				$this->display('Setting:sendsms');
			}
		}
		elseif(trim($_GET['act']) == 'excel')
        {
			if(checkPerByAction('leads','excelexport')){
				$order = $order ? $order : 'create_time desc';
				$dc_id = $_GET['daochu'];
				if($dc_id !=''){
					$where['leads_id'] = array('in',$dc_id);
				}
				$current_page = intval($_GET['current_page']);
				$export_limit = intval($_GET['export_limit']);
				$limit = ($export_limit*($current_page-1)).','.$export_limit;
				$leadsList = $d_v_leads->where($where)->order($order)->limit($limit)->select();
				$this->excelExport($leadsList);
			}else{
				alert('error',  L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}
		}
		else
		{
			if($_GET['listrows']){
				$listrows = intval($_GET['listrows']);
				$params[] = "listrows=" . intval($_GET['listrows']);
			}else{
				$listrows = 15;
				$params[] = "listrows=".$listrows;
			}
			$count = $d_v_leads->where($where)->count();

			$p_num = ceil($count/$listrows);
			if($p_num<$p){
				$p = $p_num;
			}
			$list = $d_v_leads->where($where)->page($p.','.$listrows)->order($order)->select();
			import("@.ORG.Page");
			$Page = new Page($count,$listrows);
			if (!empty($_GET['by'])) {
				$params[] = 'by='.trim($_GET['by']);
			}
			$this->parameter = implode('&', $params);
			//by_parameter(特殊处理)
            $this->by_parameter = str_replace('by='.$_GET['by'], '', implode('&', $params));
            $this->by_parameter = str_replace('cluecate='.I('get.cluecate'), '', implode('&', $params));

			if ($_GET['desc_order']) {
				$params[] = "desc_order=" . trim($_GET['desc_order']);
			} elseif($_GET['asc_order']){
				$params[] = "asc_order=" . trim($_GET['asc_order']);
			}
			$Page->parameter = implode('&', $params);

			$this->assign('page', $Page->show());

			if($by == 'deleted') {
				foreach ($list as $k => $v) {
					$list[$k]["delete_role"] = getUserByRoleId($v['delete_role_id']);
					$list[$k]["owner"] = getUserByRoleId($v['owner_role_id']);
					$list[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
				}
			}elseif($by == 'transformed'){
				$m_business = M('Business');
				$m_contacts = M('Contacts');
				$m_customer = M('Customer');
				foreach ($list as $k => $v) {
					$list[$k]["owner"] = getUserByRoleId($v['owner_role_id']);
					$list[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
					$list[$k]["transform_role"] = getUserByRoleId($v['transform_role_id']);
					$list[$k]["business_name"] = $m_business->where('business_id = %d', $v['business_id'])->getField('name');
					$list[$k]["contacts_name"] = $m_contacts->where('contacts_id = %d', $v['contacts_id'])->getField('name');
					$list[$k]["customer_name"] = $m_customer->where('customer_id = %d', $v['customer_id'])->getField('name');
				}
			}else{
				$m_remind = M('Remind');
				foreach ($list as $k => $v) {
					$days = 0;
					//提醒
					$remind_info = array();
					$remind_info = $m_remind->where(array('module'=>'leads','module_id'=>$v['leads_id'],'create_role_id'=>session('role_id'),'is_remind'=>array('neq',1)))->order('remind_id desc')->find();
					$list[$k]['remind_time'] = !empty($remind_info) ? $remind_info['remind_time'] : '';
					$list[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
					$list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
					$days = M('leads')->where('leads_id = %d', $v['leads_id'])->getField('have_time');
					$list[$k]["days"] = $outdays-floor((time()-$days)/86400);
				}
			}

			$todaywhere['is_deleted'] = array('neq',1);
			$todaywhere['is_transformed'] = array('neq',1);
			$todaywhere['owner_role_id'] = session('role_id');
			$todaywhere['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt', 0), 'and');
			$this->todaycount = $d_v_leads->where($todaywhere)->count();

			$this->listrows = $listrows;
			$d_role_view = D('RoleView');
			$this->role_list = $d_role_view->where('role.role_id in (%s)', implode(',', $below_ids))->select();
			$this->field_array = getIndexFields('leads');
            $farr = getMainFields('leads');
            $news=[];
            foreach($farr as $kk=>$vv){
                if($vv['name']!='线索分类'){
                    $news[] = $vv;
                }
            }
			$this->field_list =$news;

//			var_dump($farr);
			$this->alert = parseAlert();
            // self、public 字段处理

            if( $this->by == 'public' ){
                $public_only        =   ['nextstep_time','now_university','now_major','expect_major'];
                // 去除列显示
                $this->field_array      =   array_filter( $this->field_array, function($v) use($public_only){
                    return (!in_array( $v['field'], $public_only ));
                } );
                // 去除集合值
                foreach( $list as $key => $value ){
                    foreach( $public_only as $only ){
                        unset( $value[$only] );
                    }
                }
            }else{
                $self_only          =   ['cluecate','market_activity'];
                $this->field_array      =   array_filter( $this->field_array, function($v) use($self_only){
                    return (!in_array( $v['field'], $self_only ));
                } );
                // 去除集合值
                foreach( $list as $key => $value ){
                    foreach( $self_only as $only ){
                        unset( $value[$only] );
                    }
                }
            }
//            dump($list);exit;
			$this->assign([
			    'blocks'        =>  $blocks,
                'clueCateList'  =>  $clueCateList,
                'leadslist'     =>  $list,
                'count'         =>  $count,
            ]);
			$this->display();
		}
	}

	public function lead_move ()
    {
        // TODO 参数接收、合法都验证、更改拥有着信息、通知拥有着
        $params             =   I();
        $to_role_id         =   array_key_exists( 'ownerId', $params )
            ?   intval($params['ownerId']) : false;
        $id_arrays          =   array_key_exists( 'id_array', $params )
            ?   array_filter( array_map( 'intval', $params['id_array'] ), function($v){
                return $v;
            } ) : false;

        if( $to_role_id && $id_arrays ){
            //
            if( in_array( $to_role_id, $this->_permissionRes ) ){
                $model      =   M('Leads');

                // 7-10 新增 线索转移操作记录功能
                // 获取当前要操作线索的原始数据
                $need_pars = M('Leads')->field('leads_id,owner_role_id,have_time,cluecate')->where(['leads_id'=>['IN',$id_arrays]])->select();

                // 7-11 修复线索分类Bug
                // 获取新负责人岗位名称(全称)
                $to_user_full_name = M('User')->field('full_name')->where(['role_id'=>['eq',$to_role_id]])->find()['full_name'];
                if ( (strpos($to_user_full_name,'上海') !== false) || (strpos($to_user_full_name,'总部') !== false) )
                {
                    $to_block_id = 1;// 上海
                }elseif(strpos($to_user_full_name,'苏州') !== false || strpos($to_user_full_name,'江苏') !== false || strpos($to_user_full_name,'南京') !== false ){
                    $to_block_id = 4;// 江苏
                }elseif (strpos($to_user_full_name,'成都') !== false){
                    $to_block_id = 3;// 成都
                }else{
                    $to_block_id = 2;// 东京
                }
                foreach ($need_pars as $k => $v)
                {
                    // 通过线索分类id 获取线索分类备注
                    $cate_name = M('LeadCategory')->field('remark')->find($v['cluecate'])['remark'];
                    // 通过线索分类备注 和 所属校区ID 获取新分类的ID
                    $to_cate_id = M('LeadCategory')->field('id,name,remark')->where(['remark'=>['eq',$cate_name],'block_id'=>['eq',$to_block_id]])->find()['id'];
                    // 创建线索转移记录
                    M('LeadsTimeoutLog')->add([
                        'leads_id'=>$v['leads_id'],// 线索ID
                        'old_owner_role_id'=>$v['owner_role_id'],// 老负责人
                        'new_owner_role_id'=> $to_role_id,// 新负责人
                        'get_time'=>time(),// 分配时间
                        'create_user'=>session('role_id')// 操作人员
                    ]);
                    // 修改线索负责人和所属分类
                    $model->where(['leads_id'=>['eq',$v['leads_id']]])->save(['owner_role_id'=>$to_role_id,'cluecate'=>$to_cate_id]);
                }

                // $model->where(['owner_role_id'=>['eq', session('role_id')], 'leads_id'=>['in',implode(',',$id_arrays)]])
//                $model->where(['leads_id'=>['in',implode(',',$id_arrays)]])
//                    ->save(['owner_role_id'=>$to_role_id]);
                sendMessage( $to_role_id, '线索分配通知', 1 );
                $this->ajaxReturn( ['status'=>true] );
            }else{
                // 非法操作
                $this->ajaxReturn( ['status'=>false,'remark'=>'非法操作'] );
            }
        }else{
            // 操作异常
            $this->ajaxReturn( ['status'=>false, 'remark'=>'操作异常，请联系管理员'] );
        }
    }

	/**
	*线索添加页面
	*
	**/
	public function add(){
		if($this->isPost()){
			$m_leads = D('Leads');
			$m_leads_data = D('LeadsData');
			$field_list = M('Fields')->where('model = "leads"  and in_add = 1')->order('order_id')->select();
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
							$b = array_filter($_POST[$v['field']]);
							$_POST[$v['field']] = !empty($b) ? implode(chr(10),$b) : '';
						}
					break;
				}
			}
			if($m_leads->create()){
				if($m_leads_data->create()!==false){
					if($_POST['nextstep_time']) $m_leads->nextstep_time = $_POST['nextstep_time'];
					$m_leads->create_time = time();
					$m_leads->update_time = time();
					$m_leads->have_time = time();
					$m_leads->creator_role_id = session('role_id');
					    // 校内人事录入
					if( session('block') > 0 ){
                        $m_leads->owner_role_id = session('role_id');
                    }else{
					    // 校外人录入 属于校区负责人
                        $cluecate               =   (int)I('cluecate') ?: 1;
                        $cateInfo               =   M('LeadCategory')->field('block_id')->find($cluecate);
                        $personInfo             =   M('Block')->field('person_id')->find( $cateInfo['block_id'] );
                        $m_leads->owner_role_id =   $personInfo['person_id'];
                    }
					if ($leads_id = $m_leads->add()) {
						$m_leads_data->leads_id = $leads_id;
						$m_leads_data->add();
						actionLog($leads_id);
						if($_POST['submit'] == L('SAVE')) {
						    // 调到详情页
							// alert('success', L('LEADS_ADD_SUCCESS'), U('leads/view','id='.$leads_id));
                            // 调到列表页
                            alert('success', L('LEADS_ADD_SUCCESS'), U('leads/index'));
						} else {
                            alert('success', L('LEADS_ADD_SUCCESS'), U('leads/index'));
						}
					} else {
						$this->error(L('INVALIDATE_PARAM_ADD_LEADS_FAILED'));
					}
				}else{
					$this->error($m_leads_data->getError());
				}
			}else{
				$this->error($m_leads->getError());
			}

		}else{
			$field_list = field_list_html("add","leads");
		 	$this->field_list = $field_list;
			$this->alert = parseAlert();
			// 校区信息
            $this->blocks    =   M('Block')->field('id,name,person_id')->select();
			$this->display();
		}
	}

	/**
	*线索编辑页面
	*
	**/
	public function edit(){
		$leads_id = $this->_get('id','intval', intval($_POST['leads_id']));
		if(!$leads_id){
			alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
		}elseif(!$d_v_leads = D('LeadsView')->where('leads.leads_id = %d',$leads_id)->find()){
			alert('error', L('LEADS_DOES_NOT_EXIST'),$_SERVER['HTTP_REFERER']);
		}elseif($this->_permissionRes && !in_array($d_v_leads['owner_role_id'], $this->_permissionRes)){
			alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
		}

		$field_list = M('Fields')->where('model = "leads"')->order('order_id')->select();
		if($this->isPost()){
			$m_leads = D('Leads');
			$m_leads_data = D('LeadsData');
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
			if($m_leads->create()){
				if($m_leads_data->create()!==false){
					$m_leads->update_time = time();

					// 2018-08-01 新增 保存线索所有操作记录 dragon
                    $old_leads = M('Leads')->where('leads_id= %d',$leads_id)->find();// 修改前数据
                    $old_leads_data = M('LeadsData')->where('leads_id= %d',$leads_id)->find();// 修改前数据
                    
					$a = $m_leads->where('leads_id= %d',$_REQUEST['leads_id'])->save();
					$b = $m_leads_data->where('leads_id=%d',$_REQUEST['leads_id'])->save();
                    $new_leads = M('Leads')->where('leads_id= %d',$leads_id)->find();// 修改后数据
                    $new_leads_data = M('LeadsData')->where('leads_id= %d',$leads_id)->find();// 修改后数据
                    $update_ago_leads = array_diff_assoc($new_leads,$old_leads); // 已修改的字段
                    $update_ago_leads_data = array_diff_assoc($new_leads_data,$old_leads_data); // 已修改的字段
					if($a && $b!==false) {

                        $m_fields = M('fields');
                        $m_action_record = M('action_record');
                        $up_message = '';
                        foreach($update_ago_leads as $k => $v){
                            if($k != 'update_time'){
                                $field_info = $m_fields ->where('model="leads" and field="%s"',$k)->field('form_type,name')->find();
                                $field_name = $field_info['name'];
                                if($field_info['form_type'] == 'datetime'){
                                    $old_value = date('Y-m-d',$old_leads[$k]);
                                    $new_value = date('Y-m-d',$v);
                                }else{
                                    $old_value = $old_leads[$k];
                                    $new_value = $v;
                                }
                                // 0802 新增.如果修改线索真实姓名,映射客户到名称
                                if ( ($k == 'name') && ($old_value != $new_value) )
                                {
                                    M('Customer')->where(['leads_id'=>['eq',$leads_id]])->save(['name'=>$new_value]);
                                }
                                // 0802 end
                                $up_message .= '将 '.$field_name.' 由 "'.$old_value.'" 修改为 "'.$new_value.'"'."<br/>";
                            }
                        }
                        foreach($update_ago_leads_data as $kk => $vv){
                            if($kk != 'update_time'){
                                $field_infos = $m_fields ->where('model="leads" and field="%s"',$k)->field('form_type,name')->find();
                                $field_names = $field_infos['name'];
                                if($field_infos['form_type'] == 'datetime'){
                                    $old_values = date('Y-m-d',$old_leads_data[$k]);
                                    $new_values = date('Y-m-d',$v);
                                }else{
                                    $old_values = $old_leads_data[$k];
                                    $new_values = $v;
                                }
                                $up_message .= '将 '.$field_names.' 由 "'.$old_values.'" 修改为 "'.$new_values.'"'."<br/>";
                            }
                        }
                        $arr['create_time'] = time();
                        $arr['create_role_id'] = session('role_id');
                        $arr['type'] = '修改';
                        $arr['duixiang'] = $up_message;
                        $arr['model_name'] = 'leads';
                        $arr['action_id'] = $leads_id;
                        $m_action_record ->add($arr);

						actionLog($_REQUEST['leads_id']);
						alert('success', L('LEADS_MODIFIED_SUCCESSFULLY'), $_POST['jump_url']);
					} else {
						$this->error(L('LEADS_MODIFIED_FAILED'));
					}
				}else{
					$this->error($m_leads_data->getError());;
				}
			}else{
				$this->error($m_leads->getError());
			}
		}elseif($_REQUEST['id']){
			$d_v_leads['owner'] = D('RoleView')->where('role.role_id = %d', $d_v_leads['owner_role_id'])->find();

			$field_list = field_list_html("edit","leads",$d_v_leads);
			$this->field_list = $field_list;
			$this->leads = $d_v_leads;
			$this->alert = parseAlert();
			$this->jump_url = $_SERVER['HTTP_REFERER'];
			$this->display();
		}else{
			$this->error(L('INVALIDATE_PARAM'));
		}
	}

	/**
	*线索回收站删除
	*
	**/
	public function completeDelete() {
		$m_leads = M('Leads');
		$m_leads_data = M('LeadsData');
		$r_module = array('Log'=>'RLeadsLog', 'File'=>'RFileLeads', 'Event'=>'REventLeads', 'Task'=>'RLeadsTask');
		if($this->isPost()){
			$leads_ids = is_array($_POST['leads_id']) ? implode(',', $_POST['leads_id']) : '';
			if ('' == $leads_ids) {
				alert('error', L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
			} else {
				if(!session('?admin')){
					alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
				}
				if(($m_leads->where('leads_id in (%s)', $leads_ids)->delete()) && ($m_leads_data->where('leads_id in (%s)', $leads_ids)->delete())){
					foreach ($_POST['leads_id'] as $value) {
						actionLog($value);
						foreach ($r_module as $key2=>$value2) {
							$module_ids = M($value2)->where('leads_id = %d', $value)->getField($key2 . '_id', true);
							M($value2)->where('leads_id = %d', $value) -> delete();
							if(!is_int($key2)){
								M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
							}
						}
					}
					alert('success', L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('DELETE FAILED CONTACT THE ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
				}
			}
		} elseif($_GET['id']) {
			$leads = $m_leads->where('leads_id = %d', $_GET['id'])->find();
			if(is_array($leads)){
				if($leads['owner_role_id'] == session('role_id') || session('?admin')){
					if($m_leads->where('leads_id = %d', $_GET['id'])->delete()){
						foreach ($r_module as $key2=>$value2) {
							$module_ids = M($value2)->where('leads_id = %d', $_GET['id'])->getField($key2 . '_id', true);
							M($value2)->where('leads_id = %d', $_GET['id']) -> delete();
							if(!is_int($key2)){
								M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
							}
						}
						actionLog($_GET['id']);
						alert('success', L('DELETED SUCCESSFULLY'),  $_SERVER['HTTP_REFERER']);
					}else{
						alert('error', L('DELETE FAILED CONTACT THE ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
					}
				} else {
					alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error', L('LEADS_DOES_NOT_EXIST'), $_SERVER['HTTP_REFERER']);
			}
		} else {
			alert('error', L('SELECT_LEADS_TO_DELETE'),$_SERVER['HTTP_REFERER']);
		}
	}

	/**
	*线索删除
	*
	**/
	public function delete(){
		$m_leads = M('Leads');
		$m_leads_data = M('LeadsData');
		$r_module = array('Log'=>'RLeadsLog', 'File'=>'RFileLeads', 'Event'=>'REventLeads', 'Task'=>'RLeadsTask');
		if($this->isPost()){
			$leads_ids = is_array($_POST['leads_id']) ? implode(',', $_POST['leads_id']) : '';
			if (!$leads_ids) {
				$this->ajaxReturn('',L('HAVE_NOT_CHOOSE_ANY_CONTENT'),0);
			}
			$where = array();
			if(!session('?admin') && !checkPerByAction('leads','del_public')){
				$where['owner_role_id'] = array('in',$this->_permissionRes);
				//判断是否属于线索池
				$where_public = array();
				$where_public['owner_role_id'] = array('in',$this->_permissionRes);
				$where_public['leads_id'] = array('in',$leads_ids);
				$outdays = M('Config') -> where('name="leads_outdays"')->getField('value');
				$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
				$where_public['have_time'] = array('gt',$outdate);

				$public_leads_ids = D('LeadsView')->where($where_public)->getField('leads_id',true);
			}
			$where['leads_id'] = array('in', $leads_ids);
			$del_leads_ids = $m_leads->where($where)->getField('leads_id',true);
			if(!session('?admin') && !checkPerByAction('leads','del_public')){
				if($public_leads_ids){
					$del_leads_ids = array_intersect($del_leads_ids, $public_leads_ids);
				}else{
					$del_leads_ids = array();
				}
			}

			if(($m_leads->where(array('leads_id'=>array('in',$del_leads_ids)))->delete())){
                $m_leads_data->where(array('leads_id'=>array('in',$del_leads_ids)))->delete();
				foreach ($del_leads_ids as $value) {
					actionLog($value);
					foreach ($r_module as $key2=>$value2) {
						$module_ids = M($value2)->where('leads_id = %d', $value)->getField($key2 . '_id', true);
						M($value2)->where('leads_id = %d', $value) -> delete();
						if(!is_int($key2)){
							M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
						}
					}
				}
				$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
			} else {
				$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
			}
		}
	}

	/**
	*线索查看页面
	*
	**/
	public function view(){
		$d_role = D('RoleView');
		$leads_id = $this->_get('id','intval');
		$outdays = M('config') -> where('name="leads_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
		$where['have_time'] = array('egt',$outdate);
		$where['owner_role_id'] = array('neq',0);
		$where['leads_id'] = $leads_id;
		if(!$leads_id){
		    $this->ajaxReturn( [
		        'status'        =>  false,
                'error'         =>  L('PARAMETER_ERROR'),
            ] );
		}
		elseif($temp = D('Leads')->where($where)->find())
        {
            // 市场部账号可以查看所有
            if( $this->isMarket==false ){
                if(!in_array($temp['owner_role_id'], $this->_permissionRes)){
                    $this->ajaxReturn([
                        'status'        =>  false,
                        'error'         =>  L('DO NOT HAVE PRIVILEGES'),
                    ]);
                }
            }
		}
		$leads = D('LeadsView')->where('leads.leads_id = %d', $leads_id)->find();

		$field_list = M('Fields')->where('model = "leads"')->order('order_id')->select();
		$leads['owner'] = $d_role->where('role.role_id = %d', $leads['owner_role_id'])->find();
		$leads['creator'] = $d_role->where('role.role_id = %d', $leads['creator_role_id'])->find();
		//沟通日志
		$log_ids = M('rLeadsLog')->where('leads_id = %d', $leads_id)->getField('log_id', true);
		$leads['log'] = M('log')->where('log_id in (%s)', implode(',', $log_ids))->order('log_id desc')->select();
		$m_user = M('User');
		$m_log_status = M('LogStatus');
		foreach ($leads['log'] as $key=>$value) {
			$leads['log'][$key]['owner'] = $m_user->where('role_id = %d', $value['role_id'])->field('full_name,role_id,thumb_path')->find();
			$leads['log'][$key]['log_type'] = 'rLeadsLog';
			$status_name = $m_log_status->where('id = %d',$value['status_id'])->getField('name');
			$leads['log'][$key]['status_name'] = $status_name ? $status_name : '';
		}

		$file_ids = M('rFileLeads')->where('leads_id = %d', $leads_id)->getField('file_id', true);
		$leads['file'] = M('file')->where('file_id in (%s)', implode(',', $file_ids))->select();
		foreach ($leads['file'] as $key=>$value) {
			$leads['file'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
			$leads['file'][$key]['size'] = ceil($value['size']/1024);
			/*判断文件格式 对应其图片*/
			$leads['file'][$key]['pic'] = show_picture($value['name']);
		}

		//负责人日志
		$leads['record'] = M('leadsRecord')->where('leads_id = %d', $leads_id)->select();
		$record_count = 0;
		foreach ($leads['record'] as $key=>$value) {
			$leads['record'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['owner_role_id'])->find();
			$record_count ++;
		}
		//日程信息
		$m_event = M('event');
		$m_user = M('user');
		$event_list = $m_event ->where('module ="leads" and module_id =%d',$leads_id)->select();
		foreach($event_list as $k=>$v){
			$event_list[$k]['create_role_name'] = $m_user ->where('role_id =%d',$v['creator_role_id'])->getField('full_name');
			$event_list[$k]['img'] = $m_user ->where('role_id =%d',$v['creator_role_id'])->getField('img');
		}

		$this->event_list = $event_list;
		$leads['record_count'] = $record_count;
		$this->statusList = M('BusinessStatus')->order('order_id')->select();
		$this->leads = $leads;
		$this->field_list = $field_list;
		$this->alert = parseAlert();
		$this->display();

//        $this->ajaxReturn([
//            'eventslist'            =>  $event_list,
//            'statusList'            =>  M('BusinessStatus')->order('order_id')->select(),
//            'leads'                 =>  $leads,
//            'fieldsList'            =>  $field_list,
//        ]);
	}

    public function detail(){
        $d_role = D('RoleView');
        $leads_id = $this->_get('id','intval');
        $outdays = M('config') -> where('name="leads_outdays"')->getField('value');
        $outdate = empty($outdays) ? 0 : time()-86400*$outdays;
        $where['have_time'] = array('egt',$outdate);
        $where['owner_role_id'] = array('neq',0);
        $where['leads_id'] = $leads_id;
        


        if( !$leads_id || !($temp=M('Leads')->find($leads_id)) ){
            $this->ajaxReturn( [
                'status'        =>  false,
                'error'         =>  L('PARAMETER_ERROR'),
                'temp'          =>  $temp
            ] );
        }elseif(
            !session('?admin')
            && !in_array(session('role_id'), [70,81,95,109])
            && !in_array( $temp['owner_role_id'], $this->_permissionRes )
            && $temp['merge'] != session('role_id')
        ){
            $this->ajaxReturn([
                'status'        =>  false,
                'error'         =>  L('DO NOT HAVE PRIVILEGES'),
                'p'             =>  $this->_permissionRes,
            ]);
        }
        $leads = D('LeadsView')->where('leads.leads_id = %d', $leads_id)->find();

        $leads['merge_name'] = M('User')->where(['role_id'=>['eq',$leads['merge']]])->find()['full_name'];

        $field_list = M('Fields')->where('model = "leads"')->order('order_id')->select();
        $leads['owner'] = $d_role->where('role.role_id = %d', $leads['owner_role_id'])->find();
        $leads['creator'] = $d_role->where('role.role_id = %d', $leads['creator_role_id'])->find();
        //沟通日志
        $log_ids = M('rLeadsLog')->where('leads_id = %d', $leads_id)->getField('log_id', true);
        $leads['log'] = M('log')->where('log_id in (%s)', implode(',', $log_ids))->order('create_date desc')->select();
        $m_user = M('User');
        $m_log_status = M('LogStatus');
        foreach ($leads['log'] as $key=>$value) {
            $leads['log'][$key]['owner'] = $m_user->where('role_id = %d', $value['role_id'])->field('full_name,role_id,thumb_path')->find();
            $leads['log'][$key]['log_type'] = 'rLeadsLog';
            $status_name = $m_log_status->where('id = %d',$value['status_id'])->getField('name');
            $leads['log'][$key]['status_name'] = $status_name ? $status_name : '';
        }

        $file_ids = M('rFileLeads')->where('leads_id = %d', $leads_id)->getField('file_id', true);
        $leads['file'] = M('file')->where('file_id in (%s)', implode(',', $file_ids))->select();
        foreach ($leads['file'] as $key=>$value) {
            $leads['file'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
            $leads['file'][$key]['size'] = ceil($value['size']/1024);
            /*判断文件格式 对应其图片*/
            $leads['file'][$key]['pic'] = show_picture($value['name']);
        }

        //负责人日志
        $leads['record'] = M('leadsRecord')->where('leads_id = %d', $leads_id)->select() ?:[];
        $record_count = 0;
        foreach ($leads['record'] as $key=>$value) {
            $leads['record'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['owner_role_id'])->find();
            $record_count ++;
        }
        //日程信息
        $m_event = M('event');
        $m_user = M('user');
        $event_list = $m_event ->where('module ="leads" and module_id =%d',$leads_id)->select();
        foreach($event_list as $k=>$v){
            $event_list[$k]['create_role_name'] = $m_user ->where('role_id =%d',$v['creator_role_id'])->getField('full_name');
            $event_list[$k]['img'] = $m_user ->where('role_id =%d',$v['creator_role_id'])->getField('img');
        }

        // 模板中使用date函数无效,故在此处理此时间戳
        $leads['nextstep_time'] = $leads['nextstep_time'] ? date('Y-m-d H:i',$leads['nextstep_time']) : '';

        // 5-3新增
        $d_v_leads = D('LeadsView')->where('leads.leads_id = %d',$leads_id)->find();
        $d_v_leads['owner'] = D('RoleView')->where('role.role_id = %d', $d_v_leads['owner_role_id'])->find();
        $fields = field_list_html("edit","leads",$d_v_leads);

        $caselist = M('cases')->select();// 626

       	$newcase = [];
        foreach($caselist as $k=>$v){
            if( ($v['undergraduate_major']==$leads['now_major'] && $leads['now_major']!='' ) || ($v['japan_language']==$leads['level_jp']  && $leads['level_jp']!='' ) || ($v['eng_language']==$leads['level_en'] && $leads['level_en']!='')  ){
                $newcase[] = $v;
            }
        }

        // 7-10 添加线索变更记录
        $update_log = M('LeadsTimeoutLog')->where(['leads_id'=>['eq',$leads_id]])->order('get_time desc')->select();
        $leads_model = M('Leads');
        $user = M('User');

        foreach ($update_log as $k => $v)
        {
//            $update_log[$k]['leads'] = $leads_model->field('contacts_name')->where(['leads_id'=>['eq',$v['leads_id']]])->find()['contacts_name'];
            $update_log[$k]['old_user'] = $user->field('full_name,thumb_path,role_id')->where(['role_id'=>['eq',$v['old_owner_role_id']]])->find();
            $update_log[$k]['current_user'] = $user->field('full_name,thumb_path,role_id')->where(['role_id'=>['eq',$v['new_owner_role_id']]])->find();
            $update_log[$k]['create_user'] = $user->field('full_name')->where(['role_id'=>['eq',$v['create_user']]])->find()['full_name'];
            $update_log[$k]['get_time'] = date('Y-m-d H:i:s',$v['get_time']);
            unset($update_log[$k]['leads_id']);
            unset($update_log[$k]['old_owner_role_id']);
            unset($update_log[$k]['new_owner_role_id']);
        }
        // end

        // 8-01 添加线索修改记录
        $field_update_log = M('ActionRecord')->where(['model_name'=>['eq','leads'],'action_id'=>['eq',$leads_id]])->order('id desc')->select();
        
        foreach ($field_update_log as $k => $v)
        {
            $field_update_log[$k]['action_user'] = M('User')->where(['user_id'=>['eq',$v['create_role_id']]])->find()['full_name']?:' - ';
            $field_update_log[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time'])?:' - ';
//            $field_update_log[$k]['duixiang'] = explode(',',$v['duixiang']);
//            $field_update_log[$k]['duixiang'] = implode("<br/>",$field_update_log[$k]['duixiang']);
        }
        
        $this->ajaxReturn([
            'eventslist'            =>  $event_list,
            'statusList'            =>  M('BusinessStatus')->order('order_id')->select(),
            'leads'                 =>  $leads,
            'fieldsList'            =>  $field_list,
            'fields'                =>  $fields,
            'update_log'            =>  $update_log,// 7-10 添加线索变更记录
            'field_update_log'     =>  $field_update_log,// 8-01 添加线索修改记录
            'cases' =>  $newcase,
        ]);
    }

	/**
	*导出excel
	*
	**/
	public function excelExport($leadsList=false){
		C('OUTPUT_ENCODE', false);
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();
		$objProps = $objPHPExcel->getProperties();
		$objProps->setCreator("mxcrm");
		$objProps->setLastModifiedBy("mxcrm");
		$objProps->setTitle("mxcrm Leads Data");
		$objProps->setSubject("mxcrm Leads Data");
		$objProps->setDescription("mxcrm Leads Data");
		$objProps->setKeywords("mxcrm Leads Data");
		$objProps->setCategory("Leads");
		$objPHPExcel->setActiveSheetIndex(0);
		$objActSheet = $objPHPExcel->getActiveSheet();

		$objActSheet->setTitle('Sheet1');
		$j = 0;
        $field_list = M('Fields')->where('model = \'leads\'')->order('order_id')->select();
        foreach($field_list as $field){
			if($field['form_type'] == 'address'){
				for($a=0;$a<=4;$a++){
					$j++;
					$pCoordinate = PHPExcel_Cell::stringFromColumnIndex($j-1); //生成Excel
					$address = array('所在省','所在市','所在县/区','街道信息');
					$objActSheet->setCellValue($pCoordinate.'1', $address[$a]);
				}
				$j--;
			}else{
				$j++;
				$pCoordinate = PHPExcel_Cell::stringFromColumnIndex($j-1); //生成Excel
				$objActSheet->setCellValue($pCoordinate.'1', $field['name']);
			}
        }

		if(is_array($leadsList)){
			$list = $leadsList;
		}else{
			$where['owner_role_id'] = array('in',getSubRoleId());
			$where['is_deleted'] = 0;
			$list = M('Leads')->where($where)->select();
		}

		$i = 1;
		foreach ($list as $k => $v) {
            $data = M('LeadsData')->where("leads_id = $v[leads_id]")->find();
            if(!empty($data)){
                $v = $v+$data;
            }
			$i++;
            $m = 0;
            foreach($field_list as $field){
                if($field['form_type'] == 'datetime'){
					$m++;
					$pCoordinate_a = PHPExcel_Cell::stringFromColumnIndex($m-1); //生成Excel
					if($v[$field['field']] == 0 || strlen($v[$field['field']]) != 10){
						$objActSheet->setCellValue($pCoordinate_a.$i, '');
					}else{
						$objActSheet->setCellValue($pCoordinate_a.$i, date('Y-m-d H:i',$v[$field['field']]));
					}
                }elseif($field['form_type'] == 'number' || $field['form_type'] == 'floatnumber' || $field['form_type'] == 'phone' || $field['form_type'] == 'mobile' || ($field['form_type'] == 'text' && is_numeric($v[$field['field']]))){
					//防止使用科学计数法，在数据前加空格
					$m++;
					$pCoordinate_a = PHPExcel_Cell::stringFromColumnIndex($m-1); //生成Excel
					$objActSheet->setCellValue($pCoordinate_a.$i, ' '.$v[$field['field']]);
				}elseif($field['form_type'] == 'address'){
					$address = $v[$field['field']];
					$arr_address = explode(chr(10),$address);
					for($a=0;$a<=4;$a++){
						$m++;
						$pCoordinate_a = PHPExcel_Cell::stringFromColumnIndex($m-1); //生成Excel
						$objActSheet->setCellValue($pCoordinate_a.$i, $arr_address[$a]);
					}
					$m--;
				}else{
					$m++;
					$pCoordinate_a = PHPExcel_Cell::stringFromColumnIndex($m-1); //生成Excel
					$objActSheet->setCellValue($pCoordinate_a.$i, $v[$field['field']]);
                }
            }
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		//ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_leads_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
		session('export_status', 0);
	}
 	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);

	}
	/**
	*下载excel模板
	*
	**/
	public function excelImportDownload(){
        import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();
		$objProps = $objPHPExcel->getProperties();
		$objProps->setCreator("mxcrm");
		$objProps->setLastModifiedBy("mxcrm");
		$objProps->setTitle("mxcrm leads");
		$objProps->setSubject("mxcrm leads Data");
		$objProps->setDescription("mxcrm leads Data");
		$objProps->setKeywords("mxcrm leads Data");
		$objProps->setCategory("mxcrm");
		$objPHPExcel->setActiveSheetIndex(0);
		$objActSheet = $objPHPExcel->getActiveSheet();

		$objActSheet->setTitle('Sheet1');
        $ascii = 65;
        $cv = '';
        $field_list = M('Fields')->where('model = \'leads\' ')->order('order_id')->select();
        foreach($field_list as $field){
			if($field['form_type'] == 'address'){
				for($i=0;$i<4;$i++){
					$address = array('所在省','所在市','所在县/区','详细地址');
					$objActSheet->setCellValue($cv.chr($ascii).'2',$address[$i]);
					$ascii++;
					if($ascii == 91){
						$ascii = 66;
						$cv = chr(strlen($cv)+65);
					}
				}
			}else{

				//检查该字段若必填，加上"*"
				$field['name'] = sign_required($field['is_validate'], $field['is_null'], $field['name']);

				$objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
				$ascii++;
				if($ascii == 91){
					$ascii = 65;
					$cv = chr(strlen($cv)+65);
				}
			}
        }
		$objActSheet->mergeCells('A1:'.$cv.chr($ascii).'1');
		$objActSheet->getRowDimension('1')->setRowHeight(80);
		$objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
		 $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $content = L('ADRESS');
        $objActSheet->setCellValue('A1', $content);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_leads.xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
    }

	/**
	*导入excel
	*
	**/
	public function excelImport(){
		if($this->isPost()){
			if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts  = array('xls');
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					alert('error', L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'), $_SERVER['HTTP_REFERER']);
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
				}else{
					$info =  $upload->getUploadFileInfo();
				}
			}

			if(is_array($info[0]) && !empty($info[0])){
				$savepath = $dirname . $info[0]['savename'];
				import("ORG.PHPExcel.PHPExcel");
				$PHPExcel = new PHPExcel();
				$PHPReader = new PHPExcel_Reader_Excel2007();
				if(!$PHPReader->canRead($savepath)){
					$PHPReader = new PHPExcel_Reader_Excel5();
				}
				$PHPExcel = $PHPReader->load($savepath);
				$currentSheet = $PHPExcel->getSheet(0);
				$allRow = $currentSheet->getHighestRow();
				$data['savepath'] = $savepath;
				$data['allrow'] = $allRow ;
				if($savepath){
					$this->ajaxReturn($data,'success',1);
				}else{
					$this->ajaxReturn(0,'error',0);
				}
			}else{
				alert('error', L('UPLOAD FAILED'), $_SERVER['HTTP_REFERER']);
			};
		}else{
			$this->display();
		}
	}
	public function excelImportact(){
		$m_leads = D('Leads');
		$m_leads_data = D('LeadsData');
		$savePath = $_GET['path'];
		import("ORG.PHPExcel.PHPExcel");
		$PHPExcel = new PHPExcel();
		$PHPReader = new PHPExcel_Reader_Excel2007();
		if(!$PHPReader->canRead($savePath)){
			$PHPReader = new PHPExcel_Reader_Excel5();
		}
		$PHPExcel = $PHPReader->load($savePath);
		$currentSheet = $PHPExcel->getSheet(0);
		$allRow = $currentSheet->getHighestRow();
		$currentRow = intval($_GET['num']);
		$field_list = M('Fields')->where('model = \'leads\'')->order('order_id')->select();
		if($currentRow+99 <=$allRow){
			$rows_excal = $currentRow+99;
		}else{
			$rows_excal = $allRow;
		}
		$message = array();
		for($currentRow;$currentRow <= $rows_excal;$currentRow++){
			$data = array();
			$data['creator_role_id'] = session('role_id');
			$data['owner_role_id'] = intval($_GET['owner_role_id']);
			$data['create_time'] = time();
			$data['update_time'] = time();
			$data['have_time'] = time();
			$ascii = 65;
			$cv = '';
			foreach($field_list as $field){
				if($field['form_type'] == 'address'){
					$address = array();
					for($i=0;$i<4;$i++){
						$info = (String)$currentSheet->getCell($cv.chr($ascii).$currentRow)->getValue();
						$address[] = $info;
						$ascii++;
						if($ascii == 91){
							$ascii = 65;
							$cv .= chr(strlen($cv)+65);
						}
					}
					if ($field['is_main'] == 1){
						$data[$field['field']] =  implode(chr(10), $address);
					}else{
						$data_date[$field['field']] =  implode(chr(10), $address);
					}
				}else{
					$cell =$currentSheet->getCell($cv.chr($ascii).$currentRow);
					$info = $cell->getValue();
					if($cell->getDataType()==PHPExcel_Cell_DataType::TYPE_NUMERIC){
						$cellstyleformat=$cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat();

						//formatcode 为 yyyy/m 时间格式
						$formatcode=$cellstyleformat->getFormatCode();
						if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
							$info=gmdate("Y-m-d H:i", PHPExcel_Shared_Date::ExcelToPHP($info));
						}else{
							$info=PHPExcel_Style_NumberFormat::toFormattedString($info,$formatcode);
						}
					}else{
						$info = (String)$cell->getCalculatedValue();
					}
					if ($field['is_main'] == 1){
						$data[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? intval(strtotime($info)) : $info;
					}else{
						$data_date[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? intval(strtotime($info)) : $info;
					}
					$ascii++;
					if($ascii == 91){
						$ascii = 65;
						$cv = chr(strlen($cv)+65);
					}
				}
			}
			if($m_leads->create($data) && $m_leads_data->create($data_date)) {
				$leads_id = $m_leads->add();
				$m_leads_data->leads_id=$leads_id;
				$m_leads_data->add();
			}else{
				$error_message = $m_leads->getError().$m_leads_data->getError();

				$error_flag = 1;
			}
			$temp['error_message'] = $error_message;
			$temp['no'] = $currentRow;
			$message[] = $temp;

			//出现错误时候停止
			if (intval($_GET['is_jump']) == 2 && $error_flag == 1) break;
		}
		$return['allrow'] = $allRow;
		$return['message'] = $message;
		if($return){
			$this->ajaxReturn($return,'success',1);
		}else{
			$this->ajaxReturn('','error',0);
		}

	}

	/**
	*弹框选择分页
	*
	**/
	public function listDialog(){
		$m_leads = M('Leads');
		$outdays = M('config') -> where('name="leads_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
		$where['have_time'] = array('egt',$outdate);
		$where['is_deleted'] = 0;
		$where['is_transformed'] = 0;
		$where['owner_role_id'] = array('in', $this->_permissionRes);

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
		$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);

		import("@.ORG.DialogListPage");

		$leadsList = $m_leads->where($where)->order('create_time desc')->page($p.',10')->select();
		$count = $m_leads->where($where)->count();

		$this->search_field = $_REQUEST;//搜索信息
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());

		$this->leadsList = $leadsList;
		$this->display();
	}

	/**
	*放入线索池
	*
	**/
	public function remove(){
		if($_POST['leads_id']){
			if($this->_permissionRes) {
				$where['owner_role_id'] = array('in', $this->_permissionRes);
				$where['leads_id'] = array('in', $_POST['leads_id']);
				$data['owner_role_id'] = 0;
				$data['have_time'] = 0;
				if(M('Leads')->where($where)->setField($data)){
					alert('success', L('BATCH_LEADS_INTO_THE_POOL_SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', L('BATCH_LEADS_INTO_THE_POOL_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error', L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
		}
	}

	/**
	*领取、分配线索操作
	*
	**/
	public function receive(){
		$leads_id = isset($_REQUEST['id']) ? intval(trim($_REQUEST['id'])) : 0;
		if($_REQUEST['owner_role_id']) {
			$owner_role_id = intval($_REQUEST['owner_role_id']);
		}else{
			$owner_role_id = session('role_id');
		}
		if ($leads_id) {
			$m_leads = M('Leads');
			$m_config = M('Config');
			$leads = $m_leads->where('leads_id = %d', $leads_id)->find();
			$config = $m_config->where(array('name'=>'leads_outdays'))->find();
			if((time() - $leads['have_time']) < ($config['value'] * 86400) && $leads['owner_role_id'] != 0 ){
				alert('error', L('RECEIVED_BY_SOMEONE',array($leads['name'])), $_SERVER['HTTP_REFERER']);
			}
			$update_data = array();
			$update_data['owner_role_id'] = $owner_role_id;
			$update_data['have_time'] = time();
			$update_data['update_time'] = time();
			$a = $m_leads->where('leads_id = %d', $leads_id)->save($update_data);
			if ($a) {
				$d = array('leads_id'=>$leads_id,'owner_role_id'=>$owner_role_id,'start_time'=>time());
				M('LeadsRecord')->data($d)->add();
				$title=L('NEW_LEADS_MESSAGE_NOTICE_TITLE');
				$content=L('NEW_LEADS_MESSAGE_NOTICE_CONTENT',array(session('name'),U('Leads/view','id='.$leads_id), $leads['name']));

				if(intval($_POST['message_alert']) == 1) {
					sendMessage($owner_role_id,$content,1);
				}
				if(intval($_POST['email_alert']) == 1){
					$email_result = sysSendEmail($owner_role_id,$title,$content);
					if(!$email_result) alert('error', L('MAIL_NOTIFICATION_FAILS_FOR_NOT_SET_EMAIL'),$_SERVER['HTTP_REFERER']);
				}
				if(intval($_POST['sms_alert']) == 1){
					$sms_result = sysSendSms($owner_role_id,$content);
					if(100 == $sms_result){
						alert('error', L('SMS_NOTIFICATION_FAILS_FOR_NOT_VALIDATE_NUMBER'),$_SERVER['HTTP_REFERER']);
					}elseif($sms_result < 0){
						alert('error',L('SMS_NOTIFICATION_FAILS_CODE', array($sms_result)), $_SERVER['HTTP_REFERER']);
					}
				}

				if($_REQUEST['owner_role_id']){
					alert('success', L('ASSIGN_LEADS_SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('success', L('RECEIVE_LEADS_SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
				}
			} else {
				if($_REQUEST['owner_role_id']){
					alert('success', L('ASSIGN_LEADS_FAILED'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('success', L('RECEIVE_LEADS_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}
		} else {
			alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
		}
	}

	/**
	*批量领取线索操作
	*
	**/
	public function batchReceive(){
		$leads_ids = $_REQUEST['leads_id'];
		$owner_role_id = session('role_id');
		if(empty($leads_ids)){
			alert('error', L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
		}
		$m_leads = M('Leads');
		$m_config = M('Config');
		foreach($leads_ids as $v){
			$leads = $m_leads->where('leads_id = %d',$v)->find();
			$config = $m_config->where(array('name'=>'leads_outdays'))->find();
			if( (time() - $leads['have_time']) > ($config['value'] * 86400) || $leads['owner_role_id'] == 0 ){
				$data['owner_role_id'] = $owner_role_id;
				$data['have_time'] = time();
				$data['update_time'] = time();
				if($m_leads->where('leads_id = %d',$v)->save($data)){
					M('LeadsRecord')->add(array('leads_id'=>$v,'owner_role_id'=>$owner_role_id,'start_time'=>time()));
				}else{
					alert('success', L('RECEIVE_LEADS_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('RECEIVED_BY_SOMEONE', array($leads['name'])), $_SERVER['HTTP_REFERER']);
			}
		}
		alert('success', L('RECEIVE_LEADS_SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
	}

	/**
	*批量分配线索操作
	*
	**/
	public function batchassign(){
		if($this->isPost()){
			$leads_ids = $_POST['leads_id'];
			$owner_role_id = $_POST['owner_id'];
			$message = empty($_POST['message']) ? 0 :$_POST['message'];
			$sms = empty($_POST['sms']) ? 0 :$_POST['sms'];
			$email = empty($_POST['email']) ? 0 :$_POST['email'];
			if(empty($leads_ids)){
				alert('error', L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
			}
			$m_leads = M('Leads');
			$m_config = M('Config');
			$title = L('NEW_LEADS_MESSAGE_NOTICE_TITLE');
			$content = '';
			$success_leads_name='';
			$error_leads_name='';
			foreach($leads_ids as $v){
				$leads = $m_leads->where('leads_id = %d',$v)->find();
				$config = $m_config->where(array('name'=>'leads_outdays'))->find();
				if( (time() - $leads['have_time']) > ($config['value'] * 86400) || $leads['owner_role_id'] == 0 ){
					$update_data = array();
					$update_data['owner_role_id'] = $owner_role_id;
					$update_data['have_time'] = time();
					$update_data['update_time'] = time();
					$a = $m_leads->where('leads_id = %d', $v)->save($update_data);
					if ($a) {
						$d = array('leads_id'=>$v,'owner_role_id'=>$owner_role_id,'start_time'=>time());
						M('LeadsRecord')->data($d)->add();
						$url = U('leads/view','id='.$v);
						$success_leads_name .= '<a href="'.$url.'">'.$leads['name'].'</a>、';
					}else{
						$error_leads_name .= $leads['name'].'、';
					}
				}else{
					alert('error', L('RECEIVED_BY_SOMEONE',array($leads['name'])), $_SERVER['HTTP_REFERER']);
				}
			}

			if($success_leads_name){
				$content = L('ASSIGN_LEADS_MESSAGE_NOTICE_CONTENT' ,array(session('name'), $success_leads_name));
				if($message == 1) {
					sendMessage($owner_role_id,$content,1);
				}
				if($email == 1){
					$email_result = sysSendEmail($owner_role_id,$title,$content);
					if(!$email_result) alert('error', L('MAIL_NOTIFICATION_FAILS_FOR_NOT_SET_EMAIL'),$_SERVER['HTTP_REFERER']);
				}
				if($sms == 1){
					$sms_result = sysSendSms($owner_role_id,$content);
					if(100 == $sms_result){
						alert('error', L('SMS_NOTIFICATION_FAILS_FOR_NOT_VALIDATE_NUMBER'),$_SERVER['HTTP_REFERER']);
					}elseif($sms_result < 0){
						alert('error', L('SMS_NOTIFICATION_FAILS_CODE', array($sms_result)) ,$_SERVER['HTTP_REFERER']);
					}
				}
			}
			if($error_leads_name){
				alert('error', L('BATCH_ASSIGN_LEADS_TO_SOMEONE_FAILED', array($error_leads_name)), $_SERVER['HTTP_REFERER']);
			}else{
				alert('success', L('BATCH_ASSIGN_LEADS_SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
			}
		}
	}

	/**
	*批量分配线索操作
	*
	**/
	public function assignDialog(){
		$role_info = getUserByRoleId(session('role_id'));
		$this->role_info = $role_info;
		$this->display();
	}

	/**
	*单条分配线索弹窗操作
	*
	**/
	public function fenpei(){
		$leads_id = intval($_GET['id']);
		 if ($leads_id > 0) {
			$this->leads_id = $leads_id;
			$this->display();
		} else {
			alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
		}
	}

	/**
	*线索分析
	*
	**/
	public function analytics(){
		$time1 = time();
		$m_leads = M('leads');

		//权限判断
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME);
		//是否仅查询销售岗
		$user_type = $_REQUEST['user_type'] ? 1 : '';
		if(intval($_GET['role'])){
			$role_ids = array(intval($_GET['role']));
		}else{
			if(intval($_GET['department'])){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_ids[] = $v['role_id'];
				}
			}else{
				$type_role_array = array();
				if(empty($user_type)){
					//过滤销售岗角色用户
					$m_user = M('User');
					foreach($below_ids as $k=>$v){
						$user_type = '';
						$user_type = $m_user->where('role_id = %d',$v)->getField('type');
						if($user_type == 1){
							$type_role_array[] = $v;
						}
					}
					$role_id_array = $type_role_array;
				}else{
					$role_id_array = $below_ids;
				}
			}
		}
		if($role_ids){
			//数组交集
			$role_id_array = array_intersect($role_ids, $below_ids);
		}

		//时间段搜索
		if($_GET['between_date']){
			$between_date = explode(' - ',trim($_GET['between_date']));
			if($between_date[0]){
				$start_time = strtotime($between_date[0]);
			}
			$end_time = $between_date[1] ?  strtotime(date('Y-m-d 23:59:59',strtotime($between_date[1]))) : strtotime(date('Y-m-d 23:59:59',time()));
		}else{
			$start_time = strtotime(date('Y-m-01 00:00:00'));
			$end_time = strtotime(date('Y-m-d H:i:s'));
		}
		$this->start_date = date('Y-m-d',$start_time);
		$this->end_date = date('Y-m-d',$end_time);

		$where_source['creator_role_id'] = array('in', implode(',', $role_id_array));

		if($start_time){
			$where_source['create_time'] = array(array('elt',$end_time),array('egt',$start_time), 'and');
		}else{
			$where_source['create_time'] = array('elt',$end_time);
		}
		$where_source['is_deleted'] = 0;

		//线索来源统计
		$m_fields = M('Fields');
		$field_info = $m_fields->where(array('model'=>'leads','field'=>'source'))->find();
		$field = array();
		$field['source'] = $field_info['name'];
		$this->assign('field',$field);

		$setting = $field_info['setting'];
		$setting_str = '$revenueList='.$setting.';';
		eval($setting_str);
		$source_count_array = array();
		$sourceList = $m_leads->where($where_source)->field('count(1) as num , source')->group('source')->select();
		foreach($sourceList as $v){
			$source = $v['source']?$v['source']:L('OTHER');
			$source_count[$source] = $v['num'];
		}
		foreach($revenueList['data'] as $v){
			if($source_count[$v]){
				$source_count_array[] = '["'.$v.'",'.$source_count[$v].']';
			}else{
				$source_count_array[] = '["'.$v.'",0]';
			}
		}
		$this->source_count = implode(',', $source_count_array);

		if($start_time){
			$create_time= array(array('elt',$end_time),array('egt',$start_time), 'and');
		}else{
			$create_time = array('elt',$end_time);
		}
		//线索池条件
		$outdays = M('config') -> where('name="leads_outdays"')->getField('value');
		$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

		$own_count_total = 0;
		$success_count_total = 0; //已转换
		$deal_count_total = 0; //已跟进
		$own_response_time_total = 0;
		$success_rate_total = 0;

		$m_user = M('User');
		foreach($role_id_array as $v){
			$user_info = array();
			$user_info = getUserByRoleId($v);
			//过滤已停用用户
			if($user_info['status'] == 1){
				//负责的线索
				$own_list = $m_leads->where(array('is_deleted'=>0, 'owner_role_id'=>$v,'is_transformed'=>array('neq',1),'create_time'=>$create_time,'have_time'=>array('egt',$outdate)))->field('first_time,have_time,leads_id')->select();
				$own_count = sizeof($own_list);
				//拥有的全部线索数（包含已转换）
				$all_own_count = $m_leads->where(array('is_deleted'=>0, 'owner_role_id'=>$v,'create_time'=>$create_time,'have_time'=>array('egt',$outdate)))->field('first_time,have_time,leads_id')->count();
				//平均响应时间（线索的最后一次领取时间至第一次跟进时间）
				$response_time_total = 0;
				foreach($own_list as $key=>$val){
					if($val['first_time'] && $val['first_time'] > $val['have_time']){
						$response_time = $val['first_time']-$val['have_time'];
					}else{
						$response_time = time()-$val['have_time'];
					}
					$response_time_total += $response_time;
				}
				$response_time_total_ave = round($response_time_total/$all_own_count,0);
				$own_response_time = $response_time_total_ave ? getTimeBySec($response_time_total_ave) : '';

				//已转换为客户
				$success_count = $m_leads->where(array('is_deleted'=>0, 'is_transformed'=>1,'owner_role_id'=>$v, 'create_time'=>$create_time))->count();
				//转化率
				$success_rate = $success_count ? round($success_count/$all_own_count,2)*100 : 0;
				//已跟进
				$deal_where = array();
				$deal_where['is_deleted'] = 0;
				$deal_where['owner_role_id'] = $v;
				$deal_where['is_transformed'] = array('neq',1);
				$deal_where['have_time'] = array('egt',$outdate);
				$deal_where['create_time'] = $create_time;
				$deal_count = $m_leads->where('update_time > create_time')->where($deal_where)->count();

				$reportList[] = array("user"=>$user_info,"own_count"=>$own_count,"success_count"=>$success_count,"deal_count"=>$deal_count,"own_response_time"=>$own_response_time,"success_rate"=>$success_rate);
				$own_count_total += $own_count;
				$success_count_total += $success_count;
				$deal_count_total += $deal_count;
				$own_response_time_total += $response_time_total;
			}
		}
		//总平均响应时间
		$own_response_time_total_ave = round($own_response_time_total/$own_count_total,0);
		$own_response_total = $own_response_time_total_ave ? getTimeBySec($own_response_time_total_ave) : '';
		//总转化率
		$success_rate_total = $success_count_total ? round($success_count_total/$own_count_total,4)*100 : 0;

		$this->total_report = array("own_count"=>$own_count_total, "success_count"=>$success_count_total, "deal_count"=>$deal_count_total,"own_response_time_total"=>$own_response_total,"success_rate_total"=>$success_rate_total);
		$this->reportList = $reportList;

		//部门岗位
		$url = getCheckUrlByAction(MODULE_NAME,ACTION_NAME);
		$per_type =  M('Permission') -> where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		$this->assign('departmentList', $departmentList);

		$roleList = array();
		foreach($below_ids as $roleId){
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;

		//时间插件处理（计算开始、结束时间距今天的天数）
		$daterange = array();
		//上个月
		$daterange[0]['start_day'] = (strtotime(date('Y-m-d',time()))-strtotime(date('Y-m-d', mktime(0,0,0,date('m')-1,1,date('Y')))))/86400;
		$daterange[0]['end_day'] = (strtotime(date('Y-m-d',time()))-strtotime(date('Y-m-01 00:00:00')))/86400;
		//本月
		$daterange[1]['start_day'] = (strtotime(date('Y-m-d',time()))-strtotime(date('Y-m-01 00:00:00')))/86400;
		$daterange[1]['end_day'] = 0;
		//上季度
		$month = date('m');
		if($month==1 || $month==2 ||$month==3){
			$year = date('Y')-1;
			$daterange_start_time = strtotime(date($year.'-10-01 00:00:00'));
			$daterange_end_time = strtotime(date($year.'-12-31 23:59:59'));
		}elseif($month==4 || $month==5 ||$month==6){
			$daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-03-31 23:59:59"));
		}elseif($month==7 || $month==8 ||$month==9){
			$daterange_start_time = strtotime(date('Y-04-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-06-30 23:59:59"));
		}else{
			$daterange_start_time = strtotime(date('Y-07-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-09-30 23:59:59"));
		}
		$daterange[2]['start_day'] = (strtotime(date('Y-m-d',time()))-$daterange_start_time)/86400;
		$daterange[2]['end_day'] = (strtotime(date('Y-m-d',time()))-$daterange_end_time-1)/86400;
		//本季度
		$month=date('m');
		if($month==1 || $month==2 ||$month==3){
			$daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-03-31 23:59:59"));
		}elseif($month==4 || $month==5 ||$month==6){
			$daterange_start_time = strtotime(date('Y-04-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-06-30 23:59:59"));
		}elseif($month==7 || $month==8 ||$month==9){
			$daterange_start_time = strtotime(date('Y-07-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-09-30 23:59:59"));
		}else{
			$daterange_start_time = strtotime(date('Y-10-01 00:00:00'));
			$daterange_end_time = strtotime(date("Y-12-31 23:59:59"));
		}
		$daterange[3]['start_day'] = (strtotime(date('Y-m-d',time()))-$daterange_start_time)/86400;
		$daterange[3]['end_day'] = 0;
		//上一年
		$year = date('Y')-1;
		$daterange_start_time = strtotime(date($year.'-01-01 00:00:00'));
		$daterange_end_time = strtotime(date('Y-01-01 00:00:00'));
		$daterange[4]['start_day'] = (strtotime(date('Y-m-d',time()))-$daterange_start_time)/86400;
		$daterange[4]['end_day'] = (strtotime(date('Y-m-d',time()))-$daterange_end_time)/86400;
		//本年度
		$daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
		$daterange[5]['start_day'] = (strtotime(date('Y-m-d',time()))-$daterange_start_time)/86400;
		$daterange[5]['end_day'] = 0;
		$this->daterange = $daterange;

		$this->type_id = intval($_GET['type_id']);
		$this->content_id = intval($_GET['content_id']);
		$this->alert = parseAlert();
		// echo time()-$time1;
		$this->display();
	}

	/**
	 * 线索转换
	 * @param
	 * @author
	 * @return
	 */
	public function change_customer(){
		$leads_id = intval($_GET['id']);
		$m_leads = M('leads');
		$m_customer = M('customer');
		$m_customer_data = M('customer_data');
		$m_contacts = M('contacts');
		$m_r_contacts_customer = M('r_contacts_customer');
		$m_r_leads_log = M('r_leads_log');
		$m_r_customer_log = M('r_customer_log');
		$where['leads_id'] = $leads_id;
		$where['is_deleted'] = 0;
		$where['is_transformed'] = 0;
		$leads_info = $m_leads->where($where)->find();
		if(!$leads_info){
			$this->ajaxReturn('','线索数据不存在或已转换',0);
		}

		if((!$leads_info['name'] || !trim($leads_info['name'])) && !$leads_info['mobile']){
            $this->ajaxReturn(['status'=>false,'msg'=>'真实姓名 和 手机号码不能为空']);
        }
		
        if(!$leads_info['name'] || !trim($leads_info['name'])){
            $this->ajaxReturn(['status'=>false,'msg'=>'真实姓名不能为空']);
        }

        if(!$leads_info['mobile']){
            $this->ajaxReturn(['status'=>false,'msg'=>'手机号码不能为空']);
        }

		//联系人
		$contacts_data['name'] =  $leads_info['contacts_name'];
		$contacts_data['telephone'] =  $leads_info['mobile'];
		$contacts_data['qq_no'] =  $leads_info['position'];
		$contacts_data['email'] =  $leads_info['email'];
		$contacts_data['saltname'] =  $leads_info['saltname'];
		$contacts_data['create_time'] =  time();
		$contacts_id = $m_contacts->add($contacts_data);
		//客户
		$owner_role_id = intval($_GET['role_id']) ? intval($_GET['role_id']) : session('role_id');
		$customer_data['owner_role_id'] = $owner_role_id;
		$customer_data['creator_role_id'] =  array_key_exists('creator_role_id',$leads_info) ? $leads_info['creator_role_id'] : session('role_id');
		$customer_data['leads_create_role_id'] =  array_key_exists('creator_role_id',$leads_info) ? $leads_info['creator_role_id'] : session('role_id');
        $customer_data['leads_id'] =  $leads_id;// 0802 新增关联线索编号
        $customer_data['name'] =  $leads_info['name'];
		$customer_data['contacts_id'] = $contacts_id;
		$customer_data['address'] =  $leads_info['state'].'/n'.$leads_info['city'].'/n'.$leads_info['area'].'/n'.$leads_info['street'];
		$customer_data['create_time'] = time();
		$customer_data['update_time'] = time();
		$customer_data['get_time'] = time();
		$customer_data['nextstep_time'] = $leads_info['nextstep_time'];
		$customer_data['nextstep'] = $leads_info['nextstep'];
        // 0615 数据同步
        $customer_data['mobile']        =  array_key_exists('mobile',$leads_info) ? $leads_info['mobile'] : '';
        $customer_data['email']         =  array_key_exists('email',$leads_info) ? $leads_info['email'] : '';
        $customer_data['level_jp']      =  array_key_exists('level_jp',$leads_info) ? $leads_info['level_jp'] : '';
        $customer_data['now_university']=  array_key_exists('now_university',$leads_info) ? $leads_info['now_university'] : '';
        $customer_data['crm_qq']        =  array_key_exists('crm_qq',$leads_info) ? $leads_info['crm_qq'] : '';
        $customer_data['expect_major']  =  array_key_exists('expect_major',$leads_info) ? $leads_info['expect_major'] : '';
        $customer_data['level_en']      =  array_key_exists('level_en',$leads_info) ? $leads_info['level_en'] : '';
        $customer_data['crm_city']      =  array_key_exists('crm_city',$leads_info) ? $leads_info['crm_city'] : '';
        $customer_data['crm_kitdgt']    =  array_key_exists('crm_kitdgt',$leads_info) ? $leads_info['crm_kitdgt'] : '';
        $customer_data['zh_city']       =  array_key_exists('zh_city',$leads_info) ? $leads_info['zh_city'] : '';
        $customer_data['zh_time']       =  array_key_exists('zh_time',$leads_info) ? $leads_info['zh_time'] : '';
        $customer_data['now_major']     =  array_key_exists('now_major',$leads_info) ? $leads_info['now_major'] : '';
        $customer_data['crm_when']      =  array_key_exists('crm_when',$leads_info) ? $leads_info['crm_when'] : '';
        $customer_data['description']   =  array_key_exists('description',$leads_info) ? $leads_info['description'] : '';
        $customer_data['industry']      =  array_key_exists('grade',$leads_info) ? $leads_info['grade'] : '';
        $customer_data['origin']        =  array_key_exists('source',$leads_info) ? $leads_info['source'] : '';

        //  字段属性查询
        $m_fields       =   M('Fields');
        $customer_slave_fields =   $m_fields->field('field')->where("model='customer' AND is_main=0")->select();
        $customer_slave_fields  =   array_map( function($v){ return $v['field']; }, $customer_slave_fields );
        $customer_data_main     =   [];
        $customer_data_slave    =   [];
        foreach ($customer_data as $key=>$value){
            if( in_array($key,$customer_slave_fields) ){
                $customer_data_slave[$key]  =   $value;
            }else{
                $customer_data_main[$key]   =   $value;
            }
        }

		if($customer_id = $m_customer->add($customer_data_main)){
            $customer_data_slave['customer_id'] =   $customer_id;

            // 获取线索附表内的备注字段信息 2018-7-27 dragon
            $leads_desc = M('LeadsData')->where(['leads_id'=>['eq',$leads_id]])->find()['description'];
            if ($leads_desc) { $customer_data_slave['description'] = $leads_desc; }
            // 2018-7-27 end

            $m_customer_data->add($customer_data_slave);

            //线索沟通日志
			$leads_log_ids = $m_r_leads_log->where('leads_id=%d',$leads_id)->getField('log_id',true);
			foreach($leads_log_ids as $vv){
				$customer_log['log_id'] = $vv;
				$customer_log['customer_id'] = $customer_id;
				$customer_logs[] = $customer_log;
			}
			if($m_r_customer_log->addAll($customer_logs)){
				// $m_r_leads_log->where('leads_id=%d',$leads_id)->delete();
			}
			$leads_data = array();
			$leads_data['contacts_id'] = $contacts_id;
			$leads_data['customer_id'] = $customer_id;
			$leads_data['is_transformed'] = 1;
			$leads_data['transform_role_id'] = session('role_id');
			$m_leads->where('leads_id=%d',$leads_id)->save($leads_data);

			//增加客户下操作记录
			$up_message = '将线索 '.$leads_info['name'].' 转化为客户';
			$arr['create_time'] = time();
			$arr['create_role_id'] = session('role_id');
			$arr['type'] = '修改';
			$arr['duixiang'] = $up_message;
			$arr['model_name'] = 'customer';
			$arr['action_id'] = $customer_id;
			M('ActionRecord')->add($arr);
		}else{
			$this->ajaxReturn(0,"新增错误！",0);
		}
		$con_cus['contacts_id'] = $contacts_id ;
		$con_cus['customer_id'] = $customer_id ;
		if($m_r_contacts_customer->add($con_cus)){
			$this->ajaxReturn(1,"修改成功！",1);
		}else{
			$this->ajaxReturn(0,"新增错误！",0);
		}
	}

	//列表字段值修改
	public function field_save(){
		$m_leads = M("leads");
		$where['leads_id'] = $this->_GET('id');
		if($this->_GET('filed') == 'nextstep_time'){
			$save[$this->_GET('filed')] = strtotime($this->_GET('commen_name'));
		}else{
			$save[$this->_GET('filed')] = $this->_GET('commen_name');
		}
		if($m_leads->where($where)->save($save)){
			if($this->_GET('filed') == 'xs_fl' && $this->_GET('commen_name') == 'A、电话未接通'){
				//添加沟通日志
				$data = array();
				$data['category_id'] = 1;
				$data['role_id'] = session('role_id');
				$data['create_date'] = time();
				$data['update_date'] = time();
				$data['subject'] = '电话未接通';
				$data['content'] = '电话未接通';
				$log_id = M('Log')->add($data);
				if($log_id){
					$res = M('RLeadsLog')->add(array('leads_id'=>$this->_GET('id'),'log_id'=>$log_id));
				}
			}
			$this->ajaxReturn($result,"修改成功！",1);
		}else{//echo $m_leads->getLastSql();exit;
			$this->ajaxReturn(0,"新增错误！",0);
		}
	}
}