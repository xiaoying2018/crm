<?php 
// 
class IndexAction extends Action {
    
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('index','widget_edit','widget_delete','widget_add','sortcharts','dynamic_data','getTransLocation','updatedata', 'getdatas','getareas', 'getAllSub', 'getdatass')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
    ##排序

    public function wpjam_array_multisort($array, $orderby, $order = SORT_ASC, $sort_flags = SORT_NUMERIC) {
        $refer = array();
        foreach ($array as $key => $value) {
            $refer[$key] = $value[$orderby];
        }
        array_multisort($refer, $order, $array);
        return $array;
    }

    public function getareas(){
         $areas = M('fields')->where(['field_id'=>74])->find();
        $a = $areas['setting'];
        $b = substr($a, 37);
        $b = substr($b,0, -2);
        $b = explode(',', $b);
        foreach ($b as $k=>$v){
            if($k<9){
                $provinces[] = substr(substr($v, 4),0,-1);
            }else{
                $provinces[] = substr(substr($v, 5),0,-1);
            }
        }
        return $provinces;
    }

    public function getdatas() {
        $m_leads = M('leads');
        $c = $this->getareas();
        $reqdata = I('get.');
        $wherea1['owner_role_id'] = array('neq', 0);
         $sucwhere['owner_role_id'] = array('neq', 0);
        foreach ($c as $k1 => $v1) {
            $wherea1['crm_city'] = $v1;
            $sucwhere['is_transformed'] = 1;
            $sucwhere['crm_city'] = $v1;
            $success_count = $m_leads->where($sucwhere)->count();

            $res1[$k1]['areas'] = $v1;

            $res1[$k1]['leads'] = $r = $m_leads->where($wherea1)->count();
            $res1[$k1]['succ'] = $succcount = $m_leads->where($sucwhere)->count();
            $success_rate = $success_count ? round($succcount / $r, 4) * 100 : 0;
            $res1[$k1]['succrate'] = $success_rate;

            if (!empty($reqdata)) {
                if ($reqdata['classes'] == 'leadss') {
                    if ($reqdata['leadorder'] == 2) {
                        $r = $this->wpjam_array_multisort($res1, 'leads', SORT_DESC);
                    } else if ($reqdata['leadorder'] == 3) {
                        $r = $this->wpjam_array_multisort($res1, 'leads', SORT_ASC);
                    } else {
                        $r = $res1;
                    }
                } else if ($reqdata['classes'] == 'trans') {
                    if ($reqdata['leadorder'] == 2) {
                        $r = $this->wpjam_array_multisort($res1, 'succ', SORT_DESC);
                    } else if ($reqdata['leadorder'] == 3) {
                        $r = $this->wpjam_array_multisort($res1, 'succ', SORT_ASC);
                    } else {
                        $r = $res1;
                    }
                } else if ($reqdata['classes'] == 'transrate') {
                    if ($reqdata['leadorder'] == 2) {
                        $r = $this->wpjam_array_multisort($res1, 'succrate', SORT_DESC);
                    } else if ($reqdata['leadorder'] == 3) {
                        $r = $this->wpjam_array_multisort($res1, 'succrate', SORT_ASC);
                    } else {
                        $r = $res1;
                    }
                }
                $areas = array_reverse(array_column($r, 'areas'));
                $leads = array_reverse(array_column($r, 'leads'));
            }else{
                 
                $r = $res1;
                $areas = array_reverse(array_column($r, 'areas'));
                $leads = array_reverse(array_column($r, 'leads'));
            }
        }
        $this->ajaxReturn(['status' => true, 'data' => $r, 'areas' => $areas, 'leads' => $leads]);
//        $this->ajaxReturn(['status' => true, 'data' => $r,'areas'=>array_column($r,'areas'),'leads'=>array_column($r,'leads')]);
    }
        public function getAllSub($data, $position_id) {
        static $arr = [];
        $m_position = M('position');
        $p = $m_position->where(['position_id' => $_SESSION['position_id']])->find();
        $arr[0] = $p;
        foreach ($data as $key => $val) {
            if ($val['parent_id'] == $position_id) {
                $arr[] = $val;
                $this->getAllSub($data, $val['position_id']);
            }
        }
        return $arr;
    }
    
	public function index(){

		//手机访问跳转
		if (isMobile()) {
			$mobile = str_replace('index.php', 'mobile', $_SERVER["PHP_SELF"]);
			header("Location: http://".$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$mobile);
		}
		$user = M('User');
		$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widget = unserialize($dashboard);	

		foreach($widget['sort'] as $k => $v){
			$res[] = $widget['dashboard'][$v];
		}
		$this->widget = $res;
		// if (!F('smtp') && session('?admin')) {
		// 	alert('info', L('NOT_CONFIGURED_SMTP_INFORMATION_CLICK_HERE_TO_SET',array(U('setting/smtp'))));
		// }
		// if (!F('defaultinfo') && session('?admin')) {
		// 	alert('info', L('SYSTEM_INFORMATION_NOT_CONFIGURED_BY_DEFAULT_CLICK_HERE_TO_SET',array(U('setting/defaultinfo'))));
		// }

        $m_leads = M('leads');
        $crmcity = $this->getareas();
        $this->assign('crmcity', $crmcity);
        //权限判断
        $m_position = M('position');
        $positions = $m_position->where(['position_id' => $_SESSION['position_id']])->find();
        $data1 = $m_position->select();
        $isHaveSub = $m_position->where(['parent_id' => $positions['position_id']])->find();

        $url = getCheckUrlByAction(MODULE_NAME, ACTION_NAME);
        $per_type = M('Permission')->where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');

        if ($per_type == 2 || session('?admin')) {
            $rList = M('role')->select();
            $role_id_array = array_column($rList, 'role_id');
            $role_id_array = array_column(M('user')->where(['role_id' => ['in', $role_id_array], 'type' => 1,'status'=>1])->select(), 'role_id');
        } else if ($isHaveSub) {
            $res = $this->getAllSub($data1, $positions['position_id']);
            $p_id = array_column($res, 'position_id');
            $role_id_array = M('role')->where(['position_id' => ['in', $p_id]])->select();
            $role_id_array = array_column($role_id_array, 'role_id');
            $role_id_array = array_column(M('user')->where(['role_id' => ['in', $role_id_array], 'type' => 1,'status'=>1])->select(), 'role_id');
        } else {
            $role_id_array[] = M('role')->where(['role_id' => $_SESSION['role_id']])->find()['role_id'];
        }
//        var_dump($role_id_array);
        if (intval($_GET['role'])) {
            if (in_array($_GET['role'], $role_id_array)) {
                $role_id_array = [];
                $role_id_array[] = $_GET['role'];
            } else {
                $role_id_array = [];
            }
        }
//        var_dump($_SESSION);
        if (intval($_GET['department'])) {
            $department_id = intval($_GET['department']);
            foreach (getRoleByDepartmentId($department_id, true) as $k => $v) {
                $role_ids[] = $v['role_id'];
            }
            $role_id_array = array_intersect($role_id_array, $role_ids);
        }
//        var_dump($role_id_array);
        ### 部门岗位
        $url = getCheckUrlByAction(MODULE_NAME, ACTION_NAME);
        $per_type = M('Permission')->where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
        if ($per_type == 2 || session('?admin')) {
            $departmentList = M('roleDepartment')->select();
        } else {
            $departmentList = M('roleDepartment')->where('department_id =%d', session('department_id'))->select();
//            $departmentList['is_s'] = 2;
        }

        $this->assign('departmentList', $departmentList);
//        var_dump($departmentList);
        ### 角色
        $roleList = [];
        if (intval($_GET['department'])) {
            $department_id = intval($_GET['department']);
            foreach (getRoleByDepartmentId($department_id, true) as $k => $v) {
                $roleidss[] = $v['role_id'];
            }
            $roleList = M('user')->where(['role_id' => ['in', $roleidss]])->select();
        } else {
            $roleList = M('user')->where(['role_id' => ['in', $role_id_array]])->select();
        }
        foreach ($roleList as $k1 => $v1) {
            $po_id = M('role')->where(['role_id' => $v1['role_id']])->find()['position_id'];
            $roleList[$k1]['full_name'] = M('position')->where(['position_id' => $po_id])->find()['name'] . '-' . $v1['full_name'];
        }
        $this->assign('roleList', $roleList);
        ###搜索条件
        //时间段搜索
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $start_time = strtotime(I('start_date'));
            $end_time = strtotime(I('end_date'));
            $this->assign('starttime', date('Y-m-d', $start_time));
            $this->assign('endtime', date('Y-m-d', $end_time));
        } else {
            $start_time = strtotime(date('Y-m-d', 1526745600));
            $end_time = strtotime(date('Y-m-d', time()));
        }

