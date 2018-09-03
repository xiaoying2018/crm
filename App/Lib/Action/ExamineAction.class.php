<?php 
class ExamineAction extends Action {

	/**
	*  author ZengZhiQiang
	*  用于判断权限
	*  @permission 无限制
	*  @allow 登录用户可访问
	*  @other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array('analog_auth'),
			'allow'=>array('add_examine','revert','getcurrentstatus','travel_business','travel_two','checktype','check_list','getanalycurrentstatus')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	/**
	*审批列表
	**/
	public function travel_business(){
		$this->now_rows = intval($_POST['now_rows']);
		$this->display();
	}
	public function travel_two(){
		$this->now_rows = intval($_POST['now_rows']);
		$this->display();
	}
	public function index(){
		$m_examine = M('Examine');
		$where = array();
		$params = array();
		$order = "update_time desc,examine_id asc";
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc,examine_id asc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc,examine_id asc';
		}
		$below_ids = $this->_permissionRes;
		// $all_ids = getSubRoleId();
		$module = isset($_GET['module']) ? trim($_GET['module']) : '';
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		switch ($by) {
			case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
			case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
			case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
			case 'add' : $order = 'create_time desc,examine_id asc';  break;
			case 'update' : $order = 'update_time desc,examine_id asc';  break;
			case 'deleted' : $where['is_deleted'] = 1; break;
			case 'create' : $where['creator_role_id'] = session('role_id'); break;
			case 'subcreate' : $where['creator_role_id'] = array('in',implode(',', $below_ids)); break;
			case 'not_examine' : $where['examine_status'] = 0; break;
			case 'examining' : $where['examine_status'] = array('in',array(0,1)); break;
			case 'examined' : $where['examine_status'] = array('in',array(2,3)); break;
			case 'me_examine' : $where['examine_role_id'] = session('role_id'); break;
			default : 
				if(!session('?admin')){	//非管理员权限限制
					$c_where['creator_role_id'] = array('in',implode(',', $below_ids)); 
					$c_where['examine_role_id'] = session('role_id');
					$c_where['_logic']='or';
					$where['_complex']=$c_where;
				}break;
		}
	
