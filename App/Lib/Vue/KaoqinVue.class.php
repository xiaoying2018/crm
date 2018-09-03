<?php
/**
 *考勤相关
 **/
class KaoqinVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('add','view','analytics','config','configcheck','index')
		);
		B('VueAuthenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
		
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
	 * 考勤添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			$m_kaoqin = M('Kaoqin');
			$m_kaoqin_config = M('KaoqinConfig');
			$m_route = M('Route');
			$config_info = $m_kaoqin_config->find();

			$x = trim($_POST['x']);
			$y = trim($_POST['y']);
			//经纬度百度转高德
			if ($x && $y) {
				$xy_info = bd_decrypt($y,$x);
				$x = $xy_info['gg_lat'];
				$y = $xy_info['gg_lon'];
			}

			$wifi_name = trim($_POST['wifi_name']);
			$mac_address = trim($_POST['mac_address']);
			//判断是否符合打开规则（如不设置，则不限制）
			$route_list = $m_route->select();
			if (($config_info['x'] && $config_info['y']) || $route_list) {
				//根据两个坐标点，换算距离（米）
				if ($x && $y) {
					$range = getDistance($config_info['x'],$config_info['y'],$x,$y);
					if ($range && $range > $config_info['radius']) {
						$this->ajaxReturn('','您不在规定的考勤位置范围内,无法打卡！',0);
					}
				} else {
					$res_route = 1;
					foreach ($route_list as $k=>$v) {
						//由于路由器有2.4G和5G两种频段（区别是后两位不同），因此只比对后两位
						if (($v['wifi_name'] == $wifi_name) && (substr($v['mac_address'],0,-2) == substr($mac_address,0,-2))) {
							$res_route = 0;
						}
					}
					if ($res_route == 1) {
						$this->ajaxReturn('','您不在规定的考勤wifi范围内,无法打卡！',0);
					}
				}
			} else {
				$this->ajaxReturn('','管理员没有配置考勤规则！',0);
			}

			//获取设置的上下班时间
			$shangban_date = $config_info['shangban_time'];
			$xiaban_date = $config_info['xiaban_time'];
			$daka_time = time();
			//判断上下班时间是否设置
			if ($shangban_date && $xiaban_date) {
				$shangban_time = strtotime($shangban_date); //转换当天上班时间戳
				$xiaban_time = strtotime($xiaban_date); //转换当天下班时间戳
				$start_time = strtotime(date('Y-m-d', time()));
				//判断打卡次数
				$daka_num = $m_kaoqin->where(array('role_id'=>session('role_id'),'daka_time'=>array('between',array($start_time,$start_time+86399))))->count();
				if ($daka_num > 2) {
					$this->ajaxReturn('','超过打卡次数,无法打卡！',0);
				}

				//判断设备打卡次数
				if ($_POST['token_id']) {
					$token_num = $m_kaoqin->where(array('token_id'=>trim($_POST['token_id']),'daka_time'=>array('between',array($start_time,$start_time+86399))))->count();
					if ($token_num > 2) {
						$this->ajaxReturn('','该设备今日已超过打卡次数,无法打卡！',0);
					}
				}

				//判断打卡状态
				$date['0']['start'] = $start_time;//当天凌晨
				$date['0']['end'] = $shangban_time + 59;//上班时间
				$date['0']['type'] = '1';
				$date['1']['start'] = $shangban_time + 60;//上班开始时间
				$date['1']['end'] =  $xiaban_time;//下班结束时间
				$date['1']['type'] = '2';
				$date['2']['start'] = $xiaban_time; //下班结束时间
				$date['2']['end'] =  $start_time + 86399; //当天最后时间
				$date['2']['type'] = '3';
				foreach ($date as $k => $v) {
					if ($daka_time >= $v['start'] && $daka_time <= $v['end']) {
						$type = $v['type'];
						break;
					}
				}
				switch ($type) {
					case '1':
						if ($daka_num == 0) {
							$data['status'] = 1;//签到
						} else {
							$data['status'] = 3;//早退
						}
						break;
					case '2':
						if ($daka_num == 0) {
							$data['status'] = 2;//迟到
						} else {
							$data['status'] = 3;//早退
						}
						break;
					default:
						$data['status'] = 4;//签退
						break;
				}
				//更新打卡
				if (($type == 3 && $daka_num == 1) || $daka_num == 2) {
					$where = array();
					$where['daka_time'] = array('between',array($start_time,$start_time+86399));
					$where['role_id'] = session('role_id');
					$where['status'] = array('in',array('3','4'));
					$kaoqin_id = $m_kaoqin->where($where)->getField('id');
				}

				$data['role_id'] = session('role_id');
				$data['daka_time'] = $daka_time;
				$data['x'] = $_POST['x'] ? trim($_POST['x']) : '';
				$data['y'] = $_POST['y'] ? trim($_POST['y']) : '';
				$data['address'] = $_POST['address'] ? trim($_POST['address']) : '';
				$data['remark'] = $_POST['remark'] ? trim($_POST['remark']) : '';
				$data['shangban_time'] = $shangban_date;
				$data['xiaban_time'] = $xiaban_date;
				$data['wifi_name'] = $_POST['wifi_name'] ? trim($_POST['wifi_name']) : '';
				$data['mac_address'] = $_POST['mac_address'] ? trim($_POST['mac_address']) : '';
				$data['token_id'] = $_POST['token_id'] ? trim($_POST['token_id']) : '';
				$data['config_type'] = $_POST['config_type'] ? intval($_POST['config_type']) : '';

				if ($kaoqin_id) {
					if ($m_kaoqin->where(array('id'=>$kaoqin_id))->save($data)) {
						$this->ajaxReturn('','更新成功！',1);
					} else {
						$this->ajaxReturn('','更新失败！',0);
					}
				} else {
					if ($m_kaoqin->add($data)) {
						$this->ajaxReturn('','打卡成功！',1);
					} else {
						$this->ajaxReturn('','打卡失败！',0);
					}
				}
			} else {
				$this->ajaxReturn('','管理员还未设置上下班时间，该功能无法使用！',0);
			}
		}
	}

	/**
	 * 考勤查看
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$m_kaoqin = M('Kaoqin');
			$m_kaoqin_config = M('KaoqinConfig');
			$today_start = date('Y-m-d',time());

			$where = array();
			$where['role_id'] = session('role_id');
			$date = $_POST['date'] ? $_POST['date'] : date('Y-m-d',time());
			$start_time = strtotime($date);
			$end_time = $start_time + 86399;
			$where['daka_time'] = array('between',array($start_time,$end_time));
			$list = $m_kaoqin->where($where)->select();

			//status 1签到2迟到3签退4早退
			foreach ($list as $k=>$v) {
				switch ($v['status']) {
					case 1 : $status_name = '签到'; break;
					case 2 : $status_name = '迟到'; break;
					case 3 : $status_name = '签退'; break;
					case 4 : $status_name = '早退'; break;
					default : $status_name = '缺卡'; break; //（switch的结构）
				}
				$list[$k]['status_name'] = $status_name;
			}

			//上下班时间
			if ($date == date('Y-m-d',time())) {
				$config_info = $m_kaoqin_config->find();
				$data['shangban_time'] = $config_info['shangban_time'] ? $config_info['shangban_time'] : '';
				$data['xiaban_time'] = $config_info['xiaban_time'] ? $config_info['xiaban_time'] : '';
			} else {
				$kaoqin_info = $m_kaoqin->where($where)->find();
				$data['shangban_time'] = $kaoqin_info['shangban_time'] ? $kaoqin_info['shangban_time'] : '';
				$data['xiaban_time'] = $kaoqin_info['xiaban_time'] ? $kaoqin_info['xiaban_time'] : '';
			}
			//计算工时
			$work_time = '0分钟';
			if (count($list) == 2) {
				$min_time = $m_kaoqin->where($where)->min('daka_time');
				$max_time = $m_kaoqin->where($where)->max('daka_time');
				$sub_time = $max_time-$min_time;
				if ($sub_time > 0) {
					$work_time = getTimeBySec($sub_time); //根据秒数转换成时间文本
				}
			}
			$data['work_time'] = $work_time;
			$data['kaoqin_count'] = $list ? count($list) : 0;
			$daka_status = 0;//不能打卡
			if ($today_start == $date && count($list) <= 2) {
				if ($data['xiaban_time'] == 0) {
					$daka_status = 0;//不能打卡
				} else {
					if (time() > strtotime($data['xiaban_time'])) {
						if (count($list)) {
							if (count($list) == 1) {
								if (in_array($list[0]['status'],array('3','4'))) {
									//已打下班卡
									$daka_status = 3;//更新打卡
								} else {
									$daka_status = 2;//下班
								}
							} else {
								$daka_status = 3;//更新打卡
							}
						} else {
							$daka_status = 2;//下班
						}
					} else {
						if (count($list) == 0) {
							$daka_status = 1;//上班
						} elseif (count($list) == 1) {
							if (in_array($list[0]['status'],array('3','4'))) {
								$daka_status = 3;//更新打卡
							} else {
								$daka_status = 2;//下班
							}
						} elseif (count($list) == 2) {
							$daka_status = 3;//更新打卡
						}
					}
				}
			}
			//设置权限
			$setting = 0;
			if (checkPerByAction('kaoqin','setting')) {
				$setting = 1;
			}
			$data['setting'] = $setting;
			$data['daka_status'] = $daka_status;
			$data['list'] = $list ? $list : array();
			$data['status'] = 1;
			$data['info'] = '成功';
			$this->ajaxReturn($data,"JSON");
		}
	}

	/**
	 * 考勤统计
	 * @param 
	 * @author 
	 * @return 
	 */
	public function analytics(){
		if ($this->isPost()) {
			$m_kaoqin = M('Kaoqin');
			$m_workrule = M('Workrule');
			$where['role_id'] = session('role_id');
			$date = $_POST['time'] ? date('Y-m',$_POST['time']) : date('Y-m',time());
			//根据月份计算天数
			$days = getmonthdays(strtotime($date));
			//获取时间范围内的每日时间戳数组(当月)
			$start = strtotime($date.'-'.'01');
			$end = (strtotime($date.'-'.$days) > time()) ? time() : strtotime($date.'-'.$days);
			$day_list = dateList($start,$end);
			$num = array();
			$list_arr = array();

			$num_a_list = array(); //出勤
			$num_b_list = array(); //迟到
			$num_c_list = array(); //早退
			$num_d_list = array(); //缺卡
			$num_e_list = array(); //旷工
			$num_f_list = array(); //休息

			$num_a_k = 0;
			$num_b_k = 0;
			$num_c_k = 0;
			$num_d_k = 0;
			$num_e_k = 0;
			$num_f_k = 0;

			//休息日
			$month_no_array = $m_workrule->where(array('type'=>1,'sdate'=>array('between',array($start,$end))))->getField('sdate',true);
			foreach ($day_list as $k=>$v) {
				$no_work = 1;
				$week = '';
				$week = getTimeWeek($v['sdate']);
				if (!in_array($v['sdate'],$month_no_array)) {
					$no_work = 0;
				}
				$day_list[$k]['no_work'] = $no_work;
			}
			foreach ($month_no_array as $k=>$v) {
				$num_f_list[$k]['content'] = date('Y-m-d',$v).' '.getTimeWeek($v);
				$num_f_list[$k]['date'] = date('Y-m-d',$v);
			}
			$now = time();

			foreach($day_list as $key=>$val){
				$kaoqin_status = array();
				$kaoqin_status = $m_kaoqin->where(array('role_id'=>session('role_id'),'daka_time'=>array('between',array($val['sdate'],$val['edate']))))->getField('status',true);

				$kaoqin_count = count($kaoqin_status);
				if($now > $val['sdate'] && empty($val['no_work'])){
					if ($kaoqin_count == 1) {
						$num_d_list[$num_d_k]['content'] = date('Y-m-d',$val['sdate']).' '.getTimeWeek($val['sdate']); //缺卡
						$num_d_list[$num_d_k]['date'] = date('Y-m-d',$val['sdate']); //缺卡
						$num_d_k += 1;
					} elseif (empty($kaoqin_count)) {
						$num_e_list[$num_e_k]['content'] = date('Y-m-d',$val['sdate']).' '.getTimeWeek($val['sdate']); //旷工
						$num_e_list[$num_e_k]['date'] = date('Y-m-d',$val['sdate']); //旷工
						$num_e_k += 1;
					}
				}
				if ($kaoqin_count) {

					$num_a_list[$num_a_k]['content'] = date('Y-m-d',$val['sdate']).' '.getTimeWeek($val['sdate']);
					$num_a_list[$num_a_k]['date'] = date('Y-m-d',$val['sdate']);
					$num_a_k += 1;
					if (in_array('2',$kaoqin_status)) {
						$num_b_list[$num_b_k]['content'] = date('Y-m-d',$val['sdate']).' '.getTimeWeek($val['sdate']);
						$num_b_list[$num_b_k]['date'] = date('Y-m-d',$val['sdate']);
						$num_b_k += 1;
					} elseif (in_array('3',$kaoqin_status)) {
						$num_c_list[$num_c_k]['content'] = date('Y-m-d',$val['sdate']).' '.getTimeWeek($val['sdate']);
						$num_c_list[$num_c_k]['date'] = date('Y-m-d',$val['sdate']);
						$num_c_k += 1;
					}
				}
			}

			$list_arr = array('num_a_list'=>$num_a_list,'num_b_list'=>$num_b_list,'num_c_list'=>$num_c_list,'num_d_list'=>$num_d_list,'num_e_list'=>$num_e_list,'num_f_list'=>$num_f_list);
			
			//结果返回
			$data['list'] = $list_arr;
			$data['status'] = 1;
			$data['info'] = '成功';
			$this->ajaxReturn($data,"JSON");
		}
	}

	/**
	 * 考勤规则
	 * @param 
	 * @author 
	 * @return 
	 */
	public function config() {
		if ($this->isPost()) {
			$config_info = M('KaoqinConfig')->find();
			if ($config_info['x'] && $config_info['y']) {
				//高德转百度
				$config_data = bd_encrypt($config_info['y'],$config_info['x']);
				$config_info['y'] = $config_data['bd_lon'];
				$config_info['x'] = $config_data['bd_lat'];
			}
			$route_list = M('Route')->select();
			$data['config_info'] = $config_info ? $config_info : array();
			$data['route_list'] = $route_list ? $route_list : array();
			$data['status'] = 1;
			$data['info'] = '成功';
			$this->ajaxReturn($data,"JSON");
		}
	}

	/**
	 * 考勤规则设置
	 * @param 
	 * @author 
	 * @return 
	 */
	public function setting() {
		if ($this->isPost()) {
			$m_kaoqin_config = M('KaoqinConfig');
			$m_route = M('Route');
			$config_info = $m_kaoqin_config->find();
			
			if ($_POST['x'] && $_POST['y']) {
				//经纬度百度转高德
				$xy_info = array();
				$xy_info = bd_decrypt($_POST['y'],$_POST['x']);
				$y = $xy_info['gg_lon'];
				$x = $xy_info['gg_lat'];
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
			$route = $_POST['route'];
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
			$this->ajaxReturn('','设置成功！',1);
		}
	}

	/**
	 * 考勤月历
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			//权限判断
			// if(!getPerByAction('kaoqin','index')){
			// 	$this->ajaxReturn('','您没有此权利！',-2);
			// }
			$m_kaoqin = M('Kaoqin');
			$where = array();
			$order = "daka_time asc,id asc";
			//获取当月时间范围
			$date = $_POST['time'] ? date('Y-m',$_POST['time']) : date('Y-m',time());
			$timestamp = strtotime($date);
			$mdays = date('t',$timestamp);
			$start_time = strtotime(date($date.'-1 00:00:00',$timestamp));
			$end_time = (strtotime($date.'-'.$mdays) > time()) ? time() : strtotime($date.'-'.$mdays);		
			$where['role_id'] = session('role_id');

			//生成从开始日期到结束日期的日期数组
			$date_list = dateList($start_time,$end_time);
			//休息日
			$no_work_date = M('Workrule')->where(array('type'=>1,'sdate'=>array('between',array($start_time,$end_time))))->getField('sdate',true);

			$kaoqin_arr = array();
			foreach ($date_list as $k=>$v) {
				$status_arr = array();
				$a_arr = array('1','4'); //正常打卡
				$b_arr = array('2','3'); //迟到加早退

				$kaoqin_list = array();
				$where['daka_time'] = array('between',array($v['sdate'],$v['edate']));
				$kaoqin_list = $m_kaoqin->where($where)->select();
				if ($kaoqin_list) {
					foreach ($kaoqin_list as $key1=>$val1) {
						$status_arr[] = $val1['status'];
					}
					
					if ($status_arr == $a_arr) {
						$kaoqin_type = 1; //正常
					} elseif ($status_arr == $b_arr) {
						$kaoqin_type = 2; //非正常
					} elseif (in_array('2',$status_arr)) {
						$kaoqin_type = 2; //迟到
					} else {
						$kaoqin_type = 2; //早退
					}
				} else {
					if (in_array($v['sdate'],$no_work_date)) {
						$kaoqin_type = 0;
					} else {
						$kaoqin_type = 2; //缺卡
					}
				}
				$kaoqin_arr[$k]['date'] = date('Y-m-d',$v['sdate']);
				$kaoqin_arr[$k]['kaoqin_type'] = $kaoqin_type;
			}

			$data['list'] = $kaoqin_arr ? $kaoqin_arr : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 判断考勤规则
	 * @param 
	 * @author 
	 * @return 
	 */
	public function configCheck() {
		if ($this->isPost()) {
			$m_kaoqin_config = M('KaoqinConfig');
			$m_route = M('Route');
			$config_info = $m_kaoqin_config->find();
			$x = trim($_POST['x']);
			$y = trim($_POST['y']);
			//经纬度百度转高德
			if ($x && $y) {
				$xy_info = bd_decrypt($y,$x);
				$x = $xy_info['gg_lat'];
				$y = $xy_info['gg_lon'];
			}
			$wifi_name = trim($_POST['wifi_name']);
			$mac_address = trim($_POST['mac_address']);
			//判断是否符合打开规则（如不设置，则不限制）
			$route_list = $m_route->select();
			if (($config_info['x'] && $config_info['y']) || $route_list) {
				//根据两个坐标点，换算距离（米）
				if ($x && $y) {
					$range = getDistance($config_info['x'],$config_info['y'],$x,$y);
					if ($range && $range > $config_info['radius']) {
						$this->ajaxReturn('','您不在规定的考勤位置范围内,无法打卡！',0);
					}
				} else {
					$res_route = 1;
					foreach ($route_list as $k=>$v) {
						if (($v['wifi_name'] == $wifi_name) && (substr($v['mac_address'],0,-2) == substr($mac_address,0,-2))) {
							$res_route = 0;
						}
					}
					if ($res_route == 1) {
						$this->ajaxReturn('','您不在规定的考勤wifi范围内,无法打卡！',0);
					}
				}
			} else {
				$this->ajaxReturn('','管理员没有配置考勤规则！',0);
			}
		}
	}
}