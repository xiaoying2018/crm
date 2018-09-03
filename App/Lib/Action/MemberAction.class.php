<?PHP 
/**
*线上客户模块
*
**/
class MemberAction extends Action{
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
	*线上客户列表页（默认页面）
	*
	**/
	public function index(){
		$m_member = M('Member');
		$order = 'update_time desc';
		$where = array();
		$p = intval($_GET['p'])?intval($_GET['p']):1;
		
		$member_list = $m_member->where($where)->order($order)->select();
		$count = $m_member->where($where)->count();
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
		$this->member_list = $member_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*线上客户详情页
	*
	**/
	public function view(){
		$member_id = intval($_GET['id']);
		if(!$member_id){
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$m_member = M('Member');
		$m_order = M('Order');
		$m_log = M('Log');
		$m_r_member_log = M('RMemberLog');
		$m_product = M('Product');
		$m_r_order_product = M('ROrderProduct');
		$member_info = $m_member->where('member_id = %d',$member_id)->find();
		//订单
		$order_list = array();
		$order_ids = array();
		$order_list = $m_order->where('member_id = %d',$member_id)->select();
		foreach($order_list as $k=>$v){
			//订单编号
			$order_list[$k]['code'] = $v['prefixion'].$v['code'];
			//产品
			$product_ids = array();
			$product_ids = $m_r_order_product->where('order_id = %d',$v['order_id'])->getField('product_id',true);
			$product_name = $m_product->where(array('product_id'=>array('in',$product_ids)))->getField('name',true);
			$product_count = count($product_name);
			if($product_count > 0){
				$product_name = $product_name[0].'、...';
			}else{
				$product_name = $product_name[0];
			}
			$order_list[$k]['product_count'] = $product_count;
			$order_list[$k]['product_name'] = $product_name;
			//订单状态
			$status_name = '待确认';
			switch($v['payment_state']){
				case 1 : $status_name = '生产中'; break;
				case 2 : $status_name = '配送中'; break;
				case 3 : $status_name = '已完成'; break;
			}
			$order_list[$k]['status_name'] = $status_name;
			$order_ids[] = $v['order_id'];
		}
		$member_info['order_list'] = $order_list;
		$member_info['order_ids'] = $order_ids;
		$this->member_info = $member_info;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*线上客户编辑页
	*
	**/
	public function edit(){
		$m_member = M('Member');
		$member_id = intval($_GET['id']);
		$member_info = $m_member->where('member_id = %d',$member_id)->find();
		if(!$member_info){
			alert('error','数据不存在或已删除！',$_SERVER['HTTP_REFERER']);
		}
		$this->member_info = $member_info;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*线上客户删除
	*
	**/
	public function delete(){
		$m_member = M('Member');
		$m_order = M('Order');
		$member_ids = is_array($_REQUEST['member_id']) ? $_REQUEST['member_id'] : array($_REQUEST['member_id']);
		$order_ids = $m_order->where(array('member_id'=>array('in',$member_ids)))->getField('order_id',true);
		if($order_ids){
			//过滤出无订单数据的客户
			$is_member_ids = array();
			$is_member_ids = $m_order->where(array('order_id'=>array('in',$order_ids)))->getField('member_id',true);
			//数组差集
			$no_member_ids = array_udiff($member_ids, $is_member_ids);
			if($no_member_ids){
				if($m_member->where(array('member_id'=>array('in',$no_member_ids)))->delete()){
					if($is_member_ids){
						$this->ajaxReturn('','部分客户删除失败，请先删除客户下所有订单数据！',0);
					}else{
						$this->ajaxReturn('','删除成功！',1);
					}
				}else{
					$this->ajaxReturn('','删除失败，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','删除失败，请先删除该客户下的所有订单数据！',0);
			}
		}else{
			if($m_member->where(array('member_id'=>array('in',$member_ids)))->delete()){
				$this->ajaxReturn('','删除成功！',1);
			}else{
				$this->ajaxReturn('','删除失败，请重试！',0);
			}
		}
	}
}