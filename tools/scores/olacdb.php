<?

class OLACDB
{
    var $connect;

    function OLACDB($dbname = "olac2")
    {
	$userName = "olac";
	$password = "SECRET";
	$hostname = "localhost";
        $this->$connect = mysql_connect($hostname, $userName, $password ) or
                die ("Could not connect to database");
        mysql_select_db ($dbname) or die ("Could not select database");
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
	mysql_close($this->$connect);	
	return;
    }

}

?>
