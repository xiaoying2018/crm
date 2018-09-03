<?PHP 
/**
*外勤签到模块
*
**/
class SignAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('view')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	
	/**
	*外勤签到列表页（默认页面）
	*
	**/
	public function index(){
		$m_sign = M('Sign');
		$below_ids = getPerByAction('sign','index');
		$order = 'create_time desc';
		$where = array();

		//时间段搜索
		if($_GET['sign_time']){
			$sign_time = explode(' - ',trim($_GET['sign_time']));
			if($sign_time[0]){
				$start_time = strtotime($sign_time[0]);
			}
			$end_time = $sign_time[1] ?  strtotime(date('Y-m-d 23:59:59',strtotime($sign_time[1]))) : strtotime(date('Y-m-d 23:59:59',time()));
		}else{
			$start_time = strtotime(date('Y-m-01 00:00:00'));
			$end_time = strtotime(date('Y-m-d H:i:s'));
		}
		$this->sign_time = $_GET['sign_time'] ? trim($_GET['sign_time']) : date('Y-m-01').' - '.date('Y-m-d');
		$this->start_date = date('Y-m-d',$start_time);
		$this->end_date = date('Y-m-d',$end_time);

		$where = array();
		$where['create_time'] = array('between',array($start_time,$end_time));

		$role_id = $_REQUEST['role_id'] ? intval($_REQUEST['role_id']) : '';
		if($role_id){
			if(in_array($role_id,$below_ids)){
				$where['role_id'] = $role_id;
			}else{
				$where['role_id'] = '-1';
			}
		}else{
			$where['role_id'] = array('in',$below_ids);
		}
		
		$p = intval($_GET['p'])?intval($_GET['p']):1;
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}

		// $count = $m_sign->where($where)->count();
		// $p_num = ceil($count/$listrows);
		// if($p_num<$p){
		// 	$p = $p_num;
		// }
		// $sign_list = $m_sign->where($where)->order($order)->page($p.','.$listrows)->select();
		$sign_list = $m_sign->where($where)->order($order)->select();
		$d_role = D('RoleView');
		$m_customer = M('Customer');
		foreach($sign_list as $k=>$v){
			$sign_list[$k]['user_info'] = $d_role->where('role.role_id = %d',$v['role_id'])->find();
			$sign_list[$k]['customer_info'] = $m_customer->where('customer_id = %d',$v['customer_id'])->field('name')->find();
		}
		$this->my_img = session('user_img');
		// import("@.ORG.Page");
		// $Page = new Page($count,$listrows);
		// $this->listrows = $listrows;
		// $this->assign('count',$count);
		$Page->parameter = implode('&', $params);
		// $this->assign('page', $Page->show());
		$this->sign_list = $sign_list;
		$this->role_id = $_GET['role_id'] ? intval($_GET['role_id']) : '';
		$this->role_list = D('RoleView')->where('role.role_id in (%s)', implode(',', $below_ids))->select();

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

		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*外勤签到详情页
	*
	**/
	public function view(){
		$m_sign = M('Sign');
		$m_sign_img = M('SignImg');
		$d_role = D('RoleView');
		$m_customer = M('Customer');
		$below_ids = getPerByAction('sign','index');
		$where = array();
		$sign_id = $_REQUEST['sign_id'] ? intval($_REQUEST['sign_id']) : '';
		if($sign_id){
			$where['sign_id'] = $sign_id;
			$role_id = $m_sign->where('sign_id = %d',$sign_id)->getField('role_id');
		}else{
			//时间段搜索
			if($_GET['sign_time']){
				$sign_time = explode(' - ',trim($_GET['sign_time']));
				if($sign_time[0]){
					$start_time = strtotime($sign_time[0]);
				}
				$end_time = $sign_time[1] ?  strtotime(date('Y-m-d 23:59:59',strtotime($sign_time[1]))) : strtotime(date('Y-m-d 23:59:59',time()));
			}elseif($_GET['start_date']){
				$start_time = strtotime(trim($_GET['start_date']));
				if($_GET['end_date']){
					$end_time = strtotime(trim($_GET['end_date']))+86399;
				}else{
					$end_time = strtotime(date('Y-m-d H:i:s'));
				}
			}else{
				$start_time = strtotime(date('Y-m-d'))-86400;
				$end_time = strtotime(date('Y-m-d H:i:s'));
			}
			
			$where['create_time'] = array('between',array($start_time,$end_time));
			$role_id = $_REQUEST['role_id'] ? intval($_REQUEST['role_id']) : '';
			if($role_id){
				$where['role_id'] = $role_id;
			}else{
				$where['role_id'] = array('in',$below_ids);
			}
		}
		//判断权限
		if($role_id && !in_array($role_id,$below_ids)){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}else{
			$sign_list = $m_sign->where($where)->select();
			$center_sign = array('0'=>'116.405467','1'=>'39.907761');
			$lnglats_array = '';
			$user_img = '';
			$sign_title = '';
			$sign_content = '';
			foreach($sign_list as $k=>$v){
				//经纬度百度转高德
				$xy_info = array();
				$xy_info = bd_decrypt($v['y'],$v['x']);

				if($k == 0){
					// $center_sign = array('0'=>$v['x'],'1'=>$v['y']);
					$center_sign = array('0'=>$xy_info['gg_lat'],'1'=>$xy_info['gg_lon']);
				}
				$user_info = array();
				$user_info = $d_role->where('role.role_id = %d',$v['role_id'])->find();
				$sign_list[$k]['user_info'] = $user_info;
				$sign_list[$k]['customer_info'] = $m_customer->where('customer_id = %d',$v['customer_id'])->field('name')->find();

				//获取坐标
				// $lnglats_array .= '['.$v['y'].','.$v['x'].'],';
				$lnglats_array .= '['.$xy_info['gg_lon'].','.$xy_info['gg_lat'].'],';
				//获取头像
				if($user_info['thumb_path']){
					$user_img .= '"'.$user_info['thumb_path'].'",';
				}else{
					$user_img .= '"'.'__PUBLIC__/img/avatar_default.png'.'",';
				}
				//获取姓名、职位
				$sign_title .= '"'.$user_info['user_name'].'</br>'."<span style='font-size:12px;color:#b5b5b5;font-weight:normal;'>".$user_info['department_name'].'-'.$user_info['role_name']."</span>".'",';
				$v_log = '';
				$search = array(" ","　","\n","\r","\t");
				$replace = array("","","","","");
				$v_log = str_replace($search, $replace, $v['log']);

				//签到图片
				$sign_img_list = array();
				$sign_img_list = $m_sign_img->where(array('sign_id'=>$v['sign_id']))->select();
				$sign_img = '';
				foreach($sign_img_list as $k=>$v){
					$sign_img .= "<a href=".$v['path']." target='_block' class='litebox'><img src=".$v['path']." style='width:60px;height:60px;margin-top: 15px;' /></a>";
				}
				//获取内容
				if($v['address']){
					$sign_content .= '"'."<div class='sign_content'>"."<span style='line-height:25px;margin-bottom:15px;'>".'地址：'.$v['address'].$v['title']."</span>".'</br>'."<span style='line-height:25px;margin-bottom:15px;color:#000;font-size:13px;'>".trim($v_log)."</span>".'</br>'.$sign_img.'</div>'.'",';
				}else{
					$sign_content .= '"'."<div class='sign_content'>"."<span style='line-height:25px;margin-bottom:15px;color:#000;font-size:13px;'>".trim($v_log)."</span>".'</br>'.$sign_img.'</div>'.'",';
				}
			}
			$this->center_sign = $center_sign;
			$this->lnglats_array = $lnglats_array;
			$this->user_img = $user_img;
			$this->sign_title = $sign_title;
			$this->sign_content = $sign_content;
			$this->sign_role_info = $sign_role_info;
			$this->sign_list = $sign_list;
			$this->display();
		}
	}
}