<?php
# Change of base URL confirmation script.
# User uses this page to confirm the change request.

require_once('olac.php');
define("OLAC_PATH", olacvar('docroot'));
require_once(OLAC_PATH.'/lib/php/OLACDB.php');
require_once(OLAC_PATH.'/lib/php/utils.php');
define("OLAC_SYS_ADMIN_EMAIL", olacvar('tech_email'));
$OLAC_ADMIN_EMAIL = olacvar('olac_admin_email');

$error_log = array();

function error_handler($errno, $errstr, $file, $lineno)
{
  global $error_log;
  $error_log[] = "$file [line $lineno]: $errstr";
}

set_error_handler(error_handler);

function error($msg)
{
  global $error_log;

  $msg .= "\n\n";
  foreach ($error_log as $error_line) {
    $msg .= $error_line;
    $msg .= "\n";
  }
  while (array_pop($error_log)); # clear the log for next use

  mail_by_olac_admin
      (OLAC_SYS_ADMIN_EMAIL,
       "registration problem (/register/confirm.php)",
       $msg);
  echo "<p><font color=red><b>Error</b></font></p>" .
       "<p>Due to a system problem, we cannot change your base url " .
       "at this moment. Please try again. " .
       "The OLAC System Admin has been notified.</p>";
  return;
}

function success($magic, $repoid, $baseurl)
{
  global $DB;
  $DB->sql("delete from PendingConfirmation where magic_string='$magic'");
  echo "<p><font color=green><b>CHANGE OF BASE URL REQUEST CONFIRMED</b></font></p>" .
       "<p>The base url of $repoid has changed to $baseurl.</p>";
}

function change_baseurl($repoid, $repotype, $url)
{
  global $DB;
  $sql = "update ARCHIVES set BASEURL='$url', type='$repotype' where ID='$repoid'";
  $DB->sql($sql);
  if ($DB->saw_error()) {
    error("database error while updating the base url\n\n" .
	 "repoid: $repoid\n" .
	  "new url: $url\n" .
	  "DB error msg: " . $DB->get_error_message());
    return false;
  }
  return true;
}

$magic = $_GET["v"];
$DB = new OLACDB();
$sql = "select * from PendingConfirmation c, OLAC_ARCHIVE oa ";
$sql .= "where c.repository_id=oa.RepositoryIdentifier ";
$sql .= "and c.magic_string='$magic' and c.ctype is null";
$rows = $DB->sql($sql);
if ($DB->saw_error()) {
  error("database error while confirming change of base url request\n" .
	"it happened while trying to retrieve the confirmation record\n\n" .
	"Magic string: $magic\n" .
	"DB error msg: " . $DB->get_error_message());
  return;
} else if (count($rows) == 0) {
  header("HTTP/1.1 404 not found");
  echo "<h2>Not Found</h2>";
  return;
}


?>
<HTML>
<HEAD>
<TITLE>OLAC Archive Registration</TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<LINK REL="stylesheet" TYPE="text/css" HREF="/olac.css">
</HEAD>

<BODY>
<HR>
<TABLE CELLPADDING="10">
<TR>
<TD> <A HREF="<?=olacvar('baseurl')?>"><IMG
SRC="/images/olac100.gif"
BORDER="0"></A></TD>
<TD> <H1><FONT COLOR="0x00004a">OLAC Archive Registration<br></FONT></H1></TD>
</TR>
</TABLE>
<HR>
<?php


$repoid = $rows[0]["repository_id"];
$repotype = $row[0]["repository_type"];
$baseurl = $rows[0]["new_url"];
$oldurl = $rows[0]['BaseURL'];

if (change_baseurl($repoid, $repotype, $baseurl))
  success($magic, $repoid, $baseurl);


?>
<br><br>
Please report any problems to
<a href="mailto:<?=$OLAC_ADMIN_EMAIL?>"><?=$OLAC_ADMIN_EMAIL?></a>

<HR>
<A HREF="<?=olacvar('baseurl')?>">OLAC</A>

</BODY>
</HTML>
