<?php 
	class BusinessCustomerModel extends ViewModel{
		public $viewFields = array(
			'business'=>array('*','_type'=>'LEFT'),
			'customer'=>array('name'=>'customer_name','owner_role_id'=>'customer_owner_role_id','_on'=>'business.customer_id=customer.customer_id')
		);
	}