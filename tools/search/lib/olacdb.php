<?php

require_once("olac.php");

class OLACDB
{
    var $connect;

    function OLACDB()
    {
      $host = olacvar('mysql/host');
      $user = olacvar('mysql/user');
      $password = olacvar('mysql/passwd');
      $database = olacvar('mysql/olacdb');

      $this->connect = 
	mysql_connect($host, $user, $password ) or
	die ("Could not connect to database");
      
      mysql_select_db ($database) or die ("Could not select database: $dbname");
      mysql_query("set names 'utf8'");
    }

    function sql($query)
    {
        ($query_result = mysql_query($query)) 
		or die ("Query unsuccessful: $query\n" . mysql_error() );

        $result = array();
        $i=0;
        while ($row = mysql_fetch_array($query_result))
        {
            $result[$i] = $row;
            $i = $i + 1;
        }
        return $result;
    }

    function insertQuery($query)
    {
        ($query_result = mysql_query($query));
	   return mysql_error();
    }

    function saw_error()
    {
	return mysql_error();
    }

    function disconnect()
    {
	mysql_close($this->connect);	
	return;
    }

}

?>
