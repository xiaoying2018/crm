<?php
class AccountSettingAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(''),
			'allow'=>array('')
		);
		B('Authenticate',$action);
	}

	/**
	 * 财务科目
	 * @param
	 * @author
	 * @return 
	 */
	public function account(){
		//判断权限
		
		$m_account = M('VoucherAccount');
		$m_auxiliary = M('Auxiliary');
		$where = array();
		$by = $_GET['by'] ? intval($_GET['by']) : 1;
		$by_array = array('1','2','3','4','5');
		if (!in_array($by,$by_array)) {
			alert('error','参数错误！',U('setting/account'));
		}
		$account_array = array();
		switch ($by) {
			case 1 : $type_name = '资产';break;
			case 2 : $type_name = '负债';break;
			case 3 : $type_name = '权益';break;
			case 4 : $type_name = '成本';break;
			case 5 : $type_name = '损益';break;
		}
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		if ($_GET['listrows']) {
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		} else {
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import('@.ORG.Page');// 导入分页类

		$account_list = $m_account->where($where)->page($p.','.$listrows)->order('code asc')->select();
		$count = $m_account->where($where)->count();
		foreach ($account_list as $k=>$v) {
			if ($by == substr($v['code'],0,1)) {
				$v['type_name'] = $type_name;

				$auxiliary_name = array();
				//辅助核算
				$auxiliary_list = $m_auxiliary->where(array('auxiliary_id'=>array('in',explode(',',$v['assisting']))))->select();
				foreach ($auxiliary_list as $key=>$val) {
					$auxiliary_name[] = $val['name'];
				}
				$v['auxiliary_name'] = implode('/',$auxiliary_name);

				$account_array[] = $v;
			}
		}
		$this->account_list = $account_array;

		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('page',$show);// 赋值分页输出
		$this->assign("listrows",$listrows);

		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 财务科目（添加）
	 * @param 
	 * @author
	 * @return 
	 */
	public function accountAdd(){
		//判断权限
		
		$m_account = M('VoucherAccount');
		if ($_GET['parent_id']) {
			$parent_id = intval($_GET['parent_id']);
			$account_parent = $m_account->where(array('id'=>$parent_id))->find();
			$this->parent_id = $parent_id;
			$this->parent_name = $account_parent['code'].'&nbsp;&nbsp;'.$account_parent['name'];
			$this->parent_code = $account_parent['code'];

			$where['code']  = array(array('like','%'.$account_parent['code'].'%'),array('neq',$account_parent['code']),'and');
			$max_num = $m_account->where($where)->count();
			$this->max_num = str_pad($max_num+1,2,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
		}
		$by = $_GET['by'] ? intval($_GET['by']) : 1;
		$by_array = array('1','2','3','4','5');
		if (!in_array($by,$by_array)) {
			alert('error','参数错误！',U('setting/account'));
		}
		switch ($by) {
			case 1 : $type_name = '资产';$type = 1;break;
			case 2 : $type_name = '负债';$type = 2;break;
			case 3 : $type_name = '权益';$type = 2;break;
			case 4 : $type_name = '成本';$type = 1;break;
			case 5 : $type_name = '损益';$type = 2;break;
		}
		$this->type_name = $type_name;
		$this->type = $type;

		if ($this->isPost()) {
			if ($m_account->create()) {
				if (trim($_POST['prefixion'])) {
					$code = trim($_POST['prefixion']).trim($_POST['max_num']);
				} else {
					$code = trim($_POST['code']);
				}
				$m_account->code = $code;
				$m_account->update_time = time();
				$m_account->create_role_id = session('role_id');
				$m_account->is_item = 0;
				$m_account->is_qtyaux = 0;
				$is_value = $_POST['is_val'] ? $_POST['is_val'] : array();
				foreach ($is_value as $k=>$v) {
					if ($v == 1) {
						$m_account->is_item = 1;
					}
					if ($v == 2) {
						$m_account->is_qtyaux = 1;
					}
				}
				//辅助核算类型
				if (in_array('1',$is_value)) {
					$m_account->assisting = ','.implode(',',$_POST['assisting']).',';
				}
				if ($m_account->add()) {
					$by = substr($code,0,1);
					alert('success','添加成功！',$_SERVER['HTTP_REFERER'].'&by='.$by);
				} else {
					alert('error','添加失败，请重试！',$_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error','添加失败，请重试！',$_SERVER['HTTP_REFERER']);
			}
		}
		//辅助核算
		$this->auxiliary_list = M('Auxiliary')->where(array('auxiliary_id'=>array('gt',100)))->select();
		$this->display();
	}

	/**
	 * 财务科目（编辑）
	 * @param 
	 * @author
	 * @return 
	 */
	public function accountEdit(){
		//判断权限

		$m_account = M('VoucherAccount');
		$m_voucher_data = M('VoucherData');
		$id = intval($_REQUEST['id']);
		$account_info = $m_account->where(array('id'=>$id))->find();
		if (!$account_info) {
			alert('error','数据不存在或已删除！',$_SERVER['HTTP_REFERER']);
		}
		//科目被使用后不能编辑
		$data_info = $m_voucher_data->where('account_id = %d',$id)->find();
		if ($data_info) {
			echo '<div class="alert alert-error">该科目已被使用，不能编辑！</div>';die();
			// alert('error','该科目已被使用，不能编辑！',$_SERVER['HTTP_REFERER']);
		}
		//查询科目
		$account_list = $m_account->where(array('id'=>array('neq',$id)))->order('code asc')->select();
		$this->account_list = $account_list;

		//父级科目信息
		if ($account_info['parent_id']) {
			$parent_id = $account_info['parent_id'];
			$account_parent = $m_account->where(array('id'=>$parent_id))->find();
			$parent_code = $account_parent['code'];

			$where['code']  = array(array('like','%'.$account_parent['code'].'%'),array('neq',$account_parent['code']),'and');
			$max_num = $m_account->where($where)->count();
			$this->max_num = str_pad($max_num+1,2,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）

			$max_num = substr($account_info['code'], -2);
		} else {
			$parent_id = 0;
			$max_num = '';
			$parent_code = $account_info['code'];
		}
		$this->max_num = $max_num;
		$this->parent_id = $parent_id;
		$this->parent_code = $parent_code;

		$by = $_GET['by'] ? intval($_GET['by']) : 1;
		$by_array = array('1','2','3','4','5');
		if (!in_array($by,$by_array)) {
			alert('error','参数错误！',U('setting/account'));
		}
		switch ($by) {
			case 1 : $type_name = '资产';$status = 1;break;
			case 2 : $type_name = '负债';$status = 2;break;
			case 3 : $type_name = '权益';$status = 2;break;
			case 4 : $type_name = '成本';$status = 1;break;
			case 5 : $type_name = '损益';$status = 2;break;
		}
		$this->type_name = $type_name;
		$this->status = $status;
		
		if ($this->isPost()) {
			if ($m_account->create()) {
				$m_account->update_time = time();
				$is_value = $_POST['is_val'] ? $_POST['is_val'] : array();
				$m_account->is_item = 0;
				$m_account->is_qtyaux = 0;
				foreach ($is_value as $k=>$v) {
					if ($v == 1) {
						$m_account->is_item = 1;
					}
					if ($v == 2) {
						$m_account->is_qtyaux = 1;
					} 
				}
				//辅助核算类型
				if (in_array('1',$is_value)) {
					$m_account->assisting = ','.implode(',',$_POST['assisting']).',';
				}
				if ($m_account->where('id = %d',$id)->save()) {
					alert('success','修改成功！',$_SERVER['HTTP_REFERER']);
				} else {
					alert('error','修改失败，请重试！',$_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error','修改失败，请重试！',$_SERVER['HTTP_REFERER']);
			}
		}
		//辅助核算数组
		$assisting_array = array_filter(explode(',',$account_info['assisting']));
		$this->account_info = $account_info;
		$this->assisting_array = $assisting_array;
		//辅助核算
		$auxiliary_list = M('Auxiliary')->where(array('auxiliary_id'=>array('gt',100)))->select();
		$this->auxiliary_list = $auxiliary_list;
		$this->display();
	}

	/**
	 * 查找上级科目，返回id和名称
	 * @param 
	 * @author
	 * @return 
	 */
	public function accountParent(){
		//判断权限

		if ($this->isAjax()) {
			$code = $this->_get('code','intval');
			$account_id = $this->_get('account_id','intval');
			if ($code) {
				$m_account = M('VoucherAccount');
				$where = array();
				$where['code'] = $code;
				if ($account_id) {
					$where['id'] = array('neq',$account_id);
				}
				$result = $m_account->where($where)->find();
				if ($result) {
					$this->ajaxReturn('','error',3); //编码已存在
				} else {
					$parent_code = substr($code,0,-2);
					$account_info = $m_account->where('code = %d',$parent_code)->find();
					if ($account_info) {
						$this->ajaxReturn($account_info,'success',1);
					} else {
						$this->ajaxReturn('','error',2);
					}
				}
			} else {
				$this->ajaxReturn('','error',2);
			}
		}
	}

	/**
	 * 财务科目（删除）
	 * @param 
	 * @author
	 * @return 
	 */
	public function accountDel(){
		//判断权限

		$m_account = M('VoucherAccount');
		$m_activity = M('FinanceCategory');
		if ($this->isAjax()) {
			$account_ids = $_POST['account_id'];
			//需删除的科目
			$del_account_ids = array();
			$activity_account_ids = $m_activity->getField('account_id',true);
			foreach ($account_ids as $value) {
				//判断财务活动里是否使用
				if (!in_array($value,$activity_account_ids)) {
					$del_account_ids[] = $value;
				}
			}
			if (empty($del_account_ids) || count($account_ids) != count($del_account_ids)) {
				$message = '部分科目在财务活动中已使用，不能删除！';
			} else {
				$message = '删除成功！';
			}

			if ($m_account->where(array('id'=>array('in',$del_account_ids)))->delete()) {
				$this->ajaxReturn('',$message,1);
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
		}	
	}

	/**
	 * 财务科目暂停、启用
	 * @param 
	 * @author
	 * @return 
	 */
	public function accountPause(){
		//判断权限

		if ($this->isAjax()) {
			$account_id = intval($_GET['id']);
			if ($account_id) {
				$m_account = M('VoucherAccount');
				$pause = $m_account->where('id = %d',$account_id)->getField('is_pause');
				if ($pause == 1) {
					$result = $m_account->where('id = %d',$account_id)->setField('is_pause',0);
					if ($result) {
						$this->ajaxReturn('1','开启成功！',1);
					} else {
						$this->ajaxReturn('1','开启失败！',2);
					}
				} else {
					$result = $m_account->where('id = %d',$account_id)->setField('is_pause',1);
					if ($result) {
						$this->ajaxReturn('2','暂停成功！',1);
					} else {
						$this->ajaxReturn('3','暂停失败！',2);
					}
				}
			} else {
				$this->ajaxReturn('','参数错误！',2);
			}
		}
	}

	public function accountImport() {

	}

	public function accountExport() {
		C('OUTPUT_ENCODE', false);
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("voucher account");    
		$objProps->setSubject("voucher account list");    
		$objProps->setDescription("voucher account list");    
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		   
		$objActSheet->setTitle('Sheet1');
		$objActSheet->setCellValue('A1', '编码');
		$objActSheet->setCellValue('B1', '名称');
		$objActSheet->setCellValue('C1', '类别');
		$objActSheet->setCellValue('D1', '余额方向');

        $list = M('VoucherAccount')->order('code asc')->select();
        foreach ($list as $key => $value) {
        	$objActSheet->setCellValue('A'.($key+2), $value['code']);
        	$objActSheet->setCellValue('B'.($key+2), $value['name']);
        	
        	switch ($value['category']) {
        		case '10':
        			$type = "流动资产";
        			break;
        		case '11':
        			$type = "非流动资产";
        			break;
        		case '20':
        			$type = "流动负债";
        			break;
        		case '21':
        			$type = "非流动负债";
        			break;
        		case '30':
        			$type = "所有者权益";
        			break;
        		case '40':
        			$type = "成本";
        			break;
        		case '50':
        			$type = "营业收入";
        			break;
        		case '51':
        			$type = "其他收益";
        			break;
        		case '52':
        			$type = "期间费用";
        			break;
        		case '53':
        			$type = "其他损失";
        			break;
        		case '54':
        			$type = "营业成本及税金";
        			break;
        		case '55':
        			$type = "以前年度损益调整";
        			break;
        		case '56':
        			$type = "所得税";
        			break;
        		case '57':
        			$type = "表外科目";
        			break;

        		default:
        			break;
        	}
        	$objActSheet->setCellValue('C'.($key+2), $type);
        	if ($value['type'] == 1) {
        		$objActSheet->setCellValue('D'.($key+2), '借');
        	} elseif ($value['type'] == 2) {
        		$objActSheet->setCellValue('D'.($key+2), '贷');
        	}	
        }
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		//ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_caiwukemu_".date('Y-m-d',mktime()).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
		session('export_status', 0);
	}

	/**
	 * 财务活动
	 * @param 
	 * @author
	 * @return 
	 */
	public function activity(){
		//判断权限
		
		$m_activity = M('FinanceCategory');
		$m_account = M('VoucherAccount');

		$where = array();
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		if ($_GET['listrows']) {
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		} else {
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import('@.ORG.Page');// 导入分页类

		$activity_list = $m_activity->where($where)->page($p.','.$listrows)->order('id desc')->select();
		foreach ($activity_list as $k=>$v) {
			$account_list = $m_account->where(array('status'=>1,'id'=>array('in',explode(',', $v['account_ids']))))->field('code,name')->select();
			$account_array = array();
			foreach ($account_list as $key=>$val) {
				if($key < 5){
					$account_array[] = $val['code'].'|'.$val['name'];
				}
			}
			$activity_list[$k]['accounts'] = implode(',',$account_array);
			if (sizeof($account_list) > 4) {
				$activity_list[$k]['accounts'] .= "...";
			}
		}
		$count = $m_activity->where($where)->count();
		$this->activity_list = $activity_list;

		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('page',$show);// 赋值分页输出
		$this->assign("listrows",$listrows);

		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 财务活动(添加)
	 * @param 
	 * @author
	 * @return 
	 */
	public function activityAdd(){
		$m_activity = M('FinanceCategory');
		$m_account = M('VoucherAccount');

		if ($this->isPost()) {
			$account_ids = $_POST['account_id'];
			if (sizeof($_POST['account_id']) < 2) {
				$this->ajaxReturn('','请至少选择两个科目！',0);
			}
			if ($m_activity->create()) {
				$m_activity->update_time = time();
				$m_activity->account_ids = ','.implode(',', $_POST['account_id']).',';
				if ($activity_id = $m_activity->add()) {
					$this->ajaxReturn('','添加成功！',1);
				} else {
					$this->ajaxReturn('','添加失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','添加失败，请重试！',0);
			}
		}
		$account_list = $m_account->where(array('is_pause'=>1))->order('code asc')->select();
		$this->account_list = $account_list;
		//活动编号
		$max_activity = $m_activity->count();
		$this->code = str_pad($max_activity+1,3,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）

		$this->display();
	}

	/**
	 * 财务活动(编辑)
	 * @param 
	 * @author
	 * @return 
	 */
	public function activityEdit(){
		$m_activity = M('FinanceCategory');
		$m_account = M('VoucherAccount');
		$activity_id = intval($_REQUEST['id']);
		$activity_info = $m_activity->where(array('id'=>$activity_id))->find();
		if (!$activity_info) {
			alert('error','数据不存在或已删除！',$_SERVER['HTTP_REFERER']);
		}

		if ($this->isPost()) {
			if (sizeof($_POST['account_id']) < 2) {
				$this->ajaxReturn('','请至少选择两个科目！',0);
			}
			$account_ids = array();
			foreach ($_POST['account_id'] as $v) {
				$account_ids[] = intval($v);
			}
			if ($m_activity->create()) {
				$m_activity->update_time = time();
				$m_activity->account_ids = ','.implode(',', $account_ids).',';
				if ($m_activity->save()) {
					$this->ajaxReturn('','修改成功！',1);
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败，请重试！',0);
			}
		}
		$account_list = $m_account->where(array('is_pause'=>1))->order('code asc')->select();
		$this->account_list = $account_list;

		$activity_account_list = $m_account->where(array('id'=>array('in',explode(',', $activity_info['account_ids']))))->select();
		$this->activity_account_list = $activity_account_list;
		$this->activity_account_count = sizeof($activity_account_list);

		$this->activity_info = $activity_info;
		$this->display();
	}

	/**
	 * 财务科目（删除）
	 * @param 
	 * @author
	 * @return 
	 */
	public function activityDel(){
		//判断权限

		$m_activity = M('FinanceCategory');
		$m_r_activity_account = M('RActivityAccount');
		if ($this->isAjax()) {
			$activity_ids = $_POST['activity_id'];
			//需删除的科目
			$del_activity_ids = array();

			// foreach($account_ids as $value){
			// 	//判断财务活动里是否使用

			// }

			if ($m_activity->where(array('id'=>array('in',$del_activity_ids)))->delete()) {
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
		}	
	}

	/**
	 * 财务科目暂停、启用
	 * @param 
	 * @author
	 * @return 
	 */
	public function activityPause(){
		//判断权限

		if ($this->isAjax()) {
			$activity_id = intval($_GET['id']);
			if ($activity_id) {
				$m_activity = M('FinanceCategory');
				$pause = $m_activity->where('id = %d',$activity_id)->getField('is_pause');
				if ($pause == 1) {
					$result = $m_activity->where('id = %d',$activity_id)->setField('is_pause',0);
					if ($result) {
						$this->ajaxReturn('1','启用成功！',1);
					} else {
						$this->ajaxReturn('1','启用失败！',2);
					}
				} else {
					$result = $m_activity->where('id = %d',$activity_id)->setField('is_pause',1);
					if ($result) {
						$this->ajaxReturn('2','停用成功！',1);
					} else {
						$this->ajaxReturn('3','停用失败！',2);
					}
				}
			} else {
				$this->ajaxReturn('','参数错误！',2);
			}
		}
	}

	/**
	 * 验证身份（通过账户密码）
	 * @param 
	 * @author
	 * @return 
	 */
	public function verify_identity(){
		//判断权限
		$below_ids = getPerByAction('setting','initialize');
		if (!in_array(session('role_id'),$below_ids)) {
			$this->ajaxReturn('','您没有此权利！',0);
		}

		$user_info = M('User')->where('role_id = %d',session('role_id'))->field('password,salt')->find();
		if ($this->isAjax()) {
			if ($user_info['password'] == md5(trim($_POST['the_password']) . $user_info['salt'])) {
				session('initialize_money',1);
				$this->ajaxReturn('','身份验证通过！',1);
			} else {
				$this->ajaxReturn('','密码错误，请重新输入！',0);
			}
		}
	}

	/**
	 * 初始化账户余额
	 * @param 
	 * @author
	 * @return 
	 */
	public function initialize(){
		//判断权限
		$below_ids = getPerByAction('setting','initialize');
		if (!in_array(session('role_id'),$below_ids)) {
			unset($_SESSION['initialize_money']);
			$this->ajaxReturn('','您没有此权利！',0);
		}
		if ($_SESSION['initialize_money']) {
			$m_account_money = M('AccountMoney');
			if ($this->isAjax()) {
				$initialize_money = trim($_POST['initialize_money']);
				$account_id = intval($_POST['account_id']);
				if (!$account_id) {
					$this->ajaxReturn('','参数错误！',0);
				}
				if ($m_account_money->create()) {
					$m_account_money->money = $initialize_money;
					$m_account_money->create_role_id = session('role_id');
					$m_account_money->create_time = time();
					if ($m_account_money->add()) {
						$this->ajaxReturn('','初始化成功！',1);
					} else {
						$this->ajaxReturn('','操作失败，请重试',0);
					}
				} else {
					unset($_SESSION['initialize_money']);
					$this->ajaxReturn('','操作失败，请重试',0);
				}
			}
		} else {
			$this->ajaxReturn('','身份验证未通过，请重新输入您的账户密码！',2);
		}
	}

	/**
	 * 辅助核算模块
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliary(){
		$m_auxiliary = M('Auxiliary');
		$auxiliary_list = $m_auxiliary->select();
		foreach ($auxiliary_list as $k=>$v) {
			switch ($v['auxiliary_id']) {
				case 1 : $i_class = 'fa fa-user';$alink = U('customer/index');break;
				case 2 : $i_class = 'fa fa-list-alt';$alink = U('contract/index');break;
				case 3 : $i_class = 'fa fa-inbox';$alink = U('product/index');break;
				default : $i_class = 'fa fa-cube';$alink = U('account_setting/auxiliarylist','id=').$v['auxiliary_id'];break;
			}
			$auxiliary_list[$k]['i_class'] = $i_class;
			$auxiliary_list[$k]['alink'] = $alink;
		}
		$this->auxiliary_list = $auxiliary_list;
		$this->display();
	}

	/**
	 * 辅助核算模块(添加)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryAdd(){
		$m_auxiliary = M('Auxiliary');
		if ($this->isPost()) {
			if ($m_auxiliary->create()) {
				$m_auxiliary->create_role_id = session('role_id');
				$m_auxiliary->create_time = time();
				$m_auxiliary->update_time = time();
				if ($m_auxiliary->add()) {
					$this->ajaxReturn('','辅助核算类型添加成功！',1);
				} else {
					$this->ajaxReturn('','辅助核算类型添加失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','辅助核算类型添加失败，请重试！',0);
			}
		}
		$this->display();
	}

	/**
	 * 辅助核算模块(编辑)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryEdit(){
		$m_auxiliary = M('Auxiliary');
		$auxiliary_id = $_REQUEST['auxiliary_id'] ? intval($_REQUEST['auxiliary_id']) : 0;
		$auxiliary_info = $m_auxiliary->where('auxiliary_id = %d',$auxiliary_id)->find();
		if (!$auxiliary_info) {
			$this->ajaxReturn('','数据不存在或已删除！',0);
		}
		if ($this->isPost()) {
			if ($m_auxiliary->create()) {
				$m_auxiliary->update_time = time();
				if ($m_auxiliary->where('auxiliary_id = %d',$auxiliary_id)->save()) {
					$this->ajaxReturn('','辅助核算类型修改成功！',1);
				} else {
					$this->ajaxReturn('','辅助核算类型修改失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','辅助核算类型修改失败，请重试！',0);
			}
		}
		$this->auxiliary_info = $auxiliary_info;
		$this->display();
	}

	/**
	 * 辅助核算模块(列表)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryList(){
		$m_auxiliary = M('Auxiliary');
		$m_auxiliary_data = M('AuxiliaryData');
		$auxiliary_id = $_GET['id'] ? intval($_GET['id']) : '';
		$auxiliary_info = $m_auxiliary->where('auxiliary_id = %d',$auxiliary_id)->find();
		if (!$auxiliary_info) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$auxiliary_list = $m_auxiliary_data->where('auxiliary_id = %d',$auxiliary_id)->select();
		$this->auxiliary_list = $auxiliary_list;
		$this->display();
	}

	/**
	 * 辅助核算模块(dialog列表)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryDialog(){
		$m_auxiliary = M('Auxiliary');
		$m_auxiliary_data = M('AuxiliaryData');
		$auxiliary_id = $_GET['id'] ? intval($_GET['id']) : '';
		$auxiliary_info = $m_auxiliary->where('auxiliary_id = %d',$auxiliary_id)->find();
		if (!$auxiliary_info) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$where = array();
		$where['auxiliary_id'] = $auxiliary_id;

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
		$auxiliary_list = $m_auxiliary_data->where($where)->select();
		$count = $m_auxiliary_data->where($where)->count();
		$this->auxiliary_list = $auxiliary_list;
		$this->search_field = $_REQUEST;//搜索信息
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());
		$this->display();
	}

	/**
	 * 辅助核算数据(添加)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryDataAdd(){
		$m_auxiliary = M('Auxiliary');
		$m_auxiliary_data = M('AuxiliaryData');
		$auxiliary_id = $_REQUEST['auxiliary_id'] ? intval($_REQUEST['auxiliary_id']) : '';
		if (!$auxiliary_id) {
			$this->ajaxReturn('','参数错误！',0);
		}
		if ($this->isPost()) {
			if ($m_auxiliary_data->create()) {
				$name = trim($_POST['name']);
				$code = trim($_POST['code']);
				if (!$name) {
					$this->ajaxReturn('','名称不能为空！',0);
				}
				if (!$code) {
					$this->ajaxReturn('','编码不能为空！',0);
				}
				$m_auxiliary_data->create_role_id = session('role_id');
				$m_auxiliary_data->create_time = time();
				$m_auxiliary_data->update_time = time();
				if ($m_auxiliary_data->add()) {
					$this->ajaxReturn('','辅助核算类型添加成功！',1);
				} else {
					$this->ajaxReturn('','辅助核算类型添加失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','辅助核算类型添加失败，请重试！',0);
			}
		}
		$this->display();
	}

	/**
	 * 辅助核算数据(添加)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryDataEdit(){
		$m_auxiliary = M('Auxiliary');
		$m_auxiliary_data = M('AuxiliaryData');
		$data_id = $_REQUEST['data_id'] ? intval($_REQUEST['data_id']) : '';
		$data_info = $m_auxiliary_data->where('id = %d',$data_id)->find();
		if (!$data_info) {
			$this->ajaxReturn('','数据不存在或已删除！',0);
		}
		if ($this->isPost()) {
			if ($m_auxiliary_data->create()) {
				$name = trim($_POST['name']);
				$code = trim($_POST['code']);
				if (!$name) {
					$this->ajaxReturn('','名称不能为空！',0);
				}
				if (!$code) {
					$this->ajaxReturn('','编码不能为空！',0);
				}
				$m_auxiliary_data->update_time = time();
				if ($m_auxiliary_data->where('id = %d',$data_id)->save()) {
					$this->ajaxReturn('','辅助核算类型添加成功！',1);
				} else {
					$this->ajaxReturn('','辅助核算类型添加失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','辅助核算类型添加失败，请重试！',0);
			}
		}
		$this->data_info = $data_info;
		$this->display();
	}

	/**
	 * 辅助核算数据(添加)
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryDataDel(){
		$m_auxiliary = M('Auxiliary');
		$m_auxiliary_data = M('AuxiliaryData');
		if ($_POST['data_id']) {
			$id_array = $_POST['data_id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['data_id'];
			}
			$del_array = array();
			$error_res = false;
			//判断是否使用
			foreach ($id_array as $k=>$v) {

				$parameter_info = $m_parameter->where('parameter_id = %d',$v)->find();
				if ($m_voucher->where(array('mark'=>$parameter_info['name']))->find()) {
					$error_res = true;
				} else {
					$del_array[] = $v;
				}
			}

			if ($m_auxiliary_data->where('id in (%s)', implode(',', $del_array))->delete()) {
				if ($error_res) {
					$this->ajaxReturn('','部分数据已被使用，无法删除！',0);
				} else {
					$this->ajaxReturn('','删除成功！',1);
				}
			} else {
				if ($error_res) {
					$this->ajaxReturn('','数据已被使用，删除失败！',0);
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			}
		}
		$this->display();
	}

	/**
	 * 辅助核算数据（暂停、启用）
	 * @param 
	 * @author
	 * @return 
	 */
	public function auxiliaryPause(){
		//判断权限

		if ($this->isAjax()) {
			$id = intval($_GET['id']);
			if ($id) {
				$m_auxiliary_data = M('AuxiliaryData');
				$pause = $m_auxiliary_data->where('id = %d',$id)->getField('is_pause');
				if ($pause == 1) {
					$result = $m_auxiliary_data->where('id = %d',$id)->setField('is_pause',0);
					if ($result) {
						$this->ajaxReturn('1','启用成功！',1);
					} else {
						$this->ajaxReturn('1','启用失败！',2);
					}
				} else {
					$result = $m_auxiliary_data->where('id = %d',$id)->setField('is_pause',1);
					if ($result) {
						$this->ajaxReturn('2','停用成功！',1);
					} else {
						$this->ajaxReturn('3','停用失败！',2);
					}
				}
			} else {
				$this->ajaxReturn('','参数错误！',2);
			}
		}
	}

	/**
	 * 凭证字
	 * @param 
	 * @author
	 * @return 
	 */
	public function parameter(){
		//判断权限
		
		$m_parameter = M('VoucherParameter');
		$where = array();
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		if ($_GET['listrows']) {
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		} else {
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import('@.ORG.Page');// 导入分页类

		$parameter_list = $m_parameter->where($where)->page($p.','.$listrows)->select();
		$count = $m_parameter->where($where)->count();
		$this->parameter_list = $parameter_list;

		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('page',$show);// 赋值分页输出
		$this->assign("listrows",$listrows);
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 凭证字(添加)
	 * @param 
	 * @author
	 * @return 
	 */
	public function parameterAdd(){
		//判断权限
		
		$m_parameter = M('VoucherParameter');
		if ($this->isPost()) {
			$name = trim($_POST['name']);
			$print_card = trim($_POST['print_card']);
			if (!$name) {
				$this->ajaxReturn('','凭证字不能为空！',0);
			}
			//验重
			if ($m_parameter->where('name = "%s"',$name)->find()) {
				$this->ajaxReturn('','凭证字已经存在！',0);
			}
			if (!$print_card) {
				$this->ajaxReturn('','打印标题不能为空！',0);
			}
			if ($m_parameter->create()) {
				$m_parameter->create_time = time();
				$m_parameter->update_time = time();
				$m_parameter->create_role_id = session('role_id');
				if ($parameter_id = $m_parameter->add()) {
					//是否默认处理
					$is_default = intval($_POST['is_default']);
					if ($is_default == 1) {
						$res = $m_parameter->where(array('parameter_id'=>array('neq',$parameter_id)))->setField('is_default','0');
					}
					$this->ajaxReturn('','添加成功！',1);
				} else {
					$this->ajaxReturn('','添加失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','添加失败，请重试！',0);
			}
		}
		$this->display();
	}

	/**
	 * 凭证字(编辑)
	 * @param 
	 * @author
	 * @return 
	 */
	public function parameterEdit(){
		//判断权限
		
		$m_parameter = M('VoucherParameter');
		$m_voucher = M('Voucher');
		$parameter_id = intval($_REQUEST['parameter_id']);
		$parameter_info = $m_parameter->where(array('parameter_id'=>$parameter_id))->find();
		if (!$parameter_info) {
			$this->ajaxReturn('','数据不存在或已删除！',0);
		}

		if ($this->isPost()) {
			$name = trim($_POST['name']);
			$print_card = trim($_POST['print_card']);
			if (!$name) {
				$this->ajaxReturn('','凭证字不能为空！',0);
			}
			//判断凭证字是否启用
			if ($name != $parameter_info['name']) {
				if ($m_voucher->where(array('mark'=>$parameter_info['name']))->find()) {
					$this->ajaxReturn('','凭证字已被使用，不能编辑！',0);
				}
			}
			if (!$print_card) {
				$this->ajaxReturn('','打印标题不能为空！',0);
			}
			if ($m_parameter->create()) {
				$m_parameter->update_time = time();
				if ($m_parameter->where('parameter_id = %d',$parameter_id)->save()) {
					//是否默认处理
					$is_default = intval($_POST['is_default']);
					if ($is_default == 1) {
						$res = $m_parameter->where(array('parameter_id'=>array('neq',$parameter_id)))->setField('is_default','0');
					}
					$this->ajaxReturn('','修改成功！',1);
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败，请重试！',0);
			}
		}
		$this->parameter_info = $parameter_info;
		$this->display();
	}

	/**
	 * 凭证字(删除)
	 * @param 
	 * @author
	 * @return 
	 */
	public function parameterDel(){
		//判断权限
		
		$m_parameter = M('VoucherParameter');
		$m_voucher = M('Voucher');

		if ($_POST['parameter_id']) {
			$id_array = $_POST['parameter_id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['parameter_id'];
			}
			$del_array = array();
			$error_res = false;
			//判断凭证字是否启用
			foreach ($id_array as $k=>$v) {
				$parameter_info = $m_parameter->where('parameter_id = %d',$v)->find();
				if ($m_voucher->where(array('mark'=>$parameter_info['name']))->find()) {
					$error_res = true;
				} else {
					$del_array[] = $v;
				}
			}

			if ($m_parameter->where('parameter_id in (%s)', implode(',', $del_array))->delete()){
				if ($error_res) {
					$this->ajaxReturn('','部分凭证字已被使用，无法删除！',0);
				} else {
					$this->ajaxReturn('','删除成功！',1);
				}
			} else {
				if ($error_res) {
					$this->ajaxReturn('','凭证字已被使用，删除失败！',0);
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			}
		}
		
		$this->display();
	}

	/**
	 * 财务初始余额
	 * @param 
	 * @author
	 * @return 
	 */
	public function balance(){
		//判断权限
		
		$m_account = M('VoucherAccount');
		$where = array();
		$by = $_GET['by'] ? intval($_GET['by']) : 1;
		$by_array = array('1','2','3','4','5');
		if (!in_array($by,$by_array)) {
			alert('error','参数错误！',U('setting/account'));
		}
		$account_array = array();
		switch ($by) {
			case 1 : $type_name = '资产';break;
			case 2 : $type_name = '负债';break;
			case 3 : $type_name = '权益';break;
			case 4 : $type_name = '成本';break;
			case 5 : $type_name = '损益';break;
		}

		$account_list = $m_account->where($where)->order('code asc')->select();
		foreach ($account_list as $k=>$v) {
			if ($by == substr($v['code'],0,1)) {
				$v['type_name'] = $type_name;
				$account_array[] = $v;
				//计算余额
				
			}
		}
		$this->account_list = $account_array;
		$this->alert = parseAlert();
		$this->display();
	}

}