        $m_user = M('User');
        $m_leads = M('leads');
        $outdays = M('config')->where('name="leads_outdays"')->getField('value');
        $outdate = empty($outdays) ? 0 : time() - 86400 * $outdays;

        $own_count_total = 0;
        $success_count_total = 0; //转换
        $deal_count_total = 0; //跟进
        $own_response_time_total = 0;
        $success_rate_total = 0;
        if (!empty($_REQUEST['area_name']) && $_REQUEST['area_name'] != 'all') {
            $where['crm_city'] = $_REQUEST['area_name'];
            $sucwheres['crm_city'] = $_REQUEST['area_name'];
            $deal_where['crm_city'] = $_REQUEST['area_name'];
        }
        
        ## 根据省获取数据
        // $areas = M('areas')->select();
        $areas = $this->getareas();
        $this->assign('areass',$areas);
         $wherea1['owner_role_id'] = array('neq', 0);
         $sucwhere['owner_role_id'] = array('neq', 0);
        foreach ($areas as $k1 => $v1) {
//            if ($user_info['status'] == 1) {
//            $wherea1['is_deleted'] = 0;
//            $wherea1['is_transformed'] = ['neq', 1];
//            $wherea1['create_time'][] = ['gt', $start_time];
//            $wherea1['create_time'][] = ['lt', $end_time];
            $wherea1['crm_city'] =  $v1;
//            var_dump($wherea1);exit;
//                $res1['allleads'][] = $re = $m_leads->where($wherea1)->count();
//                echo $re . '<br>';
//            $sucwhere['is_deleted'] = 0;
//            $sucwhere['have_time'] = array('egt', $outdate);
            $sucwhere['is_transformed'] = 1;
//            $sucwhere['create_time'][] = ['gt', $start_time];
//            $sucwhere['create_time'][] = ['lt', $end_time];
            $sucwhere['crm_city'] =  $v1;
            $success_count = $m_leads->where($sucwhere)->count();

            $res1[$v1]['leads'][] = $r = $m_leads->where($wherea1)->count();
//            var_dump($wherea1);
//            exit;
            $res1[$v1]['succ'][] = $succcount = $m_leads->where($sucwhere)->count();
            //转化率
            $success_rate = $success_count ? round($succcount / $r, 4) * 100 : 0;
            $res1[$v1]['succrate'][] = $success_rate;
//            $r = $this->wpjam_array_multisort($res1, 'leads', SORT_DESC);
        }
        $ss = array_column($res1, 'leads');
        foreach ($ss as $kk => $vv) {
            $n[] = $vv[0];
        }
        $this->assign('sums', array_sum($n));
        $this->assign('datas', $res1);
        ## 角色
        foreach ($role_id_array as $v) {
            $user_info = array();
            $user_info = getUserByRoleId($v);
            //过滤已停用用户
            if ($user_info['status'] == 1) {
                //负责的线索
//                $where['is_deleted'] = 0;
                $where['owner_role_id'] = $v;
//                $where['is_transformed'] = ['neq', 1];
                $where['create_time'][] = ['gt', $start_time];
                $where['create_time'][] = ['lt', $end_time];
//                var_dump($where);echo '<br>';
//                $sucwheres['owner_role_id'] = array('neq', 0);
//                $where['have_time'] = ['egt',$outdate];
                $own_list = $m_leads->where($where)->field('first_time,have_time,leads_id')->select();
//                var_dump($where);
                $own_count = count($own_list);

                //已转换客户
                $sucwheres['is_deleted'] = 0;
                $sucwheres['is_transformed'] = 1;
                $sucwheres['owner_role_id'] = $v;
                $sucwheres['create_time'][] = ['gt', $start_time];
                $sucwheres['create_time'][] = ['lt', $end_time];
                $success_count = $m_leads->where($sucwheres)->count();
                //转化率
                $success_rate = $success_count ? round($success_count / $own_count, 4) * 100 : 0;
                //已跟进
                $deal_where = array();
                $deal_where['is_deleted'] = 0;
                $deal_where['owner_role_id'] = $v;
//                $deal_where['is_transformed'] = array('eq', 1);
                $deal_where['create_time'][] = ['gt', $start_time];
                $deal_where['create_time'][] = ['lt', $end_time];
                $rllog = M('rLeadsLog');
                $deal_count = '';
                foreach ($own_list as $k1 => $v1) {
                    if ($rllog->where(['leads_id' => $v1['leads_id']])->find()) {
                        ++$deal_count;
                    }
                }
                if (!$deal_count) {
                    $deal_count = 0;
                }
//                echo '<pre>';
//                var_dump($deal_where);
//                $deal_where['have_time'] = array('egt', $outdate);
//                $deal_where['create_time'] = $create_time;
//                $deal_count = $m_leads->where('update_time > create_time')->where($where)->count();
                $reportList[] = array("user" => $user_info, "own_count" => $own_count, "deal_count" => $deal_count, "success_count" => $success_count, "own_response_time" => $own_response_time, "success_rate" => $success_rate);
                $own_count_total += $own_count;
                $success_count_total += $success_count;
                $deal_count_total += $deal_count;
//                $own_response_time_total += $response_time_total;
            }
        }
//        var_dump($reportList);exit;
        $tsuccess_rate = $own_count_total ? round($success_count_total/$own_count_total, 4) * 100 : 0;
        $total_report['own_count'] = $own_count_total;
        $total_report['deal_count'] = $deal_count_total;
        $total_report['own_response_time_total'] = $own_response_time_total;
        $total_report['success_count'] = $success_count_total;
        $total_report['tsuccess_rate'] = $tsuccess_rate;
        $this->assign('total_report', $total_report);
//        var_dump($total_report);exit;
        $this->assign('reportList', $reportList);
        $this->roleList = $roleList;
        $this->assign('roleList', $roleList);
        ###  线索搜索end
		
		//动态信息
		$m_action_log = M('ActionLog');
		$m_comment = M('Comment');
		$m_customer = M('Customer');
		$m_leads = M('Leads');
		$m_business = M('Business');
		$m_sales = M('Sales');
		$m_contacts = M('Contacts');
		$m_contract = M('Contract');
		$m_product = M('Product');
		$m_log = M('Log');
		$m_fields = M('Fields');
		$m_r_file_log = M('RFileLog');
		$m_file = M('File');
		$m_log_talk = M('LogTalk');//日志评论回复表
		$m_event = M('Event');
		$m_task = M('Task');
		$where = array();	//查询条件
		$where['action_delete'] = 0;
		$opeartion = 'view';	//默认都跳转到view界面
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$this->p = $p;
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		//module=log时，module_id为log表的log_id; module为dynamic时，module_id为action_log表的log_id
		switch ($by) {
			case 'customer' : 
				$where['module_name'] =  array('eq', 'customer');
				break;
			case 'log' : 
				$where['module_name'] =  array('eq', 'log');
				break;
			case 'sign' :
				$where['module_name'] =  array('eq','sign');
				break;
			case 'examine' :
				$where['module_name'] =  array('eq','examine');
				break;
			case 'task' :
				$where['module_name'] = array('eq','task');
				break;
			case 'business' :
				$where['module_name'] = array('eq','business');
				break;
			case 'crm' : 
				$where['module_name'] =  array('not in','log,examine,sign');
				break;
		}
		if(!empty($reply)){
			$params[] = "reply=" . trim($_GET['reply']);
		}
		
