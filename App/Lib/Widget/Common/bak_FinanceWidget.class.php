<?php 

class FinanceWidget extends Widget {
	
	public function render($data)
	{
		if($data['redirect'] == 'monthlyreceive'){
			return $this->renderFile("monthlyreceive", $data);
		}elseif($data['redirect'] == 'yearreceivecomparison'){
			return $this->renderFile("yearreceivecomparison", $data);
		}else{
			return $this->renderFile("index", $data);
		}
	}
}
