<?php
/**
*日志模块
*
**/
class KaoqinAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('record','set_map','index','indexdata')
		);
		B('Authenticate', $action);
	}

	/**
	 * 考勤统计
	 **/
	public function analytics(){
		$m_kaoqin = M('Kaoqin');
		$m_examine = M('Examine');
		$m_user = M('User');
		$m_kaoqin_config = M('KaoqinConfig');
		$config_info = $m_kaoqin_config->find();
		//权限范围
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME);
		$role_id_array = array();
		if(intval($_GET['role'])){
			$role_id_array = array(intval($_GET['role']));
		}else{
			if(intval($_GET['department'])){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			}
		}
		//过滤权限范围内的role_id
		if($role_id_array){
			//数组交集
			$idArray = array_intersect($role_id_array,$below_ids);
		}else{
			$idArray = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		}
		$p = $_GET['p'] ? intval($_GET['p']) : 1;
		import("@.ORG.Page");
		//分页功能
		$role_list = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->page($p.',15')->field('role_id,full_name,thumb_path')->order('user_id')->select();
		$count = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->count();
		$Page = new Page($count,15);
		$this->count = $count;
		$this->assign('count',$count);
		$Page->parameter = implode('&', $params);
		$this->assign('page', $Page->show());

		//时间段搜索
		$search_time_year = $_GET['search_year'] ? intval($_GET['search_year']) : date('Y',time());
		$search_time_month = $_GET['search_month'] ? intval($_GET['search_month']) : date('m',time());
		$search_time = $search_time_year.'-'.$search_time_month;
		//查询使用年份、月份数组
		$min_time = $m_kaoqin->min('daka_time');
		$min_year = $min_time ? date('Y',$min_time) : date('Y');
		$max_year = date('Y');
		$year_array = array();
		for ($i=$min_year; $i <= $max_year; $i++) { 
			$year_array[] = $i;
		}
		$month_array = array('1','2','3','4','5','6','7','8','9','10','11','12');
		$this->year_array = $year_array;
		$this->month_array = $month_array;
		$this->search_time_year = $search_time_year;
		$this->search_time_month = $search_time_month;

		//当前时间
		$date = $search_time;
		$this->date_now = $date;
		//根据月份计算天数
		$days = getmonthdays(strtotime($date));
		$this->days = $days;
		
		$m_workrule = M('Workrule');
		//获取时间范围内的每日时间戳数组(当月)
		$start = strtotime($date.'-'.'01');
		$end = strtotime($date.'-'.$days);
		$day_list = dateList($start,$end);

		//月时间戳范围
		$month_time = array('between',array($start,$end+86400));

		//计算月休息天数
		$month_no_array = $m_workrule->where(array('sdate'=>$month_time,'type'=>1))->getField('sdate',true);

		//开始到当前月时间戳范围
		$end = (strtotime($date.'-'.$days) > time()) ? time() : strtotime($date.'-'.$days);
		$month_time = array('between',array($start,$end+86400));
		$month_no = $m_workrule->where(array('sdate'=>$month_time,'type'=>1))->count();
		//月总天数
		$month_count_total = $days;
		$week_array = array(); //星期六、星期日的日期数组

		foreach ($day_list as $k=>$v) {
			$no_work = 1;
			$week = '';
			$week = getTimeWeek($v['sdate']);
			if (!in_array($v['sdate'],$month_no_array)) {
				$no_work = 0;
			}
			$day_list[$k]['no_work'] = $no_work;

			//判断星期六、日
			if ($week == '星期六' || $week == '星期日') {
				$week_array[] = $k+1;
			}
		}
		$this->week_array = $week_array;
		$now = time();

		// 出勤、休息天数、迟到、早退、缺卡、旷工
		//考勤
		foreach($role_list as $k=>$v){
			$chuqin = 0; //出勤
			$queka = 0; //缺卡
			$kuanggong = 0; //旷工
			//本月休息天数
			$role_list[$k]['xiuxi'] = $month_no;
			//本月迟到数
			$month_chidao_count = 0;
			$month_chidao_count = $m_kaoqin->where(array('role_id'=>$v['role_id'],'status'=>2,'daka_time'=>$month_time))->count();
			$role_list[$k]['chidao'] = $month_chidao_count;
			//本月早退数
			$month_zaotui_count = 0;
			$month_zaotui_count = $m_kaoqin->where(array('role_id'=>$v['role_id'],'status'=>3,'daka_time'=>$month_time))->count();
			$role_list[$k]['zaotui'] = $month_zaotui_count;

			//判断是否请假、出差
			$examine_list = array();
			$examine_list = $m_examine->where(array('owner_role_id'=>$v['role_id'],'create_time'=>$month_time,'type'=>array('in',array('2','5')),'examine_status'=>2))->select();
			//每日数据
			foreach($day_list as $key=>$val){
				if (time() > $val['sdate']) {
					$kaoqin_count = 0;
					$is_comment = 0;
					$title = '';
					$search_daka_time = date('Y-m-d',$val['sdate']).'+-+'.date('Y-m-d',$val['sdate']);
					
					$kaoqin_list = array();
					$kaoqin_list = $m_kaoqin->where(array('role_id'=>$v['role_id'],'daka_time'=>array('between',array($val['sdate'],$val['edate']))))->select();

					$kaoqin_count = count($kaoqin_list);
					if($now > $val['sdate']){
						if($val['no_work'] == 1 && !$kaoqin_count){
							$kaoqin_type = 3; //休
						} else {
							if ($kaoqin_count == 1) {
								$kaoqin_type = 4; //缺卡
							} elseif (empty($kaoqin_count)) {
								if (strtotime(date('Y-m-d')) == $val['sdate']) {
									if ($now > strtotime(date('Y-m-d').$config_info['xiaban_time'])) {
										$kaoqin_type = 9; //旷工
									} else {
										$kaoqin_type = 4; //缺卡
									}
								} else {
									$kaoqin_type = 9; //旷工
								}
							}
						}
					}else{
						$kaoqin_type = 0; //未到日期
					}
					if ($kaoqin_count == 1) {
						$queka += 1;
					}

					if ($kaoqin_list) {
						$chuqin += 1;
						$status_arr = array();
						$title = '';
						$a_arr = array('1','4'); //正常打卡
						$b_arr = array('2','3'); //迟到加早退

						foreach ($kaoqin_list as $key2=>$val2) {
							$status_arr[] = $val2['status'];
							switch ($val2['status']) {
								case 1 : $status_name = '签到';break;
								case 2 : $status_name = '迟到';break;
								case 3 : $status_name = '早退';break;
								case 4 : $status_name = '签退';break;
								default : $status_name = '缺卡';break;
							}
							if ($key2) {
								$title .= '&#10;'.date('Y-m-d H:i:s',$val2['daka_time']).'&nbsp;&nbsp;'.$status_name;
							} else {
								$title = date('Y-m-d H:i:s',$val2['daka_time']).'&nbsp;&nbsp;'.$status_name;
							}
						}
						
						if ($status_arr == $a_arr) {
							$kaoqin_type = 1; //正常
						} elseif ($status_arr == $b_arr) {
							$kaoqin_type = 2; //非正常
						} elseif (in_array('2',$status_arr)) {
							$kaoqin_type = 5; //迟到
						} elseif (in_array('3',$status_arr)) {
							$kaoqin_type = 6; //早退
						}
					} else {
						if($now > $val['sdate'] && empty($val['no_work'])){
							$kuanggong += 1;
						}
					}
					//判断是否请假、出差
					if ($examine_list) {
						foreach ($examine_list as $key1=>$val1) {
							$dateList = array();
							$dateList = dateList($val1['start_time'],$val1['end_time']);
							$new_dateList = array();
							foreach ($dateList as $key2=>$val2) {
								$new_dateList[] = $val2['sdate'];
							}
							if (in_array($val['sdate'],$new_dateList) && $val1['type'] == 2) {
								$kaoqin_type = 7; //假
								$module_url = U('examine/view','id='.$val1['examine_id'].'&type=2');
							}
							if (in_array($val['sdate'],$new_dateList) && $val1['type'] == 5) {
								$kaoqin_type = 8; //差
								$module_url = U('examine/view','id='.$val1['examine_id'].'&type=5');
							}
						}
					}

					$role_list[$k]['kaoqin_type'][$key+1]['title'] = $title;
					$role_list[$k]['kaoqin_type'][$key+1]['type'] = $kaoqin_type;
					$role_list[$k]['kaoqin_type'][$key+1]['kaoqin_count'] = $kaoqin_count;
					$role_list[$k]['kaoqin_type'][$key+1]['url'] = $module_url ? : '';
					$role_list[$k]['kaoqin_type'][$key+1]['search_daka_time'] = $search_daka_time;
				} else {
					$role_list[$k]['kaoqin_type'][$key+1][] = array();
				}
				
			}
			$role_list[$k]['chuqin'] = $chuqin;
			$role_list[$k]['queka'] = $queka;
			$role_list[$k]['kuanggong'] = $kuanggong;
		}
		//部门岗位

		$roleList = array();
		foreach($idArray as $k=>$v){
			$roleList[$k] = $m_user->where(array('role_id'=>$v))->field('role_id,full_name')->find();
		}
		$this->roleList = $roleList;
		$this->role_list = $role_list;
		$this->alert = parseAlert();
		$this->display();
	}


	/**
	 * 考勤统计
	 **/
	public function record(){
		//权限判断
		if(!getPerByAction('kaoqin','analytics')){
			alert('error','您没有此权利！',0);
		}
		$m_kaoqin = M('Kaoqin');
		//权限范围
		$below_ids = getPerByAction('kaoqin','analytics');
		$role_id_array = array();
		if(intval($_GET['role'])){
			$role_id_array = array(intval($_GET['role']));
		}else{
			if(intval($_GET['department'])){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			}
		}
		//过滤权限范围内的role_id
		if($role_id_array){
			//数组交集
			$idArray = array_intersect($role_id_array,$below_ids);
		}else{
			$idArray = getPerByAction('kaoqin','analytics');
		}
		//时间段搜索
		if($_GET['daka_time']){
			$daka_time = explode(' - ',trim($_GET['daka_time']));
			if($daka_time[0]){
				$start_time = strtotime($daka_time[0]);
			}
			$end_time = $daka_time[1] ?  strtotime(date('Y-m-d 23:59:59',strtotime($daka_time[1]))) : strtotime(date('Y-m-d 23:59:59',time()));
			$params[] = "daka_time=" . trim($_GET['daka_time']);
		}else{
			$start_time = strtotime(date('Y-m-01 00:00:00'));
			$end_time = strtotime(date('Y-m-d H:i:s'));
		}
		if($_GET['role_id']){
			$where['role_id'] = intval($_GET['role_id']);
			$params[] = "role_id=" . trim($_GET['role_id']);
		}else{
			$where['role_id'] = array('in',$idArray);
		}
		if($_GET['month']){
			//本月时间戳范围
			$month_start_time = strtotime(date($_GET['year'].'-'.$_GET['month'].'-01')); 
			$month_end_time = strtotime($_GET['year']."-".$_GET['month']."-".date("t",$month_start_time))+86400;
			$month_time = array('between',array($month_start_time,$month_end_time));
			$where['daka_time'] = $month_time;
			$params[] = "year=" . trim($_GET['year']);
			$params[] = "month=" . trim($_GET['month']);
		}else{
			$where['daka_time'] = array('between',array($start_time,$end_time));
		}
		if($_GET['status']){
			$where['status'] = trim($_GET['status']);
			$params[] = "status=" . trim($_GET['status']);
		}
	
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		$p = intval($_GET['p'])?intval($_GET['p']):1;

		$count = $m_kaoqin->where($where)->count();
		$p_num = ceil($count/$listrows);
		if($p_num<$p){
			$p = $p_num;
		}
		$list = $m_kaoqin->where($where)->order('daka_time desc')->page($p.','.$listrows)->select();
		import("@.ORG.Page");
		$Page = new Page($count,$listrows);
		
		$this->parameter = implode('&', $params);
		$weekarray=array("日","一","二","三","四","五","六");
		foreach ($list as $k => $v) {
			$user_info = array();
			$user_info = getUserByRoleId($v['role_id']);
			$list[$k]['user'] = $user_info;
			$list[$k]['date'] = date('Y-m-d',$v['daka_time']);
			$list[$k]['time'] = date('H:i:s',$v['daka_time']);
			$list[$k]['week'] = "星期".$weekarray[date("w",$v['daka_time'])];
			$list[$k]['address'] = $v['address'] ? $v['address'] : $v['wifi_name'];
			switch ($v['status']) {
				case 1: $status_name = '正常签到'; break;
				case 2: $status_name = '迟到'; break;
				case 3: $status_name = '早退'; break;
				case 4: $status_name = '正常签退'; break;
			}
			$list[$k]['status_name'] = $status_name;
		}
		
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
		//部门岗位
		$url = getCheckUrlByAction(MODULE_NAME,ACTION_NAME);
		$per_type =  M('Permission')->where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('RoleDepartment')->select();
		}else{
			$departmentList = M('RoleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		$this->assign('departmentList', $departmentList);
		$roleList = array();
		foreach($idArray as $roleId){
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		$this->list = $list;
		$this->role_list = $role_list;
		$this->start_date = date('Y-m-d',$start_time);
		$this->end_date = date('Y-m-d',$end_time);
		$this->listrows = $listrows;
		$this->assign('count',$count);
		$Page->parameter = implode('&', $params);
		$this->assign('page', $Page->show());
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 考勤规则设置
	 * @param 
	 * @author 
	 * @return 
	 */
	public function setting() {
		$m_kaoqin_config = M('KaoqinConfig');
		$m_route = M('Route');
		$config_info = $m_kaoqin_config->find();
		if ($this->isPost()) {
			$attendance_address = explode(',',trim($_POST['attendance_address']));
			if ($_POST['attendance_address']) {
				$y = $attendance_address[0];
				$x = $attendance_address[1];
			} else {
				$x = '';
				$y = '';
			}
			
			if ($m_kaoqin_config->create()) {
				$m_kaoqin_config->x = $x;
				$m_kaoqin_config->y = $y;
				$m_kaoqin_config->create_role_id = session('role_id');
				$m_kaoqin_config->update_time = time();
				if ($config_info) {
					$m_kaoqin_config->where(array('id'=>$config_info['id']))->save();
				} else {
					$m_kaoqin_config->add();
				}
			}
			$route = array_values($_POST['route']);
			$m_route->where(array('id'=>array('gt',0)))->delete();
			if ($route) {
				foreach ($route as $k=>$v) {
					$data = array();
					$data['wifi_name'] = $v['wifi_name'];
					$data['mac_address'] = $v['mac_address'];
					$data['create_time'] = time();
					$data['create_role_id'] = session('role_id');
					$m_route->add($data);
				}
			}
			alert('success','保存成功！',$_SERVER['HTTP_REFERER']);
		} else {
			$this->route_list = $m_route->select();
			$config_info['attendance_address'] = '';
			if ($config_info['x'] && $config_info['y']) {
				$config_info['attendance_address'] = $config_info['y'].','.$config_info['x'];
			}
			$config_info['radius'] = $config_info['radius'] ? $config_info['radius'] : '';	
			$this->config_info = $config_info;
			$this->alert = parseAlert();
			$this->display();
		}
	}

	/**
	 * 考勤规则地图
	 * @param 
	 * @author 
	 * @return 
	 */
	public function set_map(){
		$this->display();
	}

	/**
	 * 考勤月历
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		$this->now_date = date('Y-m-d',time());
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 考勤月历(数据调用)
	 * @param 
	 * @author 
	 * @return 
	 */
	public function indexdata(){
		//权限判断
		// $below_ids = getPerByAction('kaoqin','index');
		// if(!$below_ids){
		// 	$this->ajaxReturn('','您没有此权利！',0);
		// }
		$m_kaoqin = M('Kaoqin');
		$m_user = M('User');

		$where = array();
		$order = "daka_time asc";
		$type = $_POST['type'] ? trim($_POST['type']) : '';

		if($_GET['search_date']){
			$timestamp = strtotime($_POST['search_date']);
			$mdays = date('t',$timestamp);
			$start_time = strtotime(date('Y-m-1 00:00:00',$timestamp));
			$end_time = strtotime(date('Y-m-'.$mdays.' 23:59:59',$timestamp));
		}else{
			if($_GET['start'] && $_GET['end']){
				$start_time = trim($_GET['start']);
				$end_time = trim($_GET['end']);
			}else{
				$start_time = strtotime(date('Y-m-01')); 
				$end_time = strtotime(date("Y")."-".date("m")."-".date("t"))+86400;
			}
		}
		$where['daka_time'] = array('between',array($start_time,$end_time));
		$where['role_id'] = session('role_id');

		$list = $m_kaoqin->where($where)->order($order)->select();
		//status 状态（1 正常签到）（2 迟到）（3 早退）（4 正常签退）
		foreach($list as $k=>$v){
			$start_date = strtotime(date('Y-m-d',$v['daka_time']));
			$end_date = $start_date+86399;
			switch ($v['status']) {
				case '1' : 
					$name = '【 签到 】'.date('H:i:s',$v['daka_time']);
					$color = '#62A8EA';
					break;
				case '2' : 
					$name = '【 迟到 】'.date('H:i:s',$v['daka_time']);
					$color = '#d3a005';
					break;
				case '3' :
					$name = '【 早退 】'.date('H:i:s',$v['daka_time']);
					$color = '#1ab394';
					break;
				case '4' :
					$name = '【 签退 】'.date('H:i:s',$v['daka_time']);
					$color = '#62A8EA';
					break;
				default : 
					$name = '【 缺卡 】';
					break;
			}
			$list[$k]['title'] = $name;
			$list[$k]['start'] = date('Y-m-d H:i:s',$start_date);
			$list[$k]['end'] = date('Y-m-d H:i:s',$end_date);
			$list[$k]['allDay'] = true;
			$list[$k]['color'] = $color;
		}
		if($this->isAjax()){
			echo $kaoqin_list = json_encode($list);
		}
	}
}
