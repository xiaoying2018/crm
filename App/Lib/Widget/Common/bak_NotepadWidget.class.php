<?php 

class NotepadWidget extends Widget 
{
	public function render($data)
	{
		return $this->renderFile ("index");
	}
}