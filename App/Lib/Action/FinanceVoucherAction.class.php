<?php 
/**
*财务凭证模块
*
**/
class FinanceVoucherAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
	}

	/**
	 * 财务收支列表
	 * @param 
	 * @author
	 * @return 
	 */
	public function index() {
		$m_receivingorder = M('Receivingorder');//收款单
		$m_paymentorder = M('Paymentorder');//付款单
		$d_role = D('RoleView');

		$order = 'update_time desc,status asc';
		$where = array();

		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		$account_id = $_GET['account_id'] ? intval($_GET['account_id']) : '';
		if($account_id){
			$where['bank_account_id'] = $account_id;
		}

		import('@.ORG.Page');// 导入分页类

		/**
		 * @sql 将回款单和付款单数据联合查询放入一个结果集
		 * @cate = 1 收款
		 * @cate = 2 付款
		 **/

		$receivingorder_sql = $m_receivingorder->field('`receivingorder_id` as `order_id`,pay_time,name,money,creator_role_id,owner_role_id,status,update_time,bank_account_id,1 as cate')->select(false);

		$sql = $m_paymentorder->field('`paymentorder_id` as `order_id`,pay_time,name,money,creator_role_id,owner_role_id,status,update_time,bank_account_id, 2 as cate')->union($receivingorder_sql,true)->select(false);

		$voucher_list = M('')->table($sql.' a')->where($where)->Page($p.','.$listrows)->order($order)->select();

		//计算总收入、总支出
		$m_receivingorder = M('Receivingorder');
		$m_paymentorder = M('Paymentorder');
		//查询最新的初始化余额(审核通过，并且时间最新的)
		$m_account_money = M('AccountMoney');
		$account_info = $m_account_money->where(array('account_id'=>$account_id))->order('create_time desc')->find();
		$where_account = array();
		if($account_info){
			$where_account['update_time'] = array('gt',$account_info['create_time']);
		}
		$where_account['bank_account_id'] = $account_id;
		$where_account['status'] = 1;
		$receivingorder_money = $m_receivingorder->where($where_account)->sum('money');
		$paymentorder_money = $m_paymentorder->where($where_account)->sum('money');
		$balance = '0.00';
		if($account_info){
			$balance = $account_info['money']+$receivingorder_money-$paymentorder_money;
		}else{
			$balance = $receivingorder_money-$paymentorder_money;
		}
		$this->receivingorder_money = $receivingorder_money;
		$this->paymentorder_money = $paymentorder_money;
		$this->balance = number_format($balance,2);

		foreach($voucher_list as $k=>$v){
			$voucher_list[$k]['owner_role_info'] = $d_role->where('role.role_id = %d',$v['owner_role_id'])->find();
		}

		$count = M('')->table($sql.' a')->where($where)->count();
		$this->voucher_list = $voucher_list;

		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('page',$show);// 赋值分页输出
		$this->assign("listrows",$listrows);

		//银行账户
		$this->bank_list = M('BankAccount')->select();

		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 生成凭证
	 * @param 
	 * @author
	 * @return 
	 */
	public function voucherAdd(){
		$m_voucher_account = M('VoucherAccount');
		$m_voucher = M('Voucher');
		$m_voucher_data = M('VoucherData');
		if($this->isPost()){
			//判断是否借贷平衡
			$sum_borrow = 0; //借
			$sum_loan = 0; //贷
			if($_POST['account']){
				foreach($_POST['account'] as $k=>$v){
					$sum_borrow += $v['borrow_money'];
					$sum_loan += $v['loan_money'];
				}
			}
			if($sum_borrow <= 0 || $sum_loan <= 0){
				$this->ajaxReturn('','金额填写有误，请检查后重新提交！',0);
				// $this->error('金额填写有误，请检查后重新提交！');
			}
			if($sum_borrow != $sum_loan){
				$this->ajaxReturn('','借贷不平衡！',0);
				// $this->error('金额填写有误，请检查后重新提交！');
			}
			if($m_voucher->create()){
				$m_voucher->creator_role_id = session('role_id');
				$m_voucher->create_time = time();
				$m_voucher->update_time = time();
				if($voucher_id = $m_voucher->add()){
					$res_voucher_data = true;
					foreach($_POST['account'] as $k=>$v){
						$data = array();
						$data['voucher_id'] = $voucher_id;
						$data['account_id'] = $v['account_id'];
						$data['borrow'] = $v['borrow_money'];
						$data['loan'] = $v['loan_money'];
						$data['remark'] = $v['remark'];
						if(!$m_voucher_data->add($data)){
							$res_voucher_data = false;
						}
					}
					if(!$res_voucher_data){
						$m_voucher->where('voucher_id = %d',$voucher_id)->delete();
						$this->ajaxReturn('','数据保存有误，请重试！',1);
					}
				}else{
					$this->ajaxReturn('','提交失败，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','提交失败，请重试！',0);
			}
		}
		//科目
		$account_array = array();
		$account_array = $m_voucher_account->where(array('is_pause'=>1))->select();
		$this->account_array = $account_array;
		//凭证字
		//期数

		$this->display();
	}

	/**
	 * 凭证列表
	 * @param 
	 * @author
	 * @return 
	 */
	public function voucher(){
		$m_voucher = M('Voucher');
		$m_voucher_data = M('VoucherData');
		$m_voucher_account = M('VoucherAccount');
		$d_role = D('RoleView');

		$order = 'update_time desc';
		$where = array();

		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import('@.ORG.Page');// 导入分页类

		$voucher_list = $m_voucher->where($where)->Page($p.','.$listrows)->order($order)->select();
		foreach($voucher_list as $k=>$v){
			$voucher_list[$k]['creator_role_info'] = $d_role->where('role.role_id = %d',$v['creator_role_id'])->find();
			$voucher_data_list = array();
			$voucher_data_list = $m_voucher_data->where('voucher_id = %d',$v['voucher_id'])->select();
			foreach($voucher_data_list as $key=>$val){
				$account_info = $m_voucher_account->where('id = %d',$val['account_id'])->find();
				$voucher_data_list[$key]['account_info'] = $account_info;
			}
			$voucher_list[$k]['voucher_data_list'] = $voucher_data_list;
			$voucher_list[$k]['voucher_data_count'] = count($voucher_data_list);
		}

		$count = $m_voucher->where($where)->count();
		$this->voucher_list = $voucher_list;

		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('page',$show);// 赋值分页输出
		$this->assign("listrows",$listrows);
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 生成凭证
	 * @param 
	 * @author
	 * @return 
	 */
	public function add(){
		$m_voucher_account = M('VoucherAccount');
		$m_voucher = M('Voucher');
		$m_voucher_data = M('VoucherData');
		if ($this->isPost()) {
			//判断是否借贷平衡
			$sum_borrow = 0; //借
			$sum_loan = 0; //贷
			if ($_POST['account']) {
				foreach($_POST['account'] as $k=>$v){
					$sum_borrow += $v['borrow_money'];
					$sum_loan += $v['loan_money'];
				}
			}
			if ($sum_borrow <= 0 || $sum_loan <= 0) {
				$this->ajaxReturn('','金额填写有误，请检查后重新提交！',0);
			}
			if ($sum_borrow != $sum_loan) {
				$this->ajaxReturn('','借贷不平衡！',0);
			}
			if ($m_voucher->create()) {
				$m_voucher->creator_role_id = session('role_id');
				$m_voucher->voucher_date = strtotime($_POST['voucher_date']);
				$m_voucher->create_time = time();
				$m_voucher->update_time = time();
				if ($voucher_id = $m_voucher->add()) {
					$res_voucher_data = true;
					foreach ($_POST['account'] as $k=>$v) {
						$data = array();
						$data['voucher_id'] = $voucher_id;
						$data['account_id'] = $v['account_id'];
						$data['borrow'] = $v['borrow_money'];
						$data['loan'] = $v['loan_money'];
						$data['remark'] = $v['remark'];
						//处理辅助核算
						$assisting_array = array();
						$data['assisting_customer'] = $v['customer_id'];
						$data['assisting_contract'] = $v['contract_id'];
						$data['assisting_product'] = $v['product_id'];
						$data['assisting_other'] = $v['other_id'];
						$data['num'] = $v['num'];
						$data['price'] = $v['price'];
						if (!$m_voucher_data->add($data)) {
							$res_voucher_data = false;
						}
					}
					if ($res_voucher_data == false) {
						$m_voucher->where('voucher_id = %d',$voucher_id)->delete();
						$this->ajaxReturn('','数据保存有误，请重试！',2);
					} else {
						$this->ajaxReturn('','凭证创建成功！',1);
					}
				} else {
					$this->ajaxReturn('','创建失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','创建失败，请重试！',0);
			}
		}
		//科目
		$account_array = array();
		$account_array = $m_voucher_account->where(array('is_pause'=>1))->select();
		$this->account_array = $account_array;
		//凭证字
		$this->parameter_list = M('VoucherParameter')->order('is_default desc')->select();
		//期数
		$this->display();
	}

	/**
	 * 修改凭证
	 * @param 
	 * @author
	 * @return 
	 */
	public function edit(){
		//权限

		$m_voucher_account = M('VoucherAccount');
		$m_voucher = M('Voucher');
		$m_voucher_data = M('VoucherData');
		$m_voucher_parameter = M('VoucherParameter');

		$voucher_id = $_REQUEST['voucher_id'] ? intval($_REQUEST['voucher_id']) : '';
		$voucher_info = $m_voucher->where('voucher_id = %d',$voucher_id)->find();
		$voucher_data_list = $m_voucher_data->where('voucher_id = %d',$voucher_id)->select();
		//是否审核
		
		if ($this->isPost()) {
			//判断是否借贷平衡
			$sum_borrow = 0; //借
			$sum_loan = 0; //贷
			if ($_POST['account']) {
				foreach($_POST['account'] as $k=>$v){
					$sum_borrow += $v['borrow_money'];
					$sum_loan += $v['loan_money'];
				}
			}
			if ($sum_borrow <= 0 || $sum_loan <= 0) {
				$this->ajaxReturn('','金额填写有误，请检查后重新提交！',0);
			}
			if ($sum_borrow != $sum_loan) {
				$this->ajaxReturn('','借贷不平衡！',0);
			}
			if ($m_voucher->create()) {
				$m_voucher->voucher_date = strtotime($_POST['voucher_date']);
				$m_voucher->update_time = time();
				//原数据ID
				$old_ids = array();
				foreach ($voucher_data_list as $k=>$v) {
					$old_ids[] = $v['id'];
				}
				$new_ids = array();
				if ($m_voucher->where('voucher_id = %d',$voucher_id)->save()) {
					$res_voucher_data = true;
					foreach ($_POST['account'] as $k=>$v) {
						$data = array();
						$data['voucher_id'] = $voucher_id;
						$data['account_id'] = $v['account_id'];
						$data['borrow'] = $v['borrow_money'];
						$data['loan'] = $v['loan_money'];
						$data['remark'] = $v['remark'];
						//处理辅助核算
						$assisting_array = array();
						$data['assisting_customer'] = $v['customer_id'];
						$data['assisting_contract'] = $v['contract_id'];
						$data['assisting_product'] = $v['product_id'];
						$data['assisting_other'] = $v['other_id'];
						$data['num'] = $v['num'];
						$data['price'] = $v['price'];
						//添加
						if (empty($v['id'])) {
							if (!$m_voucher_data->add($data)) {
								$res_voucher_data = false;
							}
						} else {
							//修改
							$m_voucher_data->where('id = %d',$v['id'])->save($data);
							$new_ids[] = $v['id'];
						}
					}
					if ($res_voucher_data == false) {
						$this->ajaxReturn('','部分数据保存有误或数据无变化，请核对！',2);
					} else {
						//删除
						$del_ids = array_diff($old_ids,$new_ids);
						if ($del_ids) {
							$m_voucher_data->where(array('id'=>array('in',$del_ids)))->delete();
						}
						$this->ajaxReturn('','凭证修改成功！',1);
					}
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败，请重试！',0);
			}
		}
		//借贷合计
		$total_borrow = '';
		$total_loan = '';
		//数量列是否显示
		$show_num = '';
		$m_customer = M('Customer');
		$m_contract = M('Contract');
		$m_product = M('Product');
		$m_auxiliary_data = M('AuxiliaryData');
		foreach ($voucher_data_list as $k=>$v) {
			//辅助核算内容
			$assisting_message = '';
			$assisting_show_num = '';
			if ($v['assisting_customer']) {
				$customer_name = $m_customer->where('customer_id = %d',$v['assisting_customer'])->getField('name');
				$assisting_message .= $customer_name.'&nbsp;&nbsp;';
			}
			if ($v['assisting_contract']) {
				$contract_name = $m_contract->where('contract_id = %d',$v['assisting_contract'])->getField('number');
				$assisting_message .= $contract_name.'&nbsp;&nbsp;';
			}
			if ($v['assisting_product']) {
				$product_name = $m_product->where('product_id = %d',$v['assisting_product'])->getField('name');
				$assisting_message .= $product_name.'&nbsp;&nbsp;';
			}
			if ($v['assisting_other']) {
				$other_name = $m_auxiliary_data->where('id = %d',$v['assisting_other'])->getField('name');
				$assisting_message .= $other_name.'&nbsp;&nbsp;';
			}
			$voucher_data_list[$k]['assisting_message'] = $assisting_message;
			if ($v['num'] > 0 || $v['price'] > 0) {
				$show_num = 1;
				$assisting_show_num = 1;
			}
			$voucher_data_list[$k]['assisting_show_num'] = $assisting_show_num;
			//科目计量单位
			$account_info = $m_voucher_account->where('id = %d',$v['account_id'])->find();
			$voucher_data_list[$k]['account_info'] = $account_info;
			//借贷金额
			$voucher_data_list[$k]['borrow_val'] = $v['borrow'] != '0.00' ? $v['borrow']*100 : '';
			$voucher_data_list[$k]['loan_val'] = $v['loan'] != '0.00' ? $v['loan']*100 : '';

			//总借贷金额
			$total_borrow += $v['borrow'];
			$total_loan += $v['loan'];
		}
		$voucher_info['total_borrow'] = $total_borrow;
		$voucher_info['total_borrow_val'] = $total_borrow*100;
		$voucher_info['total_loan'] = $total_loan;
		$voucher_info['total_loan_val'] = $total_loan*100;
		$this->show_num = $show_num;
		//凭证字
		$voucher_info['mark_id'] = $m_voucher_parameter->where(array('name'=>$voucher_info['mark']))->getField('parameter_id');

		$this->voucher_info = $voucher_info;
		$this->voucher_data_list = $voucher_data_list;
		$this->voucher_id = $voucher_id;
		//科目
		$account_array = array();
		$account_array = $m_voucher_account->where(array('is_pause'=>1))->select();
		$this->account_array = $account_array;
		//凭证字
		$this->parameter_list = $m_voucher_parameter->order('is_default desc')->select();
		//期数
		$this->display();
	}

	/**
	 * 通过科目ID，获取自定义辅助参数
	 * @param 
	 * @author
	 * @return 
	 */
	public function getAuxiliaryByAccount(){
		//判断权限
		if ($this->isPost()) {
			$m_voucher_account = M('VoucherAccount');
			$m_auxiliary = M('Auxiliary');
			$m_auxiliary_data = M('AuxiliaryData');
			$m_voucher_data = M('VoucherData');
			$m_customer = M('Customer');
			$m_contract = M('Contract');
			$m_product = M('Product');

			$account_id = $_REQUEST['account_id'] ? intval($_REQUEST['account_id']) : '';
			$data_id = $_REQUEST['data_id'] ? intval($_REQUEST['data_id']) : '';
			if (!$account_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			if ($data_id) {
				$voucher_data_info = $m_voucher_data->where('id = %d',$data_id)->find();
			}
			//辅助核算
			$assisting = $m_voucher_account->where(array('id'=>$account_id))->getField('assisting');
			$assisting_val = !empty($assisting) ? array_filter(explode(',', $assisting)) : array();
			
			if ($assisting_val) {
				$assisting_list = array();
				foreach ($assisting_val as $k=>$v) {
					$assisting_list[$k]['id'] = $v;
					$assisting_list[$k]['name'] = $m_auxiliary->where('auxiliary_id = %d',$v)->getField('name');

					if ($v == 1 && $voucher_data_info['assisting_customer']) {
						$customer_name = $m_customer->where('customer_id = %d',$voucher_data_info['assisting_customer'])->getField('name');
						$assisting_list[$k]['assisting_id'] = $voucher_data_info['assisting_customer'];
						$assisting_list[$k]['assisting_val'] = $customer_name;
					} elseif ($v == 2 && $voucher_data_info['assisting_contract']) {
						$contract_name = $m_contract->where('contract_id = %d',$voucher_data_info['assisting_contract'])->getField('number');
						$assisting_list[$k]['assisting_id'] = $voucher_data_info['assisting_contract'];
						$assisting_list[$k]['assisting_val'] = $contract_name;
					} elseif ($v == 3 && $voucher_data_info['assisting_product']) {
						$product_name = $m_product->where('product_id = %d',$voucher_data_info['assisting_product'])->getField('name');
						$assisting_list[$k]['assisting_id'] = $voucher_data_info['assisting_product'];
						$assisting_list[$k]['assisting_val'] = $product_name;
					} elseif ($voucher_data_info['assisting_other']) {
						$other_name = $m_auxiliary_data->where('id = %d',$voucher_data_info['assisting_other'])->getField('name');
						$assisting_list[$k]['assisting_id'] = $voucher_data_info['assisting_other'];
						$assisting_list[$k]['assisting_val'] = $other_name;
					} else {
						$assisting_list[$k]['assisting_val'] = '';
					}
				}
				$this->ajaxReturn($assisting_list,'success',1);
			}
		} else {
			$this->ajaxReturn('','参数错误！',0);
		}
	}

	/**
	 * 科目dialog
	 * @param 
	 * @author
	 * @return 
	 */
	public function accountList() {
		$this->display();
	}

}