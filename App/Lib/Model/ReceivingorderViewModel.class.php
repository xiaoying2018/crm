<?php 
	class ReceivingorderViewModel extends ViewModel{
		public $viewFields = array(
			'receivingorder'=>array('*', '_type'=>'LEFT'),
			'receivables'=>array('name'=>'receivables_name','payer','type'=>'receivables_type', '_on'=>'receivingorder.receivables_id=receivables.receivables_id','_type'=>'LEFT'),
			'role'=>array('_on'=>'receivingorder.creator_role_id=role.role_id', '_type'=>'LEFT'),
			'user'=>array('full_name'=>'creator_name', '_on'=>'role.user_id = user.user_id')
		);
	}