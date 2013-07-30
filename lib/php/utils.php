<?php

require_once('olac.php');

function mail_by_olac_admin($to, $subject, $msg, $cc="")
{
  $header = "From: " . olacvar('olac_admin_email') . "\r\n";
  $header .= "Reply-To: " . olacvar('olac_admin_email') . "\r\n";
  if ($cc) $header .= "Cc: $cc\r\n";
  $header .= "X-Mailer: PHP/" . phpversion();
  mail($to, $subject, $msg, $header);
}

function get_confirmation_magic_string($DB)
{
  while (true) {
    # repeat until a uniq magic string is obtained
    $magic = sha1(rand().rand().rand());
    $sql = "select count(*) c from PendingConfirmation where magic_string='$magic'";
    $rows = $DB->sql($sql);
    if ($DB->saw_error())
      return FALSE;
    if ($rows[0]["c"] == 0)
      return $magic;
  }
}

function get_admin_email($DB, $repoid)
{
  $sql = "select AdminEmail from OLAC_ARCHIVE
          where RepositoryIdentifier='$repoid'";
  $rows = $DB->sql($sql);
  if ($DB->saw_error())
    return FALSE;
  $adminemail = $rows[0]['AdminEmail'];
  $adminemail = preg_replace("/^mailto:/", "", $adminemail);
  return $adminemail;
}

function get_baseurl($DB, $repoid)
{
  $sql = "select BASEURL from ARCHIVES where ID='$repoid'";
  $rows = $DB->sql($sql);
  if ($DB->saw_error())
    return FALSE;
  return $rows[0]['BASEURL'];
}

function get_repository_type($DB, $repoid)
{
  $sql = "select Type from ARCHIVES where ID='$repoid'";
  $rows = $DB->sql($sql);
  if ($DB->saw_error())
    return FALSE;
  return $rows[0]['Type'];
}

function get_archive_id_for_repository_id($DB, $repoid)
{
  # $repoid: oai repository identifier
  $sql = "select Archive_ID from OLAC_ARCHIVE ";
  $sql .= "where RepositoryIdentifier='$repoid'";
  $rows = $DB->sql($sql);
  if ($DB->saw_error())
    return FALSE;
  return $rows[0]['Archive_ID'];
}

?>
