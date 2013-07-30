<?php

require_once('../../lib/php/OLACDB.php');
require_once('../../lib/php/utils.php');
$DB = new OLACDB();

### Helpers

$LOG = array();

function error_handler($errno, $errstr, $file, $lineno) {
  global $LOG;
  $LOG[] = "$file [line $lineno]: $errstr";
}

set_error_handler(error_handler);

function notify_admin($msg) {
  global $LOG;
  
  $msg .= "\n\n";
  while ($error_line = array_shift($LOG)) {
    $msg .= $error_line . "\n";
  }

  mail_by_olac_admin(OLAC_SYS_ADMIN_EMAIL,
		     "olac error -- harvester web interface",
		     $msg);
}


# All functions return an array. If error occurs, the array has a key "error"
# whose value is the error message.

function harvest($token) {
  global $DB;

  $response = array();
  $sql = "
    select * from PendingConfirmation c, OLAC_ARCHIVE oa
    where c.repository_id=oa.RepositoryIdentifier
    and c.magic_string='$token' and c.ctype='h'
  ";
  $rows = $DB->sql($sql);
  if ($DB->saw_error()) {
    notify_admin("Database error occurred while verifying confirmation\n" .
		 "token.\n" .
		 "\n" .
		 "Confirmation token: $token\n" .
		 "DB error message: " . $DB->get_error_message());
    $response['error'] =
      "Sorry, your request cannot be completed at this time. " .
      "Please try again later. The system admin has been notified.";
    return $response;
  }

  if (count($rows) == 0) {
    $response['error'] = "Invalid confirmation token.";
    return $response;
  }

  $repotype = $rows[0]["repository_type"];
  $harvesturl = $rows[0]["new_url"];

  $lockfile = olacvar('locks/harvester');
  $fp = fopen($lockfile, "w");
  $res = flock($fp, LOCK_EX | LOCK_NB);
  if ($res === FALSE) {
    $response['error'] =
      "A harvester process is already running. " .
      "We cannot start a new harvester process while another is running. " .
      "Please try again later.";
    return $response;
  }

  if ($repotype == 'Static')
    $opt = '-s';
  else
    $opt = '';
  $cmd = olacvar('harvester/web_interface_backend') . " $opt '$harvesturl'";
  $fp = popen($cmd, "r");
  ob_end_flush();
  while (!feof($fp)) {
    echo fgets($fp);
    flush();
  }
  pclose($fp);
  @unlink($lockfile);
  $DB->sql("delete from PendingConfirmation where magic_string='$token'");

  return 'DONE';
}

function register_request($repoid) {
  global $DB;
  $response = array();

  $adminemail = get_admin_email($DB, $repoid);
  if ($adminemail === FALSE) {
    notify_admin("Database error while obtaining AdminEmail for $repoid\n\n".
		 "DB error msg: " . $DB->get_error_message() . "\n" .
		 "SQL: " . $DB->get_error_sql());
    $response['error'] =
      "Sorry, your request cannot be completed at this time. " .
      "Please try again later. The system admin has been notified.";
    return $response;
  }

  if (!$adminemail) {
    $response['error'] =
      "Cannot obtain AdminEmail for repository '$repoid'. " .
      "Make sure the repository ID is correct. " .
      "Please let us know if the problem persists.";
    return $response;
  }

  $response['adminemail'] = $adminemail;

  $baseurl = get_baseurl($DB, $repoid);
  if ($baseurl === FALSE) {
    notify_admin("Database error while obtaining base URL for $repoid\n\n".
		 "DB error msg: " . $DB->get_error_message() . "\n" .
		 "SQL: " . $DB->get_error_sql());
    $response['error'] = 
      "Sorry, your request cannot be completed at this time. " .
      "Please try again later. The system admin has been notified.";
    return $response;
  }

  $magic = get_confirmation_magic_string($DB);
  if ($magic === FALSE) {
    notify_admin("Database error while obtaining magic string\n\n" .
		 "DB error msg: " . $DB->get_error_message() . "\n" .
		 "SQL: " . $DB->get_error_sql());
    $response['error'] = 
      "Sorry, your request cannot be completed at this time. " .
      "Please try again later. The system admin has been notified.";
    return $response;
  }

  $repotype = get_repository_type($DB, $repoid);
  if ($repotype === FALSE) {
    notify_admin("Database error while obtaining repository type\n\n" .
		 "DB error msg: " . $DB->get_error_message() . "\n" .
		 "SQL: " . $DB->get_error_sql());
    $response['error'] = 
      "Sorry, your request cannot be completed at this time. " .
      "Please try again later. The system admin has been notified.";
    return $response;
  }

  $sql = "
    insert into PendingConfirmation
    (magic_string, repository_id, repository_type, new_url, ctype)
    values ('$magic', '$repoid', '$repotype', '$baseurl', 'h')
  ";

  $DB->sql($sql);
  if ($DB->saw_error()) {
    notify_admin("Database error while recording pending confirmation\n\n" .
		 "DB error msg: " . $DB->get_error_message() . "\n" .
		 "SQL: " . $DB->get_error_sql());
    $response['error'] =
      "Sorry, your request cannot be completed at this time. " .
      "Please try again later. The system admin has been notified.";
    return $response;
  }

  $subject = "[OLAC] forced full harvest confirmation";
  $url = olacvar('harvester/web_interface_confirm');
  $msg = <<<EOT
Administrator of $repoid:

Someone has requested a full re-harvest of your OLAC repository hosted at
$baseurl.

To confirm this, please visit the following url.

$url?confirm=$magic

EOT;

  mail_by_olac_admin($adminemail, $subject, $msg);

  return $response;
}

?>