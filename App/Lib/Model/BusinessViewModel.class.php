<?php
	class BusinessViewModel extends ViewModel {
        public $viewFields;
		public function _initialize(){
			$main_must_field = array('*');
            
			$main_list = array_unique(array_merge(M('Fields')->where(array('model'=>'business','is_main'=>1))->getField('field', true),$main_must_field));
			$main_list['_type'] = 'LEFT';
			$data_list = M('Fields')->where(array('model'=>'business','is_main'=>0))->getField('field', true);
			$data_list['_on'] = 'business.business_id = business_data.business_id';
            $data_list['_type'] = 'LEFT';
			
			$this->viewFields = array(
				'business'=>$main_list,
				'business_data'=>$data_list,
				'contacts'=>array('name'=>'contacts_name', '_on'=>'contacts.contacts_id=business.contacts_id')
			);
		}
	}