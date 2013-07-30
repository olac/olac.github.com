<?php

require_once("olac.php");

class OLACDB
{
  var $db;
  var $err;
  var $err_sql;

  function OLACDB($dbname=null)
  {
    $this->errmsg = NULL;
    $host = olacvar('mysql/host');
    $user = olacvar('mysql/user');
    $password = olacvar('mysql/passwd');
    if ($dbname)
      $database = $dbname;
    else
      $database = olacvar('mysql/olacdb');
    $con = mysql_connect($host, $user, $password);
    if ($con === FALSE) {
      $this->errmsg = "connection failed";
      return;
    }
    mysql_select_db($database);
    mysql_query("set names 'utf8'");
  }

  function _OLACDB()
  {
    mysql_close();
  }

  function sql($sql)
  {
    $res = mysql_query($sql);
    if ($res === FALSE) {
      $this->errmsg = mysql_error();
      $this->err_sql = $sql;
      return FALSE;
    } elseif ($res === TRUE) {
      return TRUE;
    }
    $arr = array();
    $row = mysql_fetch_assoc($res);
    while ($row !== FALSE) {
      $arr[] = $row;
      $row = mysql_fetch_assoc($res);
    }
    return $arr;
  }

  function escape($s) {
    return mysql_real_escape_string($s);
  }

  function saw_error() {
    return !is_null($this->errmsg);
  }

  function get_error_message() {
    return $this->errmsg;
  }

  function get_error_sql()
  { return $this->err_sql; }

}

?>
