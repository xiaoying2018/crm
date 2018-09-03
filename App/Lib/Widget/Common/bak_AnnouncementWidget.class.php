<?php 

class AnnouncementWidget extends Widget 
{
	public function render($data)
	{
		return $this->renderFile ("index", $data);
	}
}