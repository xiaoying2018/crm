<?PHP 
/**
*知识模块
*
**/
class KnowledgeAction extends Action{
	
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('getcurrentstatus')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	
	/**
	*知识列表页
	*
	**/
	public function index(){
		$d_knowledge = D('KnowledgeView'); // 实例化User对象
		import('@.ORG.Page');// 导入分页类
		$where = array();
		$params = array();
		
		$order = "knowledge.update_time desc,knowledge.knowledge_id asc";
		if($_GET['desc_order']){
			$order = 'knowledge.'.trim($_GET['desc_order']).' desc,knowledge.knowledge_id asc';
		}elseif($_GET['asc_order']){
			$order = 'knowledge.'.trim($_GET['asc_order']).' asc,knowledge.knowledge_id asc';
		}
		$m_category = M('knowledge_category');
		$category_id = intval($_GET['category_id']);
		$where['knowledge.role_id'] = array('in',$this->_permissionRes);
		
		if(!session('?admin')){
			$category_ids = $m_category ->where('to_department like "%,'.session('department_id').',%"')->getField('category_id', true);
			if($category_id){
				if(!in_array($category_id, $category_ids)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}else{
					$idArray = Array();
					if($category_id){
						$category_list = $m_category ->select();
						$categoryList = getSubCategory($category_id, $category_list, '');

						$idArray[] = $category_id;
						foreach($categoryList as $value){
							$idArray[] = $value['category_id'];
						}
					}
					$where['knowledge.category_id'] = array('in',$idArray);
				}
			}else{
				if($category_ids){
					$where['knowledge.category_id'] = array('in', $category_ids);
				}else{
					$where['knowledge.category_id'] = array('eq',0);
				}
			}
			$category_where['category_id'] = array('in', $category_ids);
			$this->categoryList = $m_category->where($category_where)->select();
		}else{
			if($category_id){
				$idArray = Array();
				if($category_id){
					$category_list = $m_category ->select();
					$categoryList = getSubCategory($category_id, $category_list, '');
					$idArray[] = $category_id;
					foreach($categoryList as $value){
						$idArray[] = $value['category_id'];
					}
				}
				$where['knowledge.category_id'] = array('in',$idArray);
			} 
			$this->categoryList = $m_category->select();
		}

		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']) == 'all' ? 'title|content' : $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('create_time' == $field || 'update_time' == $field) $search = is_numeric($search)?$search:strtotime($search);
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
			$params = array('field='.$field, 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
		}
		//高级搜索
		$fields_search = array();
		if(!$_GET['field']){
			foreach($_GET as $kd=>$vd){
				if($kd != 'act' && $kd != 'p' && $kd !='condition' && $kd != 'listrows' && $kd != 'category_id' && $kd !='daochu' && $kd !='export_limit' && $kd!='current_page'){
					if(in_array($kd,array('create_time','update_time','due_time'))){
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
					}elseif($kd == 'hits'){
						$where[$kd] = field($vd['value'], $vd['condition']);
						$fields_search[$kd]['field'] = $kd;
						$fields_search[$kd]['value'] = $vd['value'];
						$fields_search[$kd]['form_type'] = 'number';
					}elseif($kd =='role_id'){
						if(!empty($vd)){
							$where['role_id'] = $vd['value'];
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
		$p = isset($_GET['p'])?$_GET['p']:1;
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=15";
		}
		if ($category_id) {
			$count = $d_knowledge->where($where)->count();
			$p_num = ceil($count/$listrows);
			if($p_num<$p){
				$p = $p_num;
			}
			$list = $d_knowledge->where($where)->order($order)->Page($p.','.$listrows)->select();
			$params['category_id'] = 'category_id=' . trim($_REQUEST['category_id']);		
		} else {
			$count = $d_knowledge->where($where)->count();// 查询满足要求的总记录数
			$p_num = ceil($count/$listrows);
			if($p_num<$p){
				$p = $p_num;
			}
			$list = $d_knowledge->where($where)->order($order)->Page($p.','.$listrows)->select();
		}	

		if(trim($_GET['act']) == 'excel'){	
			if(!checkPerByAction('knowledge','excelexport')){
				alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}else{
				$dc_id = $_GET['daochu'];
				if($dc_id !=''){
					$where['knowledge_id'] = array('in',$dc_id);
				}
				$current_page = intval($_GET['current_page']);
				$export_limit = intval($_GET['export_limit']);
				$limit = ($export_limit*($current_page-1)).','.$export_limit;
				$knowledgeList = $d_knowledge->order($order)->where($where)->limit($limit)->select();
				session('export_status', 1);
				$this->excelExport($knowledgeList);
			}
		}	
		
		$this->parameter = implode('&', $params);
		if ($_GET['desc_order']) {
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif($_GET['asc_order']){
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}
		
		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$userRole = M('userRole');
		foreach($list as $k => $v){
			$list[$k]['owner'] = D('RoleView')->where('role.role_id = %d', $v['role_id'])->find();
		}
		$this->tree_code = getKnowledgeTreeCode(0,1); //类别选项
		$this->assign('list',$list);// 赋值数据集
		$this->assign('page',$Page->show());// 赋值分页输出
		$this->assign("count",$count);
		$this->assign("listrows",$listrows);
		$this->alert=parseAlert();
		$this->display(); // 输出模板
	}
	
	/**
	*添加知识
	*
	**/
	public function add(){
		if($this->isPost()){
			$title = trim($_POST['title']);
			if ($title == '' || $title == null) {
				alert('error',L('TITLE CAN NOT NULL'),$_SERVER['HTTP_REFERER']);
			}
			$knowledge = D('Knowledge');
			if($knowledge->create()){
				$knowledge->create_time = time();
				$knowledge->update_time = time();
				$konwledge_id = $knowledge->add();
				if($_POST['submit'] == L('SAVE')) {
					alert('success', L('ARTICLE_ADD_SUCCESS'), U('Knowledge/index'));
					alert('success', L('ARTICLE_SAVE_SUCCESS'), U('knowledge/index','id='.$konwledge_id));
				} else {
					alert('success', L('ADD_SUCCESS'), U('Knowledge/add'));
				}
			}else{
				$this->error($knowledge->getError(), U('Knowledge/index'));
			}

		}else{
			$m_category = M('knowledge_category');
			if(!session('?admin')){
				$category_ids = $m_category ->where('to_department like "%,'.session('department_id').',%"')->getField('category_id', true);
				
				$where['category_id'] = array('in', $category_ids);
				
				$category_list = $m_category->where($where)->select();
			}else{
				$category_list = $m_category->select();
			}
			// $categoryList = getSubCategory(0, $category_list, '');

			$this->assign('category_list', $category_list);
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	*知识详情页
	*
	**/
	public function view(){
		if($_GET['id']){
			$knowledge = M('Knowledge');
			$knowledge->where('knowledge_id=%d',$_GET['id'])->setInc('hits');
			$knowledge = $knowledge->where('knowledge_id = %d ', $_GET['id'])->find();
			//知识类别权限
			if(!session('?admin')){
				$category_ids = M('KnowledgeCategory')->where('to_department like "%,'.session('department_id').',%"')->getField('category_id', true);
				if(!in_array($knowledge['category_id'],$category_ids)){
					alert('error',L('DO NOT HAVE PRIVILEGES'),U('knowledge/index'));
				}
			}
			//知识岗位权限
			if(!in_array($knowledge['role_id'], $this->_permissionRes)){
				alert('error',L('DO NOT HAVE PRIVILEGES'),U('knowledge/index'));
			}
			
			$knowledge['owner'] = D('RoleView')->where('role.role_id = %d', $knowledge['role_id'])->find();
			$m_userRole = M('userRole');
			$knowledge['username']  = $m_userRole->where('role_id = %d',$knowledge['role_id'])->getField('name');
			$this->knowledge = $knowledge;
			$this->alert = parseAlert();
			$this->display();
		}else{
			$this->error(L('PARAMETER_ERROR'));
		}
	}
	
	/**
	*知识编辑页
	*
	**/
	public function edit(){
		$m_knowledge = M('Knowledge');
		$knowledge_id = $this->_get('id','intval', intval($_POST['knowledge_id']));
		if(!$knowledge = $m_knowledge->where('knowledge_id = %d',$knowledge_id)->find()){
			$this->error(L('PARAMETER_ERROR'));
		}elseif(!in_array($knowledge['role_id'], $this->_permissionRes)){
			alert('error',L('DO NOT HAVE PRIVILEGES'),U('knowledge/index'));
		}
		//知识类别权限
		if(!session('?admin')){
			$category_ids = M('KnowledgeCategory')->where('to_department like "%,'.session('department_id').',%"')->getField('category_id', true);
			if(!in_array($knowledge['category_id'],$category_ids)){
				alert('error',L('DO NOT HAVE PRIVILEGES'),U('knowledge/index'));
			}
		}
		
		if($this->isPost()){
			$title = trim($_POST['title']);
			if ($title == '' || $title == null) {
				alert('error',L('TITLE CAN NOT NULL'),$_SERVER['HTTP_REFERER']);
			}
			if($m_knowledge->create()){
				$m_knowledge->update_time = time();
				if($m_knowledge->save()){
					if($_POST['submit'] == L('SAVE')) {
						alert('success', "编辑成功！", $_POST['jump_url']);
					} else {
						alert('success', L('SAVE_SUCCESS_CONTINUE_INPUT'), U('knowledge/add'));
					}
				} else {
					alert('error', L('MODIFY_FAILY_DATA_UNCHANGE'),$_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error',L('MODIFY_FAILY_PLEASE_CONTACT_ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
			}
		}elseif($_GET['id']){
			$m_category = M('knowledge_category');
			if(!session('?admin')){
				$category_ids = $m_category ->where('to_department like "%,'.session('department_id').',%"')->getField('category_id', true);
				
				$where['category_id'] = array('in', $category_ids);
				
				$category_list = $m_category->where($where)->select();
			}else{
				$category_list = $m_category->select();
			}
			// $categoryList = getSubCategory(0, $category_list, '');
			$this->assign('category_list', $category_list);
			$this->knowledge = $knowledge;
			$this->jump_url = $_SERVER['HTTP_REFERER'];
			$this->display();
		}else{
			$this->error(L('PARAMETER_ERROR'));
		}
	}
	
	/**
	*知识删除页
	*
	**/
	public function delete(){
		$knowledge = M('Knowledge');
		$knowledge_idarray = $_POST['knowledge_id'];
		if (is_array($knowledge_idarray)) {
			if($this->_permissionRes){
				foreach ($knowledge_idarray as $v) {
					if (!$knowledge->where('knowledge_id = %d and role_id in (%s)', $v, implode(',', $this->_permissionRes))->find()){
						$this->ajaxReturn('',L('DONOT_HAVE_PERMISSIONS_ONLY_AUTHOR_CAN_DELETE'),0);
					}
				}
			}
			if ($knowledge->where('knowledge_id in (%s)', join(',', $knowledge_idarray))->delete()) {
				$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
			} else {
				$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
			}
		} elseif($_GET['id']) {
			if ($this->_permissionRes && !$knowledge->where('knowledge_id = %d and role_id in (%s)', $_GET['id'], implode(',', $this->_permissionRes))->find()){
				$this->ajaxReturn('',L('DONOT_HAVE_PERMISSIONS_ONLY_AUTHOR_CAN_DELETE'),0);
			}
			//知识类别权限
			if(!session('?admin')){
				$category_ids = M('KnowledgeCategory')->where('to_department like "%,'.session('department_id').',%"')->getField('category_id', true);
				if(!in_array($knowledge['category_id'],$category_ids)){
					$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
				}
			}
			if($knowledge->where('knowledge_id = %d', $_GET['id'])->delete()){
				$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
			}else{
				$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
			}
		} else {
			$this->ajaxReturn('',L('PLEASE_SELECT_DELETE_ARTICLE'),0);
		}
	}
	
	/**
	*知识分类页
	*只有管理员可以操作
	**/
	public function category(){
		$knowledge_category = M('knowledge_category');
		$category_list = $knowledge_category->select();
		$category_list = getSubCategory(0, $category_list, '');

		foreach($category_list as $key=>$value){
			$knowledge = M('knowledge');
			$count = $knowledge->where('category_id = %d', $value['category_id'])->count();
			$category_list[$key]['count'] = $count;
			$category_list[$key]['list'] = $knowledge->where('category_id = %d', $value['category_id'])->select();
			$category_list[$key]['to_department_ids'] = explode(',', $value['to_department']);
		}
		
		$department_list = M('RoleDepartment')->select();
		$this->alert=parseAlert();
		$this->assign('category_list', $category_list);
		$this->assign('department_list', $department_list);
		$this->display();
	}
	
	/**
	*添加知识分类
	*只有管理员可以操作
	**/
	public function categoryadd(){
		if ($this->isPost()) {
			$departments = implode(',',$_POST['department_id']);
			$category = D('KnowledgeCategory');
			if(!trim($_POST['name'])){
				alert('error','请填写分类名！',$_SERVER['HTTP_REFERER']);
			}
			if ($t = $category->create()) {
				$category ->to_department = ','.$departments.',';
				if ($category->add()) {
					alert('success', L('ADD_SUCCESS'),$_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('PARAMETER_ERROR_ADD_FAILY'),$_SERVER['HTTP_REFERER']);
				}
			} else {
				exit($category->getError());
			}
		}else{
			$this->assign('tree_code', getKnowDepartmentTreeCode(0,0, 1));
			$category = M('knowledge_category');
			$category_list = $category->select();
			$this->assign('category_list', getSubCategory(0, $category_list, ''));
			$this->display();
		}
		$this->alert = parseAlert();
	}
	
	/**
	*添加知识分类
	*只有管理员可以操作
	**/
	public function categoryEdit(){
		if($_GET['id']){
			$category_id = intval($_GET['id']);
			$knowledge_category = M('knowledgeCategory');
			$this->assign('tree_code', getKnowDepartmentTreeCode($category_id, 0, 1));
			$category_list = $knowledge_category -> select();
			$this->assign('category_list', getSubCategory(0, $category_list, ''));
			$this->knowledge_category =$knowledge_category->where('category_id = %s',$_GET['id'])->find();
			$this->display();
		}elseif($this->isPost()){
			if(!trim($_POST['name'])){
				alert('error','请填写分类名！',$_SERVER['HTTP_REFERER']);
			}
			$knowledge_category = M('knowledgeCategory');
			$knowledge_category -> create();
			$knowledge_category ->to_department = ','.implode(',',$_POST['department_id']).',';
			if($knowledge_category ->where('category_id =%d',$_POST['category_id'])->save()){
				alert('success',L('UPDATE_CATEGORY_INFO_SUCCESS'),U('knowledge/category'));
			}else{
				alert('error',L('DATA_UNCHANGE_UPDATE_CATEGORY_INFO_FAILY'),$_SERVER['HTTP_REFERER']);
			}
		}else{
			$this->error(L('PARAMETER_ERROR'));
		}
		$this->alert = parseAlert();
	}
	
	/**
	*删除知识分类
	*只有管理员可以操作
	**/
	public function categoryDelete(){
		$knowledge_category = M('KnowledgeCategory');
		$knowledge = M('knowledge');
		if($_POST['category_list']){
			foreach($_POST['category_list'] as $value){
				if($knowledge->where('category_id = %d',$value)->select()){
					$name = $knowledge_category->where('category_id = %d',$value)->getField('name');
					$this->ajaxReturn('', L('DELETE_FAILED_REMOVE_THIS_KNOWLEDGE',array($name)),0);
				}
				if($knowledge_category->where('parent_id = %d',$value)->select()){
					$name = $knowledge_category->where('category_id = %d',$value)->getField('name');
					$this->ajaxReturn('',L('DELETE_FAILED_REMOVE_THIS_CATEGORY',$name),0);
				}
			}
			if($knowledge_category->where('category_id in (%s)', join($_POST['category_list'],','))->delete()){
				$this->ajaxReturn('',L('DELETE_CATEGORY_SUCCESS'),1);
			}else{
				$this->ajaxReturn('',L('DELETE_CATEGORY_FAILY'),0);
			}
		}elseif($_GET['id']){
			if($knowledge->where('category_id = %d',$_GET['id'])->select()){
				$this->error(L('DELETE_FAILED_REMOVE_KNOWLEDGE'));
				alert('error', L('PARAMETER_ERROR_ADD_FAILY'),$_SERVER['HTTP_REFERER']);	
			}
			if($knowledge->where('parent_id = %d',$_GET['id'])){
				alert('error', L('PLEASE_REMOVE_THIS_CATEGORY'),$_SERVER['HTTP_REFERER']);	
			}else{
				$this->error(L('PARAMETER_ERROR'));
			}
		}else{
			$this->error(L('DELETE_FAILY'));
		}
		$this->alert = parseAlert();
	}
	
	/**
	*知识导出
	*
	**/
	public function excelExport($knowledgeList=false){
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
		   
		$objActSheet->setTitle('Sheet1');
		$objActSheet->setCellValue('A1', L('TITLE'));
		$objActSheet->setCellValue('B1', L('CATEGORY'));
		$objActSheet->setCellValue('C1', L('CLICK_NUM'));
		$objActSheet->setCellValue('D1', L('CREATOR_ROLE'));
		$objActSheet->setCellValue('E1', L('CREATOR_TIME'));
		
		if(is_array($knowledgeList)){
			$list = $knowledgeList;
		}else{
			$list = D('KnowledgeView')->select();
		}
		
		$i = 1;
		foreach ($list as $k => $v) {
			$i++;
			$creator = D('RoleView')->where('role.role_id = %d', $v['role_id'])->find();
			$objActSheet->setCellValue('A'.$i , $v['title']);
			$objActSheet->setCellValue('B'.$i, $v['name']);
			$objActSheet->setCellValue('C'.$i, $v['hits']);
			$objActSheet->setCellValue('D'.$i, $creator['user_name'].'['.$creator['department_name'] . '-' . $creator['role_name'] .']');
			$objActSheet->setCellValue('E'.$i, date("Y-m-d H:i:s", $v['create_time']));
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_knowledge_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
		session('export_status', 0);
	}
	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);
		
	}
	
	/**
	*知识导入
	*
	**/
	public function excelImport(){
		$m_knowledge = M('knowledge');
		if($this->isPost()){
			if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts  = array('xls');
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					alert('error', L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'), U('knowledge/index'));
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					alert('error', $upload->getErrorMsg(), U('knowledge/index'));
				}else{
					$info =  $upload->getUploadFileInfo();
				}
			}
			if(is_array($info[0]) && !empty($info[0])){
				$savePath = $dirname . $info[0]['savename'];
			}else{
				alert('error', L('UPLOAD FAILED'), U('knowledge/index'));
			}
			import("ORG.PHPExcel.PHPExcel");
			$PHPExcel = new PHPExcel();
			$PHPReader = new PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($savePath)){
				$PHPReader = new PHPExcel_Reader_Excel5();
			}
			$PHPExcel = $PHPReader->load($savePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allRow = $currentSheet->getHighestRow();
			if ($allRow <= 1) {
				alert('error', L('UPLOAD A FILE WITHOUT A VALID DATA'), U('knowledge/index'));
			} else {
				for($currentRow = 3;$currentRow <= $allRow;$currentRow++){
					$data = array();
					$data['category_id'] = intval($_POST['category_id']);
					$data['role_id'] = session('role_id');
					$data['create_time'] = time();
					$data['update_time'] = time();
					$title = (string)$currentSheet->getCell('A'.$currentRow)->getValue();
					if($title != '' && $title != null) $data['title'] = $title;
					
					$category = (String)$currentSheet->getCell('B'.$currentRow)->getValue();
					$category_id = M('KnowledgeCategory')->where('name = "%s"' ,trim($category))->getField('category_id');
					if($category){
						if($category_id > 0){
							$data['category_id'] = $category_id;
						} else {
							if($this->_post('error_handing','intval',0) == 0){
								alert('error', L('IMPORT_FAILY_SOURCE_NOT_EXIST',array($currentRow,$category)), U('knowledge/index'));
							}else{
								$error_message .= L('FAILY_SOURCE_NOT_EXIST',array($currentRow,$category));
							}
							break;
						}
					}
					
					$content = (string)$currentSheet->getCell('C'.$currentRow)->getValue();
					if($content != '' && $content != null) $data['content'] = $content;
					if (!$m_knowledge->add($data)) {
						if($this->_post('error_handing','intval',0) == 0){
							alert('error', L('IMPORT_FAILY_SOURCE',array($currentRow)), U('knowledge/index'));
						}else{
							$error_message .= L('FAILY_SOURCE',array($currentRow,$m_knowledge->getError()));
							$m_knowledge->clearError();
						}
						
						break;
					}
					
				}
				alert('success', $error_message .L('IMPORT SUCCESS'), U('knowledge/index'));
			}
		}else{
			$this->category_list = getSubCategory(0, M('KnowledgeCategory')->select(), '');
			$this->display();
		}
	}
}
