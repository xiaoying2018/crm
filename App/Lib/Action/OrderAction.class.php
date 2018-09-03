<?PHP 
/**
*订单模块
*
**/
class OrderAction extends Action{
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
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	
	/**
	*订单列表页（默认页面）
	*
	**/
	public function index(){
		$m_order = M('Order');
		$m_member = M('Member');
		$order = 'update_time desc';
		$where = array();
		$p = intval($_GET['p'])?intval($_GET['p']):1;
		
		$order_list = $m_order->where($where)->order($order)->select();
		foreach($order_list as $k=>$v){
			$member_info = array();
			$member_info = $m_member->where('member_id = %d',$v['member_id'])->find();
			$order_list[$k]['member_info'] = $member_info;
			//支付状态
			$payment_state_name = '未支付';
			switch($v['payment_state']){
				case 1 : $payment_state_name = '已支付（线上）'; break;
				case 2 : $payment_state_name = '已支付（线下）'; break;
				case 3 : $payment_state_name = '已退还'; break;
			}
			$order_list[$k]['payment_state_name'] = $payment_state_name;
			//审核状态
			$check_state_name = '未接单';
			switch($v['check_state']){
				case 1 : $check_state_name = '已接单'; break;
				case 2 : $check_state_name = '已取消'; break;
			}
			$order_list[$k]['check_state_name'] = $check_state_name;
			//订单阶段
			$status_name = '待确认';
			switch($v['status']){
				case 1 : $status_name = '生产中'; break;
				case 2 : $status_name = '配送中'; break;
				case 3 : $status_name = '已完成'; break;
			}
			$order_list[$k]['status_name'] = $status_name;
		}
		$count = $m_order->where($where)->count();
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import("@.ORG.Page");
		$Page = new Page($count,$listrows);
		$this->listrows = $listrows;
		$Page->parameter = implode('&', $params);
		$this->assign('page', $Page->show());
		$this->order_list = $order_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*订单详情页（客户页面调用）
	*
	**/
	public function view_ajax(){
		$m_order = M('Order');
		$m_r_order_log = M('ROrderLog');
		$m_r_member_log = M('RMemberLog');
		$m_r_order_product = M('ROrderProduct');
		$m_product = M('Product');
		$d_role = D('RoleView');
		$m_log = M('Log');
		$order_id = intval($_POST['id']);
		$member_id = intval($_POST['member_id']);
		if($member_id){
			$order_list = $m_order->where('member_id = %d',$member_id)->select();
			$order_ids = array();
			foreach($order_list as $k=>$v){
				$order_ids[] = $v['order_id'];
				$order_info = array();
				$order_info['code'] = $v['prefixion'].$v['code'];
				$order_info['price'] = $v['price'];
				$order_info['remark'] = $v['remark'];
				//订单状态
				$status_name = '待确认';
				switch($order_info['status']){
					case 1 : $status_name = '生产中'; break;
					case 2 : $status_name = '配送中'; break;
					case 3 : $status_name = '已完成'; break;
				}
				$order_info['status_name'] = $status_name;

				$product_list = array();
				$product_list = $m_r_order_product->where('order_id = %d',$v['order_id'])->select();
				foreach($product_list as $key=>$val){
					$product_info = array();
					$product_info = $m_product->where('product_id = %d',$val['product_id'])->field('name')->find();
					$product_list[$key]['product_info'] = $product_info;
				}
				$order_list[$k]['product_list'] = $product_list;
				$order_list[$k]['order_info'] = $order_info;
			}
			//沟通日志
			$member_log_ids = $m_r_member_log->where('member_id = %d',$member_id)->getField('log_id',true);
			$order_log_ids = $m_r_order_log->where(array('order_id'=>array('in',$order_ids)))->getField('log_id',true);
			//数组合并
			$r_log_ids = array();
			if($member_log_ids && $order_log_ids){
				$r_log_ids = array_merge($member_log_ids,$order_log_ids);
			}else{
				if($member_log_ids){
					$r_log_ids = $member_log_ids;
				}elseif($order_log_ids){
					$r_log_ids = $order_log_ids;
				}
			}
			$log_list = $m_log->where(array('log_id'=>array('in',$r_log_ids)))->order('create_date desc')->select();
			foreach($log_list as $k=>$v){
				$order_info = array();
				$code = '';
				$log_type = 'rMemberLog';
				if(in_array($v['log_id'],$order_log_ids)){
					$order_id = '';
					$order_id = $m_r_order_log->where('log_id = %d',$v)->getField('order_id');
					$order_info = $m_order->where('order_id = %d',$order_id)->find();
					$code = $order_info['prefixion'].$order_info['code'];
					$log_type = 'rOrderLog';
				}
				$log_list[$k]['code'] = $code;
				$log_list[$k]['owner'] = $d_role->where('role.role_id = %d',$v['role_id'])->find();
				$log_list[$k]['log_type'] = $log_type;
			}
			$this->order_list = $order_list;
			$this->log_list = $log_list;
			$this->member_id = $member_id;
			$this->is_member = 1;
		}else{
			$order_info = $m_order->where('order_id = %d',$order_id)->find();
			if(!$order_info){
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			//订单编号
			$order_info['code'] = $order_info['prefixion'].$order_info['code'];
			//订单状态
			$status_name = '待确认';
			switch($order_info['status']){
				case 1 : $status_name = '生产中'; break;
				case 2 : $status_name = '配送中'; break;
				case 3 : $status_name = '已完成'; break;
			}
			$order_info['status_name'] = $status_name;
			//订单产品
			$product_list = $m_r_order_product->where('order_id = %d',$order_id)->select();
			foreach($product_list as $k=>$v){
				$product_info = array();
				$product_info = $m_product->where('product_id = %d',$v['product_id'])->field('name,is_deleted')->find();
				$product_list[$k]['product_info'] = $product_info;
			}
			$order_list[0]['product_list'] = $product_list;
			$order_list[0]['order_info'] = $order_info;
			$this->order_list = $order_list;
			//沟通日志
			$log_ids = $m_r_order_log->where('order_id = %d',$order_id)->getField('log_id',true);
			$log_list = $m_log->where(array('log_id'=>array('in',$log_ids)))->order('create_date desc')->select();
			foreach($log_list as $k=>$v){
				$log_list[$k]['owner'] = $d_role->where('role.role_id = %d',$v['role_id'])->find();
				$log_list[$k]['code'] = $order_info['prefixion'].$order_info['code'];
				$log_list[$k]['log_type'] = 'rOrderLog';
			}
			$this->log_list = $log_list;
			$this->order_info = $order_info;
			$this->order_id = $order_id;
			$this->member_id = $order_info['member_id'];
		}
		if(!$member_id){
			$member_id = $order_info['member_id'];
		}
		//附件
        $m_rfc = M('RMemberFile');//附件 关联表
        $file_id = $m_rfc->where('member_id = %d',$member_id)->getField('file_id',true);
        $file_info = M('File')->where(array('file_id'=>array('in',$file_id)))->select();
        foreach ($file_info as $fk=>$fv){
            $file_info[$fk]['owner'] = $d_role->where('role.role_id = %d',$fv['role_id'])->find();
            $file_info[$fk]['size'] = ceil($fv['size']/1024);
			/*判断文件格式 对应其图片*/
			$file_info[$fk]['pic'] = show_picture($fv['name']);
        }
        $this->file_info = $file_info;
		$this->display();
	}

	/**
	*订单编辑页
	*
	**/
	public function edit(){
		$m_order = M('Order');
		$this->display();
	}

	/**
	*订单编辑产品
	*
	**/
	public function edit_product(){
		$m_order = M('Order');
		$m_r_order_product = M('ROrderProduct');
		if($this->isPost()){
			$order_id = trim($_POST['order_id']);
			if(!$order_id){
				alert('error','参数错误，请重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_order->create()){
				$m_order->update_time = time();
				if(false !== $m_order->save()){
					$update_res = true;
					$add_res = true;
					$delete_res = true;
					//有r_id的为更新，之前有现在无的为删除，其他的为新增
					$old_r_ids = $m_r_order_product->where('order_id = %d', $order_id)->getField('id',true);
					$new_r_ids = array();
					$order_product_ids = $_POST['order']['product'];
					foreach($order_product_ids as $v){
						$new_r_ids[] = $v['r_id'];
					}
					//获取差集(需要删除的r_id)
					$delete_r_ids = array_diff($old_r_ids,$new_r_ids);
					foreach($order_product_ids as $v){
						$product_data = array();
						$product_data['order_id'] = $order_id;
						$product_data['product_id'] = $v['product_id'];
						$product_data['ori_price'] = $v['ori_price'];
						$product_data['discount_rate'] = $v['discount_rate'];
						$product_data['unit_price'] = $v['unit_price'];
						$product_data['amount'] = $v['amount'];
						$product_data['subtotal'] = $v['subtotal'];
						$product_data['unit'] = $v['unit'];
						if(!empty($v['r_id'])){
							//更新
							$update_res = $m_r_order_product->where('id = %d',$v['r_id'])->save($product_data);
						}else{
							//添加
							$add_res = $m_r_order_product->add($product_data);
						}
					}
					//删除
					if($delete_res){
						$delete_res = $m_r_order_product->where(array('id'=>array('in',$delete_r_ids)))->delete();
					}
					if($update_res !== false && $add_res !== false && $delete_res !== false){
						alert('success', '修改订单成功!', $_SERVER['HTTP_REFERER']);
					}else{
						alert('error', '修改订单失败，请重试!', $_SERVER['HTTP_REFERER']);
					}
				}else{
					alert('error','修改订单失败，请重试！',$_SERVER['HTTP_REFERER']);
				}
			}
		}
	}

	/**
	*订单删除（取消）
	*
	**/
	public function delete(){
		$m_order = M('Order');
		$order_id = intval($_GET['order_id']);
		if(!$order_id){
			$this->ajaxReturn('','参数错误，请重试！',0);
		}
		//判断订单状态
		$order_info = $m_order->where(array('order_id'=>$order_id,'check_state'=>array('neq',2)))->find();
		if(!$order_info){
			$this->ajaxReturn('','该订单信息不存在或已取消！',0);
		}

		$this->display();
	}

	/**
	*订单详情
	*
	**/
	public function view(){
		$m_order = M('Order');
		$m_r_order_log = M('ROrderLog');
		$m_r_order_product = M('ROrderProduct');
		$m_product = M('Product');
		$d_role = D('RoleView');
		$m_log = M('Log');
		$m_member = M('Member');
		$order_id = intval($_GET['id']);
		$order_info = $m_order->where('order_id = %d',$order_id)->find();
		if(!$order_info){
			alert('error','数据不存在或已删除！',$_SERVER['HTTP_REFERER']);
		}
		//订单编号
		$order_info['code'] = $order_info['prefixion'].$order_info['code'];
		//订单状态
		$status_name = '待确认';
		switch($order_info['status']){
			case 1 : $status_name = '生产中'; break;
			case 2 : $status_name = '配送中'; break;
			case 3 : $status_name = '已完成'; break;
		}
		$order_info['status_name'] = $status_name;
		//支付状态
		$payment_state_name = '未支付';
		switch($order_info['payment_state']){
			case 1 : $payment_state_name = '已支付（线上）'; break;
			case 2 : $payment_state_name = '已支付（线下）'; break;
			case 3 : $payment_state_name = '已退还'; break;
		}
		$order_info['payment_state_name'] = $payment_state_name;
		//订单产品
		$product_list = $m_r_order_product->where('order_id = %d',$order_id)->select();
		foreach($product_list as $k=>$v){
			$product_info = array();
			$product_info = $m_product->where('product_id = %d',$v['product_id'])->field('name,is_deleted')->find();
			$product_list[$k]['product_info'] = $product_info;
		}
		$order_info['product_list'] = $product_list;
		//客户
		$order_info['member_info'] = $m_member->where('member_id = %d',$order_info['member_id'])->find();
		//沟通日志
		$log_ids = $m_r_order_log->where('order_id = %d',$order_id)->getField('log_id',true);
		$log_list = $m_log->where(array('log_id'=>array('in',$log_ids)))->select();
		foreach($log_list as $k=>$v){
			$log_list[$k]['owner'] = $d_role->where('role.role_id = %d',$v['role_id'])->find();
		}
		$order_info['log_list'] = $log_list;
		$this->order_info = $order_info;
		$this->order_id = $order_id;
		$this->alert = parseAlert();
		$this->display();
	}
}