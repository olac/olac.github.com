<?php
# Update repository confirmation script.
# User uses this page to confirm an update request.

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
       "registration problem (/register/confirm_u.php)",
       $msg);
  echo "<p><font color=red><b>Error</b></font></p>" .
       "<p>Due to a system problem, we cannot update your repository " .
       "at this moment. Please try again. " .
       "The OLAC System Admin has been notified.</p>";
  return;
}

function success($magic)
{
  global $DB;
  $DB->sql("delete from PendingConfirmation where magic_string='$magic'");
  echo "<p><font color=green><b>UPDATE REPOSITORY REQUEST CONFIRMED</b></font></p>" .
       "<p>The new repository file has replaced the old one.</p>";
}

function update_repository($repoid, $postedurl)
{
  global $DB;
  $sql = "select * from ARCHIVES where ID='$repoid'";
  $rows = $DB->sql($sql);
  if ($DB->saw_error()) {
    error("database error while obtaining old baseurl.\n\n" .
	  "repository identifier: $repoid\n" .
	  "DB error msg: " . $DB->get_error_message());
    return FALSE;
  }
  $file = basename($rows[0]['BASEURL']);
  $docroot = olacvar('docroot');
  $src = $docroot . olacvar('registration/upload_area') . "/" . basename($postedurl);
  $dst = $docroot . olacvar('registration/hosting_area') . "/" . $file;
  if (!rename($src, $dst)) {
    error("file system error while updating the repository\n\n" .
          "repoid: $repoid\n" .
	  "posted url: $postedurl\n");
    return FALSE;
  }
  @chmod($dst, 0664);
  return TRUE;
}

$magic = $_GET["v"];
$DB = new OLACDB();
$sql = "select * from PendingConfirmation c, OLAC_ARCHIVE oa ";
$sql .= "where c.repository_id=oa.RepositoryIdentifier ";
$sql .= "and c.magic_string='$magic' and c.ctype='u'";
$rows = $DB->sql($sql);
if ($DB->saw_error()) {
  error("database error while confirming update repository request.\n\n" .
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
$postedurl = $rows[0]["new_url"];

if (update_repository($repoid, $postedurl))
  success($magic);


?>
<br><br>
Please report any problems to
<a href="mailto:<?=$OLAC_ADMIN_EMAIL?>"><?=$OLAC_ADMIN_EMAIL?></a>

<HR>
<A HREF="<?=olacvar('baseurl')?>">OLAC</A>

</BODY>
</HTML>