		if($_GET['department_id']){
			$department_id = intval($_GET['department_id']);
			//选中部门下的所有员工
			$subPositionIdArr = M('position')->where('department_id = %d',$department_id)->order('position_id asc')->getField('position_id',true);
			$subRoleIdArr = M('role')->where(array('position_id'=>array('in', $subPositionIdArr)))->getField('role_id',true);	//部门下role_id
			if(!session('admin')){
				//条件为选中部门下,我的下属员工的role_id
				$mySubRoleIdArr = getSubRoleId();	//我的下属role_id
				$where['role_id'] = array('in', array_intersect($subRoleIdArr,$mySubRoleIdArr));
			}else{
				$where['role_id'] = array('in', $subRoleIdArr);
			}
		}else{
			//条件为选中部门下,我的下属员工的role_id
			if(!session('?admin')){
				$where['role_id'] = array('in', getSubRoleId());
			}
		}

		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']);
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			if($field == 'role'){
				if(!empty($search)){
					$same_role_id_array = M('user')->where('name like "%s"', "%$search%")->getField('role_id',true);
					$role_id_array = getSubRoleId(true, 1);		//下属role_id
					$role_idArr = array_intersect($same_role_id_array,$role_id_array);	//交集
					$where['role_id'] = array('in',$role_idArr);
				}
			}elseif($field == 'content'){
				$where['content'] = array('like',"%$search%");
			}
			$params = array('field='.trim($_REQUEST['field']), 'search='.$search);
		}
		$action_log = $m_action_log->where($where)->order('create_time desc')->page($p.',10')->select();
		$count = $m_action_log->where($where)->count();
		import("@.ORG.Page");
		$Page = new Page($count,10);
		if (!empty($_GET['by'])) {
			$params[] = "by=" . trim($_GET['by']);
		}
		$m_sign_img = M('signImg');
		$r_contract_sales = M('r_contract_sales');
		foreach($action_log as $k=>$v){
			if($v['module_name'] == 'finance'){
				$module_name = substr($v['param_name'],2);
			}else{
				$module_name = $v['module_name'];
			}
			$param_name = $v['param_name'];
			
			$action_log[$k]['sign_img'] = $m_sign_img ->where('sign_id = "%d"',$v['action_id'])->select();
			$m_module_name = M($module_name);
			$pk_id = $m_module_name->getPk();
			$object_module = $m_module_name->where("$pk_id = %d", $v['action_id'])->find();
			
			if($v['module_name'] == 'examine'){
				$name = $object_module['content'];
			}elseif($v['module_name'] == 'contract'){
				$name = $object_module['number'];
			}elseif($v['module_name'] == 'sales'){
				$contract_id = $r_contract_sales->where('sales_id =%d',$v['action_id'])->getField('contract_id');
				$name = $m_contract->where('contract_id =%d',$contract_id)->getField('number');
			}elseif($v['module_name'] == 'purchase'){
				$name = $object_module['sn_code'];
			}elseif($v['module_name'] == 'user'){
				$name = $object_module['full_name'];
			}elseif($v['module_name'] == 'examine'){
				$name = $object_module['content'] ? $object_module['content'] : '查看详情';
			}else{
				$name = $object_module['name'];
			}
			if(empty($name)){
				$name = $object_module['subject'];
			}
			//如果是日志，则追加日志内容
			if($v['action_name'] == 'mylog_add'){
				$apContent = $object_module['content'];
				$appHtml = strip_tags($apContent);
				//如果是日志，则跳转到mylog_view
				$action_name = 'mylog_view';
			}else{
				$appHtml = '';
				$action_name = strtolower($v['action_name']);
			}
			$action_log[$k]['creator'] = getUserByRoleId($v['role_id']);

			$username = $action_log[$k]['creator']['user_name'];
			$operation = L(strtolower($v['action_name'])).'了';
			if($v['module_name'] == "quote"){
				$module = '报价单';
			}elseif($v['module_name'] == 'sales'){
				$module = '销售合同';
			}elseif($v['module_name'] == 'purchase' && $v['action_name'] == 'outof'){
				$module = '采购退货';
			}else{
				$module = L(strtolower($v['module_name']));
			}
			$action_log[$k]['dynamic'] = $operation.$module;

			$action_log[$k]['address'] = $object_module['address'].' '.$object_module['title'];
			$action_log[$k]['log'] = $object_module['log'];
			$action_log[$k]['x'] = $object_module['x'];
			$action_log[$k]['y'] = $object_module['y'];
			//外勤签到相关客户
			$sign_customer_id = '';
			$sign_customer_id = M('Sign')->where(array('sign_id'=>$v['action_id']))->getField('customer_id');
			$customer_info = M('Customer')->where(array('customer_id'=>$sign_customer_id))->field('name,customer_id')->find();
			$action_log[$k]['customer_info'] = $customer_info; 
			
			//获取阶段
			switch ($v['module_name']) {
				case 'log' : 
					$log_info = $m_log ->where('log_id =%d',$v['action_id'])->find();
					$action_log[$k]['cut_content'] = '<a href="./index.php?m='.$v['module_name'].'&a=mylog_view&'.$param_name.'&id='.$v['action_id'].'" title="查看详情" style="color:#676a6c;">'.cutString($log_info['content'],110).'</a>';
					$action_log[$k]['log_content'] = '<a href="./index.php?m='.$v['module_name'].'&a=mylog_view&'.$param_name.'&id='.$v['action_id'].'" title="查看详情" style="color:#676a6c;">'.$log_info['content'].'</a>';;
					$action_log[$k]['content_open'] = 0;
					//是否需展开全文
					if(mb_strlen($log_info['content'],'UTF8') > 109){
						$action_log[$k]['content_open'] = 1;
					}
					//附件
					$file_ids = array();
					$file_ids = $m_r_file_log->where('log_id = %d',$v['action_id'])->getField('file_id',true);
					$log_files = $m_file->where(array('file_id'=>array('in',$file_ids)))->select();
					if(!empty($file_ids)){
						foreach ($log_files as $key=>$value) {
							$log_files[$key]['size'] = ceil($value['size']/1024);
							/*判断文件格式 对应其图片*/
							$log_files[$key]['pic'] = show_picture($value['name']);
							$log_files[$key]['cut_name'] = cutString($value['name'],25);
						}
					}
					$action_log[$k]['log_files'] = $log_files;
					$action_log[$k]['file_count'] = count($file_ids);
					//日志评论
					$action_log[$k]['comment_count'] = $m_log_talk->group('g_mark')->where('log_id = %d',$v['action_id'])->count();
					break;
				case 'customer' : 
					$customer_list = $m_customer ->where('customer_id =%d',$v['action_id'])->find();
					$fields_type = $m_fields->where('model = "customer" and field = "industry"')->getField('name');
					$fields_type2 = $m_fields->where('model = "customer" and field = "origin"')->getField('name');
					$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v['action_id'].'">'.$name.'</a>';
					$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$customer_list['industry'];
					$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.$customer_list['origin'].'&nbsp;&nbsp;'.$something;
					break;
				case 'contract' :
					$contract_list = $m_contract ->where('contract_id =%d',$v['action_id'])->find();
					if($contract_list['type'] == 1){
						$customer_name = M('customer')->where('customer_id = %d',$contract_list['customer_id'])->getField('name');
					}elseif($contract_list['type'] == 2){
						$customer_name = M('supplier')->where('supplier_id = %d',$info['supplier_id'])->getField('name');
					}
					$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v['action_id'].'">'.$contract_list['number'].'</a>';
					$action_log[$k]['type1'] = '<span style="color:#000">客户/供应商&nbsp:&nbsp</span>'.$customer_name;
					$action_log[$k]['type2'] = '<span style="color:#000">状态&nbsp:&nbsp</span>'.$contract_list['status'].'&nbsp;&nbsp;'.$something;
					break;
				case 'business' :
					$business_info = $m_business ->where('business_id =%d',$v['action_id'])->find();
					$status_name = M('business_status')->where('status_id =%d',$business_info['status_id'])->getField('name');
					$fields_type = $m_fields->where('model = "business" and field = "status_id"')->getField('name');
					$fields_type2 = $m_fields->where('model = "business" and field = "nextstep_time"')->getField('name');

					$action_log[$k]['type'] = '<a href="./index.php?m=customer'.'&a=view&id='.$business_info['customer_id'].'&bid='.$v['action_id'].'">'.$name.'</a>';
					$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$status_name;
					$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.date("Y-m-d H:i", $business_info['nextstep_time']).'&nbsp;&nbsp;'.$something;
					break;
				case 'user' :
					$user_list = D('UserView') ->where('user.user_id =%d',$v['action_id'])->find();
					$action_log[$k]['type'] = $name;
					$action_log[$k]['type1'] = '<span style="color:#000">用户类型&nbsp:&nbsp</span>'.$user_list['category_name'];
					$action_log[$k]['type2'] = '<span style="color:#000">岗位&nbsp:&nbsp</span>'.$user_list['role_name'].'&nbsp;&nbsp;'.$something;
					break;
				case 'product' :
					$product_list = $m_product ->where('product_id =%d',$v['action_id'])->find();
					$category_name = M('product_category')->where('category_id =%d',$product_list['category_id'])->getField('name');
					$fields_type = $m_fields->where('field = "category_id" and model = "product"')->getField('name');
					$fields_type2 = $m_fields->where('model = "product" and field = "standard"')->getField('name');
					$action_log[$k]['type'] = $name;
					$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$category_name;
					$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.$product_list['standard'].'&nbsp;&nbsp;'.$something;
					break;
				case 'sales' :
					$sales_list = $m_sales ->where('sales_id =%d',$v['action_id'])->find();
					if($sales_list['status'] == 97){$status ='未出库';}
					elseif($sales_list['status'] == 98){$status ='已出库';}
					elseif($sales_list['status'] == 99){$status ='未入库';}
					else{$status ='已入库';}
					$customer_name = $m_customer->where('customer_id = %d',$sales_list['customer_id'])->getField('name');
					$contract_id = $r_contract_sales->where('sales_id =%d',$v['action_id'])->getField('contract_id');
					$sales_number = $m_contract->where('contract_id =%d',$contract_id)->getField('number');
					$action_log[$k]['type'] = $sales_number;
					$action_log[$k]['type1'] = '<span style="color:#000">客户/供应商&nbsp:&nbsp</span>'.$customer_name;
					$action_log[$k]['type2'] = '<span style="color:#000">状态&nbsp:&nbsp</span>'.$status.'&nbsp;&nbsp;'.$something;
					break;
				case 'leads' :
					$leads_list = $m_leads ->where('leads_id =%d',$v['action_id'])->find();
					$fields_type = $m_fields->where('field = "source" and model = "leads"')->getField('name');
					$fields_type2 = $m_fields->where('model = "leads" and field = "nextstep_time"')->getField('name');
					$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v['action_id'].'">'.$name.'</a>';
					$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$leads_list['source'];
					$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.date("Y-m-d H:i", $leads_list['nextstep_time']).'&nbsp;&nbsp;'.$something;
					break;
				case 'event' :
					$event_info = $m_event ->where('event_id =%d',$v['action_id'])->find();
					$start_date = $event_info['start_date'] ? date("Y-m-d H:i", $event_info['start_date']):'';
					$end_date = $event_info['end_date'] ? date("Y-m-d H:i", $event_info['end_date']):'';
					$action_log[$k]['type'] = $event_info['subject'];
					$action_log[$k]['type1'] = '<span style="color:#000">开始时间&nbsp:&nbsp</span>'.$start_date;
					$action_log[$k]['type2'] = '<span style="color:#000">结束时间&nbsp:&nbsp</span>'.$end_date.'&nbsp;&nbsp;'.$something;
					break;
				case 'finance' :
					$type = substr($v['param_name'],2);
					$finance_info = M($type)->where($type.'_id = %d',$v['action_id'])->find();
					$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v[action_id].'">'.$finance_info['name'].'</a>';
					break;
				case 'examine' :
					$examine_info = M('Examine') ->where('examine_id =%d',$v['action_id'])->find();
					//审批类型
					switch ($examine_info['type']) {
						case '1': $type_name = '普通审批';break;
						case '2': $type_name = '请假审批';break;
						case '3': $type_name = '普通报销';break;
						case '4': $type_name = '差旅报销';break;
						case '5': $type_name = '出差申请';break;
						case '6': $type_name = '借款申请';break;
						
						default : $type_name = '普通审批';break;
					}
					$action_log[$k]['type'] = '<a href="./index.php?m=examine&a=view&id='.$v["action_id"].'">'.$name.'【'.$type_name.'】'.'</a>';
					break;
				case 'task' :
					$task_info = $m_task ->where('task_id =%d',$v['action_id'])->find();
					$action_log[$k]['type'] = $task_info['subject'];
					break;
			}
		}
		$this->actionLog = $action_log;
		$show = $Page->show();		
		$this->page = $show;
		
		//查询今日数据(首页简报)，默认为自己和下属的数据
		$briefing_role_ids = array();
		if(session('?admin')){
			$briefing_role_ids = getSubRoleId(true, 1);
		}else{
			$briefing_role_ids = getSubRoleId();
		}
		$create_time = array();
		//今日时间范围
		$start_time_day = strtotime(date('Y-m-d'));
		$end_time_day = strtotime(date('Y-m-d'))+86400;
		$create_time[0] = array('between',array($start_time_day,$end_time_day));
		//本周时间范围
		$now_date = date("Y-m-d"); //当前日期 
		$first=1; //$first =1 表示每周星期一为开始时间 0表示每周日为开始时间 
		$w = date("w", strtotime($now_date)); //获取当前周的第几天 周日是 0 周一 到周六是 1 -6 
		$d = $w ? $w - $first : 6; //如果是周日 -6天 
		$start_time_week = strtotime("".$now_date." -".$d." days"); //本周开始时间 
		$end_time_week = strtotime("".date("Y-m-d",$start_time_week)." +7 days"); //本周结束时间
		$create_time[1] = array('between',array($start_time_week,$end_time_week));
		//本月时间范围
		$start_time_month = strtotime(date('Y-m-01')); 
		$end_time_month = strtotime(date("Y")."-".date("m")."-".date("t"))+86400;
		$create_time[2] = array('between',array($start_time_month,$end_time_month));
		//本年时间范围
		$year = @date("Y",time());
		$year_next = $year+1;
		$start_time_year = strtotime("$year-01-01");
		$end_time_year = strtotime("$year_next-01-01");
		$create_time[3] = array('between',array($start_time_year,$end_time_year));

		$customer_count = array();
		$contacts_count = array();
		$business_count = array();
		$log_count = array();
		$mylog_count = array();
		foreach($create_time as $k=>$v){
			$customer_count[] = $m_customer->where(array('creator_role_id'=>array('in',$briefing_role_ids),'is_deleted'=>0,'create_time'=>$v))->count();
			$contacts_count[] = $m_contacts->where(array('creator_role_id'=>array('in',$briefing_role_ids),'is_deleted'=>0,'create_time'=>$v))->count();
			$business_count[] = $m_business->where(array('creator_role_id'=>array('in',$briefing_role_ids),'is_deleted'=>0,'create_time'=>$v))->count();
			$log_count[] = $m_log->where(array('role_id'=>array('in',$briefing_role_ids),'category_id'=>1,'create_date'=>$v))->count();//沟通日志
			$mylog_count[] = $m_log->where(array('role_id'=>array('in',$briefing_role_ids),'category_id'=>array('neq',1),'create_date'=>$v))->count();//工作日志
		}
		//是否填写日志
		$mylog_count_today = $m_log->where(array('role_id'=>session('role_id'),'category_id'=>array('neq',1),'create_date'=>$create_time[0]))->count();//工作日志
		$this->mylog_count_today = $mylog_count_today;

		//指标数据
		$blows_id = getPerByAction('finance','target'); //权限判断
		$m_receivables = M('Receivables');
		$m_receivingorder = M('Receivingorder');
		$sum_receivables_price = 0.00;
		$sum_price = 0.00;
		$sum_price_month = 0.00;
		$sum_price_week = 0.00;
		$sum_price_year = 0.00;
		$schedule = 0;
		if($blows_id){
			$owner_customer_ids = $m_customer->where(array('owner_role_id'=>session('role_id')))->getField('customer_id',true);
			// $owner_business_ids = $m_business->where(array('customer_id'=>array('in',$owner_customer_ids)))->getField('business_id',true);
			$owner_contract_ids = $m_contract->where(array('customer_id'=>array('in',$owner_customer_ids),'is_checked'=>1))->getField('contract_id',true);
			
			//应收款
			$receivables_ids = array();
			$receivables_list = $m_receivables->where(array('contract_id'=>array('in',$owner_contract_ids)))->field('receivables_id,price')->select();
			foreach($receivables_list as $k=>$v){
				$sum_receivables_price += $v['price']; //应收款总额
				$receivables_ids[] = $v['receivables_id'];
			}
			//总回款
			$sum_price = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1))->sum('money');
			//收款进度
			if($sum_receivables_price == 0 || $sum_receivables_price == 0.00 || $sum_price > $sum_receivables_price){
				$schedule = 100;
			}else{
				$schedule = round(($sum_price/$sum_receivables_price),4)*100;
			}
			
			// //本月回款
			// $sum_price_month = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1,'pay_time'=>$create_time[2]))->sum('money');
			// //本周回款
			// $sum_price_week = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1,'pay_time'=>$create_time[1]))->sum('money');
			// //本年回款
			// $sum_price_year = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1,'pay_time'=>$create_time[3]))->sum('money');

			//本月回款
            $sum_price_month = $m_receivingorder->where(array('owner_role_id'=>session('role_id'), 'status' => 1, 'pay_time' => $create_time[2]))->sum('money');
            //本周回款
            $sum_price_week = $m_receivingorder->where(array('owner_role_id'=>session('role_id'), 'status' => 1, 'pay_time' => $create_time[1]))->sum('money');

            //本年回款
            $sum_price_year = $m_receivingorder->where(array('owner_role_id'=>session('role_id'), 'status' => 1, 'pay_time' => $create_time[3]))->sum('money');

			$sum_price = !empty($sum_price) ? $sum_price : '0.00';
			$sum_price_month = !empty($sum_price_month) ? $sum_price_month : '0.00';
			$sum_price_week = !empty($sum_price_week) ? $sum_price_week : '0.00';
			$sum_price_year = !empty($sum_price_year) ? $sum_price_year : '0.00';
			$schedule = !empty($schedule) ? $schedule : '0.00';
		}
		$anly_count = array('customer_count'=>$customer_count,'contacts_count'=>$contacts_count,'business_count'=>$business_count,'log_count'=>$log_count,'mylog_count'=>$mylog_count,'sum_price'=>$sum_price,'sum_price_month'=>$sum_price_month,'sum_price_week'=>$sum_price_week,'sum_price_year'=>$sum_price_year,'schedule'=>$schedule);
		$this->anly_count = $anly_count;
		
		$this->alert = parseAlert();
		$this->display();
	}

	//签到地图加载
	public function getTransLocation(){
		
		$x = trim($_GET['longtitude']);
		$y = trim($_GET['latitude']);
		$url = "http://api.map.baidu.com/geoconv/v1/?coords=$x,$y&from=3&to=5&ak=Z0Fo0ib1GUgWlylCWeLvQh2U";
		$res = json_decode(file_get_contents($url));

		if($res->status == 0){
			$data['x'] = $res->result[0]->x;
			$data['y'] = $res->result[0]->y;
			$this->ajaxReturn($data, 'success', 1);
		}
		
		
	}
	
	//动态数据加载
	public function dynamic_data(){
		if($this->isAjax()){

			// 如果是查询跟进记录 dragon 6-15
            if ($_REQUEST['by'] == 'follows')
            {
                // 分页
                $p = $_REQUEST['p']?:1;
                $_page_start = ($p - 1) * 10;
                $_page_end = 10;

                // 获取当前用户下属岗位
                if(!session('?admin')){
                    $where['owner_role_id'] = array('in', getSubRoleId());
                }
                $leads_ids = M('Leads')->where($where)->getField('leads_id', true);
                // 获取跟进记录ID列表
                $log_ids = M('rLeadsLog')->where('leads_id in (%s)', implode(',', $leads_ids))->getField('log_id', true);
                // 获取跟进记录
                $log = M('log')->where('log_id in (%s)', implode(',', $log_ids))->order('create_date desc')->limit($_page_start,$_page_end)->select();
                // 获取其他信息
                $m_user = M('User');
                foreach ($log as $key=>$value) {
                    // 跟进老师姓名
                    $log[$key]['creator'] = $m_user->where('role_id = %d', $value['role_id'])->find();
                    $log[$key]['creator']['user_name'] = $log[$key]['creator']['full_name'];
                    $leads_id = M('rLeadsLog')->field('leads_id')->where('log_id='.$log[$key]['log_id'])->find()['leads_id'];
                    $log[$key]['leads_id'] = $leads_id;
                    $log[$key]['module_name'] = 'LeadsLog';
                    $log[$key]['create_time'] = date('Y-m-d H:i:s',$log[$key]['create_date']);
                    $log[$key]['type'] = $log[$key]['content'];
                    $leads_name = M('Leads')->field('contacts_name')->where('leads_id='.$leads_id)->find()['contacts_name'];

                    $jump = U('leads/view','id='.$leads_id);
                    $end_leads_name = "<a href=\"".$jump."\">".$leads_name."</a>";

                    $log[$key]['dynamic'] = '跟进了线索 - '.$end_leads_name;
                }

                if($_REQUEST['date_type'] == 1){
                    $this->actionLog = $log;
                    $this->display();
                }else{
                    if($log){
                        $this->ajaxReturn($log,'success',1);
                    }else{
                        $this->ajaxReturn('没有更多数据啦！','error',0);
                    }
                }

                return ;// 防止后续代码继续执行

            }

			//动态信息
			$m_action_log = M('actionLog');
			$m_comment = M('comment');
			$m_customer = M('customer');
			$m_leads = M('leads');
			$m_business = M('business');
			$m_sales = M('sales');
			$m_contacts = M('contacts');
			$m_contract = M('contract');
			$m_product = M('product');
			$m_log = M('Log');
			$m_fields = M('fields');
			$m_r_file_log = M('RFileLog');
			$m_file = M('File');
			$m_event = M('Event');
			$m_task = M('Task');
			$m_log_talk = M('LogTalk');//日志评论回复表
			$where = array();	//查询条件
			$where['action_delete'] = 0;
			$opeartion = 'view';	//默认都跳转到view界面
			$p = isset($_REQUEST['p']) ? intval($_REQUEST['p']) : 1 ;
			$this->p = $p;
			$by = isset($_REQUEST['by']) ? trim($_REQUEST['by']) : 'crm';
			//module=log时，module_id为log表的log_id; module为dynamic时，module_id为action_log表的log_id
			switch ($by) {
				case 'customer' : 
					$where['module_name'] =  array('eq', 'customer');
					break;
				case 'log' : 
					$where['module_name'] =  array('eq', 'log');
					break;
				case 'sign' :
					$where['module_name'] =  array('eq','sign');
					break;
				case 'examine' :
					$where['module_name'] =  array('eq','examine');
					break;
				case 'task' :
					$where['module_name'] = array('eq','task');
					break;
				case 'business' :
					$where['module_name'] = array('eq','business');
					break;
				case 'crm' : 
					$where['module_name'] =  array('not in','log,examine,sign');
					break;
			}
			
			if($_GET['department_id']){
				$department_id = intval($_GET['department_id']);
				//选中部门下的所有员工
				$subPositionIdArr = M('position')->where('department_id = %d',$department_id)->order('position_id asc')->getField('position_id',true);
				$subRoleIdArr = M('role')->where(array('position_id'=>array('in', $subPositionIdArr)))->getField('role_id',true);	//部门下role_id
				if(!session('admin')){
					//条件为选中部门下,我的下属员工的role_id
					$mySubRoleIdArr = getSubRoleId();	//我的下属role_id
					$where['role_id'] = array('in', array_intersect($subRoleIdArr,$mySubRoleIdArr));
				}else{
					$where['role_id'] = array('in', $subRoleIdArr);
				}
			}else{
				//条件为选中部门下,我的下属员工的role_id
				if(!session('?admin')){
					$where['role_id'] = array('in', getSubRoleId());
				}
			}

			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				if($field == 'role'){
					if(!empty($search)){
						$same_role_id_array = M('user')->where('name like "%s"', "%$search%")->getField('role_id',true);
						$role_id_array = getSubRoleId(true, 1);		//下属role_id
						$role_idArr = array_intersect($same_role_id_array,$role_id_array);	//交集
						$where['role_id'] = array('in',$role_idArr);
					}
				}elseif($field == 'content'){
					$where['content'] = array('like',"%$search%");
				}
				$params = array('field='.trim($_REQUEST['field']), 'search='.$search);
			}
			$action_log = $m_action_log->where($where)->order('create_time desc')->page($p.',10')->select();
			$count = $m_action_log->where($where)->count();
			import("@.ORG.Page");
			$Page = new Page($count,5);
			if (!empty($_GET['by'])) {
				$params[] = "by=" . trim($_GET['by']);
			}
			$m_sign_img = M('signImg');
			$r_contract_sales = M('r_contract_sales');
			foreach($action_log as $k=>$v){
				if($v['module_name'] == 'finance'){
					$module_name = substr($v['param_name'],2);
				}else{
					$module_name = $v['module_name'];
				}
				$param_name = $v['param_name'];
				$action_log[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
				$action_log[$k]['sign_img'] = $m_sign_img ->where('sign_id = "%d"',$v['action_id'])->select();
				$m_module_name = M($module_name);
				$pk_id = $m_module_name->getPk();
				$object_module = $m_module_name->where("$pk_id = %d", $v['action_id'])->find();
				
				if($v['module_name'] == 'examine'){
					$name = $object_module['content'];
				}elseif($v['module_name'] == 'contract'){
					$name = $object_module['number'];
				}elseif($v['module_name'] == 'sales'){
					$contract_id = $r_contract_sales->where('sales_id =%d',$v['action_id'])->getField('contract_id');
					$name = $m_contract->where('contract_id =%d',$contract_id)->getField('number');
				}elseif($v['module_name'] == 'purchase'){
					$name = $object_module['sn_code'];
				}else{
					$name = $object_module['name'];
				}
				if(empty($name)){
					$name = $object_module['subject'];
				}
				//如果是日志，则追加日志内容
				if($v['action_name'] == 'mylog_add'){
					$apContent = $object_module['content'];
					$appHtml = strip_tags($apContent);
					//如果是日志，则跳转到mylog_view
					$action_name = 'mylog_view';
				}else{
					$appHtml = '';
					$action_name = strtolower($v['action_name']);
				}
				$action_log[$k]['creator'] = getUserByRoleId($v['role_id']);

				$username = $action_log[$k]['creator']['user_name'];
				$operation = L(strtolower($v['action_name'])).'了';
				if($v['module_name'] == "quote"){
					$module = '报价单';
				}elseif($v['module_name'] == 'sales'){
					$module = '销售合同';
				}elseif($v['module_name'] == 'purchase' && $v['action_name'] == 'outof'){
					$module = '采购退货';
				}else{
					$module = L(strtolower($v['module_name']));
				}
				$action_log[$k]['dynamic'] = $operation.$module;
				
				$action_log[$k]['address'] = $object_module['address'].' '.$object_module['title'];
				$action_log[$k]['log'] = $object_module['log'];
				$action_log[$k]['x'] = $object_module['x'];
				$action_log[$k]['y'] = $object_module['y'];
				//外勤签到相关客户
				$sign_customer_id = '';
				$sign_customer_id = M('Sign')->where(array('sign_id'=>$v['action_id']))->getField('customer_id');
				$customer_info = M('Customer')->where(array('customer_id'=>$sign_customer_id))->field('name,customer_id')->find();
				$action_log[$k]['customer_info'] = $customer_info ? $customer_info : array();
				
				//获取阶段
				switch ($v['module_name']) {
					case 'log' : 
						$log_info = $m_log ->where('log_id =%d',$v['action_id'])->find();
						$action_log[$k]['cut_content'] = '<a href="./index.php?m='.$v['module_name'].'&a=mylog_view&'.$param_name.'&id='.$v['action_id'].'" title="查看详情" style="color:#676a6c;">'.cutString($log_info['content'],110).'</a>';
						$action_log[$k]['log_content'] = '<a href="./index.php?m='.$v['module_name'].'&a=mylog_view&'.$param_name.'&id='.$v['action_id'].'" title="查看详情" style="color:#676a6c;">'.$log_info['content'].'</a>';;
						$action_log[$k]['content_open'] = 0;
						//是否需展开全文
						if(strlen($log_info['content']) > 120){
							$action_log[$k]['content_open'] = 1;
						}
						//附件
						$file_ids = array();
						$file_ids = $m_r_file_log->where('log_id = %d',$v['action_id'])->getField('file_id',true);
						$log_files = $m_file->where(array('file_id'=>array('in',$file_ids)))->select();
						if(!empty($file_ids)){
							foreach ($log_files as $key=>$value) {
								$log_files[$key]['size'] = ceil($value['size']/1024);
								/*判断文件格式 对应其图片*/
								$log_files[$key]['pic'] = show_picture($value['name']);
								$log_files[$key]['cut_name'] = cutString($value['name'],25);
							}
						}
						$action_log[$k]['log_files'] = $log_files;
						$action_log[$k]['file_count'] = count($file_ids);
						//日志评论
						$action_log[$k]['comment_count'] = $m_log_talk->group('g_mark')->where('log_id = %d',$v['action_id'])->count();
						break;
					case 'customer' : 
						$customer_list = $m_customer ->where('customer_id =%d',$v['action_id'])->find();
						$fields_type = $m_fields->where('model = "customer" and field = "industry"')->getField('name');
						$fields_type2 = $m_fields->where('model = "customer" and field = "origin"')->getField('name');
						$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v[action_id].'">'.$name.'</a>';
						$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$customer_list['industry'];
						$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.$customer_list['origin'].'&nbsp;&nbsp;'.$something;
						break;
					case 'contract' :
						$contract_list = $m_contract ->where('contract_id =%d',$v['action_id'])->find();
						if($contract_list['type'] == 1){
							$customer_name = M('customer')->where('customer_id = %d',$contract_list['customer_id'])->getField('name');
						}elseif($contract_list['type'] == 2){
							$customer_name = M('supplier')->where('supplier_id = %d',$info['supplier_id'])->getField('name');
						}
						$action_log[$k]['type'] = $contract_list['number'];
						$action_log[$k]['type1'] = '<span style="color:#000">客户/供应商&nbsp:&nbsp</span>'.$customer_name;
						$action_log[$k]['type2'] = '<span style="color:#000">状态&nbsp:&nbsp</span>'.$contract_list['status'].'&nbsp;&nbsp;'.$something;
						break;
					case 'business' :
						$business_info = $m_business ->where('business_id =%d',$v['action_id'])->find();
						$status_name = M('business_status')->where('status_id =%d',$business_info['status_id'])->getField('name');
						$fields_type = $m_fields->where('model = "business" and field = "status_id"')->getField('name');
						$fields_type2 = $m_fields->where('model = "business" and field = "nextstep_time"')->getField('name');
						$action_log[$k]['type'] = '<a href="./index.php?m=customer'.'&a=view&id='.$business_info['customer_id'].'&bid='.$v[action_id].'">'.$name.'</a>';
						$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$status_name;
						$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.date("Y-m-d H:i", $business_info['nextstep_time']).'&nbsp;&nbsp;'.$something;
						break;
					case 'user' :
						$user_list = D('UserView') ->where('user.user_id =%d',$v['action_id'])->find();
						$action_log[$k]['type'] = $name;
						$action_log[$k]['type1'] = '<span style="color:#000">用户类型&nbsp:&nbsp</span>'.$user_list['category_name'];
						$action_log[$k]['type2'] = '<span style="color:#000">岗位&nbsp:&nbsp</span>'.$user_list['role_name'].'&nbsp;&nbsp;'.$something;
						break;
					case 'product' :
						$product_list = $m_product ->where('product_id =%d',$v['action_id'])->find();
						$category_name = M('product_category')->where('category_id =%d',$product_list['category_id'])->getField('name');
						$fields_type = $m_fields->where('field = "category_id" and model = "product"')->getField('name');
						$fields_type2 = $m_fields->where('model = "product" and field = "standard"')->getField('name');
						$action_log[$k]['type'] = $name;
						$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$category_name;
						$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.$product_list['standard'].'&nbsp;&nbsp;'.$something;
						break;
					case 'sales' :
						$sales_list = $m_sales ->where('sales_id =%d',$v['action_id'])->find();
						if($sales_list['status'] == 97){$status ='未出库';}
						elseif($sales_list['status'] == 98){$status ='已出库';}
						elseif($sales_list['status'] == 99){$status ='未入库';}
						else{$status ='已入库';}
						$customer_name = M('customer')->where('customer_id = %d',$sales_list['customer_id'])->getField('name');
						$contract_id = $r_contract_sales->where('sales_id =%d',$v['action_id'])->getField('contract_id');
						$sales_number = $m_contract->where('contract_id =%d',$contract_id)->getField('number');
						$action_log[$k]['type'] = $sales_number;
						$action_log[$k]['type1'] = '<span style="color:#000">客户/供应商&nbsp:&nbsp</span>'.$customer_name;
						$action_log[$k]['type2'] = '<span style="color:#000">状态&nbsp:&nbsp</span>'.$status.'&nbsp;&nbsp;'.$something;
						break;
					case 'leads' :
						$leads_list = $m_leads ->where('leads_id =%d',$v['action_id'])->find();
						$fields_type = $m_fields->where('field = "source" and model = "leads"')->getField('name');
						$fields_type2 = $m_fields->where('model = "leads" and field = "nextstep_time"')->getField('name');
						$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v['action_id'].'">'.$name.'</a>';
						$action_log[$k]['type1'] = '<span style="color:#000">'.$fields_type.'&nbsp:&nbsp</span>'.$leads_list['source'];
						$action_log[$k]['type2'] = '<span style="color:#000">'.$fields_type2.'&nbsp:&nbsp</span>'.date("Y-m-d H:i", $leads_list['nextstep_time']).'&nbsp;&nbsp;'.$something;
						break;
					case 'event' :
						$event_info = $m_event ->where('event_id =%d',$v['action_id'])->find();
						$start_date = $event_info['start_date'] ? date("Y-m-d H:i", $event_info['start_date']):'';
						$end_date = $event_info['end_date'] ? date("Y-m-d H:i", $event_info['end_date']):'';
						$action_log[$k]['type'] = $event_info['subject'];
						$action_log[$k]['type1'] = '<span style="color:#000">开始时间&nbsp:&nbsp</span>'.$start_date;
						$action_log[$k]['type2'] = '<span style="color:#000">结束时间&nbsp:&nbsp</span>'.$end_date.'&nbsp;&nbsp;'.$something;
						break;
					case 'finance' :
						$type = substr($v['param_name'],2);
						$finance_info = M($type)->where($type.'_id = %d',$v['action_id'])->find();
						$action_log[$k]['type'] = '<a href="./index.php?m='.$v['module_name'].'&a=view&'.$param_name.'&id='.$v[action_id].'">'.$finance_info['name'].'</a>';
						break;
					case 'examine' :
						$examine_info = M('Examine') ->where('examine_id =%d',$v['action_id'])->find();
						//审批类型
						switch ($examine_info['type']) {
							case '1': $type_name = '普通审批';break;
							case '2': $type_name = '请假审批';break;
							case '3': $type_name = '普通报销';break;
							case '4': $type_name = '差旅报销';break;
							case '5': $type_name = '出差申请';break;
							case '6': $type_name = '借款申请';break;
							
							default : $type_name = '普通审批';break;
						}
						$action_log[$k]['type'] = '<a href="./index.php?m=examine&a=view&id='.$v["action_id"].'">'.$name.'【'.$type_name.'】'.'</a>';
					break;
					case 'task' :
						$task_info = $m_task ->where('task_id =%d',$v['action_id'])->find();
						$action_log[$k]['type'] = $task_info['subject'];
					break;
				}
			}
			if($_REQUEST['date_type'] == 1){
				$this->actionLog = $action_log;
				$this->display();
			}else{
				if($action_log){
					$this->ajaxReturn($action_log,'success',1);
				}else{
					$this->ajaxReturn('没有更多数据啦！','error',0);
				}
			}
		}
	}
	
	public function widget_edit(){
		$user = M('User');
		$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widgets = unserialize($dashboard);
		if(isset($_GET['id']) && $_GET['id']!=''){
			/**
			 * 所有的小部件
			 * Function : 判断模块下的某个操作是否有权限
			 * @action  : 默认使用index操作来判断权限
			 */
			$widget_module = array(
				array('module'=>'customer','action'=>'index','tag'=>'Salesfunnel','name'=>'销售漏斗'),
				array('module'=>'customer','action'=>'index','tag'=>'Customerorigin','name'=>'客户信息来源'),
				array('module'=>'log','action'=>'index','tag'=>'Notepad','name'=>'便笺'),
				array('module'=>'customer','action'=>'index','tag'=>'Productmonthlysales','name'=>'合同金额统计'),
				array('module'=>'customer','action'=>'index','tag'=>'Productmonthlyamount','name'=>'月最高销量'),
				array('module'=>'finance','action'=>'index_receivables','tag'=>'Receivemonthly','name'=>'回款金额统计'),
				array('module'=>'finance','action'=>'index_receivables','tag'=>'Receiveyearcomparison','name'=>'财务年度对比')
			);

			//如果没有权限，从数组中去除
			foreach($widget_module as $k=>$v){
				if($v['module'] == 'log') continue;//默认便笺所有人都有权限
				if(!in_array(session('role_id'), getPerByAction($v['module'], $v['action']))){
					unset($widget_module[$k]);
				}
			}
			//商机状态组
			$this->type_list = M('BusinessType')->select();
			$this->widget_module = $widget_module;
			$this->edit_demo = $widgets['dashboard'][$_GET['id']];
			$this->display();
		} elseif(isset($_POST['widget_id']) && $_POST['widget_id']!='') {
			$title = $_POST['title']!='' && isset($_POST['title']) ? $_POST['title'] : '未定义组件';	
			$widgets['dashboard'][$_POST['widget_id']]['title'] = $title;
			$widgets['dashboard'][$_POST['widget_id']]['widget'] = $_POST['widget'];
			$widgets['dashboard'][$_POST['widget_id']]['level'] = $_POST['level'] == '' ? 0 : $_POST['level'];
			$widgets['dashboard'][$_POST['widget_id']]['status_type_id'] = $_POST['status_type_id'] == '' ? 1 : intval($_POST['status_type_id']);
			
			if($user->where('user_id = %d', session('user_id'))->setField('dashboard', serialize($widgets))){
				alert('success', L('MODIFY_THE_COMPONENT_INFORMATION_SUCCESSFULLY',array($title)), U('index/index'));
			}else{
				alert('error', L('MODIFY_THE_COMPONENT_INFORMATION_NO_CHANGE',array($title)), U('index/index'));
			}
		}
	}
	
	public function widget_delete(){
		if(isset($_GET['id']) && $_GET['id']!=''){
			$user = M('User');
			$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
			$widget = unserialize($dashboard);

			unset($widget['dashboard'][$_GET['id']]);
			unset($widget['sort'][array_search($_GET['id'], $widget['sort'])]);

			if($user->where('user_id = %d', session('user_id'))->setField('dashboard', serialize($widget))){
				alert('success', L('THE_COMPONENT_WAS_REMOVED_SUCCESSFULLY'), U('index/index'));
			}else{
				alert('error', L('THE_COMPONENT_WAS_REMOVED_FAILURE'),$_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	//serialize  unserialize
	public function widget_add(){
		if($this->isPost()){
			if($_POST['widget']){
				$user = M('User');
				$title = $_POST['title']!='' && isset($_POST['title']) ? $_POST['title'] : L('UNNAMED_COMPONENT');			
				$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
				$widget = unserialize($dashboard);
				if(!is_array($widget)){
					$widget = array();
				}
				
				$max_id = 0;
				foreach($widget['dashboard'] as $v){
					if($v['id'] > $max_id) $max_id = $v['id'];
				}
				
				$widget['dashboard'][$max_id+1] = array('widget'=>$_POST['widget'], 'level'=>$_POST['level'], 'title'=>$title, 'id'=>$max_id+1, 'status_type_id'=>intval($_POST['status_type_id']));
				
				$widget['sort'][] = $max_id+1;

				$newdashboard['dashboard'] = serialize($widget);
				if($user->where('user_id = %d', session('user_id'))->save($newdashboard)){
					alert('success', L('ADD_COMPONENTS_TO_SUCCESS'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('ADD_THE_COMPONENT_FAILS_PLEASE_FILL_IN_THE_COMPONENT_NAME'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			/**
			 * 所有的小部件
			 * Function : 判断模块下的某个操作是否有权限
			 * @action  : 默认使用index操作来判断权限
			 */
			 //获取客户来源 字段标识
			$m_fields = M('Fields');
			$origin_name = $m_fields->where(array('model'=>'customer','field'=>'origin'))->getField('name');
			$widget_module = array(
				array('module'=>'customer','action'=>'index','tag'=>'Salesfunnel','name'=>'销售漏斗'),
				array('module'=>'customer','action'=>'index','tag'=>'Customerorigin','name'=>$origin_name),
				array('module'=>'log','action'=>'index','tag'=>'Notepad','name'=>'便笺'),
				array('module'=>'customer','action'=>'index','tag'=>'Productmonthlysales','name'=>'合同金额统计'),
				array('module'=>'customer','action'=>'index','tag'=>'Productmonthlyamount','name'=>'月最高销量'),
				array('module'=>'finance','action'=>'index_receivables','tag'=>'Receivemonthly','name'=>'回款金额统计'),
				array('module'=>'finance','action'=>'index_receivables','tag'=>'Receiveyearcomparison','name'=>'财务年度对比')
			);

			//如果没有权限，从数组中去除
			foreach($widget_module as $k=>$v){
				if($v['module'] == 'log') continue;//默认便笺所有人都有权限
				if(!in_array(session('role_id'), getPerByAction($v['module'], $v['action']))){
					unset($widget_module[$k]);
				}
			}
			//商机状态组
			$this->type_list = M('BusinessType')->select();
			$this->widget_module = $widget_module;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	//首页图表排序
	public function sortCharts(){
		$chart_arr = explode(',',$_POST['chart_arr']);	//用户拖动后的顺序
		$m_user = M('user');
		$dashboardSer = $m_user->where('role_id = %d', session('role_id'))->getField('dashboard');	//拖动前数据库的顺序
		$dashboard = unserialize($dashboardSer);
		$dashboard['sort'] = $chart_arr;
		$m_user->where('role_id = %d', session('role_id'))->setField('dashboard',serialize($dashboard));
	}

	/**
	 * 首页升级内容提示
	 * @param 
	 * @author 
	 * @return 
	 */
	public function updateData() {
		if ($this->isAjax()) {
			$update_show = 1;
			$this->ajaxReturn('','success',$update_show);
		}
	}
         public function getdatass() {
        $m_position = M('position');
        $positions = $m_position->where(['position_id' => $_SESSION['position_id']])->find();
        $data1 = $m_position->select();
        $isHaveSub = $m_position->where(['parent_id' => $positions['position_id']])->find();

        $url = getCheckUrlByAction(MODULE_NAME, ACTION_NAME);
        $per_type = M('Permission')->where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');

        if ($per_type == 2 || session('?admin')) {
            $rList = M('role')->select();
            $role_id_array = array_column($rList, 'role_id');
            $role_id_array = array_column(M('user')->where(['role_id' => ['in', $role_id_array], 'type' => 1])->select(), 'role_id');
        } else if ($isHaveSub) {
            $res = $this->getAllSub($data1, $positions['position_id']);
            $p_id = array_column($res, 'position_id');
            $role_id_array = M('role')->where(['position_id' => ['in', $p_id]])->select();
            $role_id_array = array_column($role_id_array, 'role_id');
            $role_id_array = array_column(M('user')->where(['role_id' => ['in', $role_id_array], 'type' => 1])->select(), 'role_id');
        } else {
            $role_id_array[] = M('role')->where(['role_id' => $_SESSION['role_id']])->find()['role_id'];
        }

        if (intval($_GET['arole']) && in_array($_GET['arole'], $role_id_array)) {
            $role_id_array = [];
            $role_id_array[] = $_GET['arole'];
        }
        //时间段搜索
        if (!empty($_GET['stime']) && !empty($_GET['etime'])) {
            $start_time = strtotime(I('stime'));
            $end_time = strtotime(I('etime'));
            $this->assign('stime', date('Y-m-d', $start_time));
            $this->assign('etime', date('Y-m-d', $end_time));
        } else {
            $start_time = strtotime(date('Y-m-d', 1526745600));
            $end_time = strtotime(date('Y-m-d', time()));
        }

        $own_count_total = 0;
        $success_count_total = 0; //转换
        $deal_count_total = 0; //跟进
        $own_response_time_total = 0;
        $success_rate_total = 0;
        if (!empty($_REQUEST['aarea']) && $_REQUEST['aarea'] != 'all') {
            $where['crm_city'] = urldecode($_REQUEST['aarea']);
            $sucwheres['crm_city'] = urldecode($_REQUEST['aarea']);
            $deal_where['crm_city'] = urldecode($_REQUEST['aarea']);
        }

        ## 角色
        $m_leads = M('leads');
        foreach ($role_id_array as $v) {
            $user_info = array();
            $user_info = getUserByRoleId($v);
            //过滤已停用用户
            if ($user_info['status'] == 1) {
                //负责的线索
//                $where['is_deleted'] = 0;
                $where['owner_role_id'] = $v;
//                $where['is_transformed'] = ['neq', 1];
                $where['create_time'][] = ['gt', $start_time];
                $where['create_time'][] = ['lt', $end_time];

//                var_dump($where);echo '<br>';
//                $sucwheres['owner_role_id'] = array('neq', 0);
//                $where['have_time'] = ['egt',$outdate];
                $own_list = $m_leads->where($where)->field('first_time,have_time,leads_id')->select();
//                var_dump($where);
//                $this->ajaxReturn($where);
                $own_count = count($own_list);
                //已转换客户
                $sucwheres['is_deleted'] = 0;
                $sucwheres['is_transformed'] = 1;
                $sucwheres['owner_role_id'] = $v;
                $sucwheres['create_time'][] = ['gt', $start_time];
                $sucwheres['create_time'][] = ['lt', $end_time];
                $success_count = $m_leads->where($sucwheres)->count();
                //转化率
                $success_rate = $success_count ? round($success_count / $own_count, 4) * 100 : 0;
                //已跟进
                $deal_where = array();
                $deal_where['is_deleted'] = 0;
                $deal_where['owner_role_id'] = $v;
//                $deal_where['is_transformed'] = array('eq', 1);
                $deal_where['create_time'][] = ['gt', $start_time];
                $deal_where['create_time'][] = ['lt', $end_time];
                $rllog = M('rLeadsLog');
                $deal_count = '';
                foreach ($own_list as $k1 => $v1) {
                    if ($rllog->where(['leads_id' => $v1['leads_id']])->find()) {
                        ++$deal_count;
                    }
                }
                if (!$deal_count) {
                    $deal_count = 0;
                }
//                echo '<pre>';
//                var_dump($deal_where);
//                $deal_where['have_time'] = array('egt', $outdate);
//                $deal_where['create_time'] = $create_time;
//                $deal_count = $m_leads->where('update_time > create_time')->where($where)->count();
                $reportList[] = array("user" => $user_info, "own_count" => $own_count, "deal_count" => $deal_count, "success_count" => $success_count, "own_response_time" => $own_response_time, "success_rate" => $success_rate);
                $own_count_total += $own_count;
                $success_count_total += $success_count;
                $deal_count_total += $deal_count;
//                $own_response_time_total += $response_time_total;
            }
        }
//        echo '<pre>';
//        var_dump($reportList);die;
        ###排序
        if ($_REQUEST['classes'] == 'leadsnum') {
            if ($_REQUEST['leadorder'] == 2) {
                $r = $this->wpjam_array_multisort($reportList, 'own_count', SORT_DESC);
            } else if ($_REQUEST['leadorder'] == 3) {
                $r = $this->wpjam_array_multisort($reportList, 'own_count', SORT_ASC);
            } else {
                $r = $reportList;
            }
        } else if ($_REQUEST['classes'] == 'leadsdeal') {
            if ($_REQUEST['leadorder'] == 2) {
                $r = $this->wpjam_array_multisort($reportList, 'deal_count', SORT_DESC);
            } else if ($_REQUEST['leadorder'] == 3) {
                $r = $this->wpjam_array_multisort($reportList, 'deal_count', SORT_ASC);
            } else {
                $r = $reportList;
            }
        } else if ($_REQUEST['classes'] == 'leadstrans') {
            if ($_REQUEST['leadorder'] == 2) {
                $r = $this->wpjam_array_multisort($reportList, 'success_count', SORT_DESC);
            } else if ($_REQUEST['leadorder'] == 3) {
                $r = $this->wpjam_array_multisort($reportList, 'success_count', SORT_ASC);
            } else {
                $r = $reportList;
            }
        } else if ($_REQUEST['classes'] == 'leadsrate') {
            if ($_REQUEST['leadorder'] == 2) {
                $r = $this->wpjam_array_multisort($reportList, 'success_rate', SORT_DESC);
            } else if ($_REQUEST['leadorder'] == 3) {
                $r = $this->wpjam_array_multisort($reportList, 'success_rate', SORT_ASC);
            } else {
                $r = $reportList;
            }
        }


        $this->ajaxReturn($r);
        $tsuccess_rate = $own_count_total ? round($success_count_total / $own_count_total, 4) * 100 : 0;
        $total_report['own_count'] = $own_count_total;
        $total_report['deal_count'] = $deal_count_total;
        $total_report['own_response_time_total'] = $own_response_time_total;
        $total_report['success_count'] = $success_count_total;
        $total_report['tsuccess_rate'] = $tsuccess_rate;
    }
	
}