<?php
class SalesAction extends Action {
	
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('changeContent','prevprint','alllistdialog','allchangecontent','outof')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}

	public function index(){
		alert('error','参数错误！',U('dynamic/index'));
		$d_sales = D('SalesmoduleView'); // 实例化Sales对象
		import('@.ORG.Page');// 导入分页类
		$params = array();
		$where['creator_role_id'] = array('in', $this->_permissionRes);
		$order = "update_time desc";
		
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		
		$p = isset($_GET['p'])?$_GET['p']:1;
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		switch ($by) {
			case 'not_check' :
				$where['is_checked'] =  array('eq', 0); 
				break;
			case 'checked' : 
				$where['is_checked'] =  array('eq', 1);
				break;
		}
		if ($_REQUEST["field"]) {
			if (trim($_REQUEST['field']) == "all") {
				$field = is_numeric(trim($_REQUEST['search'])) ? 'product.name|cost_price|sales_price|link|pre_sale_count|stock_count' : 'product.name|link|development_team';
			} else {
				$field = trim($_REQUEST['field']);
			}
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			if($field == 'create_time' || $field == 'update_time'){
				$search= strtotime($search);
			}
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if (!empty($field)) {
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
			}
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}
		if($_GET['listrows']){
			$listrows = $_GET['listrows'];
			$params[] = "listrows=" . trim($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		$where['type'] = array('eq', 0);
		$count = $d_sales->where($where)->count();// 查询满足要求的总记录数
		$p_num = ceil($count/$listrows);
		if($p_num<$p){
			$p = $p_num;
		}
		$list = $d_sales->where($where)->order($order)->Page($p.','.$listrows)->select();

		foreach ($list as $k => $v) {
			$list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
			//财务状态
			$receivables = M('receivables')->where('sales_id = %d',$v['sales_id'])->find();
			$list[$k]['receivables_status'] = empty($receivables['status']) ? 0 : $receivables['status'];
			if(!empty($receivables['receivables_id'])){
				$not_received = M('receivingorder')->where('is_deleted <> 1 and receivables_id = %d and status = 1', $receivables['receivables_id'])->sum('money');
			}
			$list[$k]['un_received'] = $receivables['price'] - $not_received;
		}
		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		if (!empty($_GET['type'])) {
			$params['type'] = 'type='.trim($_GET['type']);
		}
		
		$this->parameter = implode('&', $params);
		if ($_GET['desc_order']) {
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif($_GET['asc_order']){
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}
		$this->listrows = $listrows;
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('list',$list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->alert=parseAlert();
		$this->display(); // 输出模板
	}
	
	public function add() {
		if($this->isPost()) {
			$no_data_flag = true;
			foreach($_POST['business']['product'] as $v){
				if((!empty($v['product_id'])) && ($v['product_id'] > 0)){
					$no_data_flag = false;
					break;
				}
			}
			if($no_data_flag){
				$this->ajaxReturn('产品不能为空！','',0);
			}
			
			//生成序列号
			$table_info = getTableInfo('sales');
			$m_sales = M('sales');
			if($m_sales->create()){
				$m_sales->creator_role_id = session('role_id');
				$m_sales->status = 97;//未出库
				$m_sales->type = 0;
				$m_sales->sn_code = 'XSD'.date('Ymd',time());
				$m_sales->sales_time = time();
				$m_sales->create_time = time();
				$sales_id = $m_sales->add();
				if($sales_id){
					$add_product_flag = true;
					$count_nums = 0;
					$m_sales_product = M('salesProduct');
					foreach($_POST['business']['product'] as $v){
						if(!empty($v['product_id'])){
							$count_nums += 1;
							$data['sales_id'] = $sales_id;
							$data['product_id'] = $v['product_id'];
							//$data['warehouse_id'] = $v['warehouse_id'];
							$data['amount'] = $v['amount'];
							$data['unit_price'] = $v['unit_price'];
							$data['discount_rate'] = $v['discount_rate'];
							$data['subtotal'] = $v['subtotal'];
							//$data['tax_rate'] = $v['tax_rate'];
							//$data['description'] = $v['description'];
							
							$sales_product_id = $m_sales_product->add($data);
							if(empty($sales_product_id)){
								$add_product_flag = false;
								break;
							}
						}
					}
					if($add_product_flag){
						$datas['sales_id'] = $sales_id;
						$datas['count_nums'] = $count_nums;
						$datas['sales_price'] = $_POST['sales_price'];
						$this->ajaxReturn($datas,'',1);
					}else{
						$this->ajaxReturn('添加失败！','',0);
					}
				}else{
					$this->ajaxReturn('添加失败！','',0);
				}
			}else{
				$this->ajaxReturn('获取参数错误！','',0);
			}
		}
	}
	
	public function edit(){
		$sales_id = intval($_REQUEST['sales_id']);
		if($this->isPost()){
			$no_data_flag = true;
			foreach($_POST['business']['product'] as $v){
				if((!empty($v['product_id'])) && ($v['product_id'] > 0)){
					$no_data_flag = false;
					break;
				}
			}
			if($no_data_flag){
				$this->error(L('PRODUCT_INFORMATION_CANNOT_BE_EMPTY'), $_SERVER['HTTP_REFERER']);
			}
			$m_sales = M('sales');
			$operation_flag = true;
			$m_sales_product = M('salesProduct');
			$count_nums = 0;
			foreach($_POST['business']['product'] as $v){
				if(!empty($v['product_id'])){
					$count_nums += 1;
					$data['sales_id'] = $sales_id;
					$data['product_id'] = $v['product_id'];
					//$data['warehouse_id'] = $v['warehouse_id'];
					$data['amount'] = $v['amount'];
					$data['unit_price'] = $v['unit_price'];
					$data['discount_rate'] = $v['discount_rate'];
					$data['subtotal'] = $v['subtotal'];
					//在编辑时，如果又添加商品，根据是否存在sales_product_id来进行编辑或添加
					if(empty($v['sales_product_id'])){
						//添加
						$result_sales_product= $m_sales_product->add($data);
						if(empty($result_sales_product)){
							$operation_flag = false;
							break;
						}
					}else{
						//编辑
						$result_sales_product = $m_sales_product->where('sales_product_id = %d', $v['sales_product_id'])->save($data);
						if($result_sales_product === false){
							$operation_flag = false;
							break;
						}
					}
				}
				//在编辑时，如果从原来的商品中去除一条信息，则删除该产品
				if($v['sales_product_id'] && empty($v['product_id'])){
					$result_sales_product = $m_sales_product->where('sales_product_id = %d', $v['sales_product_id'])->delete();
					if($result_sales_product == 0 || $result_sales_product === false){
						$operation_flag = false;
					}
				}
			}
			if($operation_flag){
				$datas['sales_id'] = $sales_id;
				$datas['count_nums'] = $count_nums;
				$datas['sales_price'] = $_POST['sales_price'];
				$this->ajaxReturn($datas,'',1);
			}else{
				$this->ajaxReturn('添加失败！','',0);
			}
		}
	}
	
	//详情
	public function view(){
		$sales_id = intval($_REQUEST['id']);
		$d_sales = D('SalesmoduleView');
		if(!$sales_id){
			alert('error', L('PARAMETER_ERROR'), U('sales/index'));
		} elseif(!$sales = $d_sales->where('sales_id = %d', $sales_id)->find()) {
			alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
		} else {
			$sales['creator_role_name'] = M('user')->where('role_id = %d', $sales['creator_role_id'])->getField('name');
			$m_sales_product = M('salesProduct');
			$m_product = M('product');
			$sales_product = $m_sales_product->where('sales_id = %d',$sales['sales_id'])->order('sales_product_id ASC')->select();
			//折扣额总数
			$total_discount_price = 0.00;
			//税额总数
			$total_tax_price = 0.00;
			//税前总数
			$total_no_tax_price = 0.00;
			//税后总数
			$total_prime_price = 0.00;

			foreach($sales_product as $k=>$v){
				$sales_product[$k]['product'] = $m_product->where('product_id = %d',$v['product_id'])->find();
				$sales_product[$k]['warehouse_name'] = M('warehouse')->where('warehouse_id = %d', $v['warehouse_id'])->getField('name');
				//折扣额
				$sales_product[$k]['discount'] = bcmul($v['unit_price'] * $v['amount'], $v['discount_rate']/100, 2);
				//税前
				$sales_product[$k]['no_tax_price'] = bcsub($v['unit_price'] * $v['amount'], $sales_product[$k]['discount'], 2);
				//税额
				$sales_product[$k]['tax_price'] = bcmul($v['tax_rate']/100, $sales_product[$k]['no_tax_price'], 2);
				//税后
				$sales_product[$k]['prime_price'] = bcadd($sales_product[$k]['no_tax_price'], $sales_product[$k]['tax_price'], 2);
				
				$total_discount_price += $sales_product[$k]['discount'];
				$total_tax_price += $sales_product[$k]['tax_price'];
				$total_no_tax_price += $sales_product[$k]['no_tax_price'];
				$total_prime_price += $sales_product[$k]['prime_price'];
			}
			$sales['total_discount_price'] = $total_discount_price;
			$sales['total_tax_price'] = $total_tax_price;
			$sales['total_no_tax_price'] = $total_no_tax_price;
			$sales['total_prime_price'] = $total_prime_price;
			//物流信息
			$sales_log = M('salesLog')->where('sales_id =%d',$sales['sales_id'])->select();
			$this->sales_log = $sales_log;
			$this->sales = $sales;
			$this->sales_product = $sales_product;
			$this->alert = parseAlert();
			$this->display();
		}
	}

	/**
	 * 销售单列表
	 * @by  all:全部  0:销售单	1:销售退货单
	 * @re  y：允许重复		n:不允许重复（在财务中添加应收款应付款时，是否允许再次添加已经有财务信息的销售单和采购单）
	 **/
	public function listdialog(){
		$d_sales = D('SalesmoduleView');
		$m_receivables = M('receivables');
		$m_payables = M('payables');
		$by = $_GET['by'];
		$re = empty($_GET['re']) ? 'y' : 'n';
		if($by == '0'){
			$where['type'] = '0';
			if($re == 'n'){
				$receivables_idArr = $m_receivables->where('sales_id <> ""')->getField('sales_id',true);
				if(!empty($receivables_idArr)){
					$where['sales_id'] = array('not in',$receivables_idArr);
				}
			}
		}else if($by == '1'){
			$where['type'] = '1';
			if($re == 'n'){
				$payables_idArr = $m_payables->where('sales_id <> ""')->getField('sales_id',true);
				if(!empty($payables_idArr)){
					$where['sales_id'] = array('not in',$payables_idArr);
				}
			}
		}
		$where['creator_role_id'] = array('in', $this->_permissionRes);
		$where['is_checked'] = 1;
		$sales = $d_sales->where($where)->order('create_time desc')->select();
		$count = $d_sales->where($where)->count();
	
		$this->sales = $sales;
		$this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
		$this->count_num = $count;
		$this->display();
	}
	
	public function changeContent(){
		if($this->isAjax()){
			$d_sales = D('SalesmoduleView'); // 实例化User对象
			$m_receivables = M('receivables');
			$m_payables = M('payables');
			import('@.ORG.Page');// 导入分页类
			$where['creator_role_id'] = array('in', $this->_permissionRes);
			$params = array();
			
			$by = $_GET['by'];
			$re = empty($_GET['re']) ? 'y' : 'n';
			if($by == '0'){
				$where['type'] = '0';
				if($re == 'n'){
					$receivables_idArr = $m_receivables->where('sales_id <> ""')->getField('sales_id',true);
					if(!empty($receivables_idArr)){
						$where['sales_id'] = array('not in',$receivables_idArr);
					}
				}
			}else if($by == '1'){
				$where['type'] = '1';
				if($re == 'n'){
					$payables_idArr = $m_payables->where('sales_id <> ""')->getField('sales_id',true);
					if(!empty($payables_idArr)){
						$where['sales_id'] = array('not in',$payables_idArr);
					}
				}
			}

			$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
				if	('development_time' == $field || 'listing_time' == $field) $search = is_numeric($search)?$search:strtotime($search);;
				if (!empty($field)) {
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
				}
				$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
			}
			$where['is_checked'] = 1;
			$count = $d_sales->where($where)->count();// 查询满足要求的总记录数
			$list = $d_sales->where($where)->order('create_time desc')->Page($p.',10')->select();

			$data['list'] = $list;
			$data['p'] = $p;
			$data['count'] = $count;
			$data['total'] = $count%10 > 0 ? ceil($count/10) : $count/10;
			$this->ajaxReturn($data,"",1);
		}
	}
	
	//入库
	public function enter(){
		$sales_id = $_GET['id'];
		if($sales_id){
			$m_sales = M('sales');
			$sales = $m_sales->where('sales_id = %d and type=1',$sales_id)->find();
			
			if($sales){
				if($sales['is_checked'] != '1'){
					alert('error', L('CHECK_THROUGH_THE_ORDER_FIRST'), $_SERVER['HTTP_REFERER']);
				}
				if($sales['status'] == '100'){
					alert('error', L('SALES_RETURN_HAS_BEEN_ENTERD_WAREHOUSE_PLEASE_DO_NOT_REPEAT_THE_OPERATION'),$_SERVER['HTTP_REFERER']);
				}
				$m_stock = M('stock');
				$m_sales_product = M('salesProduct');
				$m_specode = M('Specode');
				$sales_product = $m_sales_product->where('sales_id = %d', $sales_id)->select();
				$last_change_time = time();
				$excute_flag = true;
				foreach($sales_product as $v){
					$stock= $m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $v['warehouse_id'])->find();
					if(empty($stock)){
						//库存中不存在该产品，此时新增
						$data['product_id'] = $v['product_id'];
						$data['warehouse_id'] = $v['warehouse_id'];
						$data['amounts'] = $v['amount'];
						$data['last_change_time'] = $last_change_time;
						$stock_id = $m_stock->add($data);
						if(!$stock_id){
							$excute_flag = false;
							break;
						}
					}else{
						//库存中存在该产品，此时只增加数量
						$effect_rows = $m_stock->where('stock_id = %d', $stock['stock_id'])->setInc('amounts',$v['amount']);
						if(!$effect_rows){
							$excute_flag = false;
							break;
						}
						//变更时间
						$m_stock->where('product_id = %d and warehouse_id = %d',$v['product_id'], $v['warehouse_id'])->setField('last_change_time', $last_change_time);
					}
					//产品SN状态改变
					if(!empty($v['specode_id'])){
						$m_specode->where('specode_id = %d',$v['specode_id'])->setField('type',1);
					}
					
					$m_stock_record = M('stock_record');
					$enter_data['owner_role_id'] = session('role_id');
					$enter_data['product_id'] = $v['product_id'];
					$enter_data['warehouse_id'] = $v['warehouse_id'];
					$enter_data['amounts'] = $v['amount'];
					$enter_data['out_of_stock'] = 1;
					$enter_data['type'] = 1;
					$enter_data['create_time'] = time();
					$enter_data['sales_purchase_id'] = $sales_id;
					$m_stock_record ->add($enter_data);
				}
				if($excute_flag){
					actionLog($sales_id);
					//入库成功改变状态
					$m_sales->where('sales_id = %d',$sales_id)->setField('status','100');
					//发送站内信给创建人
					$url = U('sales/view','id='.$sales_id);
					sendMessage($sales['creator_role_id'],'您创建的《<a href="'.$url.'">'.$sales['sn_code'].' - '.$sales['subject'].'</a>》<font style="color:green;">已入库</font>！',1);
					alert('success', L('PRODUCT_HAS_BEEN_ENTERD_WAREHOUSE'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', L('PRODUCT_ENTER_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('SALES_RETURN_DOES_NOT_EXIST_OR_IS_INVALID'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
		}
		$this->alert = parseAlert();
	}
	
	//出库
	public function outof(){
		if($this->isPost()){
			$stock_id = intval($_POST['stock_id']);
			if($stock_id){
				
				$m_sales = M('sales');
				$sales_id = $stock_id;
				$sales = $m_sales->where('sales_id = %d and type=0',$sales_id)->find();
				if($sales){
					if($sales['is_checked'] != '1'){
						alert('error', L('CHECK_THROUGH_THE_ORDER_FIRST'), $_SERVER['HTTP_REFERER']);
					}
					if($sales['status'] == '98'){
						alert('error', L('SALES_HAVE_BEEN_OUT_DO_NOT_REPEAT_THE_OPERATION'), $_SERVER['HTTP_REFERER']);
					}
					$m_stock = M('stock');
					$m_sales_product = M('salesProduct');
					$productArr = $m_sales_product->field('sales_product_id,sales_id,product_id,warehouse_id,amount,sum(amount) as outof_count')->where('sales_id = %d', $sales_id)->group('product_id,warehouse_id')->select();
					//判断库存是否充足
					$message_alert = '';
					foreach($productArr as $v){
						$stock_count = $m_stock->where('product_id = %d and warehouse_id = %d',$v['product_id'],$v['warehouse_id'])->sum('amounts');
						empty($stock_count) ? $stock_count = 0 : $stock_count = $stock_count;
						if($stock_count < $v['outof_count']){
							$product_name = M('product')->where('product_id = %d',$v['product_id'])->getField('name');
							$warehouse_name = M('warehouse')->where('warehouse_id = %d',$v['warehouse_id'])->getField('name');
							$message_alert .= '“'.$product_name.'”在“'.$warehouse_name.'”中库存不足('.($stock_count - $v['outof_count']).')，';
						}
					}
					$message_alert .= L('UNABLE_TO_COMPLETE_OUT_OF_WAREHOUSE_OPERATION');
					if($message_alert == L('UNABLE_TO_COMPLETE_OUT_OF_WAREHOUSE_OPERATION')){
						//减少库存
						$outof_flag = true;
						foreach($productArr as $v){
							$result = $m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $v['warehouse_id'])->setDec('amounts', $v['outof_count']);
							if(empty($result)){
								$out_flag = false;
								break;
							}
							//修改状态
							$m_stock_record = M('stock_record');
							$m_sales->where('sales_id = %d',$v['sales_id'])->setField('status','98');
							$enter_data['owner_role_id'] = session('role_id');
							$enter_data['product_id'] = $v['product_id'];
							$enter_data['warehouse_id'] = $v['warehouse_id'];
							$enter_data['amounts'] = $v['outof_count'];
							$enter_data['out_of_stock'] = 2;
							$enter_data['type'] = 1;
							$enter_data['create_time'] = time();
							$enter_data['sales_purchase_id'] = $v['sales_id'];
							$m_stock_record ->add($enter_data);
					
							//变更时间
							$m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $v['warehouse_id'])->setField('last_change_time',time());
							//修改状态
							$m_sales->where('sales_id = %d',$v['sales_id'])->setField('status','98');
						}
						if($outof_flag){
							actionLog($sales_id);
							$data['logistics_number'] = $_POST['logistics_number'];
							$data['outof_time'] = strtotime($_POST['outof_time']);
							$m_sales->where('sales_id = %d and type=0',$sales_id)->save($data);
							//发送站内信
							$url = U('sales/view','id='.$sales_id);
							sendMessage($sales['creator_role_id'],'您创建的《<a href="'.$url.'">'.$sales['sn_code'].' - '.$sales['subject'].'</a>》<font style="color:green;">已出库</font>！',1);
							alert('success',L('OPERATION_SUCCESSED_PRODUCT_HAS_BEEN_OUT_OF_WAREHOUSE'),$_SERVER['HTTP_REFERER']);
						}else{
							alert('error',L('OPERATE_FAILED_NO_CHANGE_IN_STOCK'), $_SERVER['HTTP_REFERER']);
						}
					}else{
						alert('error',$message_alert,$_SERVER['HTTP_REFERER']);
					}
				}else{
					alert('error', L('SALES_DOES_NOT_EXIST_OR_IS_INVALID'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			$sales_id = intval($_GET['id']);
			$d_sales = D('SalesmoduleView');
			$sales = $d_sales->where('sales_id = %d', $sales_id)->find();

			$sales['creator_role_name'] = M('user')->where('role_id = %d', $sales['creator_role_id'])->getField('name');
			$m_sales_product = M('salesProduct');
			$m_product = M('product');
			$sales_product = $m_sales_product->where('sales_id = %d',$sales['sales_id'])->order('sales_product_id ASC')->select();
			//折扣额总数
			$total_discount_price = 0.00;
			//税额总数
			$total_tax_price = 0.00;
			//税前总数
			$total_no_tax_price = 0.00;
			//税后总数
			$total_prime_price = 0.00;

			foreach($sales_product as $k=>$v){
				$product = $m_product->where('product_id = %d',$v['product_id'])->find();
				$product['category_name'] = M('product_category')->where('category_id =%d',$product['category_id'])->getField('name');
				$sales_product[$k]['product'] = $product;
				$sales_product[$k]['warehouse_name'] = M('warehouse')->where('warehouse_id = %d', $v['warehouse_id'])->getField('name');
				if($v['discount_rate'] >0){
					$sales_product[$k]['discount'] = bcmul($v['unit_price'] * $v['amount'], $v['discount_rate']/100, 2);
					$sales_product[$k]['subtotal'] = bcmul($v['unit_price'] * $v['amount'], $v['discount_rate']/100, 2);
				}else{
					$sales_product[$k]['discount'] = '无';
					$sales_product[$k]['subtotal'] = bcmul($v['unit_price'] , $v['amount'], 2);
				}
			}
			$this->sales = $sales;
			$this->stock_id = $sales_id;
			$this->cate = $cate;
			$this->sales_product = $sales_product;
			$this->display();
		}
	}
	
	public function cancel(){
		$sales_id = $_GET['id'];
		if($sales_id){
			$m_sales = M('sales');
			$sales = $m_sales->where('sales_id = %d',$sales_id)->find();
			if($sales['status'] == '97'){
				alert('error', L('SALES_HAS_BEEN_REVOKED_DO_NOT_REPEAT_THE_OPERATION'),$_SERVER['HTTP_REFERER']);
			}
			if($sales['status'] == '99'){
				alert('error', L('SALES_RETURN_HAS_BEEN_REVOKED_DO_NOT_REPEAT_THE_OPERATION'),$_SERVER['HTTP_REFERER']);
			}
			
			if($sales){
				$m_stock = M('stock');
				$m_sales_product = M('salesProduct');
				if($sales['type'] == '0'){
					//撤销出库
					$sales_product = $m_sales_product->where('sales_id = %d', $sales_id)->select();
					$last_change_time = time();
					$excute_flag = true;
					foreach($sales_product as $v){
						$stock= $m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $v['warehouse_id'])->find();
						if(empty($stock)){
							//库存中不存在该产品，此时新增
							$data['product_id'] = $v['product_id'];
							$data['warehouse_id'] = $v['warehouse_id'];
							$data['amounts'] = $v['amount'];
							$data['last_change_time'] = $last_change_time;
							$stock_id = $m_stock->add($data);
							if(!$stock_id){
								$excute_flag = false;
								break;
							}
						}else{
							//库存中存在该产品，此时只增加数量
							$effect_rows = $m_stock->where('stock_id = %d', $stock['stock_id'])->setInc('amounts',$v['amount']);
							if(!$effect_rows){
								$excute_flag = false;
								break;
							}
							//变更时间
							$m_stock->where('product_id = %d and warehouse_id = %d',$v['product_id'], $v['warehouse_id'])->setField('last_change_time', $last_change_time);
						}
					}
					if($excute_flag){
						//入库成功改变状态
						$m_sales->where('sales_id = %d',$sales_id)->setField('status','97');
						alert('success', L('OPERATE_SUCCESSED_SALES_HAS_BEEN_REVOKED'), $_SERVER['HTTP_REFERER']);
					}else{
						alert('error',L('OPERATE_FAILED_TERMINATE_THE_REVOCATION'), $_SERVER['HTTP_REFERER']);
					}
				}elseif($sales['type'] == '1'){
					//撤销入库
					$productArr = $m_sales_product->field('sales_product_id,sales_id,product_id,warehouse_id,amount,sum(amount) as outof_count')->where('sales_id = %d', $sales_id)->group('product_id,warehouse_id')->select();
					//判断库存是否充足
					$message_alert = '';
					foreach($productArr as $v){
						$stock_count = $m_stock->where('product_id = %d and warehouse_id = %d',$v['product_id'],$v['warehouse_id'])->sum('amounts');
						empty($stock_count) ? $stock_count = 0 : $stock_count = $stock_count;
						if($stock_count < $v['outof_count']){
							$product_name = M('product')->where('product_id = %d',$v['product_id'])->getField('name');
							$warehouse_name = M('warehouse')->where('warehouse_id = %d',$v['warehouse_id'])->getField('name');
							$message_alert .= '“'.$product_name.'”在“'.$warehouse_name.'”中库存不足('.($stock_count - $v['outof_count']).')，';
						}
					}
					$message_alert .= L('UNABLE_TO_COMPLETE_THE_REVOCATION_OPERATION');
					if($message_alert == L('UNABLE_TO_COMPLETE_THE_REVOCATION_OPERATION')){
						//减少库存
						$outof_flag = true;
						foreach($productArr as $v){
							$result = $m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $v['warehouse_id'])->setDec('amounts', $v['outof_count']);
							if(empty($result)){
								$out_flag = false;
								break;
							}
							//变更时间
							$m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $v['warehouse_id'])->setField('last_change_time',time());
							//修改状态
							$m_sales->where('sales_id = %d',$v['sales_id'])->setField('status','99');
						}
						if($outof_flag){
							alert('success',L('OPERATE_SUCCESS_SALES_RETURN_REVOKED'),$_SERVER['HTTP_REFERER']);
						}else{
							alert('error',L('OPERATE_FAILED_TERMINATE_THE_REVOCATION'), $_SERVER['HTTP_REFERER']);
						}
					}else{
						alert('error',$message_alert,$_SERVER['HTTP_REFERER']);
					}
				}
			}else{
				alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_IS_INVALID'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
		}
		$this->alert = parseAlert();
	}
	
	public function salesreturn(){
		$d_sales = D('SalesmoduleView'); // 实例化Sales对象
		import('@.ORG.Page');// 导入分页类
		$where['creator_role_id'] = array('in', $this->_permissionRes);
		$params = array();
		$p = isset($_GET['p'])?$_GET['p']:1;
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		
		$order = "create_time desc";
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		
		switch ($by) {
			case 'not_check' :
				$where['is_checked'] =  array('eq', 0); 
				break;
			case 'checked' : 
				$where['is_checked'] =  array('eq', 1);
				break;
		}
		if ($_REQUEST["field"]) {
			if (trim($_REQUEST['field']) == "all") {
				$field = is_numeric(trim($_REQUEST['search'])) ? 'product.name|cost_price|sales_price|link|pre_sale_count|stock_count' : 'product.name|link|development_team';
			} else {
				$field = trim($_REQUEST['field']);
			}
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			if($field == 'create_time' || $field == 'update_time'){
				$search= strtotime($search);
			}

			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if (!empty($field)) {
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
			}
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
			//过滤不在权限范围内的role_id
			if(trim($_REQUEST['field']) == 'creator_role_id'){
				if(!in_array(trim($search),$this->_permissionRes)){
					$where['creator_role_id'] = array('in',$this->_permissionRes);
				}
			}
		}

		$where['type'] = array('eq', 1);
		$count = $d_sales->where($where)->count();// 查询满足要求的总记录数
		$list = $d_sales->order('create_time desc')->where($where)->order($order)->Page($p.',15')->select();
		foreach ($list as $k => $v) {
			$list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
			//财务状态
			$list[$k]["check_role_name"] = M('user')->where('role_id =%d',$v['check_role_id'])->getField('name');
			$payables = M('payables')->where('sales_id = %d',$v['sales_id'])->find();
			$list[$k]['payables_status'] = empty($payables['status']) ? 0 : $payables['status'];
			$not_pay = M('paymentorder')->where('is_deleted <> 1 and payables_id = %d and status = 1', $payables['payables_id'])->sum('money');
			$list[$k]['un_payable'] = $payables['price'] - $not_pay;
		}
		$Page = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
		if (!empty($_GET['type'])) {
			$params['type'] = 'type='.trim($_GET['type']);
		}
		
		$this->parameter = implode('&', $params);
		if ($_GET['desc_order']) {
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif($_GET['asc_order']){
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}
		
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		$this->assign('list',$list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->alert=parseAlert();
		$this->display(); // 输出模板
	}
	
	public function addSalesReturn() {
		if($this->isPost()) {
			$customer_id = $_POST['customer_id'];
			if(empty($customer_id)){
				$this->error(L('PLEASE_SELECT_THE_CUSTOMER'), U('sales/addsalesreturn'));
			}
			
			$subject = $_POST['subject'];
			if(empty($subject)){
				$this->error(L('PLEASE_FILL_THE_SALES_SUBJECT'), U('sales/addsalesreturn'));
			}
			
			$no_data_flag = true;
			foreach($_POST['sales']['product'] as $v){
				if((!empty($v['product_id'])) && ($v['product_id'] > 0)){
					$no_data_flag = false;
					break;
				}
			}
			if($no_data_flag){
				$this->error(L('PRODUCT_INFORMATION_CANNOT_BE_EMPTY'), U('sales/addsalesreturn'));
			}
			
			//生成序列号
			$table_info = getTableInfo('sales');
			$m_sales = M('sales');
			if($m_sales->create()){
				$m_sales->creator_role_id = session('role_id');
				$m_sales->status = 99;//未入库
				$m_sales->type = 1;
				$m_sales->sn_code = 'THD'.date('Ymd',time()).$table_info['0']['AUTO_INCREMENT'];
				$m_sales->sales_time = $_POST['sales_time'] ? strtotime($_POST['sales_time']) : time();
				$m_sales->create_time = time();
				$sales_id = $m_sales->add();
				if($sales_id){
					$add_product_flag = true;
					$m_sales_product = M('salesProduct');
					foreach($_POST['sales']['product'] as $v){
						if(!empty($v['product_id'])){
							$data['sales_id'] = $sales_id;
							$data['product_id'] = $v['product_id'];
							$data['warehouse_id'] = $v['warehouse_id'];
							$data['amount'] = $v['amount'];
							$data['unit_price'] = $v['unit_price'];
							$data['discount_rate'] = $v['discount_rate'];
							$data['tax_rate'] = $v['tax_rate'];
							$data['description'] = $v['description'];
							
							$sales_product_id = $m_sales_product->add($data);
							if(empty($sales_product_id)){
								$add_product_flag = false;
								break;
							}
						}
					}
					if($add_product_flag){
						//如果有contract_id(合同),将合同信息添加至销售合同关系表
						$contract_id = $this->_post('contract_id','intval');
						if(!empty($contract_id)){
							$m_r_contract_sales = M('rContractSales');
							$r_data['contract_id'] = $contract_id;
							$r_data['sales_id'] = $sales_id;
							$m_r_contract_sales->add($r_data);
							alert('success', L('ADD_SALES_ORDER_SUCCESS'), U('contract/view','id='.$contract_id));
						}
						alert('success', L('CREATE_SALES_RETURN_SUCCESS'), U('sales/salesreturn'));
					}else{
						$this->error(L('ERROR_PROCESSING_OF_GOODS_CREATE_SALES_RETURN_FAILURE'), U('sales/addsalesreturn'));
					}
				}else{
					$this->error(L('CREATE_SALES_RETURNS_FAILED'), U('sales/addsalesreturn'));
				}
			}else{
				$this->error($m_sales->getError(), U('sales/addsalesreturn'));
			}
		}else{
			//生成序列号
			$type = $_GET['type'];
			$table_info = getTableInfo('sales');
			$this->sn_code = 'THD'.date('Ymd',time()).$table_info['0']['AUTO_INCREMENT'];
			$this->warehouse = M('warehouse')->order('warehouse_id ASC')->select();
			$this->type = $_GET['type'];
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	public function editSalesReturn(){
		$sales_id = intval($_REQUEST['id']);
		$d_sales = D('SalesmoduleView');
		if(!$sales_id){
			$this->error(L('PARAMETER_ERROR'), U('sales/index'));
		} elseif(!$sales = $d_sales->where('sales_id = %d', $sales_id)->find()) {
			$this->error(L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
		}
		$contract_id = M('rContractSales')->where('sales_id = %d && sales_type = 1',$sales_id)->getField('contract_id');
		$sales['contract_num'] = M('contract')->where('contract_id =%d',$contract_id)->getField('number');
		$sales['contract_id'] = $contract_id;
		if($this->isPost()){
			if($sales['status'] == 98){
				$this->error(L('THE_GOODS_HAVE_BEEN_DELIVERED_CAN_NOT_MODIFY_THE_SALES_RETURNS'), U('sales/salesreturn'));
			}
			$customer_id = $_POST['customer_id'];
			if(empty($customer_id)){
				$this->error(L('PLEASE_SELECT_THE_CUSTOMER'), $_SERVER['HTTP_REFERER']);
			}
			
			$no_data_flag = true;
			foreach($_POST['sales']['product'] as $v){
				if((!empty($v['product_id'])) && ($v['product_id'] > 0)){
					$no_data_flag = false;
					break;
				}
			}
			if($no_data_flag){
				$this->error(L('PRODUCT_INFORMATION_CANNOT_BE_EMPTY'), $_SERVER['HTTP_REFERER']);
			}
			
			$m_sales = M('sales');
			if($m_sales->create()){
				$m_sales->sales_id = $sales_id;
				$m_sales->sales_time = $_POST['sales_time'] ? strtotime($_POST['sales_time']) : time();
				$result_sales = $m_sales->save();
				if($result_sales !== false){
					$operation_flag = true;
					$m_sales_product = M('salesProduct');
					foreach($_POST['sales']['product'] as $v){
						if(!empty($v['product_id'])){
							$data['sales_id'] = $sales_id;
							$data['product_id'] = $v['product_id'];
							$data['warehouse_id'] = $v['warehouse_id'];
							$data['amount'] = $v['amount'];
							$data['unit_price'] = $v['unit_price'];
							$data['discount_rate'] = $v['discount_rate'];
							$data['tax_rate'] = $v['tax_rate'];
							$data['description'] = $v['description'];
							//在编辑时，如果又添加商品，根据是否存在sales_product_id来进行编辑或添加
							if(empty($v['sales_product_id'])){
								//添加
								$result_sales_product= $m_sales_product->add($data);
								if(empty($result_sales_product)){
									$operation_flag = false;
									break;
								}
							}else{
								//编辑
								$result_sales_product = $m_sales_product->where('sales_product_id = %d', $v['sales_product_id'])->save($data);
								if($result_sales_product === false){
									$operation_flag = false;
									break;
								}
							}
						}
						//在编辑时，如果从原来的商品中去除一条信息，则删除该产品
						if($v['sales_product_id'] && empty($v['product_id'])){
							$result_sales_product = $m_sales_product->where('sales_product_id = %d', $v['sales_product_id'])->delete();
							if($result_sales_product == 0 || $result_sales_product === false){
								$operation_flag = false;
							}
						}
					}
					
					if($operation_flag){
						alert('success', L('EDIT_SALES_RETURN_SUCCESSED'), U('sales/salesreturn'));
					}else{
						$this->error(L('ERROR_PROCESSING_OF_GOODS_MODIFY_THE_SALES_RETURN_FAILURE'), U('sales/editsalesreturn'));
					}
				}else{
					$this->error(L('EDIT_SALES_RETURN_FAILED'), U('sales/salesreturn'));
				}
			}else{
				$this->error($m_sales->getError(), U('sales/salesreturn'));
			}
		}else{
			$sales['creator_role_name'] = M('user')->where('role_id = %d', $sales['creator_role_id'])->getField('name');
			$m_sales_product = M('salesProduct');
			$m_product = M('product');
			$sales_product = $m_sales_product->where('sales_id = %d',$sales['sales_id'])->order('sales_product_id ASC')->select();
			foreach($sales_product as $k=>$v){
				$sales_product[$k]['product'] = $m_product->where('product_id = %d',$v['product_id'])->find();
			}
			$this->sales = $sales;
			$this->sales_product = $sales_product;
			$this->warehouse = M('warehouse')->order('warehouse_id ASC')->select();
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	public function delete(){
		$ids = $_POST['sales_id'];
		if(empty($ids)){
			alert('error',L('PLEASE_SELECT_TO_DELETE_ORDER'), $_SERVER['HTTP_REFERER']);
		}else{
			$alert_msg = '';
			$m_sales = M('sales');
			if(!session('?admin')){
				foreach($ids as $v){
					$result = $m_sales->where('sales_id = %d', $v)->find();
					if ($result['is_checked']){
						$alert_msg .= '"'.$result['subject'].'"、';
					}
				}
			}
			if($alert_msg == ''){
				foreach($ids as $v){
					actionLog($v);
				}
				//删除
				$where['sales_id'] = array('in', $ids);
				//如果是管理员，则删除相关信息
				if(session('?admin')){
					//删除相关合同销售单
					$contract_sales = M('rContractSales')->where($where)->find();
					if($contract_sales) M('rContractSales')->where($where)->delete();
					//删除相关应收款和付款单
					$payables = M('payables')->where($where)->getField('payables_id',true);
					if($payables) M('paymentorder')->where('payables_id in (%s)',implode(',',$payables))->delete();
					if($payables) M('payables')->where('payables_id in (%s)',implode(',',$payables))->delete();
					//删除相关应付款和付款单
					$receivables = M('receivables')->where($where)->getField('receivables_id',true);
					if($receivables) M('receivingorder')->where('receivables_id in (%s)',implode(',',$receivables))->delete();
					if($receivables) M('receivables')->where('receivables_id in (%s)',implode(',',$receivables))->delete();
				}
				//删除销售单相关产品
				$sales_product = M('salesProduct')->where($where)->find();
				if($sales_product) M('salesProduct')->where($where)->delete();
				$effect_rows = $m_sales->where($where)->delete();
				if($effect_rows){
					alert('success', L('DELETED_SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				//提示并返回
				$alert_msg .= '，审核通过后的单据不可删除！';
				alert('error', $alert_msg, $_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	//审核
	public function check(){
		$sales_id = $this->_get('id','intval');
		$m_sales = M('sales');
		if(!$sales_id){
			alert('error', L('PARAMETER_ERROR'), U('sales/index'));
		}
		if(!$sales = $m_sales->where('sales_id = %d', $sales_id)->find()) {
			alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
		}
		$contract_id = M('rContractSales')->where('sales_id = %d && sales_type = 1',$sales_id)->getField('contract_id');
		if($sales['is_checked'] != 1){
			if($sales['status'] == 99){
				$return_data['check_role_id'] = session('role_id');
				$return_data['is_checked'] = 1;
				$result = $m_sales->where('sales_id = %d', $sales_id)->save($return_data);
				if($result){
					$payables = M('payables');
					$r_data['type'] = 2;
					$r_data['name'] = 'YFK'.date('Ymd').mt_rand(1000,9999);
					$r_data['price'] = $sales['sales_price'];;
					$r_data['price'] = $sales['sales_price'];
					//$r_data['supplier_id'] = $purchase['supplier_id'];
					$r_data['sales_id'] = $sales['sales_id'];
					$r_data['sales_code'] = $sales['sn_code'];
					$r_data['sales_code'] = $sales['sn_code'];
					$r_data['contract_id'] = $contract_id;
					$r_data['customer_id'] = $sales['customer_id'];
					$r_data['pay_time'] = time();
					$r_data['creator_role_id'] = session('role_id');
					$r_data['owner_role_id'] = session('role_id');
					$r_data['create_time'] = time();
					$r_data['update_time'] = time();
					$r_data['status'] = 0;
					$payables->add($r_data);

				
					actionLog($sales_id);
					//发送站内信
					$url = U('sales/edit','id='.$sales_id);
					sendMessage($sales['creator_role_id'],'您创建的《<a href="'.$url.'">'.$sales['sn_code'].' - '.$sales['subject'].'</a>》<font style="color:green;">已通过审核</font>！',1);
					//发站内信给仓库
					if($sales['type'] == '1'){
						$enter_userId = getRoleByPer(array('sales/enter'));
						foreach($enter_userId as $v){
							$b=U('sales/edit','id='.$sales_id);
							sendMessage($v,'《<a href="'.$b.'">'.$sales['sn_code'].' - '.$sales['subject'].'</b>》<font style="color:green;">已通过审核，库管人员可进行入库操作</font>！',1);
						}
					}
					//发站内信给财务
					if($sales['type'] == '1'){
						$paymentorder_userId = getRoleByPer(array('finance/add_paymentorder'));
						foreach($paymentorder_userId as $v){
							$d=U('sales/edit','id='.$sales_id);
							sendMessage($v,'《<a href="'.$d.'">'.$sales['sn_code'].' - '.$sales['subject'].'</a>》<font style="color:green;">已通过审核，财务人员可添加应付款单据</font>！',1);
						}
					}
					alert('success', L('CHECK_SUCCESS'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', L('CHECK_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('CAN_NOT_CHECK_THE_INBOUNDED_ORDER_AND_OUT_BOUNDED_ORDER'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error', L('THE_ORDER_HAS_BEEN_CHECKED_DO_NO_REPEAT_THE_OPERATION'), $_SERVER['HTTP_REFERER']);
		}
	}
	
	//撤销审核
	public function revokeCheck(){
		$sales_id = $this->_get('id','intval');
		$m_sales = M('sales');
		if(!$sales_id){
			alert('error', L('PARAMETER_ERROR'), U('sales/index'));
		}
		if(!$sales = $m_sales->where('sales_id = %d', $sales_id)->find()) {
			alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
		}

		if($sales['is_checked'] == 1){
			if($sales['type'] == 0){
				if(M('Receivables')->where('sales_id = %d', $sales['sales_id'])->count()){
					alert('error', L('CAN_NOT_REVOKE_CHECK_FINANCE_REASON'), $_SERVER['HTTP_REFERER']);
				}
			}elseif($sales['type'] == 1){
				if(M('Payables')->where('sales_id = %d', $sales['sales_id'])->count()){
					alert('error', L('CAN_NOT_REVOKE_CHECK_FINANCE_REASON'), $_SERVER['HTTP_REFERER']);
				}
			}
			if($sales['status'] == 97 || $sales['status'] == 99){
				$result = $m_sales->where('sales_id = %d', $sales_id)->setField('is_checked',0);
				if($result){
					actionLog($sales_id);
					//发送站内信
					$url=U('sales/view','id='.$sales_id);
					sendMessage($sales['creator_role_id'],'您创建的《<a href="'.$url.'">'.$sales['sn_code'].' - '.$sales['subject'].'</a>》<font style="color:red;">已被撤销审核</font>！',1);
					alert('success', L('REVOKE_CHECK_SUCCESS'), $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', L('REVOKE_CHECK_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('CAN_NOT_REVOKE_CHECK_THE_INBOUNDED_ORDER_AND_OUT_BOUNDED_ORDER'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error', L('THE_ORDER_HAS_BEEN_REVOKE_CHECKED_DO_NO_REPEAT_THE_OPERATION'), $_SERVER['HTTP_REFERER']);
		}
	}
	
	//销售单据统计
	public function analyticsOrder(){
		$m_sales = M('sales');
		$role_array = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		if(intval($_GET['role'])){
			$where['creator_role_id'] = array('eq', intval($_GET['role']));
		}else{
			if($_GET['department']){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			}else{
				$role_id_array = $role_array;
			}
			$where['creator_role_id'] = array('in', implode(',', $role_id_array));
		}
		
		if($_GET['start_time']){
			$start_time = strtotime($_GET['start_time']);
		}
		if($_GET['end_time']){
			$end_time = strtotime($_GET['end_time']);
		}else{
			$end_time = time();
		}
		if($start_time){
			$where['sales_time']= array(array('elt',$end_time),array('egt',$start_time), 'and');
		}else{
			$where['sales_time'] = array('elt',$end_time);
		}
		if($_GET['type'] != ''){
			$where['type'] = array('eq',intval($_GET['type']));
		}else{
			$where['type'] = array('eq',0);
		}
		if($_GET['is_checked'] != ''){
			$where['is_checked'] = array('eq',intval($_GET['is_checked']));
		}

		//统计报表
		$sales = $m_sales->where($where)->order('total_amount DESC')->select();
		$sales_count = $m_sales->where($where)->count();
		$sales_sum_count = 0;
		$sales_sum_money = 0.00;
		$r_contract = M('RContractSales');
		$m_contract = M('contract');
	
		foreach($sales as $k=>$v){
			if($v['type'] != 0){
				$sales[$k]['sn_code'] = $v['sn_code'];
			}else{
				$contract_id = $r_contract ->where('sales_id =%d',$v['sales_id'])->getField('contract_id');
				$sales[$k]['sn_code'] = $m_contract ->where('contract_id =%d',$contract_id)->getField('number');
			}
			$sales_sum_count += $v['total_amount'];
			$sales_sum_money += $v['sales_price'];
		}
		$sumArr = array(
			'count'=>$sales_sum_count,
			'money'=>$sales_sum_money,
		);
	
		//员工列表
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		$idArray = $below_ids;
		$roleList = array();
		foreach($idArray as $roleId){				
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		
		//部门列表
		$url = getCheckUrlByAction(MODULE_NAME,ACTION_NAME);
		$per_type =  M('Permission') -> where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		
		$this->assign('departmentList', $departmentList);
		$this->sumArr = $sumArr;
		$this->sales = $sales;
		$this->alert = parseAlert();
		$this->display();
	}
	
	//销售金额统计
	public function analyticsMoney(){
		$m_sales = M('sales');
		$role_array = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		if(intval($_GET['role'])){
			$condition['creator_role_id'] = array('eq', intval($_GET['role']));
		}else{
			if($_GET['department']){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			}else{
				$role_id_array = $role_array;
			}
			$condition['creator_role_id'] = array('in', implode(',', $role_id_array));
		}
		if($_GET['start_time']){
			$start_time = strtotime($_GET['start_time']);
		}
		if($_GET['end_time']){
			$end_time = strtotime($_GET['end_time']);
		}else{
			$end_time = time();
		}
		if($start_time){
			$where['sales_time']= array(array('elt',$end_time),array('egt',$start_time), 'and');
		}else{
			$where['sales_time'] = array('elt',$end_time);
		}
		if($_GET['type'] != ''){
			$where['type'] = array('eq',intval($_GET['type']));
		}else{
			$where['type'] = array('eq',0);
		}
		$where['is_checked'] = array('eq','1');

		//X轴员工
		$rolenameArr = M('user')->where(array('role_id'=>$condition['creator_role_id']))->order('role_id asc')->getField('name', true);
		$json_role_name = json_encode($rolenameArr);
		//Y轴金额,为保证X轴与Y轴数据对应，需循环X轴数据来获取X轴顺序，并以X轴数据为基础存储Y轴数据
		$moneyArr = array();
		$role_idArr = explode(',', $condition['creator_role_id'][1]);
		sort($role_idArr);
		foreach($role_idArr as $v){
			$where['creator_role_id'] = $v;
			$money = $m_sales->where($where)->sum('sales_price');
			$money = empty($money) ? 0 : floatval($money);
			$moneyArr[] = $money;
		}
		$json_money = json_encode($moneyArr);
		
		//员工列表
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		$idArray = $below_ids;
		$roleList = array();
		foreach($idArray as $roleId){				
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		
		//部门列表
		$url = getCheckUrlByAction(MODULE_NAME,ACTION_NAME);
		$per_type =  M('Permission') -> where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		$this->assign('departmentList', $departmentList);
		$this->json_role_name = $json_role_name;
		$this->json_money = $json_money;
		$this->alert = parseAlert();
		$this->display();
	}
	
	/**
	 * 打印
	 **/
	public function prevprint(){
		$sales_id = intval($_REQUEST['id']);
		$d_sales = D('SalesmoduleView');
		if(!$sales_id){
			alert('error', L('PARAMETER_ERROR'), U('sales/index'));
		} elseif(!$sales = $d_sales->where('sales_id = %d', $sales_id)->find()) {
			alert('error', L('THE_ORDER_DOES_NOT_EXIST_OR_HAS_BEEN_DELETED'),$_SERVER['HTTP_REFERER']);
		} else {
			$sales['creator_role_name'] = M('user')->where('role_id = %d', $sales['creator_role_id'])->getField('name');
			$contract_id = M('rContractSales')->where('sales_id = %d && sales_type = 1',$sales_id)->getField('contract_id');
			$sales['contract_num'] = M('contract')->where('contract_id =%d',$contract_id)->getField('number');
			$m_sales_product = M('salesProduct');
			$m_product = M('product');
			$sales_product = $m_sales_product->where('sales_id = %d',$sales['sales_id'])->order('sales_product_id ASC')->select();
			//折扣额总数
			$total_discount_price = 0.00;
			//税额总数
			$total_tax_price = 0.00;
			//税前总数
			$total_no_tax_price = 0.00;
			//税后总数
			$total_prime_price = 0.00;

			foreach($sales_product as $k=>$v){
				$sales_product[$k]['product'] = $m_product->where('product_id = %d',$v['product_id'])->find();
				$sales_product[$k]['warehouse_name'] = M('warehouse')->where('warehouse_id = %d', $v['warehouse_id'])->getField('name');
				//折扣额
				$sales_product[$k]['discount'] = bcmul($v['unit_price'] * $v['amount'], $v['discount_rate']/100, 2);
				//税前
				$sales_product[$k]['no_tax_price'] = bcsub($v['unit_price'] * $v['amount'], $sales_product[$k]['discount'], 2);
				//税额
				$sales_product[$k]['tax_price'] = bcmul($v['tax_rate']/100, $sales_product[$k]['no_tax_price'], 2);
				//税后
				$sales_product[$k]['prime_price'] = bcadd($sales_product[$k]['no_tax_price'], $sales_product[$k]['tax_price'], 2);
				
				$total_discount_price += $sales_product[$k]['discount'];
				$total_tax_price += $sales_product[$k]['tax_price'];
				$total_no_tax_price += $sales_product[$k]['no_tax_price'];
				$total_prime_price += $sales_product[$k]['prime_price'];
			}
			$sales['total_discount_price'] = $total_discount_price;
			$sales['total_tax_price'] = $total_tax_price;
			$sales['total_no_tax_price'] = $total_no_tax_price;
			$sales['total_prime_price'] = $total_prime_price;
			// println($sales,false);
			$this->sales = $sales;
			$this->sales_product = $sales_product;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	 * 销售单列表无权限限制，显示所有销售单
	 * 在添加和编辑工单时，销售单弹出框中使用到
	 **/
	public function allListDialog(){
		$d_sales = D('SalesmoduleView');
		$where['type'] = '0';
		$where['is_checked'] = 1;
		$sales = $d_sales->where($where)->order('create_time desc')->limit(10)->select();
		$count = $d_sales->where($where)->count();
	
		$this->sales = $sales;
		$this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
		$this->count_num = $count;
		$this->display();
	}
	
	/**
	 * 销售单列表分页，显示所有销售单
	 **/
	public function allChangeContent(){
		if($this->isAjax()){
			$d_sales = D('SalesmoduleView'); // 实例化User对象
			import('@.ORG.Page');// 导入分页类
			$params = array();
			$where['type'] = '0';
			$where['is_checked'] = 1;

			$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
				if	('development_time' == $field || 'listing_time' == $field) $search = is_numeric($search)?$search:strtotime($search);;
				if (!empty($field)) {
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
				}
				$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
			}
			
			$count = $d_sales->where($where)->count();// 查询满足要求的总记录数
			$list = $d_sales->where($where)->order('create_time desc')->Page($p.',10')->select();

			$data['list'] = $list;
			$data['p'] = $p;
			$data['count'] = $count;
			$data['total'] = $count%10 > 0 ? ceil($count/10) : $count/10;
			$this->ajaxReturn($data,"",1);
		}
	}
}