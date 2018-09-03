<?php 
	class paymentorderViewModel extends ViewModel{
		public $viewFields = array(
			'paymentorder'=>array('*', '_type'=>'LEFT'),
			'payables'=>array('customer_id','name'=>'payables_name','price'=>'price', '_on'=>'paymentorder.payables_id=payables.payables_id','_type'=>'LEFT'),
			'role'=>array('_on'=>'paymentorder.creator_role_id=role.role_id', '_type'=>'LEFT'),
			'user'=>array('full_name'=>'creator_name', '_on'=>'role.user_id = user.user_id')
		);
	}