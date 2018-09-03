<?php 
	class SalesmoduleViewModel extends ViewModel{
		public $viewFields = array(
			'sales'=>array('sales_id','customer_id','creator_role_id','sn_code','subject','prime_price','sales_price','total_amount','type','status','is_checked','check_role_id','discount_price','shipping_address','receiving_people','receiving_phone','description','create_time','sales_time','logistics_number','final_discount_rate','_type'=>'LEFT'),
			'customer'=>array('name'=>'customer_name','_on'=>'sales.customer_id=customer.customer_id'),
		);
	}