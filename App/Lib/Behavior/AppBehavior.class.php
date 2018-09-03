<?php 

class AppBehavior extends Behavior {
	protected $options = array();
	
	public function run(&$params) {
		
		
		if (!session('?error')) {
			if ($m == 'Error') {
				redirect(U('index/index'));
			}

			import('@.ORG.Scan');
			if (defined('APP_TYPE') && APP_TYPE == 'Mobile') {
				C('DEFAULT_C_LAYER','Mobile');
			}
			if (defined('APP_TYPE') && APP_TYPE == 'Vue') {
				C('DEFAULT_C_LAYER','Vue');
			}

			if(strtolower(ACTION_NAME) == 'view'){
				if(strtolower(MODULE_NAME) == 'customer'){
					$type = 'customer';
					$id = intval($_GET['id']);
					$title = M('customer')->where('customer_id = %d', $id)->getField('name');
				}elseif(strtolower(MODULE_NAME) == 'business'){
					$type = 'business';
					$id = intval($_GET['id']);
					$title = M('business')->where('business_id = %d', $id)->getField('name');
				}elseif(strtolower(MODULE_NAME) == 'contract'){
					$type = 'contract';
					$id = intval($_GET['id']);
					$title = M('contract')->where('contract_id = %d', $id)->getField('contract_name');
				}elseif(strtolower(MODULE_NAME) == 'leads'){
					$type = 'leads';
					$id = intval($_GET['id']);
					$title = M('leads')->where('leads_id = %d', $id)->getField('name');
				}elseif(strtolower(MODULE_NAME) == 'examine'){
					$type = 'examine';
					$id = intval($_GET['id']);
					$t_title = M('examine')->where('examine_id = %d', $id)->getField('content');
					$title = msubstr($t_title,0,6);
				}elseif(strtolower(MODULE_NAME) == 'finance'){
					$type = 'finance';
					$id = intval($_GET['id']);
					$t = trim($_GET['t']);
					$title = M($t)->where($t.'_id = %d', $id)->getField('name');
				}
				if($id && $title){
					$record =  $data = array(
						'type'    => $type,
						'id'      => $id,
						'title'   => $title
					);
					doRecord($record);
				}
			}
			
			if (!file_exists( CONF_PATH.'install.lock') && MODULE_NAME != 'Install') {
				redirect(U('install/index'));
			} elseif (MODULE_NAME != 'Install') {
				if (!F('smtp')) {
					$value = M('Config')->where('name = "smtp"')->getField('value');
					F('smtp',unserialize($value));			
				}
				C('smtp', F('smtp'));
				if (!F('defaultinfo')) {
					$value = M('Config')->where('name = "defaultinfo"')->getField('value');
					F('defaultinfo',unserialize($value));			
				}
				C('defaultinfo', F('defaultinfo'));
				if (!F('sms') && $value = M('Config')->where('name = "sms"')->getField('value')) {
					F('sms',unserialize($value));
					C('sms', F('sms'));
				}
			}
		}
	}
}