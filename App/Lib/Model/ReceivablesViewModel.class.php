<?php 
	class ReceivablesViewModel extends ViewModel{
		public $viewFields = array(
			'receivables'=>array('*', '_type'=>'LEFT'),					
			'customer'=>array('name'=>'customer_name', '_on'=>'receivables.customer_id=customer.customer_id' ,'_type'=>'LEFT'),
			'contract'=>array('number'=>'contract_name', '_on'=>'receivables.contract_id=contract.contract_id'),
			'role'=>array('_on'=>'receivables.creator_role_id=role.role_id', '_type'=>'LEFT'),
			'user'=>array('full_name'=>'creator_name', '_on'=>'role.user_id = user.user_id')
		);
	}