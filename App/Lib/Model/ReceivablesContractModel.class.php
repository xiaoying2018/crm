<?php 
	class ReceivablesContractModel extends ViewModel{
		public $viewFields = array(
			'receivables'=>array('*', '_type'=>'LEFT'),
			'contract'=>array('number'=>'contract_name','business_id', '_on'=>'receivables.contract_id=contract.contract_id')
		);
	}