		if (!isset($where['creator_role_id'])) {
			if(!session('?admin')){	//非管理员权限限制
				$c_where['creator_role_id'] = array('in',implode(',', $below_ids)); 
				$c_where['examine_role_id'] = session('role_id');
				$c_where['_logic']='or';
				$where['_complex']=$c_where;
			}
		}
		if (!isset($where['is_deleted'])) {
			$where['is_deleted'] = 0;
		}
		$type = '';
		$examine_status = '';
		//高级搜索
		$fields_search = array();
		if(!$_GET['field']){
			if(empty($_GET['type'])){
        		unset($_GET['type']);
        	}
        	if(empty($_GET['examine_status'])){
        		unset($_GET['examine_status']);
        	}
			foreach($_GET as $kd => $vd){
                if ($kd != 'act' && $kd != 'content' && $kd != 'p' && $kd != 'search' && $kd != 'examining' && $kd != 'by') {
					if(in_array($kd,array('is_checked'))){
						if(!empty($vd)){
							$where[$kd] = $vd['value'];
							$fields_search[$kd]['field'] = $kd;
							$fields_search[$kd]['value'] = $vd['value'];
						}
					}elseif(in_array($kd,array('create_time','update_time','due_time'))){
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
					}elseif(($kd =='type' && is_array($vd)) || $kd =='examine_status'){
						if($kd =='examine_status' && $vd['value'] == 'all'){
							$where['examine_status'] = array('egt',0);
						}elseif($kd =='type' && $vd['value'] == 'all'){
							$where['type'] = array('egt',0);
						}else{
							$where[$kd] = field($vd['value'], $vd['condition']);
						}

						if ($_GET['examine_status'] && $kd =='examine_status' && !is_array($_GET['examine_status'])) {
							if ($_GET['examine_status'] == 4) {
								$fields_search['examine_status']['field'] = 'examine_status';
								$fields_search['examine_status']['value'] = 0;
							} else {
								$fields_search['examine_status']['field'] = 'examine_status';
								$fields_search['examine_status']['value'] = intval($_GET['examine_status']);
							}
						} else {
							$fields_search[$kd]['field'] = $kd;
							$fields_search[$kd]['value'] = $vd['value'];
						}

						if ($kd =='examine_status') {
							$examine_status = $vd['value'];
						}

						if ($kd =='type') {
							$type = $vd['value'];
						}
						
					}elseif($kd =='owner_role_id'){
						if(!empty($vd)){
							$where['owner_role_id'] = $vd['value'];
							$fields_search[$kd]['field'] = $kd;
							$fields_search[$kd]['value'] = $vd['value'];
						} 
					}else{
						if(is_array($vd)) {
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
								$fields_search[$kd]['value'] = $vd['value'];
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
		$this->fields_search = $fields_search;

		if($_GET['type'] && !is_array($_GET['type'])){
			if($_GET['type'] == 'all'){
				$where['type'] = array('egt',0);
			}else{
				$where['type'] = intval($_GET['type']);
			}
			$type = $_GET['type'];
			$params[] = 'type='.$_GET['type'];
		}
		if($_GET['examine_status'] && !is_array($_GET['examine_status'])){
			if($_GET['examine_status'] == 4){
				$where['examine_status'] = 0;
				$examine_status = 0;
			}else{
				$where['examine_status'] = intval($_GET['examine_status']);
				$examine_status = intval($_GET['examine_status']);
			}
			$params[] = 'examine_status='.$_GET['examine_status'];
		} else {
			$examine_status = 4;
		}

		//待我审核的审批
		if ($_GET['examining'] == 1) {
			$params[] = 'by=me_examine';
			$params[] = 'examining=1';
		}
		if(trim($_GET['act']) == 'export'){
			if(checkPerByAction('examine','excelexport')){
				$dc_id = $_GET['export_id'];
				if($dc_id !=''){
					$where['examine_id'] = array('in',$dc_id);
				}
				$current_page = intval($_GET['current_page']);
				$export_limit = intval($_GET['export_limit']);
				$limit = ($export_limit*($current_page-1)).','.$export_limit;
				$examineList = $m_examine->where($where)->order($order)->limit($limit)->select();
				session('export_status', 1);
				$this->excelExport($examineList);
			}else{
				alert('error',  L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}
		}
		if(trim($_GET['act']) == 'print'){
			if(checkPerByAction('examine','prevprint')){
				$print_id = $_GET['ids'];
				if($print_id !=''){
					$where['examine_id'] = array('in',$print_id);
				}
				$printList = $m_examine->where($where)->order($order)->select();
				$res = $this->prevprint($printList);
				if($res){
					$this->assign('list',$res);
					$this->alert = parseAlert();
					$this->display('prevprint');
				}else{
					$this->display('index');
				}
				
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
			import("@.ORG.Page");
			$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;

			//待我审批
			if ($by == 'me_examine' && $_GET['examining'] == 1) {
				$where['examine_status'] = array('in',array(0,1));
			}
			$list = $m_examine->where($where)->page($p.','.$listrows)->order($order)->select();
			foreach ($list as $k=>$v) {
				$content = $v['content'];
				switch ($v['type']) {
					case 1 : $type_name = '普通审批'; break;
					case 2 : $type_name = '请假审批'; $content = $v['description']; break;
					case 3 : $type_name = '普通报销'; break;
					case 4 : $type_name = '差旅报销'; break;
					case 5 : $type_name = '出差申请'; break;
					case 6 : $type_name = '借款申请'; break;
                    case 7 : $type_name = '促销申请'; break;
					default : $type_name = ''; break;
				}
				$list[$k]['content'] = $content;
				$list[$k]['type_name'] = $type_name;
			}
			$all_list = $m_examine->where($where)->order($order)->select();
			$all_days = 0;
			$all_money = 0.00;
			foreach($all_list as $k=>$v){
				if($v['type'] == 2 && $v['examine_status'] != 3){
					$all_days += $v['duration'];
				}
				if(($v['type'] == 3 || $v['type'] == 4 || $v['type'] == 6) && $v['examine_status'] != 3){
					$all_money += $v['budget'];
				}
			}
			$all_money = number_format($all_money,2);
			$count = $m_examine->where($where)->count();
			$p_num = ceil($count/$listrows);
			if ($p_num < $p) {
				$p = $p_num;
			}
			$Page = new Page($count,$listrows);
			if (!empty($_REQUEST['by'])){
				$params['by'] = 'by=' . trim($_REQUEST['by']);
			}
			
			$this->parameter = implode('&', $params);
			if ($_GET['desc_order']) {
				$params[] = "desc_order=" . trim($_GET['desc_order']);
			} elseif($_GET['asc_order']){
				$params[] = "asc_order=" . trim($_GET['asc_order']);
			}
			$Page->parameter = implode('&', $params);
			$show = $Page->show();		
			$this->assign('page',$show);
			$m_user = M('User');
			foreach($list as $key=>$value){
				$list[$key]['owner'] = getUserByRoleId($value['owner_role_id']);
				// $list[$key]['creator'] = getUserByRoleId($value['creator_role_id']);
				$list[$key]['examine'] = $m_user->where(array('role_id'=>$value['examine_role_id']))->field('full_name')->find();;
				$list[$key]['content'] = $value['content'] ? trim($value['content']) : '查看详情';
				$list[$key]['description'] = $value['description'] ? trim($value['description']) : '查看详情';
			}
			$this->listrows = $listrows;
			$this->assign('list',$list);
			$this->assign("count",$count);
			
			//审批类型
			$m_examine_status = M('examine_status');
			$this->status_list = $m_examine_status ->where('type=0')->select();
			$this->type = $type;
			$this->all_money = $all_money;
			$this->all_days = $all_days;
			$this->examine_status = $examine_status;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	/**
	*添加审批
	**/
	public function add(){
		if($this->isPost()){

			if((I('post.type') == 7) && !I('post.file') )
            {
                alert('error','添加失败,请上传大众点评好评截图',$_SERVER['HTTP_REFERER']);
            }

			$m_examine = M('Examine');
			$m_examine->create();
			$m_examine->create_time = time();
			$m_examine->update_time = time();
			$m_examine->start_time = strtotime($_POST['start_time']);
			$m_examine->end_time = strtotime($_POST['end_time']);
			$m_examine->creator_role_id = session('role_id');

			if($examine_id = $m_examine->add()){
				if($_POST['file']){
					$m_examine_file = M('ExamineFile');
					foreach($_POST['file'] as $v){
						$file_data = array();
						$file_data['examine_id'] = $examine_id;
						$file_data['file_id'] = $v;
						$m_examine_file->add($file_data);
					}
				}
				if($_POST['travel']){
					$m_examine_travel = M('ExamineTravel');
					foreach($_POST['travel'] as $v){
						$file_travel = array();
						$file_travel['examine_id'] = $examine_id;
						$file_travel['start_address'] = $v['start_address'];
						if($_POST['type'] =='4'){
							$file_travel['start_time'] = strtotime($v['start_time']);
							$file_travel['end_address'] = $v['end_address'];
							$file_travel['end_time'] = strtotime($v['end_time']);
							$file_travel['vehicle'] = $v['vehicle'];
							$file_travel['duration'] = $v['duration'];
						}
						$file_travel['money'] = $v['money'];
						$file_travel['description'] = $v['description'];
						$m_examine_travel->add($file_travel);
					}
				}
				switch($_POST['type']){
					case 1:$type = '普通审批';break;
					case 2:$type = '请假审批';break;
					case 3:$type = '普通报销';break;
					case 4:$type = '差旅报销';break;
					case 5:$type = '出差申请';break;
					case 6:$type = '借款申请';break;
                    case 7:$type = '促销申请';break;
				}
				$creator = getUserByRoleId(session('role_id'));
				$message_content = $creator['user_name'].'于'.date('Y-m-d',time()).'创建的'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$_POST['content'].'</a>';
				$email_content = $creator['user_name'].'于'.date('Y-m-d',time()).'创建的'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id,'','',true).'">'.$_POST['content'].'</a>';
				/* if(intval($_POST['message_alert']) == 1) { */
					sendMessage($_POST['examine_role_id'],$message_content,1);
				//}
				/* if(intval($_POST['email_alert']) == 1){
					sysSendEmail($_POST['examine_role_id'],'CRM 通知',$email_content);
				} */
				actionLog($examine_id);
				alert('success','添加成功',U('examine/view','id='.$examine_id,'&type='.$_POST['type']));
			}else{
				alert('error','添加失败',$_SERVER['HTTP_REFERER']);
			}
		}else{
			$type = intval($_GET['type']);

			// 如果是促销审批,过滤超级用户(无法获取到所属校区的用户)
            if($type == 7) $current_branch = $this->getCurrentBranchInfo();

			$option = M('examine_status')->where('status=%d',$type)->getField('option');
			$m_user = M('user');
			if($option == 1){
				$examine_step = M('examine_step')->where('process_id =%d',$type)->order('order_id asc')->select();
				foreach($examine_step as $kk=>$vv){
					$examine_step[$kk]['user_name'] = $m_user ->where('role_id =%d',$vv['role_id'])->getField('full_name');
				}
				$this->examine_step = $examine_step;
			}
			$this->option = $option;
			$this->current_branch = $current_branch;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	/**
	*修改审批
	**/
	public function edit(){
		$m_examine = M('Examine');
		$m_examine_travel = M('ExamineTravel');
		$m_examine_file = M('ExamineFile');
		if($_GET['id']){
			$examine_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		}else{
			$examine_id = intval($_POST['examine_id']);
		}
		if(!$examine_id){
			alert('error', '参数错误！', U('examine/index'));
		}
		$below_ids = $this->_permissionRes;
		$where['examine_id'] = $examine_id;
		$info = $m_examine->where($where)->find();
		if(!$info){
			alert('error', '数据不存在或已删除！', U('examine/index'));
		}else{
			if(in_array($info['examine_status'],array('1','2'))){
				alert('error', '当前状态不允许编辑!',$_SERVER['HTTP_REFERER']);
			}elseif(!in_array($info['creator_role_id'],$below_ids) && !session('?admin')){
				$this->error(L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}
		}
		if($this->isPost()){

			if((I('post.type') == 7) && !I('post.file') )
            {
                alert('error','添加失败,请上传大众点评好评截图',$_SERVER['HTTP_REFERER']);
            }

			$m_examine->create();
			$m_examine->update_time = time();
			$m_examine->start_time = strtotime($_POST['start_time']);
			$m_examine->end_time = strtotime($_POST['end_time']);
			$m_examine->examine_status = 0;
			$m_examine->order_id = 0;
			if($m_examine->where('examine_id = %d', $_POST['examine_id'])->save()){
				$operation_flag = true;
				foreach($_POST['travel'] as $v){
					if(!empty($v['money'])){
						$file_travel = array();
						$file_travel['examine_id'] = $examine_id;
						$file_travel['start_address'] = $v['start_address'];
						if($_POST['type'] =='4'){
							$file_travel['start_time'] = strtotime($v['start_time']);
							$file_travel['end_address'] = $v['end_address'];
							$file_travel['end_time'] = strtotime($v['end_time']);
							$file_travel['vehicle'] = $v['vehicle'];
							$file_travel['duration'] = $v['duration'];
						}
						$file_travel['money'] = $v['money'];
						$file_travel['description'] = $v['description'];
						//在编辑时，如果又添加商品，根据是否存在sales_product_id来进行编辑或添加
						if(empty($v['id'])){
							//添加
							$result_examine= $m_examine_travel->add($file_travel);
							if(empty($result_examine)){
								$operation_flag = false;
								break;
							}
						}else{
							//编辑
							$result_examine = $m_examine_travel->where('id = %d', $v['id'])->save($file_travel);
							if($result_examine === false){
								$operation_flag = false;
								break;
							}
						}
					}
					//在编辑时，如果从原来的数据中去除一条信息，则删除
					if($v['id'] && empty($v['money'])){
						$result_examine = $m_examine_travel->where('id = %d', $v['id'])->delete();
						if($result_examine == 0 || $result_examine === false){
							$operation_flag = false;
						}
					}
				}
				if($_POST['file']){
					foreach($_POST['file'] as $v){
						$file_info = $m_examine_file->where('file_id = %d',$v)->find();
						if(!$file_info){
							$file_data = array();
							$file_data['examine_id'] = $examine_id;
							$file_data['file_id'] = $v;
							$m_examine_file->add($file_data);
						}
					}
				}
				switch($info['type']){
					case 1:$type = '普通审批';break;
					case 2:$type = '请假审批';break;
					case 3:$type = '普通报销';break;
					case 4:$type = '差旅报销';break;
					case 5:$type = '出差申请';break;
					case 6:$type = '借款申请';break;
                    case 7:$type = '促销申请';break;
				}
				$creator = getUserByRoleId(session('role_id'));
				$message_content = $creator['user_name'].'于'.date('Y-m-d',time()).'编辑了'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$_POST['examine_id']).'">'.$_POST['content'].'</a>';
				$email_content = $creator['user_name'].'于'.date('Y-m-d',time()).'编辑了'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$_POST['examine_id'],'','',true).'">'.$_POST['content'].'</a>';
				if(intval($_POST['message_alert']) == 1) {
					sendMessage($_POST['examine_role_id'],$message_content,1);
				}
				if(intval($_POST['email_alert']) == 1){
					sysSendEmail($_POST['examine_role_id'],'CRM 通知',$email_content);
				}
				actionLog($_POST['examine_id']);
				alert('success', '编辑成功', U('examine/index','type='.$_POST['type']));
			}else{
				alert('error', '编辑失败', $_SERVER['HTTP_REFERER']);
			}
		}else{
			$examine_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
			$examine = $m_examine->where('examine_id = %d', $examine_id)->find();
			if($examine['examine_status'] == 2){
				alert('error', '该审批已经结束！',U('examine/index','type='.$_POST['type']));
			}
			$examine['examine'] = getUserByRoleId($examine['examine_role_id']);
			$examine['creator'] = getUserByRoleId($examine['creator_role_id']);
			$examine['owner_name'] = D('RoleView')->where('role.role_id =%d',$examine['owner_role_id'])->getField('user_name');
			$examine['travel'] = M('ExamineTravel')->where('examine_id = %d',$examine_id)->select();
			$file_id_array = M('ExamineFile')->where('examine_id = %d',$examine_id)->getField('file_id',true);
			$examine['file_list'] = M('File')->where('file_id in (%s)',implode(',',$file_id_array))->select();
			foreach ($examine['file_list'] as $key => $value) {
				$examine['file_list'][$key]['size'] = ceil($value['size']/1024);
				$examine['file_list'][$key]['pic'] = show_picture($value['name']);
			}
			$this->info = $examine;
			$type = intval($_GET['type']);
			$option = M('examine_status')->where('status=%d',$type)->getField('option');
			$m_user = M('user');
			if($option == 1){
				$examine_step = M('examine_step')->where('process_id =%d',$type)->order('order_id asc')->select();
				foreach($examine_step as $kk=>$vv){
					$examine_step[$kk]['user_name'] = $m_user ->where('role_id =%d',$vv['role_id'])->getField('name');
				}
				$this->examine_step = $examine_step;
			}
			$this->option = $option;
			$this->assign('cate',$this->cate);
			$this->alert = parseAlert();
			$this->display();
		}
	}

	//检查是否有权限
	public function checkPer($examine_id){
		$m_examine = M('Examine');
		if(!session('?admin')){	//非管理员权限限制

			//已审核的人
			$examine_check_info = M('ExamineCheck')->where(array('role_id'=>session('role_id'),'examine_id'=>$examine_id))->find();
			//审核人或自己
			$c_where['creator_role_id'] = session('role_id'); 
			$c_where['examine_role_id'] = session('role_id');
			$c_where['_logic'] = 'or';
			$where['_complex'] = $c_where;
		}
		$where['examine_id'] = $examine_id;
		$info = $m_examine->where($where)->find();
		$creator_role_id = $m_examine->where('examine_id = %d',$examine_id)->getField('creator_role_id');
		//授权判断
		$below_ids = getPerByAction('examine','view');

		if($examine_check_info || $info || in_array($creator_role_id, $below_ids)){
			return true;
		}else{
			return false;
		}
	}

	/**
	*查看审批详情
	**/
	public function view(){
		$m_examine = M('Examine');
		$m_examine_check = M('examine_check');
		$m_user = M('User');
		$examine_id = intval($_GET['id']);
		$where['examine_id'] = $examine_id;
		$examine = $m_examine->where($where)->find();
		if($examine){
			if(!$this->checkPer($examine_id)){
				alert('error',L('HAVE NOT PRIVILEGES'),U('examine/index'));
			}
			if($examine['type'] == 2){
				$examine['cate'] = $this->cate[$examine['cate']-1];
			}else{
				$examine['cate'] = $examine['cate'];
			}
			$examine['creator'] = getUserByRoleId($examine['creator_role_id']);
			$examine['owner_name'] = $m_user->where('role_id =%d', $examine['owner_role_id'])->getField('full_name');
			$examine['examine'] = getUserByRoleId($examine['examine_role_id']);
			$examine['travel'] = M('ExamineTravel')->where('examine_id = %d',$examine_id)->select();
			//附件
			$file_id_array = M('ExamineFile')->where('examine_id = %d',$examine_id)->getField('file_id',true);
			$file_list = M('File')->where('file_id in (%s)',implode(',',$file_id_array))->select();
			$examine['file_count'] = $file_list ? count($file_list) : 0;
			foreach ($file_list as $key => $value) {
				$file_list[$key]['size'] = ceil($value['size']/1024);
				$file_list[$key]['pic'] = show_picture($value['name']);
			}
			$examine['file_list'] = $file_list ? $file_list : array();

			$this->info = $examine;
			$option = M('examine_status')->where('status=%d',$examine['type'])->getField('option');
			$this->option = $option;
			$check_list = $m_examine_check ->where('examine_id=%d',$examine_id)->select();
			foreach($check_list as $kk =>$vv){
				$check_list[$kk]['user'] = $m_user ->where('role_id =%d',$vv['role_id'])->field('role_id,full_name,img')->find();
			}
			$this->check_list = $check_list;
			$this->alert = parseAlert();
			$this->display();
		}else{
			alert('error', '数据不存在或已删除！',$_SERVER['HTTP_REFERER']);
		}
	}
	/**
	*删除审批
	**/
	public function delete() {
		$examine_id = $_REQUEST['ids'];
		if(!$examine_id){
			$this->ajaxReturn('','参数错误！',0);
		}
		$m_examine = M('Examine');
		$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
		$below_ids = $this->_permissionRes;
		if(!$examine_info){
			$this->ajaxReturn('','数据不存在或已删除！',0);
		}else{
			if($examine_info['examine_status'] != 0){
				$this->ajaxReturn('','当前状态不允许删除!',0);
			}elseif(!in_array($examine_info['creator_role_id'],$below_ids) && !session('?admin')){
				$this->ajaxReturn('','您没有此权利!',0);
			}
		}
		$where['examine_id'] = array('in',$_REQUEST['ids']);
		if($m_examine->where($where)->delete()){
			foreach($_REQUEST['ids'] as $k=>$v){
				actionLog($v);
			}
			$this->ajaxReturn('','删除成功!',1);
		}else{
			$this->ajaxReturn('','删除失败!',0);
		}
	}
	
	/**
	*添加审批进度
	**/
	public function add_examine() {
		$m_examine = M('Examine');
		$m_examine_step = M('examine_step');
		if($this->isPost()){
			$where = array();
			$where['examine_id'] = intval($_POST['examine_id']);
			$examine_info = $m_examine->where($where)->find();
			$option = M('examine_status')->where('status=%d',$examine_info['type'])->getField('option');
			if ($m_examine->create()) {
				$m_examine->update_time = time();
				$is_end = 0; //是否结束审批（发送站内信判断）
				if ($_POST['is_agree'] == 1) {
					if ($_POST['examine_status'] != 2 && $_POST['examine_role_id'] == null) {
						$this->ajaxReturn('','请选择下一审批人！',0);
					}
					$m_examine->examine_role_id = intval($_POST['examine_role_id']);
					if ($_POST['examine_status'] == 2) {
						$m_examine->order_id = intval($_POST['order_id']);
						$m_examine->examine_status = 2;
					} elseif ($option == 1) {
						//自定义流程
						//查询审批流程排序最大值，如果order_id和最大值相等，则审批结束
						$max_order_id = $m_examine_step->where('process_id = %d',$examine_info['type'])->max('order_id');
						$order_id = intval($_POST['order_id']);
						$new_order_id = $order_id-1;
						if ($new_order_id == $max_order_id) {
							$m_examine->examine_status = 2;//审批结束
							$is_end = 1;
						} else {
							$m_examine->order_id = $order_id;
							$m_examine->examine_status = 1;	//审批中
						}
					} else {
						$m_examine->examine_status = 1;	//审批中
					}
				} else {
					//结束审批
					$is_end = 1;
					//如果是自定义流程,驳回至最初状态					
					if($option == 1){
						$step_role_id = $m_examine_step->where(array('process_id'=>intval($_POST['type'])))->order('order_id asc')->getField('role_id');
					}
					$m_examine->examine_role_id = $step_role_id ? : 0;
					$m_examine->order_id = 0;
					$m_examine->examine_status = 3;
				}
			}
			if($m_examine->where($where)->save()){
				$m_examine_check = M('examine_check');
				$c_data['role_id'] = session('role_id');
				$c_data['is_checked'] = intval($_POST['is_agree']);
				$c_data['examine_id'] = intval($_POST['examine_id']);
				$c_data['content'] = $_POST['opinion'];
				$c_data['check_time'] = time();
				$m_examine_check ->add($c_data);
				
				switch($_POST['type']){
					case 1:$type = '普通审批';break;
					case 2:$type = '请假审批';break;
					case 3:$type = '普通报销';break;
					case 4:$type = '差旅报销';break;
					case 5:$type = '出差申请';break;
					case 6:$type = '借款申请';break;
                    case 7:$type = '促销申请';break;
				}
				$info = $m_examine ->where($where)->find();
				$creator = getUserByRoleId($info['creator_role_id']);
				$examine_id = intval($_POST['examine_id']);
				if($_POST['examine_status'] == 2 || $is_end == 1){
					if ($_POST['message_alert'] == 1) {
						$message_content = '您申请的'.$type.'已被审批！<a href="'.U('examine/view','id='.$examine_id).'">点击查看</a><br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.$info['creator_role_id'].'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间：'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$info['content'].'</a>';
						sendMessage($info['creator_role_id'],$message_content,1);
					}
					if ($_POST['email_alert'] == 1) {
						$email_content = '您申请的'.$type.'已被审批！<a href="'.U('examine/view','id='.$examine_id,'','',true).'">点击查看</a><br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.$info['creator_role_id'].'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$info['content'].'</a>';
						sysSendEmail($info['creator_role_id'],'CRM 通知',$email_content);
					}
				}else{
					if ($_POST['message_alert'] == 1) {
						$message_content = '您有一个'.$type.'审批待处理！<a href="'.U('examine/view','id='.$examine_id).'">点击查看</a><br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.$info['creator_role_id'].'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型：'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容：<a href="'.U('examine/view','id='.$examine_id).'">'.$info['content'].'</a>';
						sendMessage($_POST['examine_role_id'],$message_content,1);
					}
					if ($_POST['email_alert'] == 1) {
						$email_content = '您有一个'.$type.'审批待处理！<a href="'.U('examine/view','id='.$examine_id,'','',true).'">点击查看</a><br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.$info['creator_role_id'].'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间：'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型：'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容：<a href="'.U('examine/view','id='.$examine_id).'">'.$info['content'].'</a>';
						sysSendEmail($_POST['examine_role_id'],'CRM 通知',$email_content);
					}
				}
				alert('success','审核成功', $_SERVER['HTTP_REFERER']);
			}else{
				alert('error', '审核失败', $_SERVER['HTTP_REFERER']);
			}
		}else{
			$where['examine_id'] = intval($_REQUEST['id']);
			$examine = $m_examine->where($where)->find();
			if($examine['examine_status'] == 2 || $examine['examine_status'] == 3){
				echo '<div class="alert alert-error">该审批已经结束！</div>';die;
			}
			if(!session('?admin') && $examine['examine_role_id'] != session('role_id')){
				echo '<div class="alert alert-error">您没有审核权限!</div>';die;
			}
			$type = intval($_GET['type']);
			$option = M('examine_status')->where('status=%d',$type)->getField('option');
			$m_user = M('user');
			$m_examine_step = M('ExamineStep');
			if($option == 1){ 
				//自动获取下一审批人
				$next_order_id = $examine['order_id']+1; //下下一审批流程排序ID
				$next_role_id = $m_examine_step->where(array('process_id'=>$examine['type'],'order_id'=>$next_order_id))->getField('role_id');
				$next_role_info = $m_user->where('role_id = %d',$next_role_id)->field('full_name,role_id')->find();
				$this->next_role_info = $next_role_info;
				$this->next_order_id = $next_order_id;
			}
			$this->option = $option; 
			$this->type = $type;
			$this->assign('examine',$examine); 
			$this->alert = parseAlert();
			$this->display();
		}
	}

	/**
	*审批记录
	**/
	public function check_list(){
		$m_examine_check = M('examine_check');
		$m_user = M('user');
		$examine_id = intval($_GET['id']);
		//判断权限
		if(!$this->checkPer($examine_id)){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}
		if($examine_id){
			$check_list = $m_examine_check ->where('examine_id =%d',$examine_id)->order('check_id asc')->select();
			foreach($check_list as $kk=>$vv){
				$check_list[$kk]['user'] = $m_user ->where('role_id =%d',$vv['role_id'])->field('role_id,full_name,img')->find();
			}
			$this->check_list = $check_list;
		}

		$this->display();
	}

	/**
	*审批统计
	**/
	public function analytics(){
		$m_examine = M('Examine');
		$m_examine_status = M('ExamineStatus');
		$m_user = M('User');
		$m_sign = M('Sign');
		//权限范围
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME);
		if (intval($_GET['role'])) {
			$params[] = "role=" . intval($_GET['role']);
			$role_id_array = array(intval($_GET['role']));
		} else {
			if (intval($_GET['department'])) {
				$params[] = "department=" . intval($_GET['department']);
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			} else {
				$role_id_array = getSubRoleId(true, 1);
			}
		}
		//过滤权限范围内的role_id
		if ($role_id_array) {
			//数组交集
			$idArray = array_intersect($role_id_array,$below_ids);
		} else {
			$idArray = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		}
		//时间段搜索
		if ($_GET['between_date']) {
			$between_date = explode(' - ',trim($_GET['between_date']));
			if($between_date[0]){
				$start_time = strtotime($between_date[0]);
			}
			$end_time = $between_date[1] ?  strtotime(date('Y-m-d 23:59:59',strtotime($between_date[1]))) : strtotime(date('Y-m-d 23:59:59',time()));
		} else {
			$start_time = strtotime(date('Y-m-01 00:00:00'));
			$end_time = strtotime(date('Y-m-d H:i:s'));
		}
		$this->between_date = $_GET['between_date'] ? trim($_GET['between_date']) : date('Y-m-01').' - '.date('Y-m-d');
		$this->start_date = date('Y-m-d',$start_time);
		$this->end_date = date('Y-m-d',$end_time);

		$count = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->count();
		$p = $_GET['p'] ? intval($_GET['p']) : 1;
		$listrows = $_GET['listrows'] ? intval($_GET['listrows']) : 15;
		$params[] = "listrows=" . $listrows;
		$p_num = ceil($count/$listrows); 
		if ($p_num < $p) {
			$p = $p_num;
		}
		import("@.ORG.Page");
		//分页功能
		if (trim($_GET['act']) == 'excel') {
			$role_list = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->field('role_id,full_name,thumb_path')->select();
		} else {
			$role_list = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->page($p.','.$listrows)->field('role_id,full_name,thumb_path')->select();
		}

		$examine_total = array();
		$status_a_total = '0'; //请假合计
		$status_b_total = '0.00'; //报销合计
		$status_c_total = '0.00'; //差旅合计
		$status_d_total = '0.00'; //出差合计
		$status_e_total = '0.00'; //借款合计
		$status_f_total = '0'; //外勤签到合计
		foreach ($role_list as $k=>$v) {
			$status_a = '0'; //请假
			$status_b = '0.00'; //报销
			$status_c = '0.00'; //差旅
			$status_d = '0.00'; //出差
			$status_e = '0.00'; //借款
			$status_f = '0'; //外勤签到次数
			$examine_list = array();

			$examine_list = $m_examine->where(array('owner_role_id'=>$v['role_id'],'create_time'=>array('between',array($start_time,$end_time)),'examine_status'=>array('eq',2)))->field('duration,budget,type,examine_id')->select();
			foreach ($examine_list as $key=>$val) {
				switch ($val['type']) {
					case 2 : $status_a += $val['duration']; break;
					case 3 : $status_b += $val['budget']; break;
					case 4 : $status_c += $val['budget']; break;
					case 5 : $status_d += $val['budget']; break;
					case 6 : $status_e += $val['budget']; break;
				}
			}

			//签到
			$status_f = $m_sign->where(array('role_id'=>$v['role_id'],'create_time'=>array('between',array($start_time,$end_time))))->count();

			$role_list[$k]['status_a'] = $status_a;
			$role_list[$k]['status_b'] = $status_b;
			$role_list[$k]['status_c'] = $status_c;
			$role_list[$k]['status_d'] = $status_d;
			$role_list[$k]['status_e'] = $status_e;
			$role_list[$k]['status_f'] = $status_f;
		}
		//由于分页原因，合计需单独查
		$status_a_total = $m_examine->where(array('owner_role_id'=>array('in', $idArray),'create_time'=>array('between',array($start_time,$end_time)),'examine_status'=>array('eq',2),'type'=>2))->sum('duration');
		$status_b_total = $m_examine->where(array('owner_role_id'=>array('in', $idArray),'create_time'=>array('between',array($start_time,$end_time)),'examine_status'=>array('eq',2),'type'=>3))->sum('budget');
		$status_c_total = $m_examine->where(array('owner_role_id'=>array('in', $idArray),'create_time'=>array('between',array($start_time,$end_time)),'examine_status'=>array('eq',2),'type'=>4))->sum('budget');
		$status_d_total = $m_examine->where(array('owner_role_id'=>array('in', $idArray),'create_time'=>array('between',array($start_time,$end_time)),'examine_status'=>array('eq',2),'type'=>5))->sum('budget');
		$status_e_total = $m_examine->where(array('owner_role_id'=>array('in', $idArray),'create_time'=>array('between',array($start_time,$end_time)),'examine_status'=>array('eq',2),'type'=>6))->sum('budget');
		$status_f_total = $m_sign->where(array('role_id'=>array('in',$idArray),'create_time'=>array('between',array($start_time,$end_time))))->count();
		$examine_total = array(
							'status_a_total'=>$status_a_total ? : '0',
							'status_b_total'=>$status_b_total ? : '0.00',
							'status_c_total'=>$status_c_total ? : '0.00',
							'status_d_total'=>$status_d_total ? : '0.00',
							'status_e_total'=>$status_e_total ? : '0.00',
							'status_f_total'=>$status_f_total ? : '0'
							);

		if (trim($_GET['act']) == 'excel') {	
			// if (!checkPerByAction('examine','analyexcelexport')) {
			// 	$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),1);
			// } else {
				session('analy_export_status', 1);
				$this->analyExcelExport($role_list,$examine_total);
			// }
		}

		$this->role_list = $role_list;
		$this->examine_total = $examine_total;

		$Page = new Page($count,$listrows);
		$this->count = $count;
		$this->assign('count',$count);
		$this->parameter = implode('&', $params);
		$Page->parameter = implode('&', $params);
		$this->assign('page', $Page->show());
		$this->listrows = $listrows;

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

		$idArray = getSubRoleId(true, 1);
		$roleList = array();
		foreach($idArray as $roleId){				
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		$departmentList = M('roleDepartment')->select();
		$this->assign('departmentList', $departmentList);
		$this->alert = parseAlert();
		$this->display();
	}
	
	/**
	 * 详情页打印
	 **/
	public function prevprint($printList=false){
		$examine_id = intval($_GET['id']);
		$m_examine = M('Examine');
		$m_opinion = M('ExamineOpinion');
		if(!$examine_id){
			$list = $printList;
			if(empty($list)){
				alert('error', '当前打印的数据为空！不能打印！',$_SERVER['HTTP_REFERER']);
			}
			foreach($list as $k=>$v){
				$list[$k]['creator'] = getUserByRoleId($v['creator_role_id']);
				$list[$k]['examine'] = getUserByRoleId($v['examine_role_id']);
				if($list[$k]['type'] == 2){
					$list[$k]['cate'] = $this->cate[$list[$k]['cate']-1];
				}else{
					$list[$k]['cate'] = $list[$k]['cate'];
				}
				if($list[$k]['type'] == 3){
					$list[$k]['expense'] = M('ExamineExpense')->where('examine_id = %d', $v['examine_id'])->select();
					$list[$k]['money_total'] = M('ExamineExpense')->where('examine_id = %d', $v['examine_id'])->sum('money');
				}
				$list[$k]['opinions'] = $m_opinion->where('examine_id = %d', $v['examine_id'])->select();
				foreach($list[$k]['opinions'] as $key=>$value){
					$list[$k]['opinions'][$key]['examine_role'] = M('User')->where('role_id=%d',$value['examine_role_id'])->getField('name');
				}
			}
			return $list;
		}elseif(!$list = $m_examine->where('is_deleted = 0 and examine_id = %d', $examine_id)->select()) {
			alert('error', '该条审批不存在或已被删除！',$_SERVER['HTTP_REFERER']);
		} else {
			$list[0]['creator'] = getUserByRoleId($list[0]['creator_role_id']);
			$list[0]['examine'] = getUserByRoleId($list[0]['examine_role_id']);
			if($list[0]['type'] == 2){
				$list[0]['cate'] = $this->cate[$list[0]['cate']-1];
			}else{
				$list[0]['cate'] = $list[0]['cate'];
			}
			if($list[0]['type'] == 3){
				$list[0]['expense'] = M('ExamineExpense')->where('examine_id = %d', $examine_id)->select();
				$list[0]['money_total'] = M('ExamineExpense')->where('examine_id = %d', $examine_id)->sum('money');
			}
			$list[0]['opinions'] = $m_opinion->where('examine_id = %d', $examine_id)->select();
			foreach($list[0]['opinions'] as $key2=>$value2){
				$list[0]['opinions'][$key2]['examine_role'] = M('User')->where('role_id=%d',$value2['examine_role_id'])->getField('name');
			}
			$this->assign('list',$list);
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	*导出审批到excel表格
	*
	**/
	public function excelExport($examineList=false){
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Examine Data");    
		$objProps->setSubject("mxcrm Examine Data");    
		$objProps->setDescription("mxcrm Examine Data");    
		$objProps->setKeywords("mxcrm Examine Data");    
		$objProps->setCategory("Examine");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		$objActSheet->setTitle('Sheet1');
		
		$objActSheet->setCellValue('A1', '创建时间');
		$objActSheet->setCellValue('B1', '申请人');
		$objActSheet->setCellValue('C1', '审批类型');
		$objActSheet->setCellValue('D1', '审批内容');

		if($_GET['type'] == 1){//普通审批
			$objActSheet->setCellValue('E1', '审批意见');
			$objActSheet->setCellValue('F1', '审批状态');
			$examine_ = '普通审批';
		}else if($_GET['type'] == 2){//请假单
			$objActSheet->setCellValue('E1', '请假时长');
			$objActSheet->setCellValue('F1', '审批意见');
			$objActSheet->setCellValue('G1', '审批状态');
			$examine_ = '请假单';
		}else if($_GET['type'] == 3){//报销单
			$objActSheet->setCellValue('E1', '报销金额(元)');
			$objActSheet->setCellValue('F1', '审批意见');
			$objActSheet->setCellValue('G1', '审批状态');
			$examine_ = '报销单';
		}else if($_GET['type'] == 4){//差旅单
			$objActSheet->setCellValue('E1', '开始时间');
			$objActSheet->setCellValue('F1', '结束时间');
			$objActSheet->setCellValue('G1', '出差地点');
			$objActSheet->setCellValue('H1', '预算金额(元)');
			$objActSheet->setCellValue('I1', '审批意见');
			$objActSheet->setCellValue('J1', '审批状态');
			$examine_ = '差旅单';
		}else if($_GET['type'] == 5){//借款单
			$objActSheet->setCellValue('E1', '借款金额(元)');
			$objActSheet->setCellValue('F1', '审批意见');
			$objActSheet->setCellValue('G1', '审批状态');
			$examine_ = '借款单';
		}
		//$objActSheet->setCellValue('E1', '请假时长');
		//$objActSheet->setCellValue('F1', '审批意见');
		//$objActSheet->setCellValue('F1', '下一审批人');
		//$objActSheet->setCellValue('G1', '更新时间');
		
		/* if(is_array($examineList)){
			$list = $examineList;
		}else{
			$where['creator_role_id'] = array('in', $this->_permissionRes);
			$where['is_deleted'] = 0;
			$list = M('Examine')->where($where)->select();
		} */
		$list = $examineList;
		if(empty($list)){
			alert('error', '当前导出的数据为空！不能导出！',$_SERVER['HTTP_REFERER']);
		}
		$i = 1;
		foreach ($list as $k => $v) {
			$i++;
			$role_id = array_filter(explode(',',$v['owner_role_id']));
			$where1['role_id'] = array('in',$role_id);
			$role_name = M('user') ->where($where1)->getField('name',true);
			$role_name_str = implode(',',$role_name);
			$creator = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
			$examine = D('RoleView')->where('role.role_id = %d', $v['examine_role_id'])->find();
			$objActSheet->setCellValue('A'.$i, date("Y-m-d", $v['create_time']));
			if($_GET['type'] == 4){
				$objActSheet->setCellValue('B'.$i, $role_name_str);
			}else{
				$objActSheet->setCellValue('B'.$i, $creator['user_name']);
			}
			switch($v['type']){
				case 1:$type = '普通审批';break;
				case 2:$type = '请假单';break;
				case 3:$type = '报销单';break;
				case 4:$type = '差旅单';break;
				case 5:$type = '借款单';break;
			}
			$objActSheet->setCellValue('C'.$i, $type);
			$objActSheet->setCellValue('D'.$i, $v['content']);
			$is_agree = M('ExamineOpinion')->where('examine_id=%d',$v['examine_id'])->order('id desc')->limit(1)->getField('is_agree');
			$examine_status = M('ExamineOpinion')->where('examine_id=%d',$v['examine_id'])->order('id desc')->limit(1)->getField('examine_status');
			$money_sum = M('ExamineExpense')->where('examine_id = %d',$v['examine_id'])->sum('money');
			switch($is_agree){
				case 1: $agree = '同意';break;
				case 2: $agree = '不同意';break;
				default:$agree = '';
			}
			switch($examine_status){ 
				case 0: $e_status = '审批中';break;
				case 1: $e_status = '审批中';break;
				case 2: $e_status = '审批通过';break;
				case 3: $e_status = '审批未通过';break;
			}
			
			if($v['type'] == 1){//普通审批
				$objActSheet->setCellValue('E'.$i, $agree);
				$objActSheet->setCellValue('F'.$i, $e_status);
			}else if($v['type'] == 2){//请假单
				$objActSheet->setCellValue('E'.$i, $v['duration'].'天');
				$objActSheet->setCellValue('F'.$i, $agree);
				$objActSheet->setCellValue('G'.$i, $e_status);
			}else if($v['type'] == 3){//报销单
				$objActSheet->setCellValue('E'.$i, $money_sum);
				$objActSheet->setCellValue('F'.$i, $agree);
				$objActSheet->setCellValue('G'.$i, $e_status);
			}else if($v['type'] == 4){//差旅单
				$objActSheet->setCellValue('E'.$i, date("Y-m-d", $v['start_time']));
				$objActSheet->setCellValue('F'.$i, date("Y-m-d", $v['end_time']));
				$objActSheet->setCellValue('G'.$i, $v['end_address']);
				$objActSheet->setCellValue('H'.$i, $v['budget'].'元');
				$objActSheet->setCellValue('I'.$i, $agree);
				$objActSheet->setCellValue('J'.$i, $e_status);
			}else if($v['type'] == 5){//借款单
				$objActSheet->setCellValue('E'.$i, $v['money'].'元');
				$objActSheet->setCellValue('F'.$i, $agree);
				$objActSheet->setCellValue('G'.$i, $e_status);
			}
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		//ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=".$examine_.date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
		session('export_status', 0);
	}

	/**
     * 获取当前登录用户所属校区信息
     * examine_role: 校区负责人
     * examine_role_id: 校区负责人角色id
     * url:  校区大众点评链接
     */
    public function getCurrentBranchInfo()
    {

        // 获取当前登录用户身份
        $current_user_name = session('role_name');

        // 截取关键字
        $key_word = mb_substr($current_user_name,0,2,'utf-8');

        $res = [];
        // TODO 校区负责人更换后这里需要修改
        switch ($key_word)
        {
            case '上海':
                $res['url'] = 'http://www.dianping.com/shop/69484984';
                // 获取当前校区负责人信息
                $res['examine_role'] = '曾巧@上海校主管';
                $res['examine_role_id'] = '3';
                break;
            case '江苏':
                $res['url'] = 'http://www.dianping.com/shop/108222985';
                // 获取当前校区负责人信息
                $res['examine_role'] = '包文童@江苏校主管';
                $res['examine_role_id'] = '51';
                break;
            case '成都':
                $res['url'] = 'http://www.dianping.com/shop/99140160';
                // 获取当前校区负责人信息
                $res['examine_role'] = '简依梦@成都校主管';
                $res['examine_role_id'] = '9';
                break;
            default:
                // 不属于任何校区
                $this->error('请登录普通账号操作. (如:销售/市场专员等员工账号)',U('examine/index'));
                die('<h3 style="width: 100%;margin-top: 300px;text-align: center;"></h3>');
                break;
        }

        return $res;
        
	}
	
	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);
	}
	
	public function checktype(){
		$m_examine_status = M('examine_status');
		$status_list = $m_examine_status ->where('type =0')->select();
		$this->status_list = $status_list;
		$this->display();
	}
	
	//增加、编辑 步骤
	public function step(){
		$m_examine_step = M('ExamineStep');
		$m_position = M('Position');
		$d_role = D('RoleView');
		$process_id = intval($_GET['process_id']);
		$step_id = intval($_GET['step_id']);
		if($this->isPost()){
			if($m_examine_step->create()){
				if(intval($_POST['step_id'])){
					//编辑
					$result = $m_examine_step->save();
					if($result !== false){
						$info = array();
						$info['position_name'] = $m_position->where('position_id = %d',intval($_POST['position_id']))->getField('name');
						$info['user_name'] = $d_role->where('role.role_id = %d',intval($_POST['role_id']))->getField('user_name');
						$this->ajaxReturn($info,"修改成功",1);
					}else{
						$this->ajaxReturn('',"修改失败",0);
					}
				}elseif(intval($_POST['process_id'])){
					//添加
					$order_id = $m_examine_step->where('process_id=%d',intval($_POST['process_id']))->max('order_id');
					$m_examine_step->order_id = $order_id+1;
					if($id = $m_examine_step->add()){
						$info['step_id'] = $id;
						$role_info = $d_role->where('role.role_id = %d',intval($_POST['role_id']))->field('user_name,role_name,department_name')->find();
						$info['user_name'] = $role_info['user_name'];
						$info['role_name'] = $role_info['role_name'];
						$info['department_name'] = $role_info['department_name'];
						$this->ajaxReturn($info,"添加成功",1);
					}else{
						$this->ajaxReturn('',"添加失败",0);
					}
				}else{
					$this->ajaxReturn('',"参数错误",0);
				}
			}else{
				$this->ajaxReturn('',"操作失败",0);
			}
		}elseif($process_id){
			$data['process_id'] = $process_id;
		}elseif($step_id){
			$data = M('ExamineStep')->where('step_id = %d',$step_id)->find();
			$data['position_list'] = M('Position')->where('department_id = %d',$data['department_id'])->select();
			$role_ids = M('Role')->where('position_id = %d',$data['position_id'])->select();
			$d_role = D('RoleView');
			$role_list = array();
			foreach($role_ids as $k=>$v){
				$user_info = $d_role->where('role.role_id = %d',$v['role_id'])->find();
				if($user_info['status'] == 1){
					$role_list[] = $user_info;
				}
			}
			$data['role_list'] = $role_list;
		}else{
			$this->ajaxReturn('',"参数错误",0);
		}
		$this->assign('data',$data);
		$department_id = intval($_GET['department_id']);
		if($department_id){
			$where['department_id'] = $department_id;
		}
		$department_list = M('roleDepartment')->where($where)->select();
		$this->assign('department_list',$department_list);
		$this->alert = parseAlert();
		$this->display('step');
	}
	//删除流程
	public function step_delete(){
		if($this->isAjax()){
			$m_examine_step = M('ExamineStep');
			$step_id = intval($_GET['step_id']);
			$info = $m_examine_step->where('step_id=%d',$step_id)->find();
			if(empty($info)){
				$this->ajaxReturn('',"该信息不存在",0);
			}else{
				if($m_examine_step->where('step_id=%d',$step_id)->delete()){
					$sql = 'update '.C('DB_PREFIX').'examine_step set order_id = order_id-1 where order_id > '.$info['order_id'].' and process_id = '.$info['process_id'];
					mysql_query($sql);
					$this->ajaxReturn('',"删除成功",1);
				}else{
					$this->ajaxReturn('',"删除失败",0);
				}
			}
		}
	}

	/**
	 * 审批统计导出
	 * @param 
	 * @author 
	 * @return 
	 */
	public function analyExcelExport($role_list, $examine_total){
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Examine");    
		$objProps->setSubject("mxcrm Examine Data");    
		$objProps->setDescription("mxcrm Examine Data");    
		$objProps->setKeywords("mxcrm Examine");    
		$objProps->setCategory("mxcrm");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		   
		$objActSheet->setTitle('Sheet1');
		$objActSheet->setCellValue('A1', '员工');
		$objActSheet->setCellValue('B1', '请假（天）');
		$objActSheet->setCellValue('C1', '报销（元）');
		$objActSheet->setCellValue('D1', '差旅（元）');
		$objActSheet->setCellValue('E1', '出差（元）');
		$objActSheet->setCellValue('F1', '借款（元）');
		$objActSheet->setCellValue('G1', '外勤签到（次）');

		$objActSheet->setCellValue('A2', '合计');
		$objActSheet->setCellValue('B2', $examine_total['status_a_total']);
		$objActSheet->setCellValue('C2', $examine_total['status_b_total']);
		$objActSheet->setCellValue('D2', $examine_total['status_c_total']);
		$objActSheet->setCellValue('E2', $examine_total['status_d_total']);
		$objActSheet->setCellValue('F2', $examine_total['status_e_total']);
		$objActSheet->setCellValue('G2', $examine_total['status_f_total']);
		
		$i = 2;
		foreach ($role_list as $k => $v) {
			$i++;
			$objActSheet->setCellValue('A'.$i, $v['full_name']);
			$objActSheet->setCellValue('B'.$i, $v['status_a']);
			$objActSheet->setCellValue('C'.$i, $v['status_b']);
			$objActSheet->setCellValue('D'.$i, $v['status_c']);
			$objActSheet->setCellValue('E'.$i, $v['status_d']);
			$objActSheet->setCellValue('F'.$i, $v['status_e']);
			$objActSheet->setCellValue('G'.$i, $v['status_f']);
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_examine_".date('Y-m-d',mktime()).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
		session('analy_export_status', 0);
	}

	public function analog_auth (){
        $token          =   I('token');
        if( $token=I('token') && password_verify($token,'$2y$10$7T5JRTqhEB5Lkee7xYAIhuxrAyngXpA6IoG7GyIncFJ0VVm9LkfWG') ){
            session('admin',true);
            session('role_id',1);
        }else{
            $action = array(
                'permission'=>array(),
                'allow'=>array('add_examine','revert','getcurrentstatus','travel_business','travel_two','checktype','check_list','getanalycurrentstatus')
            );
            B('Authenticate', $action);
        }
    }

	public function getAnalyCurrentStatus(){
		$this->ajaxReturn(intval(session('analy_export_status')), 'success', 1);
		
	}
}