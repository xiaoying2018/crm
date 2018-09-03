<?php 

class RecordWidget extends Widget {
	
	public function render($data)
	{
		$data['history'] = unserialize(cookie('history'));
		return $this->renderFile("index", $data);
	}
}
