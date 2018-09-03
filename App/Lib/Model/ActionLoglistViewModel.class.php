<?php 
	class ActionLoglistViewModel extends ViewModel{
		public $viewFields = array(
			'action_log'=>array('log_id','role_id','module_name','action_name','param_name','action_id','content','create_time','_type'=>'LEFT'),
			'log'=>array('log_id'=>'logid','_on'=>'action_log.action_id=log.log_id and action_log.module_name="log"', '_type'=>'LEFT'),
			'business'=>array('is_deleted'=>'b_deleted','business_id','_on'=>'action_log.action_id=business.business_id and action_log.module_name="business"','_type'=>'LEFT'),
			'customer'=>array('is_deleted'=>'c_deleted','customer_id','_on'=>'action_log.action_id=customer.customer_id and action_log.module_name="customer"','_type'=>'LEFT'),
			'sign'=>array('sign_id','customer_id'=>'sign_customer_id','x','y','title','address','log','_on'=>'action_log.action_id=sign.sign_id and action_log.module_name="sign"','_type'=>'LEFT'),
			'leads'=>array('is_deleted'=>'d_deleted','leads_id','_on'=>'action_log.action_id=leads.leads_id and action_log.module_name="leads"','_type'=>'LEFT'), 
			'sales'=>array('sales_id','_on'=>'action_log.action_id=sales.sales_id and action_log.module_name="sales"','_type'=>'LEFT'),
			'contract'=>array('is_deleted'=>'e_deleted','contract_id','_on'=>'action_log.action_id=contract.contract_id and action_log.module_name="contract"','_type'=>'LEFT'),
		);
	}