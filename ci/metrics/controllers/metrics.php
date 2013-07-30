<?php
class Metrics extends Controller {
	function index()
	{
		$this->load->view('metricsview');
	}

	function view($arcid)
	{
		$data = array();
		if (is_numeric($arcid))
		{
			$this->load->model("MetricsDB");
			if ($this->MetricsDB->validArchiveId($arcid))
			{
				$data['defaultArchiveId'] = $arcid;
			}
			else
			{
				$data['defaultArchiveId'] = -1;
				$data['error'] = "Invalid archive Id '$arcid'";
			}
		}
		else
		{
			$this->load->model("MetricsDB");
			$narcid = $this->MetricsDB->getArchiveId($arcid);
			if ($narcid)
			{
				$data['defaultArchiveId'] = $narcid;
			}
			else
			{
				$data['defaultArchiveId'] = -1;
				$data['error'] = "Invalid repository identifier '$arcid'";
			}
		}
		$this->load->view('metricsview', $data);
	}

	function compare()
	{
		$data = array('comparative' => TRUE);
		$this->load->view('metricsview', $data);
	}
}
?>

