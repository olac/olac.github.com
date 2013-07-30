<?php

function select($b, $val1, $val2)
{
	if ($b) return $val1;
	else return $val2;
}

class SurveyM extends Model {

	function SurveyM()
	{
		parent::Model();
		$this->load->database();
	}

	function element($id)
	{
		$sql = <<<EOF
select count(*) Freq, Lang, Type, Code, Content
from METADATA_ELEM where Tag_ID = $id
group by Content, Lang, Type, Code
order by Freq desc
EOF;
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	function recordIds($lang,$type,$code,$content)
	{
		$opLang = select(is_null($lang), 'is', '=');
		$opType = select(is_null($type), 'is', '=');
		$opCode = select(is_null($code), 'is', '=');
		$opContent = select(is_null($content), 'is', '=');
		$sql = <<<EOF
select distinct OaiIdentifier
from ARCHIVED_ITEM ai, METADATA_ELEM me
where ai.Item_ID=me.Item_ID and
	me.Lang $opLang ? and
	me.Type $opType ? and
	me.Code $opCode ? and
	me.Content $opContent ?
order by OaiIdentifier
EOF;
		$query = $this->db->query($sql, array($lang,$type,$code,$content));
		$res = array();
		foreach ($query->result() as $row) {
			$res[] = $row->OaiIdentifier;
		}
		return $res;
	}
}
