<?php 
    class StructureModel extends Model{
		
		/**	
		 *	检测岗位名称
		 **/
		public function checkPositionName($name,$position_id=0){
			if(!$name) return -1;
			$position = M('position');
			$where['name'] = $name;
			if($position_id){
				$where['position_id'] = array('neq',$position_id);
			}
			if($position->where($where)->find()){
				return -2;
			}else{
				return 1;
			}
		}
		public function addPosition($info){
			if(!M('position')->create($info)){
				return false;
			}
			return M('position')->add();
		}
		public function editPosition($info){
			if(!M('position')->create($info)){
				return false;
			}
			return M('position')->save();
		}
		public function getPositionInfo($position_id){
			if(!empty($position_id)){
				$info = M('position')->where(array('position_id'=>$position_id))->find();
				if(!$info) return false;
				$info['user'] = M('user')->where(array('position_id'=>$position_id,'status'=>1))->select();
				$info['sub'] = M('position')->where(array('parent_id'=>$position_id))->select();
				return $info;
			}else{
				return false;
			}
		}
		//岗位上下级关系树形html
		function getSubPositionTreeCode($position_id=0, $first=0, $department_id) {
			$string = "";
			if($department_id){
				if($first){
					$position_list = M('position')->where('position_id = %d and department_id = %d', $position_id, $department_id)->select();
				}else{
					$position_list = M('position')->where('parent_id = %d and department_id = %d', $position_id, $department_id)->select();
// if($position_id == 8){
	// echo M()->getLastSql();println($position_list);
	
// }
				}
			}else{
				if($first && $department_id){
					$position_list = M('position')->where('position_id = %d', $position_id)->select();
				}else{
					$position_list = M('position')->where('parent_id = %d', $position_id)->select();
// if($position_id == 8){
	// echo M()->getLastSql();println($position_list);
	
// }if($position_id == 8){
	// echo M()->getLastSql();println($position_list);
	
// }
				}
			}

			if ($position_list) {
				if ($first) {
					$string = '<ul id="browser_position" class="filetree">';
				} else {
					$string = "<ul>";
				}
				foreach($position_list AS $value) {
					
						$department_name = M('Department')->where('department_id = %d', $value['department_id'])->getField('name');
					
					// 0:关闭 1：有效 2可招聘 3正在招聘 4预入职 5在职 
					//0默认 1预算不保留 2预算保留
					
					$member = array();
					$info = array();
					$member_id = 0;
					$member_name = '';
					$tip = '';
					$icon_class = '';
					$icon2_class = '';
					
					//关闭
					if($value['status'] == 0){
						$icon_class = 'zhiweiguanbi';
						//$class = " disabled";
						//$control = '(<a class="activePos" rel="'.$value['position_id'].'" href="javascript:void(0);">启用</a>)';
						
						//查询有没有员工任职记录 最后一任员工的信息
						$member_id = M('PositionHistory')->where('position_id = %d', $value['position_id'])->order('position_history_id desc')->getField('member_id');
						
						if($member_id){
							$member = D('MemberView')->where('member.del_flag = 0 && member_id = %d', $member_id)->find();
							$member_name = $member['ch_gender'].$member['ch_name'].' | '.$member['en_name'].' '.$member['en_gender'];
						}
						$tip = '<span style="text-decoration:line-through;">'.$member_name. '</span> ' .date('Y-m-d', $value['status_change_time']);
						/*
						//离职不保留预算
						if($value['budget_status'] == 1){
							//5.已离职，不保留预算（灰色）：显示 离职员工姓名（并打“删除线”）、离职日期
						}else{
							//6.职位关闭（灰色）：显示 最后一任职员工姓名（并打“删除线”）、职位关闭日期
						}
						*/
						
					//有人在职
					}elseif($value['status'] == 5){
						
						if($info = D('MemberView')->where('member.del_flag = 0 && position.position_id = %d', $value['position_id'])->find()){
							$member_name = $info['ch_gender'].$info['ch_name'].' | '.$info['en_name'].' '.$info['en_gender'].' ['.$info['personal_num'].']' ;
							if($info['turnover_time']){
								//if($info['turnover_time'] > time()){
									$icon_class = 'jijianglizhi';
								//}
								$tip = $member_name.' ' .date('Y-m-d', $info['enter_time']).'~<span style="  border-bottom: #FC5454 2px dotted;">'.date('Y-m-d', $info['turnover_time']).'</span>';
							}else{
								if($info['enter_time'] > time()){
									$icon_class = 'kongzhiwei';
									$icon2_class = 'yiruzhi';
									$tip = $member_name.' <span style="  border-bottom: #FC5454 2px dotted;">' .date('Y-m-d', $info['enter_time']).'</span>~';
								}else{
									$icon_class = 'zaizhi';
									$tip = $member_name. ' ' .date('Y-m-d', $info['enter_time']).'~';
								}
							}
						}
					//新职位
					}elseif($value['status'] == 1){
						$icon_class = 'kongzhiwei';
						
					//可招聘
					}elseif($value['status'] == 2){
						
						
						
					
						//4.	已离职，保留预算（红色）：显示 离职员工姓名（并打“删除线”）、离职日期
						
						
						//离职保留预算
						if($value['budget_status'] == 2){
							$icon_class = 'lizhiquxiao';
							
						}else{
							$icon_class = 'kongzhiwei';
						}
						
						//$control = '(<a class="activePos" rel="'.$value['position_id'].'" href="javascript:void(0);">启用</a>)';
						
						//查询有没有员工任职记录 最后一任员工的信息
						$member_id = M('PositionHistory')->where('position_id = %d', $value['position_id'])->order('position_history_id desc')->getField('member_id');

						if($member = D('MemberView')->where('member.del_flag = 0 && member_id = %d', $member_id)->find()){
						
							$member_name = $member['ch_gender'].$member['ch_name'].' | '.$member['en_name'].' '.$member['en_gender'].' ['.$member['personal_num'].']';
							$tip = '<span style="text-decoration:line-through;">'.$member_name. '</span> ' .date('Y-m-d', $member['turnover_time']);
						}
						
					
					//正在招聘
					}elseif($value['status'] == 3){
						//在招聘状态
						//$icon_class = 'zhaopin';
						$icon_class = 'kongzhiwei';
					//预入职人员
					}elseif($value['status'] == 4){
						//1、预入职人员
						//2、在职人员
						//$icon_class = 'zhaopin';
						$icon_class = 'kongzhiwei';
					}
					
					if($value['status'] == 3){
						//招聘中图标
						$recruit_where['status'] = 1;
						$recruit_where['position_id'] = $value['position_id'];
						$recruit_where['del_flag'] = 0;
						$recruit = M('Recruit')->where($recruit_where)->order('start_time desc')->find();
						if(M('Posinfor')->where('recruit_id = %d', $recruit['recruit_id'])->count()){
							$icon2_class = 'zaizhaopin';
						}else{
							$icon2_class = 'wuzhaopin';
						}
						
					}elseif($value['status'] == 4){
						//$recruit_where['status'] = array('neq', 0);
						$recruit_where['del_flag'] = 0;
						$recruit_where['position_id'] = $value['position_id'];
						$recruit = M('Recruit')->where($recruit_where)->order('start_time desc')->find();
				
						$enwhere['recruit.recruit_id'] = $recruit['recruit_id'];
						//$enwhere['entrymanager.tran_member'] = 0;
						$entrymanager = D('EntrymanagerView')->where($enwhere)->find();
						
						$member_name = $entrymanager['ch_gender'].$entrymanager['ch_name'].' | '.$entrymanager['en_name'].' '.$entrymanager['en_gender'];
						
						if($member_name){
							//2、正式入职 接收offer图标
							//则仍显示 : 在职人员信息
							//则显示 : 预入职员工姓名、入职日期
							$icon2_class = 'yuruzhi';
							$tip = '<span style="">'.$member_name. '</span> ';
							//$tip = '<span>'.$member_name. '</span> <span style="  border-bottom: #FC5454 2px dotted;">' .date('Y-m-d', $member['enter_time']).'</span>~';
						}else{
							//1、预入职 出offer图标
							$icon2_class = 'yuruzhi';
							$tip = '<span style="">'.$member_name. '</span> ';
						}
					}
					
					/*
					
					$recruit_where['status'] = 1;
					$recruit_where['del_flag'] = 0;
					$recruit_where['start_time'] = array('lt', time());
					$recruit_where['end_time'] = array('gt', time());
					$recruit = M('Recruit')->where($recruit_where)->find();
					
					
					$member = M('Member')->where('position_id = %d', $value['position_id'])->find();
					
					//预入职
					$enwhere['position.position_id'] = $position_id;
					$enwhere['entrymanager.tran_member'] = 0;
					$member = D('EntrymanagerView')->where($enwhere)->find();
					
					if($member)
					
					if($recruit){
						//在招聘
					}else{
						//无招聘
					}
					*/
					
					//预入职人选
					//已入职人选
					
					
					
					$temp_name =  $value['name'];
					$name = '<span class="'.$icon2_class.'">&nbsp;</span><a href="javascript:;" class="control_pos '.$class.'">'.$temp_name.' ('.$department_name.')</a>';
					
					if($first){
						$string .= "<li><span rel='".$value['position_id']."' class='".$icon_class."'>".$name.' '. ' ' . $tip ." &nbsp; $control</span>".$this->getSubPositionTreeCode($value['position_id'], 0, $department_id)."</li>";
					} else {
						$string .= "<li><span rel='".$value['position_id']."' class='".$icon_class."'>".$name.' ' . ' ' . $tip ." &nbsp; $control</span>".$this->getSubPositionTreeCode($value['position_id'], 0, $department_id)."</li>";
					}
					
				}
				$string .= "</ul>";
			} 

			return $string;
		}
		
		/**	
		 *	检测部门名称
		 **/
		public function checkDepartmentName($name,$department_id=0){
			if(!$name) return -1;
			$department = M('department');
			$where['name'] = $name;
			if($department_id){
				$where['department_id'] = array('neq',$department_id);
			}
			if($department->where($where)->find()){
				return -2;
			}else{
				return 1;
			}
		}
		public function addDepartment($info){
			$m_dep = M('department');
			if(!$m_dep->create($info)){
				return false;
			}
			return $m_dep->add();
		}
		public function editDepartment($info){
			if(!M('department')->create($info)){
				return false;
			}
			return M('department')->save();
		}
		public function getDepartmentInfo($department_id){
			$info = M('department')->where(array('department_id'=>$department_id))->find();
			if(!$info) return false;
			$info['position'] = M('position')->where(array('department_id'=>$department_id))->select();
			$info['sub'] = M('department')->where(array('parent_id'=>$department_id))->select();
			return $info;
		}
		/**	
		 *	下级部门列表
		 *	$department_id 传入的岗位id  =0时为所有部门
		 *	$string   部门name前置字符
		 *	$me=1时  包括自身部门
		 *	$notin   被排除的部门id（包括他的下级部门）
		 **/
		function getDepartmentList($department_id=0,$string='',$me=0,$notin=0) {
			$array = array();
			$first = empty($department_id) ? '' : $string;
			if($me==1 && $department_id != 0){
				$me = M('department')->where('department_id = %d and del_flag = 0',$department_id)->find();
				$array[$me['department_id']] = array('department_id'=>$me['department_id'],'name'=>$first.$me['name'],'en_name'=>$first.$me['en_name']);
			}
			$rows = M('department')->where('parent_id = %d and del_flag = 0',$department_id)->select();
			foreach($rows as $v){
				if($v['department_id'] != $notin){
					$array[$v['department_id']] = array('department_id'=>$v['department_id'],'name'=>$first.$v['name'],'en_name'=>$first.$v['en_name']);
					$str = !empty($string) ? "&nbsp;&nbsp;&nbsp;".$string : '';
					$array = $array + $this->getDepartmentList($v['department_id'],$str,0,$notin);
				}
			}
			return $array;
		}
		
// 部门岗位树形html
		function getSubDepartmentTreeCode($department_id, $first=0) {
			$string = "";
			$department_list = M('Department')->where('parent_id = %d and del_flag = 0', $department_id)->select();
			//$position_list = M('position')->where('department_id = %d', $department_id)->select();

			//if ($department_list || $position_list) {
			if ($department_list) {
				if ($first) {
					$string = '<ul id="browser" class="filetree">';
				} else {
					$string = "<ul>";
				}
				// foreach($position_list AS $value) {
					// $edit = D('User')->checkControl(array('g'=>smart,'m'=>'structure','a'=>'editposition')) ? "<a class='position_edit' rel=".$value['position_id']." href='javascript:void(0)'>编辑</a>" : "";
					// $delete = D('User')->checkControl(array('g'=>smart,'m'=>'structure','a'=>'deleteposition')) ? "<a class='position_delete' rel=".$value['position_id']." href='javascript:void(0)'>删除</a> " : "";
					// $string .= "<li><span rel='".$value['position_id']."' class='file'>".$value['name']."(编制:".$value['plan_num']."&nbsp;在职:".$value['real_num'].") &nbsp; <span class='control' id='control_file".$value['position_id']."'>".$edit." &nbsp; ".$delete." </span> </span></li>";
				// }
				foreach($department_list AS $value) {
				/*
					$edit = D('User')->checkControl(array('g'=>smart,'m'=>'structure','a'=>'editdepartment')) ? "<a class='department_edit' rel=".$value['department_id']." href='javascript:void(0)'>编辑</a>" : "";
					$move = D('User')->checkControl(array('g'=>smart,'m'=>'structure','a'=>'movedepartment')) ? "<a class='department_move' rel=".$value['department_id']." href='javascript:void(0)'>调动</a>" : "";
					$delete = D('User')->checkControl(array('g'=>smart,'m'=>'structure','a'=>'deletedepartment')) ? "<a class='department_delete' rel=".$value['department_id']." href='javascript:void(0)'>删除</a>" : "";
				*/
					if($value['status'] == 0){
						$class = 'disabled';
						//$contets = ('<a class="activeDep" rel="'.$value['department_id'].'" href="javascript:;">　(启用)　</a>');
					}else{
						$class = '';
						//$contets = ('<a class="activestopDep" rel="'.$value['department_id'].'" href="javascript:;">　(关闭)　</a>');
					}
					
					//部门【含子部门总职位数，在职人数，空缺职位】（本部门：职位数，在职，空缺）
					$res = $this->getDepartmentPositionCount($value['department_id']);
					$contets = '<a title="含子部门总职位数，在职人数，空缺职位">【'.$res['sub_total_positions'].'，'.$res['sub_work_positions'].'，'.$res['sub_vacancy_positions'].'】</a><a title="本部门：职位数，在职，空缺">（'.$res['self_total_positions'].'，'.$res['self_work_positions'].'，'.$res['self_vacancy_positions'].'）';
					
					$temp_name =  $value['name'];
					if($_GET['department_id'] == $value['department_id']){
						
						$name = '<a href="'.U('structure/department','department_id='.$value['department_id']).'" class="control_dep '.$class.'" style="background: #F0C1C1;">  '.$temp_name.$contets.'</a>';
					}else{
						$name = '<a href="'.U('structure/department','department_id='.$value['department_id']).'" class="control_dep '.$class.'"> 
						'.$temp_name.$contets.'</a>';
					}
					
					
					if($first){
						$string .= "<li><span rel='".$value['department_id']."' class='folder'>".$name." &nbsp; <span class='control' id='control_folder".$value['department_id']."'>".$edit." &nbsp; ".$move." &nbsp; ".$delete." </span></span>".$this->getSubDepartmentTreeCode($value['department_id'])."</li>";
					} else {
						//节点收起li增加： class='closed'
						$string .= "<li><span rel='".$value['department_id']."' class='folder'>".$name." &nbsp; <span class='control' id='control_folder".$value['department_id']."'>".$edit." &nbsp; ".$move." &nbsp; ".$delete." </span></span>".$this->getSubDepartmentTreeCode($value['department_id'])."</li>";
					}
					
				}
				$string .= "</ul>";
			} 
			return $string;
		}
		/*
		*获得部门下级直属岗位
		*$position_id 不需要包含的岗位id
		*/
		public function getDepartmentPosition($department_id){
			return $position = M('position')->where(array("department_id"=>$department_id))->select();
		}
		/*
		*获得部门下属岗位及上级部门岗位
		*$position_id 不需要包含的岗位id
		*/
		function getPositionDepartment($department_id, $position_id = 0){
			$position = M('position')->where(array("department_id"=>$department_id))->select();
			$parent_id = M('department')->where(array("department_id"=>$department_id, "parent_id"=>0))->getField('parent_id');
			if($parent_id != 0){
				$parent_position = $this->getPositionDepartment($parent_id);
				if(!$position){
					$position = $parent_position;
				}elseif($parent_position){
					$position = array_merge($position,$parent_position);
				}
			}
			if($position_id != 0){
				unset($position[array_search(M('position')->where(array("position_id"=>$position_id))->find(),$position)]);
			}
			return $position;
		}
		/**
		 * 从control表中获得g=>m=>a的多维数组
		 **/
		function getControl($is_display = false){
			$where = array();
			if($is_display){
				$where['is_display'] = 1;
			}
			$group = M('control')->where($where)->select();
			$group_array = array();
			foreach($group as $k=>$v){
				$group_array[$v['g']][$v['m']][] = $v;
			}
			return $group_array;
		}
		
		
		/**	
		 *	下级岗位列表
		 *	$position_id 传入的岗位id  =0时为所有岗位
		 *	$string   岗位name前置字符
		 *	$me=1时  包括自身岗位
		 *	$notin   被排除在外的岗位id（包括他的下级岗位）
		 **/
		function getPositiontList($position_id=0,$string='',$me=0,$notin=0) {
			$array = array();
			$first = empty($position_id) ? '' : $string;
			if($me==1){
				if($me = M('position')->where('position_id = %d',$position_id)->find()){
					$array[$me['position_id']] = array('position_id'=>$me['position_id'],'name'=>$first.$me['name'],'en_name'=>$first.$me['en_name']);
				}
			}
			$rows = M('position')->where('parent_id = %d',$position_id)->select();
			foreach($rows as $v){
				if($v['position_id'] != $notin){
					$array[$v['position_id']] = array('position_id'=>$v['position_id'],'parent_id'=>$v['parent_id'],'name'=>$first.$v['name'],'en_name'=>$first.$v['en_name']);
					$str = !empty($string) ? "&nbsp;&nbsp;&nbsp;".$string : '';
					$array = array_merge($array,$this->getPositiontList($v['position_id'],$str,0,$notin));
				}
			}
			return $array;
		}
		
		/*
		*获取 事业部
		*@by Jason
		*/
		function getUnits(){
			$units = M('BusinessUnit')->where('del_flag = 0 and status = 1')->select();
			return $units;
		}
		
		/*
		*获取 Office
		*@by Jason
		*/
		
		function getOffices(){
			$offices = M('Office')->where('del_flag = 0 and status = 1')->select();
			return $offices;
		}
	
		/*
		*获取 职级
		*@by Jason
		*/
		function getPoslevel(){
			$poslevels = M('Poslevel')->where('del_flag = 0 and status = 1')->select();
			return $poslevels;
		}
		//获取国家
		function getCountry(){
			$countrys = M('Country')->where('del_flag = 0 and status = 1')->select();
			return $countrys;
		}
		//获取薪资组salary_group
		function getSalarygroup(){
			$salarygroup = M('SalaryGroup')->where('del_flag = 0 and status = 1')->select();
			return $salarygroup;
		}
		
		function positionStatusChange($act, $id){
			$m_position = M('position');
			$act = trim($act);

			if($act == 'stop'){
				if($m_position->where('position_id = %d', $id)->setField('status', 0)){
					return true;
				}else{
					return false;
				}
			}else if($act == 'active'){
				if($m_position->where('position_id = %d', $id)->setField('status', 1)){
					return true;
				}else{
					return false;
				}
			}else{
				echo '参数错误!'; exit;
			}
		}
		
		
//改变position中的值（个人的信息改变） 职位管理中的
	//升职	
		public function saveData($position_id, $data){
			$m_position = M('Position');
			$m_positionhistory = M('PositionHistory');
			$posmsg = $m_position->where(array('position_id'=>$position_id))->save($data);
			if($posmsg){
				$positionnew_info = $m_position->where(array('position_id'=>$position_id))->find();
				$positionnew_info['create_time'] = time();
				$positionnew_info['member_id'] = $_POST['member_id'];
				$m_positionhistory->add($positionnew_info);
				return true;
			}else{
				return false;
			}
		}
	//晋升
		public function saveDatas($positionid, $data){
			$m_position = M('Position');
			$m_positionhistory = M('PositionHistory');
			$posmsg = $m_position->where(array('position_id'=>$positionid))->save($data);
			if($posmsg){
				$positionnew_info = $m_position->where(array('position_id'=>$positionid))->find();
				$positionnew_info['create_time'] = time();
				$positionnew_info['member_id'] = $_POST['member_id'];
				$m_positionhistory->add($positionnew_info);
				return true;
			}else{
				return false;
			}
		}
	//获取薪资组结算日 Structure
		public function accountDay($group_id,$pay_month){
			$m_salary_group = M('SalaryGroup');
			
			$accday['group_id'] = $group_id;
			$accday['start_time'] = array('elt', $pay_month);
			$accday['end_time'] = array('egt', $pay_month);
			$account = $m_salary_group->where($accday)->find();
			if($account['account_id'] == 1){
				$account_days = date('t', $pay_month);
			}else{
				$account_days = $account['account_days'];
			}
			return $account_days;
		}

		//获取指定职位在职位集合中的下级职位id集合
		//$department_id 父级节点
		//$position 职位集
		//$first 是否包含父节点
		public function getSubPositions($position, $positionlist, $first=0) {
			$array = array();
			if($first == 1){
				$temp = array();
				$temp['status'] = $position['status'];
				$temp['position_id'] = $position['position_id'];
				$temp['department_id'] = $position['department_id'];
				$array[] = $temp;
			}
			foreach($positionlist AS $value) {
				if ($position['position_id'] == $value['parent_id']) {
					$temp = array();
					$temp['status'] = $value['status'];
					$temp['position_id'] = $value['position_id'];
					$temp['department_id'] = $value['department_id'];
					$array[] = $temp;
					$array = array_merge($array, $this->getSubPositions($temp, $positionlist));
				}
			}
			return $array;
		}
		
		//获取指定职位在职位集合中的下级职位id集合
		//$department_id 父级节点
		//$position 职位集
		//$first 是否包含父节点
		public function getSubPositionIds($position_id, $positionlist, $first=0) {
			$array = array();
			if($first == 1){
				$array[] = $position_id;
			}
			foreach($positionlist AS $value) {
				if ($position_id == $value['parent_id']) {
					$array[] = $value['position_id'];
					$array = array_merge($array, $this->getSubPositionIds($value['position_id'], $positionlist));
				}
			}
			return $array;
		}
		
		//获取下属职位的职位数量、空缺数量
		public function getSubPosCount($position_id){
			$position = D('Position')->where('position_id = %d', $position_id)->find();
			$positions = D('Position')->where('del_flag = 0')->select();
			$list = $this->getSubPositions($position, $positions, 1);
			return $list;
		}
		
		//获取部门【含子部门总职位数，在职人数，空缺职位】（本部门：职位数，在职，空缺）
		public function getDepartmentPositionCount($department_id){
			$m_position = M('Position');
		
			$department_list = M('Department')->where('del_flag = 0')->select();
			$ids = $this->getSubDepartmentIds($department_id, $department_list, 1);
			
			$where['department_id'] = array('in', $ids);
			$where['del_flag'] = 0;
			$where['status'] = array('neq', 0);
			
			//【含子部门总职位数，在职人数，空缺职位】
			
			$res['sub_total_positions'] = $m_position->where($where)->count();
			
			$where['status'] = 5;
			$res['sub_work_positions'] = $m_position->where($where)->count();
			
			$where['status'] = array('in', '1,2,3,4');
			$res['sub_vacancy_positions'] = $m_position->where($where)->count();
			
			$self_where['department_id'] = $department_id;
			$self_where['del_flag'] = 0;
			$self_where['status'] = array('neq', 0);
			
			
			//（本部门：职位数，在职，空缺）
			
			$res['self_total_positions'] = $m_position->where($self_where)->count();
			
			$self_where['status'] = 5;
			$res['self_work_positions'] = $m_position->where($self_where)->count();
			
			$self_where['status'] = array('in', '1,2,3,4');
			$res['self_vacancy_positions'] = $m_position->where($self_where)->count();
			
			return $res;
		}
		
		//获取指定部门在部门集合中的下级部门id集合
		//$department_id 父级节点
		//$department 部门集
		//$first 是否包含父节点
		
		public function getSubDepartmentIds($department_id, $department, $first=0) {
			$array = array();
			if($first == 1){
				$array[] = $department_id;
			}
			foreach($department AS $value) {
				if ($department_id == $value['parent_id']) {
					$array[] = $value['department_id'];
					$array = array_merge($array, $this->getSubDepartmentIds($value['department_id'], $department));
				}
			}
			return $array;
		}
		
    }
		