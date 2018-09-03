<?php
/**
 * 前端商城模块
 * @author
 */
class ShopAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array('login','register','index','lostpw','getimageverify'),
			'allow'=>array('')
		);
		B('Authenticate',$action);
	}

	/**
	 * 客户注册
	 * @param 
	 * @author
	 * @return 
	 */
	public function register(){
		$m_member = M('Member');
		if($this->isAjax()){
			$name = trim($_POST['name']);
			if(!$name){
				$this->ajaxReturn('','请填写您的姓名！',0);
			}
			//手机号
			$telephone = trim($_POST['telephone']);
			if(empty($telephone)){
				$this->ajaxReturn ( '',"请填写您的手机号码！" ,0);
			}elseif($m_member->where('telephone = "%s"', $telephone)->find()){
				$this->ajaxReturn ( '',"该手机号码已注册，请直接登录！",0);
			}elseif(!is_phone($telephone)){
				$this->ajaxReturn ( '',"手机号码格式不正确！" ,0);
			}
			//图片验证码
			$img_verify = trim($_POST['img_verify']);
			if(session('img_verify') != md5($img_verify)){
				$this->ajaxReturn('', '图片验证码输入错误' , 0);
			}
			//密码
			$password = trim($_POST['password']);
			if(empty($password) || strlen($password) < 6){
				$this->ajaxReturn ( '',"密码长度不能低于6位！" ,0);
			}else{
				$data['name'] = $name;
				$data['telephone'] = $telephone;
				$data['salt'] = substr(md5(time()),0,4);
				$data['password'] = md5(md5($password).$data['salt']);
				$data['reg_ip'] = get_client_ip();
				$data['create_time'] = time();
				$data['update_time'] = time();
				if($m_member->where('reg_ip = "%s" and create_time > %d', $data['reg_ip'], (time()-86400))->count() > 5){
					$this->ajaxReturn ( '',"每个IP24小时内最多注册5个账号！",0 );
				}elseif($m_member->create($data)){
					if($member_id = $m_member->add()) {
						session('name', $data['name']);
						session('member_id', $member_id);
						$this->ajaxReturn('','注册成功!',1);
					}else{
						$this->ajaxReturn ( '',"注册失败！",0);
					}
				}else{
					$this->ajaxReturn ( '',"注册失败！",0 );
				}
			}
		}else{
			$this->display();
		}
	}

	/**
	 * 客户登录
	 * @param 
	 * @author
	 * @return 
	 */
	public function login(){
		// $ips = get_client_ip();
		if(session('member_id')){
			notice('您已经登录！',U('shop/index'));
		}
		if($this->isAjax()){
			$telephone = trim($_POST['telephone']);
			if(empty($telephone)){
				$this->ajaxReturn('','请填写手机号码！',0);
			}else{
				$where['telephone'] = $telephone;
			}
			$password = $this->_post('password','trim');
			if (empty($password)) {
				$this->ajaxReturn('','请输入密码！',0);
			}else{
				$m_member = M('Member');
				$member_info = $m_member->where($where)->find();
				if ($member_info['password'] == md5(md5($_POST['password']) . $member_info['salt'])) {
					session('member_id', $member_info['member_id']);
					session('member_name', $member_info['name']);
					$this->ajaxReturn('','登录成功！',1);
				}else {
					$this->ajaxReturn('','手机号码或密码错误！',0);
				}
			}
		}else{
			$this->display();
		}
	}
	/**
	 * 图片验证码
	 * @param
	 * @author
	 * @return 
	 */
	public function getImageVerify(){
		import('@.ORG.Image');
        Image::buildImageVerify();
	}

	/**
	 * 短信验证码
	 * @param 
	 * @author
	 * @return 
	 */
	public function send_verify(){
		$by = $this->_request('by','trim');
		$telephone = intval($_POST['telephone']);
		$img_verify = $_POST['img_verify'];
		if(!is_mobile($telephone)){
			$this->ajaxReturn(0,'请正确输入手机号码！',0);
		}elseif(!trim($img_verify)){
			$this->ajaxReturn(0,'请输入图片验证码',0);
		}elseif(session('img_verify') != md5($img_verify)){
			$this->ajaxReturn(0,'图片验证码输入错误',0);
		}elseif(M('user')->where(array('telephone'=>$telephone))->find() && $by !== 'lostpw'){
			$this->ajaxReturn(0,'手机号码已存在！',0);
		}else{
			if(M('verify_code')->where(array('telephone'=>$telephone,'type'=>1))->count() > 50){
				$this->ajaxReturn(0,'检测到您的手机号码获取验证码次数过多，已被加入黑名单',0);
			}elseif(M('verify_code')->where(array('client_ip'=>get_client_ip()))->count() > 50){
				$this->ajaxReturn(0,'您获取验证码次数过多',0);
			}elseif((time()-session('verify_time')) < 60){
				$this->ajaxReturn(0,'您的手机号码获取验证码过于频繁，请稍后重试',0);
			}else{
				$verify = mt_rand(10000,99999);
				session('verify',$verify);
				session('verify_time',time());
				if(sendsms($telephone, '您的验证码为：'.$verify.'如非本人操作，请忽略本短信')){
					M('verify_code')->add(array('telephone'=>$telephone, 'client_ip'=>get_client_ip(), 'code'=>$verify,'create_time'=>time(),'type'=>1));
					$this->ajaxReturn('','发送成功，请注意查收！',1);
				}else{
					$this->ajaxReturn(0,'程序异常，请联系管理员！',0);
				}
			}
		}
	}

	/**
	 * 发送短信
	 * @param 
	 * @author
	 * @return 
	 */
	public function sendSms(){
		if($this->isPost()){
			$phoneNum = trim($_POST['phoneNum']);
			$message = trim($_POST['smsContent']);
			if($_POST['settime']){
				$send_time = strtotime(trim($_POST['sendtime']));
				if($send_time > time()){
					$sendtime = date('YmdHis',$send_time);
				}
			}
			$current_sms_num = getSmsNum();

			$phoneNum = str_replace(" ","",$phoneNum);
			$phone_array = explode(chr(10),$phoneNum);
			if(sizeof($phone_array) > $current_sms_num){
				notice('短信余额不足，请联系管理员，及时充值!',$_SERVER['HTTP_REFERER']);
			}
			$fail_array = array();
			$success_array = array();
			if($phoneNum && $message){
				if(strpos($message,'{$name}',0) === false){
					foreach($phone_array as $k=>$v){
						if($v){
							$phone = substr($v,0,11);
							if(is_phone($phone)){
								$success_array[] = $phone;
							}else{
								$fail_array[] = $v;
							}
						}
					}
					if(!empty($fail_array)){
						$fail_message = '部分号码格式不正确，导致发送失败;具体如下:'.implode(',', $fail_array);
					}
					//echo '发送成功!';die();
					$result = sendGroupSMS(implode(',', $success_array),$message,'sign_name', $sendtime);
					if($result == 1){
						notice('发送成功！',$_SERVER['HTTP_REFERER']);
					}else{
						notice('短信通知发送失败，错误代码:'.$result,$_SERVER['HTTP_REFERER']);
					}
				}else{
					foreach($phone_array as $k=>$v){
						$real_message = $message;
						$name = '';
						if($v){
							$no = str_replace(" ","",$v);
							$phone = substr($no,0,11);
							if(is_phone($phone)){
								if(strpos($v,',',0) === false){
									$info_array = explode('，', $v);
								}else{
									$info_array = explode(',', $v);
								}
								$real_message = str_replace('{$name}',$info_array[1],$real_message);
								$result = sendsms($phone, $real_message, 'sign_name', $sendtime);
								if($result<0 && $k==0){
									notice( '短信通知发送失败，错误代码'.$result,$_SERVER['HTTP_REFERER']);
								}
							}else{
								$fail_array[] = $v;
							}
						}
					}

					if(!empty($fail_array)){
						$fail_message = '部分号码格式不正确，导致发送失败;具体如下:'.implode(',', $fail_array);
					}
					notice('发送成功,',U('setting/sendsms'));

				}
			}else{
				notice('信息不完整，请确认短信内容和收件人手机号',$_SERVER['HTTP_REFERER']);
			}
		}else{
			$current_sms_num = getSmsNum();
			$user_ids = trim($_GET['user_ids']);
			$open_ids = trim($_GET['open_ids']);
			if($user_ids){
				$contacts = M('User')->where('user_id in (%s)', $user_ids)->field('name,telephone')->select();
				$this->contacts = $contacts;
			}elseif($open_ids){
				$contacts = M('Open')->where('open_id in (%s)', $open_ids)->field('telephone')->select();
				$this->contacts = $contacts;
			}
			$this->current_sms_num = $current_sms_num;
			$this->display();
		}
	}

	/**
	 * 验证短信验证码是否正确
	 * @param 
	 * @author
	 * @return 
	 */
	public function check_verify(){
		if($this->isAjax()){
			$code = intval($_POST['code']);
			$user_id = session('user_id');
			$m_verify_code = M('VerifyCode');
			$verify_code = $m_verify_code->where(array('user_id'=>$user_id))->order('create_time desc')->find();

			if(empty($code) || empty($user_id)){
				$this->ajaxReturn('','无法获取您的手机号码，请拨打官方客服电话！',0);
			}else{
				$s_code = $verify_code['code'];
				if($code == $s_code){
					if((time()-$verify_code['create_time']) > 60*30){
						$this->ajaxReturn('','error',2);
					}else{
						M('User')->where('user_id = %d',$user_id)->setField('is_verify',2);
						$this->ajaxReturn($s_code,'success',1);
					}
				}else{
					$this->ajaxReturn('','error',0);
				}
			}
		}
	}

	/**
	 * 退出登录
	 * @param 
	 * @author
	 * @return 
	 */
	public function logout() {
		unset($_SESSION['member_id']);
		unset($_SESSION['member_name']);
		notice('已经退出！',U('shop/login'));
	}

	/**
	 * 找回密码
	 * @param 
	 * @author
	 * @return 
	 */
	public function lostpw() {               
		if($this->isAjax()){
			if ($_POST['telephone'] || $_POST['email']){
				$user = M('User');
				if ($_POST['telephone']){
					$info = $user->where('telephone = "%s"',trim($_POST['telephone']))->find();
					session('newpas_id',$info['user_id']);
					if(!isset($info) || $info == null){
						$this->error('该手机号码没有开通');
					}
				} elseif ($_POST['email']){
					$info = $user->where('email = "%s"',trim($_POST['email']))->find();
					if (ereg('^([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['email'])){
						if (!isset($info) || $info == null){
							$this->error('没有用户使用该邮箱');
						}
					}else{
						$this->error('邮箱格式不正确！');
					}
				}
				$time = time();
				$user->where('user_id = ' . $info['user_id'])->save(array('lostpw_time' => $time));
				if($user){
					notice('验证通过，请填写新的密码',U('server/resetpw'));
				}else{
					notice('验证失败，请重试！',U('server/resetpw'));
				}

			} else {
				notice('请输入手机号码或注册邮箱！',U('server/lostpw'));
			}
		} else{
			$this->display();
		}
	}

	/**
	 * 密码重置
	 * @param 
	 * @author
	 * @return 
	 */
	public function resetpw(){
		$member_id = session('newpas_id');
		if(!$member_id){
			$this->ajaxReturn ( '',"找回密码链接已失效，请重新尝试找回密码！" ,0);
		}
		if($this->isAjax()){
			$password = trim($_REQUEST['password']);
			if(empty($password) || strlen($password) < 6){
				$this->ajaxReturn ( '',"密码长度不能低于6位！" ,0);
			}
			$m_member = M('Member');
			$member_info = $m_member->where('member_id = %d', $member_id)->find();
			if (is_array($member_info) && !empty($member_info)) {
				if ($password) {
					$data = array();
					$data['password'] = md5(md5($password) . $member_info['salt']);
					$data['lostpw_time'] = time();
					if($m_member->where('member_id = %d',$member_id)->save($data)){
						$this->ajaxReturn ( '','密码修改成功，请登录！' ,1);
					}else{
						$this->ajaxReturn ( '','密码修改失败，请重试！' ,0);
					}
				}
			}else{
				$this->ajaxReturn ( '','参数错误，请联系官方客服！' ,0);
			}
		}else{
			$this->display();
		}
	}

	/**
	 * 个人中心
	 * @param 
	 * @author
	 * @return 
	 */

	/**
	 * 收货地址
	 * @param 
	 * @author
	 * @return 
	 */
	public function stress(){
		$member_id = session('member_id');
		if(!$member_id){
			notice('请先登录！',U('shop/login'));
		}
		$m_member = M('Member');
		$m_member_address = M('MemberAddress');
		$address_list = $m_member_address->where('member_id = %d',$member_id)->select();
		$this->address_list = $address_list;
		$this->display();
	}

	/**
	 * 添加收货地址
	 * @param 
	 * @author
	 * @return 
	 */
	public function stressAdd(){
		$member_id = session('member_id');
		if(!$member_id){
			notice('请先登录！',U('shop/login'));
		}
		$m_member_address = M('MemberAddress');
		if($this->isAjax()){
			$data = array();
			$data['member_id'] = $member_id;
			$data['address'] = trim($_POST['address']);
			$data['create_time'] = time();
			$data['update_time'] = time();
			if($m_member_address->add($data)){
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','error',0);
			}
		}
	}

	/**
	 * 编辑收货地址
	 * @param 
	 * @author
	 * @return 
	 */
	public function stressEdit(){
		$member_id = session('member_id');
		if(!$member_id){
			$this->ajaxReturn('','请先登录！',2);
		}
		$m_member_address = M('MemberAddress');
		if($this->isAjax()){
			$address_id = intval($_POST['id']);
			$address_info = $m_member_address->where(array('member_id'=>$member_id,'address_id'=>$address_id))->find();
			if(!$address_info){
				$this->ajaxReturn('','参数错误！',0);
			}
			$data = array();
			$data['address'] = trim($_POST['address']);
			$data['upadte_time'] = time();
			if($m_member_address->where(array('member_id'=>$member_id,'address_id'=>$address_id))->save($data)){
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','error',0);
			}
		}
	}

	/**
	 * 删除收货地址
	 * @param 
	 * @author
	 * @return 
	 */
	public function stressDel(){
		$member_id = session('member_id');
		if(!$member_id){
			$this->ajaxReturn('','请先登录！',2);
		}
		$m_member_address = M('MemberAddress');
		if($this->isAjax()){
			$address_id = intval($_POST['id']);
			$address_info = $m_member_address->where(array('member_id'=>$member_id,'address_id'=>$address_id))->find();
			if(!$address_info){
				$this->ajaxReturn('','参数错误！',0);
			}
			if($m_member_address->where(array('member_id'=>$member_id,'address_id'=>$address_id))->delete()){
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','error',0);
			}
		}
	}

	/**
	 * 客户订单列表
	 * @param payment_state 支付状态{0:未支付,1:已支付（线上）,2:已支付（线下）,3:已退还}
	 * @param check_state 订单状态{0:未接单,1:已接单,2:订单取消}
	 * @param status 订单阶段{0:带确认,1:生产中,2:配送中,3:已完成}
	 * @author
	 * @return 
	 */
	public function order(){
		$this->display();
	}

	/**
	 * 订单创建
	 * @param 
	 * @author
	 * @return 
	 */
	public function orderAdd(){
		$member_id = session('member_id');
		$m_member = M('Member');
		$m_member_address = M('MemberAddress');
		$m_product = M('Product');
		if($this->isPost()){
			if (!$member_id) {
				$this->ajaxReturn('','请先登录！',2);
			}
			$product_ids = $_POST['product_ids'];
			//计算订单是否异常
			$total_product_price = 0;
			foreach($product_ids as $k=>$v){
				$product_info = array();
				$product_info = $m_product->where(array('product_id'=>$v[0],'is_delete'=>0,'is_shop'=>1))->getField('suggested_price')->find();
				if(!$product_info){
					$this->ajaxReturn('','订单数据异常，请重新提交！',0);
				}
			}
			
			$this->ajaxReturn($product_ids,'异常',0);
		}
		$address_id = intval($_GET['address_id']);
		if($address_id){
			$address_ids = $m_member_address->where()->getField('id',true);
			if(!in_array($address_id,$address_ids)){
				notice('参数错误！',U('shop/index'));
			}
			$address_info = $m_member_address->where(array('member_id'=>$member_id,'id'=>intval($_GET['address_id'])))->find();
		}else{
			//默认收货地址
			$member_info = $m_member->where('member_id = %d',$member_id)->field('name,address_id')->find();
			if($member_info['address_id']){
				$address_info = $m_member_address->where('id = %d',$member_info['address_id'])->find();
			}else{
				$address_info = $m_member_address->where('member_id = %d',$member_id)->order('id desc')->find();
			}
		}
		$this->address_info = $address_info;
		$this->display();
	}

	/**
	 * 商城产品列表
	 * @param 
	 * @author
	 * @return 
	 */
	public function index(){
		$member_id = session('member_id');
		$m_member = M('Member');		
		$m_product = M('Product');
		$m_product_category = M('ProductCategory');
		$m_product_images = M('ProductImages');
		$m_member_address = M('MemberAddress');
		// $category_list = $m_product_category->where(array('is_shop'=>1))->select();
		$category_list = $m_product_category->select();
		$category_ids = array();
		foreach ($category_list as $k => $v) {
			$category_ids[] = $v['category_id'];
		}
		$product_category_list = getSubCategoryByShop(0, $category_list, '',1);
// println($product_category_list);
		$where = array();
		if($_GET['category_id'] && in_array(intval($_GET['category_id']), $category_ids)){
			$where['category_id'] = intval($_GET['category_id']);
		}else{
			$where['category_id'] = array('gt',0);
		}
		// $where['is_shop'] = 1;
		import('@.ORG.Page');// 导入分页类
		$p = isset($_GET['p'])?$_GET['p']:1;
		$product_list = $m_product->where($where)->Page($p.',6')->select();
		$count = $m_product->where($where)->count();
		foreach($product_list as $k=>$v){
			$product_list[$k]['product_img_info'] = $m_product_images->where(array('product_id'=>$v['product_id'],'is_main'=>1))->field('path,thumb_path')->find();
			$product_list[$k]['category_name'] = $m_product_category->where('category_id = %d',$v['category_id'])->getField('name');
		}
		$Page = new Page($count,6);// 实例化分页类 传入总记录数和每页显示的记录数
		$show = $Page->show();// 分页显示输出
		$this->assign('page',$show);// 赋值分页输出
		//默认收货地址
		$member_info = $m_member->where('member_id = %d',$member_id)->field('name,address_id')->find();
		if($member_info['address_id']){
			$address_info = $m_member_address->where('id = %d',$member_info['address_id'])->find();
		}else{
			$address_info = $m_member_address->where('member_id = %d',$member_id)->order('id desc')->find();
		}
		$this->member_info = $member_info;
		$this->address_info = $address_info;
		$this->product_category_list = $product_category_list;
		$this->product_list = $product_list;
		$this->display();
	}

	/**
	 * 商城选择收货地址
	 * @param 
	 * @author
	 * @return 
	 */
	public function selAddress(){
		$member_id = session('member_id');
		if(!$member_id && $this->isAjax()){
			$this->ajaxReturn('','请先登录！',2);
		}
		$m_member_address = M('MemberAddress');
		$address_list = $m_member_address->where('member_id = %d',$member_id)->select();
		$this->address_list = $address_list;
		$this->display();
	}
}