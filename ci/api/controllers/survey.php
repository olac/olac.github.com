<?php

class Survey extends Controller {

	function Survey()
	{
		parent::Controller();
		$this->load->model("surveym");
		$this->json = new Services_JSON();
	}

	function index()
	{
	}

	function element($id)
	{
		$tab = $this->surveym->element($id);
		print $this->json->encode($tab);
	}

	function _recordidsGetInputFor($s)
	{
		if ($this->input->post($s.'Null') == 'true')
			return null;
		else
			return $this->input->post($s);
	}
	function recordids()
	{
		$lang = $this->_recordidsGetInputFor('lang');
		$type = $this->_recordidsGetInputFor('type');
		$code = $this->_recordidsGetInputFor('code');
		$content = $this->_recordidsGetInputFor('content');

		if ($lang===false || $type===false || $code===false || $content===false) {
			print "[$lang,$type,$code,$content]";
		} else {
			$list = $this->surveym->recordIds($lang,$type,$code,$content);
			print $this->json->encode($list);
		}
	}
}