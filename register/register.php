<?php
#  Registration script for OLAC
#  Originally written by Steven Bird on 2002-05-10
#  Revised by Haejoong Lee for OLAC 1.0.
#  Revised by Haejoong Lee for OLAC 1.1 in Summer 2008.
#

require_once('olac.php');
define(OLAC_PATH, olacvar('docroot'));
require_once(OLAC_PATH.'/lib/php/OLACDB.php');
require_once(OLAC_PATH."/lib/php/utils.php");

$POSTEDURL = "";
$OLAC_ADMIN_EMAIL = olacvar('olac_admin_email');
$OLAC_SYS_ADMIN_EMAIL = olacvar('tech_email');

$docroot = olacvar('docroot');
define('TESTER_EMAIL',  olacvar('tech_email'));
define('OLAC_URL',      olacvar('baseurl'));
define('CHAR_LIMIT',    2000);   # how many chars of schematron output to print
define('DR_STRON',      'scripts-1.1/olac-dynamic-repository.xsl');
define('SR_STRON',      'scripts-1.1/olac-static-repository.xsl');
define('VERSION_STRON', 'scripts-1.1/olac-version.xsl');
define('XERCESJ',       olacvar('xml_validator'));
define('XSLT',          olacvar('xslt'));
define('CGISTRON',      OLAC_URL.'/cgi-bin/schematron.cgi');
define('PENDING_DIR',   $docroot . olacvar('registration/upload_area'));
                        # dir for pending hostless repos
define('HOSTLESS_DIR',  $docroot . olacvar('registration/hosting_area'));
                        # dir for registered hostless repos
define('PENDING_URL',   OLAC_URL . olacvar('registration/upload_area'));
define('HOSTLESS_URL',  OLAC_URL . olacvar('registration/hosting_area'));


function myurlencode($url) {
  $tmp = preg_replace("'/'", "@", $url);
  return $tmp;
}


function myflush() {
  ob_end_flush();
  flush();
  ob_flush();
}


function get_data($file, $url) {
  system("curl -s -L -o '$file' '$url'", $retval);
  if ($retval == 23) {
    error("Download failed due to a system error: write error");
    return 0;
  } elseif ($retval != 0) {
    error("Could not read URL \"$url\"");
    return 0;
  }
  return 1;
}

function get_some_data($url, $n, &$data) {
  $n = intval($n);
  exec("curl -s -L -r 0-$n '$url'", $arr, $retval);
  if ($retval == 23) {
    error("Download failed due to a system error: no space left");
    return FALSE;
  } elseif ($retval != 0) {
    error("Could not read URL \"$url\"");
    return FALSE;
  }
  $data = implode("",$arr);
  return TRUE;
}

function error($str) {
  print "<p><font color=red><b>ERROR: $str</b></font></p>\n";
  myflush();
}



function report_dr_error($test, $file, $include_log) {
  global $POSTEDURL;
  $requrl = "$POSTEDURL?$test";
  error("Test failed");
  print "<p>Here are some URLs which may help in diagnosing the problem:</p>";
  print "<ul>";
  print "<li><a href=\"$requrl\">$requrl</a></li>";
  print "<li><a href=\"$file\">Local copy</a> of that response that we've tested</li>";
  if ($include_log) {
    if ($include_log == 2) {
      $xercesj_log = "$file-xercesj.txt";
      print "<li><a href=\"$xercesj_log\">Xerces-J error log</a></li>";
    }
    print "<li><a href=\"/tools/xsv\">XML Schema validator</a></li>";
  }
  print "<li><a href=\"http://rocky.dlib.vt.edu/~oai/cgi-bin/Explorer/oai2.0/testoai?archive=$POSTEDURL\">Repository Explorer for this archive</a></li>";
  print "</ul>";
  myflush();
}



function report_sr_error($file, $include_log) {
  $logprefix = $file;
  error("Test failed");
  print "<p>Here are some URLs which may help in diagnosing the problem:</p>";
  print "<ul>";
  print "<li><a href=\"$logprefix\">The file we've tested</a></li>";
  if ($include_log) {
    if ($include_log == 2) {
      $xercesj_log = "$logprefix-xercesj.txt";
      print "<li><a href=\"$xercesj_log\">Xerces-J error log</a></li>";
    }
    print "<li><a href=\"/tools/xsv\">XML Schema validator</a></li>";
  }
  print "</ul>";
  myflush();
}



function fatal_error($str) {
  global $OLAC_ADMIN_EMAIL;
  global $POSTEDURL;
  if ($_GET[testnospam])
    $OLAC_ADMIN_EMAIL = TESTER_EMAIL;

  error($str);
  #$mailmsg = "$str\n\nRepository URL: $POSTEDURL\n";
  #mail($OLAC_ADMIN_EMAIL, "OLAC REGISTRATION - FATAL ERROR", $mailmsg, "Cc: ".TESTER_EMAIL."\r\n");
  die();
}



function file_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}



function report_result($result) {
  print "<h3>Validation Result:</h3>\n";
  if ($result) {
    print "<h2><font color=green><b>SUCCESS</b></font></h2>\n";
  } else {
    print "<h2><font color=\"red\"><b>FAILURE</b></font></h2>\n";
  }
}



function get_output($cmd, $num) {
  $fp = popen($cmd, 'r');
  # fread can stop before reading $num bytes
  $result = stream_get_contents($fp, $num);
  pclose($fp);
  return $result;
}



# get the content of an XML element
function get_element($field, $file) {
  $xr = new XMLReader();
  if ($xr->open($file) == FALSE) {
    fatal_error("Could not open <a href=\"$file\">the file</a> for reading");
  }

  while ($xr->read()) {
    $tag = preg_replace("/".$xr->prefix.":/", '', $xr->name);
    if ($tag == $field) {
      $xr->read();
      $retval = $xr->value;
      $xr->close();
      return $retval;
    }
  }

  $xr->close();
  fatal_error("Field \"$field\" is missing or empty in <a href=\"$file\">the file</a>.");
}



function notify_registration_submission($id, $baseurl, $dp_admin, $bypass=0) {
  global $OLAC_ADMIN_EMAIL;
  if ($_GET[testnospam]) {
    $OLAC_ADMIN_EMAIL = TESTER_EMAIL;
    $dp_admin = TESTER_EMAIL;
  }

  $emailarr = preg_split("{\s+}", $OLAC_ADMIN_EMAIL);
  array_unshift($emailarr, $dp_admin);
  $emails = implode(", ", $emailarr);
  print "<p>Email notification sent to: $emails.</p>\n";
  $message = "
Registration request for
    Repository ID: $id
    Repository URL: $baseurl
    adminEmail: $dp_admin
has been filed for review.
";

  if ($bypass) {
    $message .= "\n(Note: This repository didn't go throuth the OLAC Validation step.)\n";
  }

  $baseurl = olacvar("baseurl");
  $msg1 = $message . "
Visit the following URL to review the archive

   $baseurl/register/archive_review.php

(This message was Generated by register.php)
";

  $msg2 = "
Dear $id administrator,

Thank you for registering the archive.

$message

We will review and register the archive shortly.  You will be notified
as soon as we process your archive. Thank you.

Regards,

OLAC administration team
$OLAC_ADMIN_EMAIL

";

  mail_by_olac_admin($OLAC_ADMIN_EMAIL,
		     "OLAC registration received",
		     $msg1,
		     $OLAC_SYS_ADMIN_EMAIL);
  mail_by_olac_admin($dp_admin,
		     "OLAC registration received",
		     $msg2,
		     $OLAC_SYS_ADMIN_EMAIL);
		     
}



function register($id, $dp_admin) {
  global $DB;
  global $OLAC_ADMIN_EMAIL;
  
  $repoid = $_POST["repositoryid"];
  $repotype = $_POST["repositorytype"];
  $baseurl = $_POST["baseurl"];
  $adminemail = $_POST["adminemail"];

  $DB->sql("insert into ARCHIVES (ID,BASEURL,contactEmail, type) " .
           "values ('$repoid', '$baseurl', '$adminemail','$repotype') " .
           "on duplicate key update ID='$repoid', BASEURL='$baseurl', " .
           "contactEmail='$adminemail', type='$repotype'");
  if ($DB->saw_error()) {
    error("Database error");

    echo <<<EOT
<p>Registration failed due to a system error. You can either try it again or
notify OLAC administrators who can manually register your repository.
Click on the "NOTIFY REGISTRATION ERROR" button below to notify
OLAC administrators.</p>

<form method="post">
<inpyt type="hidden" name="repositoryid" value="$repoid"/>
<input type="hidden" name="repositorytype" value="$repotype"/>
<input type="hidden" name="baseurl" value="$baseurl"/>
<input type="hidden" name="adminemail" value="$adminemail"/>
<input type="submit" name="action" value="NOTIFY REGISTRATION ERROR"/>
</form>
EOT;
  }
  else {
    echo <<<EOT
<p><font color="green"><b>REGISTRATION REQUEST HAS BEEN FILED FOR REVIEW</b>
</font><br>We will review the request and register the archive shortly.</p>
EOT;
    notify_registration_submission($repoid, $baseurl, $adminemail);
  }
}



function change_baseurl()
{
  global $DB;
  global $OLAC_SYS_ADMIN_EMAIL;

  clear_page();

  $baseurl = $_POST["baseurl"];
  $repoid = $_POST["repositoryid"];

  $error_msg = <<<EOT
<p><font color=red><b>Error</b></font></p>
<p>A database error occurred while processing your request.
Please try it again later.  If problem continues, please let us know
by email.  Sorry about the inconvenience.</p>
EOT;

  $adminemail = get_admin_email($DB, $repoid);
  if ($adminemail === FALSE) {
    $subject = "olac registration error (kind 1)";
    $msg = "Database failed when obtaining adminEmail of $repoid.\n\n";
    $msg .= "DB error msg: " . $DB->get_error_message();
    mail_by_olac_admin($OLAC_SYS_ADMIN_EMAIL, $subject, $msg);
    echo $error_msg;
    return;
  }

  $magic = get_confirmation_magic_string($DB);
  $sql = "insert into PendingConfirmation (magic_string, repository_id, repository_type, new_url) ";
  $sql .= "values ('$magic', '$repoid', '$_POST[repositorytype]', '$baseurl')";
  $DB->sql($sql);
  if ($DB->saw_error()) {
    $subject = "olac registration error (kind 2)";
    $msg = "Database failed when inserting confirmation request.\n\n";
    $msg .= "DB error msg: " . $DB->get_error_message();
    mail_by_olac_admin($OLAC_SYS_ADMIN_EMAIL, $subject, $msg);
    echo $error_msg;
    return;
  }
  
  $confirmation_url = OLAC_URL . "/register/confirm.php?v=$magic";

  $msg = <<<EOT
Administrator of $repoid:

Someone has requested a change of base URL of your repository registered with
OLAC. To confirm this, please visit the following URL:

  $confirmation_url

This message was automatically generated by OLAC Registration Service.

EOT;

  $subject = "OLAC Registration Confirmation";
  mail_by_olac_admin($adminemail, $subject, $msg);

  echo <<<EOT
<p><font color=green><b>REQUEST FOR CONFIRMATION HAS BEEN SENT</b></font></p>
<p>An email has been sent to the following email address. Please check the
email and follow the included link to confirm the change of registration.</p>
<p>Admin email: $adminemail</p>
EOT;
}



function update_repository()
{
  global $DB;
  global $OLAC_SYS_ADMIN_EMAIL;

  clear_page();

  $repoid = $_POST["repositoryid"];
  $postedurl = $_POST["postedurl"];
  
  $error_msg = <<<EOT
<p><font color=red><b>Error</b></font></p>
<p>A database error occurred while processing your request.
Please try it again later.  If problem continues, please let us know
by email.  Sorry about the inconvenience.</p>
EOT;

  $adminemail = get_admin_email($DB, $repoid);
  if ($adminemail === FALSE) {
    $subject = "olac registration error (kind 3)";
    $msg = "Database failed when obtaining adminEmail for $repoid.\n\n";
    $msg .= "DB error msg: " . $DB->get_error_message();
    mail_by_olac_admin($OLAC_SYS_ADMIN_EMAIL, $subject, $msg);
    echo $error_msg;
    return;
  }

  $magic = get_confirmation_magic_string($DB);
  $sql = "insert into PendingConfirmation (magic_string, repository_id, repository_type, new_url, ctype) ";
  $sql .= "values ('$magic', '$repoid', 'Static', '$postedurl', 'u')";
  $DB->sql($sql);
  if ($DB->saw_error()) {
    $subject = "olac registration error (kind 4)";
    $msg = "Database failed when inserting confirmation request.\n\n";
    $msg .= "DB error msg: " . $DB->get_error_message();
    mail_by_olac_admin($OLAC_SYS_ADMIN_EMAIL, $subject, $msg);
    echo $error_msg;
    return;
  }

  $confirmation_url = OLAC_URL . "/register/confirm_u.php?v=$magic";

  $msg = <<<EOT
Administrator of $repoid:

Someone has requested to update your repository hosted on the OLAC web site.
To confirm this, please visit the following URL:

  $confirmation_url

This message was automatically generated by OLAC Registration Service.

EOT;

  $subject = "OLAC Registration Confirmation";
  mail_by_olac_admin($adminemail, $subject, $msg);

  echo <<<EOT
<p><font color=green><b>REQUEST FOR CONFIRMATION HAS BEEN SENT</b></font></p>
<p>An email has been sent to the following email address. Please check the
email and follow the included link to confirm the change of registration.</p>
<p>Admin email: $adminemail</p>
EOT;
}



function unregister() {
  global $DB;
  global $POSTEDURL;
  $DB->sql("delete from ARCHIVES where BASEURL='$POSTEDURL'");
  if ($DB->saw_error()) {
    fatal_error("Delete query failed");
  }
  print "<h2><font color=\"green\">ARCHIVE UNREGISTERED</font></h2>\n";
}



function pmh($file, $test) {
  global $POSTEDURL;
  $requrl = "$POSTEDURL?$test";
  print "<p>Fetching data for protocol request: $test\n";
  myflush();

  if (get_data($file, $requrl)) {
    if (! preg_match("'</(\w+:)?error>\s*</(\w+:)?OAI-PMH>'s", join('',@file($file)))) {
      print "<font color=green>OK</font>\n";
      chmod($file, 0644);
      return;
    }
    else {
      error("Protocol request failed for URL: \"$requrl\"");
      if (preg_match("'verb=GetRecord'", $test)) {
        print <<<END
<p>Note: We issued the GetRecord request using the sampleIdentifier of the
oai-identifier element.  This might have caused the error.  Please make sure
the the sampleIdentifier is the id of an existing record.  See
<a href="/OLAC/repositories.html#OAI identifier description">OLAC
Repositories standard</a> for the sampleIdentifier requirement.</p>
END;
      }
    }
  }
  else {
    error("Protocol request failed for URL: \"$requrl\"");
  }

  report_dr_error($test, $file, 0);
  report_result(0);
  myflush();
  exit;
}



function download($file) {
  global $POSTEDURL;
  print "<p>Downloading...";
  myflush();

  if (get_data($file, $POSTEDURL)) {
    print "<font color=green>OK</font>\n";
    chmod($file, 0644);
  }
  else {
    error("Download failed for URL: " . $POSTEDURL);
    #report_error(0);
    report_result(0);
    exit;
  }
  myflush();
}



function test_dr_valid($file, $test) {
  $logprefix = $file;
  $result1 = 1;
  $result2 = 1;

  print '<p><b>Testing protocol request: '.$test."</b><br>\n";
  myflush();

  #####
  # Xerces-J validataion
  $xercesj_output = "$logprefix-xercesj.txt";
  $output = get_output(XERCESJ." $file", CHAR_LIMIT);
  # save output for future reference
  $fp = fopen($xercesj_output, 'w');
  fputs($fp, $output);
  fclose($fp);
  # output result
  if ($output) {
    report_dr_error($test, $file, 2);
    $result2 = 0;
  } else {
    print " - Xerces-J <font color=green>OK</font>";
  }
  myflush();

  return $result2;
}



function test_dr_conformant($file, $test) {
  print '<p><b>Testing protocol request: '.$test."</b><br>\n";
  myflush();

  $command = XSLT." $file ".DR_STRON.' | egrep -v "In pattern"';
  $output = get_output($command, CHAR_LIMIT);
  if (! $output) {
    error('Conformance test generated no output for test: '.$test);
    $url = CGISTRON."?url=/register/tmp/$file&type=dynamic";
    $here = "<a style=\"text-decoration:underline; color:blue\" onClick=\"window.open('$url')\">here</a>";
    print "Click $here to run the validation again and see if there is any error.";
    error('Please take a more closer look at your file for any (formatting) error.');
    report_dr_error($test, $file, 0);
    return 0;
  }
    
  # manually generate a response that schematron should have generated!
  $output1 = ereg_replace("\n", "<br>\n", $output);
  $output1 = preg_replace("/(ERROR[^\n]*)/", "<font color=red>$1</font>", $output1);
  $output1 = str_replace("OK", "<font color=green>OK</font>", $output1);
  print $output1;
  if (strlen($output) == CHAR_LIMIT) {
    print "&nbsp;<b>ETC...</b>";
  }

  if (preg_match("/ERROR/",$output1)) {
    report_dr_error($test, $file, 0);
    return 0;
  }

  return 1;
}



function check_olac_version($file, $msg='')
{
  if ($msg) {
    print "<p>$msg... ";
  } else {
    print '<p>Checking OLAC version... ';
  }
  myflush();
  $command = XSLT . " $file " . VERSION_STRON;
  $command .= ' | grep -v "In pattern" 2>/dev/null | head -n 10 | sed \'s/^\s*//\'';
  $output = get_output($command, CHAR_LIMIT);
  $h = array();
  foreach (explode("\n", trim($output)) as $line) {
    $key = trim($line);
    $h[$key] += 1;
  }
  if (count($h) != 1) {
    echo "<font color=red>error</font></p>";
    myflush();
    fatal_error("Cannot check the version of the repository.");
  } else {
    $keys = array_keys($h);
    echo "<font color=green>" . $keys[0] . "</font></p>";
    myflush();
    $a = explode(' ', $keys[0]);
    if ($a[1] == '1.0') {
      $msg = "OLAC 1.0 repository detected. Please upgrade it to 1.1. (See ";
      $msg .= '<a href="  http://olac.wiki.sourceforge.net/Call_for_1.1">';
      $msg .= 'instructions</a>.)';
      fatal_error($msg);
    }
    else if ($a[1] != '1.1')
      fatal_error("Not OLAC 1.1 repository.");
  }
}


function postedurl_is_hostless() {
  global $POSTEDURL;
  return is_hostless($POSTEDURL);
}

function is_uploaded_url($url) {
  # A repository is hostless if it is hosted on our site.
  if (preg_match("!^" . PENDING_URL . "/!", $url)) {
    return TRUE;
  } else {
    return FALSE;
  }
}


function is_hostless($url) {
  if (preg_match("!^" . OLAC_URL . "/!", $url)) {
    return TRUE;
  } else {
    return FALSE;
  }
}


function dr_validate() {
  global $URLTOFILE;
  global $POSTEDURL;
  global $IDENTIFY_XML;

  $ID_xml = sprintf("%s_ID.xml", $URLTOFILE);
  $LM_xml = sprintf("%s_LM.xml", $URLTOFILE);
  $LI_xml = sprintf("%s_LI.xml", $URLTOFILE);
  $LR_xml = sprintf("%s_LR.xml", $URLTOFILE);
  $GR_xml = sprintf("%s_GR.xml", $URLTOFILE);

  $ID_test = 'verb=Identify';
  $LM_test = 'verb=ListMetadataFormats';
  $LI_test = 'verb=ListIdentifiers&metadataPrefix=olac';
  $LR_test = 'verb=ListRecords&metadataPrefix=olac';
  $GR_test_base = 'verb=GetRecord&metadataPrefix=olac';

  print "<hr><br><h2>Validation Log</h2>\n";
  print "<p>Archive: $POSTEDURL";
  myflush();

  print "<h2>Retrieving Data</h2>";
  pmh($ID_xml, $ID_test);
  pmh($LM_xml, $LM_test);
  pmh($LI_xml, $LI_test);
  pmh($LR_xml, $LR_test);

  # construct the full GetRecord request
  $record_id = get_element('sampleIdentifier', $ID_xml);
  $GR_test = sprintf("%s&identifier=%s", $GR_test_base, $record_id);
  pmh($GR_xml, $GR_test);

  print "<h2>Validating XML Responses</h2>";
  myflush();

  $ID_valid = test_dr_valid($ID_xml, $ID_test);
  $LM_valid = test_dr_valid($LM_xml, $LM_test);
  $LI_valid = test_dr_valid($LI_xml, $LI_test);
  $LR_valid = test_dr_valid($LR_xml, $LR_test);
  $GR_valid = test_dr_valid($GR_xml, $GR_test);
  $valid = ($ID_valid && $LM_valid && $LI_valid && $LR_valid && $GR_valid);
  check_olac_version($ID_xml, "Checking OLAC version in Identify response...");
  check_olac_version($LR_xml, "Checking OLAC version in ListRecords response...");

  print "<h2>OLAC-PMH Validation</h2>\n";
  $ID_conf  = test_dr_conformant($ID_xml, $ID_test);
  $ID_baseurl = baseurl_check($ID_xml);
  $LM_conf  = test_dr_conformant($LM_xml, $LM_test);
  $LR_conf  = test_dr_conformant($LR_xml, $LR_test);
  $conformant = ($ID_conf && $LM_conf && $LR_conf && $ID_baseurl);

  $validation_result = $valid && $conformant;
  report_result($validation_result);

  $IDENTIFY_XML = $ID_xml;
  return $validation_result;
}



function sr_schema_valid($file) {
  $result1 = 1;
  $result2 = 1;

  #####
  # Xerces-J validataion
  print "<b>Xerces-J</b> - "; myflush();
  $xercesj_output = "$file-xercesj.txt";
  $output = get_output(XERCESJ." $file", CHAR_LIMIT);
  # save output for future reference
  $fp = fopen($xercesj_output, 'w');
  fputs($fp, $output);
  fclose($fp);
  # output result
  if ($output) {
    report_sr_error($file, 2);
    $result2 = 0;
  } else {
    print "<font color=green>OK</font>";
  }
  myflush();

  return $result2;
}



function sr_schematron_valid($file) {
  global $POSTEDURL;
  $command = XSLT." $file ".SR_STRON.' | egrep -v "In pattern"';
  $output = get_output($command, CHAR_LIMIT);
  if (! $output) {
    error('Conformance test generated no output.');
    $url = CGISTRON."?url=/register/tmp/$file&type=static";
    $here = "<a style=\"text-decoration:underline; color:blue\" onClick=\"window.open('$url')\">here</a>";
    print "Click $here to run the validation again and see if there is any error.";
    error('Please take a more closer look at your file for any (formatting) error.');
    report_sr_error($file, 0);
    return 0;
  }
    
  # manually generate a response that schematron should have generated!
  $output1 = ereg_replace("\n", "<br>\n", $output);
  $output1 = preg_replace("/(ERROR[^\n]*)/", "<font color=red>$1</font>", $output1);
  $output1 = str_replace("OK", "<font color=green>OK</font>", $output1);
  print "$output1";
  if (strlen($output) == CHAR_LIMIT) {
    print "&nbsp;<b>ETC...</b>";
  }
  $v1 = is_uploaded_url($POSTEDURL) || baseurl_check($file);
  $v2 = sr_sampleIdentifier_check($file);

  if (preg_match("/ERROR/",$output1) || $v1==0 || $v2==0) {
    report_sr_error($file, 0);
    return 0;
  }

  return 1;
}



function baseurl_check($URLTOFILE) {
  global $POSTEDURL;
  print "The Base URL in the repository file and the one supplied in the form match - ";
  myflush();
  $baseURL = get_element('baseURL', $URLTOFILE);
  $baseURL = preg_replace("'^\s*(.*?)\s*$'", "\\1", $baseURL);
  if ($baseURL == $POSTEDURL) {
    print "<font color=green>OK</font><br>";
    myflush();
    return 1;
  }
  else {
    print "<font color=red>Failed</font><br>";
    myflush();
    return 0;
  }
}



function sr_sampleIdentifier_check($file) {
  print "The record by the sampleIdentifier really exists in the repository - ";
  myflush();
  $sampleid = get_element('sampleIdentifier', $file);
  $sampleid = preg_replace("'^\s*(.*?)\s*$'", "\\1", $sampleid);

  $xr = new XMLReader();
  if ($xr->open($file) == FALSE) {
    fatal_error("Could not open <a href=\"$file\">the file</a> for reading");
  }
  while ($xr->read()) {
    $tag = preg_replace("/".$xr->prefix.":/", '', $xr->name);
    if ($tag == "identifier") {
      $xr->read();
      if (preg_match("!\s*$sampleid\s*!", $xr->value)) {
	$xr->close();
	print "<font color=green>OK</font><br>";
	myflush();
	return 1;
      }
    }
  }
  
  print "<font color=red>Failed</font><br>";
  myflush();
  return 0;
}



function sr_validate($URLTOFILE) {
  global $POSTEDURL;
  print "<hr><br><h2>Validation Log</h2>\n";
  print "<p>OLAC Static Repository: $POSTEDURL";
  myflush();

  print "<h2>Retrieving Data</h2>";
  myflush();
  download($URLTOFILE);

  print "<h2>XML Schema Validation</h2>";
  myflush();
  $v1 = sr_schema_valid($URLTOFILE);

  # we can't continue if the xml is invalid
  if (!$v1) {
    return $v1;
  }

  check_olac_version($URLTOFILE);

  print "<h2>Requirement Check</h2>\n";
  myflush();
  $v2 = sr_schematron_valid($URLTOFILE);

  $validation_result = $v1 && $v2;
  report_result($validation_result);
  return $validation_result;
}


function check_repository_type() {
  global $REPOTYPE;
  global $POSTEDURL;
  $REPOTYPE = '';
  get_some_data("$POSTEDURL?verb=badVerb", 65536, $bvout);
  if ($bvout) {

    if (preg_match("'<(\w+:)?OAI-PMH'", $bvout)) {
      $REPOTYPE = 'Dynamic';
    }
    else if (preg_match("'<(\w+:)?Repository'", $bvout)) {
      $REPOTYPE = 'Static';
    }

    if ($REPOTYPE == '') {
      print <<<END
<hr>
<p>Couldn't determine the type of the repository.  Reasons could be:
<ul>
<li>You gave the wrong URL,</li>
<li>The repository is using the old OLAC 0.4 standards,</li>
<li>The repository doesn't conform to the OLAC standards,</li>
</ul>

For the OLAC 1.0 standards, see
<a href="/OLAC/repositories.html">OLAC Repositories</a> and
<a href="/OLAC/metadata.html">OLAC Metadata</a>.</li>
</p>
END;
    }

  } else {

    print "<hr><p>Can't connect. Please check the base URL again.</p>";

  }
  
  return $bvout && $REPOTYPE != '';
}


function check_repository_status()
{
  global $DB;
  global $IDENTIFY_XML;
  global $POSTEDURL;

  $id = get_element('repositoryIdentifier', $IDENTIFY_XML);
  $baseurl = get_element('baseURL', $IDENTIFY_XML);

  $sql = "select BASEURL, dateApproved from ARCHIVES where ID='$id'";
  $rows = $DB->sql($sql);
  if ($DB->saw_error()) return "error";
  # FIXME: it's our responsibility to make sure that there's only one
  # FIXME: record in the ARCHIVES table for each unique repository ID
  if (count($rows) > 1) return "error";
  if (count($rows) == 0) return "new";
  if (is_null($rows[0]["dateApproved"])) {
    return "pending";
  } elseif (is_uploaded_url($POSTEDURL)) {
    # user ran validation after uploading a local file
    if (is_hostless($rows[0]["BASEURL"])) {
      # the repository is hosted on our site
      if ($rows[0]["BASEURL"] == $baseurl) {
        return "new_hostless_file";
      } else {
        return "new_hostless_file_with_wrong_baseurl";
      }
    } else {
      # user shouldn't override a registered repository hosted on other site
      # with the uploaded hostless file
      return "exists";
    }
  } elseif ($rows[0]["BASEURL"] == $POSTEDURL) {
    return "identical";
  } elseif ($rows[0]["BASEURL"] == $baseurl) {
    return "exists";
  } else {
    return "new_base_url";
  }
}

function validate() {
  global $REPOTYPE;
  global $URLTOFILE;
  global $IDENTIFY_XML;

  if (!check_repository_type()) return;

  $validation_ok = false;
  if ($REPOTYPE == "Dynamic") {
    $validation_ok = dr_validate();
  }
  else if ($REPOTYPE == "Static") {
    if ($_GET[register] == 1) {
      download($URLTOFILE);
      $validation_ok = true;
    } else {
      $validation_ok = sr_validate($URLTOFILE);
    }
    $IDENTIFY_XML = $URLTOFILE;
  }

  return $validation_ok;
}


function validation_response_for_pending_archive()
{
  echo "<p>An archive with the same repository identifier has been pending ";
  echo "for review.</p>";
}


function validation_response_for_new_archive()
{
  global $IDENTIFY_XML;
  global $REPOTYPE;
  global $POSTEDURL;
  $id = get_element('repositoryIdentifier', $IDENTIFY_XML);
  $dp_admin = get_element('adminEmail', $IDENTIFY_XML);
  $dp_admin = preg_replace("/^mailto:/", "", $dp_admin);

  echo <<<EOT
<p>Congratulations! Now you can continue to register your repository with OLAC.
If you press the button below, your request will be submitted to the OLAC
Coordinators.</p>

<form enctype="multipart/form-data" method="post">
<input type="hidden" name="repositoryid" value="$id"/>
<input type="hidden" name="repositorytype" value="$REPOTYPE"/>
<input type="hidden" name="baseurl" value="$POSTEDURL"/>
<input type="hidden" name="adminemail" value="$dp_admin"/>
<input type="submit" name="action" value="REGISTER NOW"/>
</form>
EOT;
}


function validation_response_for_existing_archive()
{
  if (postedurl_is_hostless()) {
    echo "<p>Your repository is valid and already registered. ";
    echo "It will continue to be harvested.</p>";
  }
  else {
    echo "<p>Your file is a valid OLAC repository. ";
    echo "However, we have a registered repository with the same repository ";
    echo "ID hosted on an external site. ";
    echo "We will concontinue to harvested the registered repository.</p>";
  }
}


function validation_response_for_new_baseurl()
{
  global $IDENTIFY_XML;
  global $POSTEDURL;
  global $REPOTYPE;
  $id = get_element('repositoryIdentifier', $IDENTIFY_XML);

  echo <<<EOT
<p>Your repository is valid. It is already registered, but at a different
baseURL. Press the "CHANGE REGISTRATION" button below to move your repository
to this new base URL.</p>

<form enctype="multipart/form-data" method="post">
<input type="hidden" name="repositoryid" value="$id"/>
<input type="hidden" name="repositorytype" value="$REPOTYPE"/>
<input type="hidden" name="baseurl" value="$POSTEDURL"/>
<input type="submit" name="action" value="CHANGE REGISTRATION"/>
</form>
EOT;
}


function validation_response_for_new_hostless_file()
{
  global $IDENTIFY_XML;
  global $POSTEDURL;
  $id = get_element('repositoryIdentifier', $IDENTIFY_XML);

  echo <<<EOT
<p>Your file is a valid OLAC repository and is already registered. You can
replace the registered repository with the one that you just uploaded by
clicking the "UPDATE REPOSITORY" button below. If you click on the button,
we will send an email to adminEmail of the registered repository. The
email contains information needed for you to complete the update.</p>

<form enctype="multipart/form-data" method="post">
<input type="hidden" name="repositoryid" value="$id"/>
<input type="hidden" name="postedurl" value="$POSTEDURL"/>
<input type="submit" name="action" value="UPDATE REPOSITORY"/>
</form>
EOT;
}


function validation_response_for_new_hostless_file2()
{
  global $IDENTIFY_XML;
  $id = get_element('repositoryIdentifier', $IDENTIFY_XML);
  $baseurl = get_element('baseURL', $IDENTIFY_XML);
  $correct_baseurl = OLAC_URL . HOSTLESS_DIR;

  echo <<<EOT
<p>Your file is a valid OLAC repository and is already registered. However,
the new file that you uploaded contains the wrong base URL.</p>

<p style="padding-left: 2em">$baseurl</p>

<p>The correct base URL should be determined as follows.</p>

<p style="padding-left: 2em">$correct_baseurl/&lt;repository_identifier&gt;.xml</p>

<p>If you would like to update your existing repository
please fix the base URL and try again.</p>
EOT;
}


function clear_page()
{
  echo "<script>document.getElementById('main').innerHTML='';</script>";
}


function registration_response()
{
  clear_page();
  register($_POST["repositoryid"], $_POST["adminemail"]);
}


function registration_response_for_system_error()
{
  clear_page();
  $msg = <<<EOT
OLAC Administrator:

An attempt to register a valid repository has failed due to a system error.
User was told that he could try later, but he chose to let us do it.  Here's
the registration information.

  Repository ID: $_POST[repositoryid]
  Repository Type: $_POST[repositorytype]
  Base URL: $_POST[baseurl]
  Admin Email: $_POST[adminemail]

This message was automatically generated by OLAC Registration Service.

EOT;

  $subject = "OLAC registration failed due to a system error";
  mail_by_olac_admin($OLAC_ADMIN_EMAIL, $subject, $msg, $OLAC_SYS_ADMIN_EMAIL);

  echo "<p><font color=green><b>NOTIFICATION HAS BEEN SENT TO OLAC ";
  echo "ADMINISTRATORS</b></font></p>";
}


function validate_and_respond()
{
  if (validate()) {
    switch (check_repository_status()) {
    case "new":
      validation_response_for_new_archive();
      break;

    case "exists":
      validation_response_for_existing_archive();
      break;

    case "pending":
      validation_response_for_pending_archive();
      break;

    case "new_hostless_file":
      validation_response_for_new_hostless_file();
      break;

    case "new_hostless_file_with_wrong_baseurl":
      # hostless repository's base url is uniquely determined
      # the baseurl value in the file was different the unique value
      validation_response_for_new_hostless_file2();
      break;

    case "new_base_url":
      validation_response_for_new_baseurl();
      break;

    case "identical":
      break;

    case "error":
      echo "<p>Your repository is valid</p>";
      echo "<p>Unfortunately we cannot access our database at this moment. ";
      echo "If you want to register your repository or change the baseURL, ";
      echo "please try again in several minutes.</p>";
      break;
    }
  }
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
<TD> <A HREF="/"><IMG
SRC="/images/olac100.gif"
BORDER="0"></A></TD>
<TD> <H1><FONT COLOR="0x00004a">OLAC Archive Registration<br></FONT></H1></TD>
</TR>
</TABLE>
<HR>

<a href="/register/archive.html">
Learn about OLAC registration</a> |
<a href="/archives.php">
View registered archives</a>

<br><br>
<div id="main">
<form enctype="multipart/form-data" method="post">
<p><b>Base URL:</b><br/>
<input type="text" size="50" maxlength="300" name="url" value=""/>
<input type="submit" name="action" value="VALIDATE"/></p>

<p>This is an interface for validating and registering a new OLAC archive.
Once the validation has finished successfully, you will be invited to register
your repository.</p>

<p>An OLAC archive can be in the form of either a dynamic or static repository.
Before registration, please make sure that your repository conforms to the
following standards, against which your repository will be tested during
the registration process:

<dl>
<dd><a href="/OLAC/repositories.html">
OLAC Repositories standard</a>, and</dd>
<dd><a href="/OLAC/metadata.html">
OLAC Metadata standard</a>.</dd>
</dl>
</p>

<p>(NB: Validation may take several minutes.)</p>

<p>If you don't have a machine to host your static repository,
please use the following form.</p>
<input type="file" name="userfile" size="50"/>
<input type="submit" name="action" value="UPLOAD & VALIDATE"/>

</form>
</div>
<?php



$script = array();
if ($_POST["url"]) {
  $POSTEDURL = $_POST["url"];
  $script[] = "document.forms[0].url.value = '$POSTEDURL';";
}
elseif ($_GET["url"] && $_GET["register"]==1) {
  $script[] = "document.forms[0].url.value = '$_GET[url]';";
  $POSTEDURL = $_GET["url"];
} 
else {
  $script[] = "document.forms[0].url.value = 'http://';";
  $POSTEDURL = "";
}

?>
<script>
<?= implode("\n", $script); ?>
</script>
<?php



$DB = new OLACDB();

if ($_POST["action"] == "VALIDATE" && $POSTEDURL) {
  # User entered an URL and clicked on the VALIDATE button.

  if (substr($POSTEDURL, 0, 7) != 'http://') {
    $POSTEDURL = "http://$POSTEDURL";
  }
  $URLTOFILE = 'tmp/'.myurlencode($POSTEDURL);
  exec("rm -f \"$URLTOFILE\"*");

  validate_and_respond();
}

else if ($_POST["action"] == "UPLOAD & VALIDATE" && $_FILES["userfile"]["name"]) {
  ob_end_flush();
  flush();
  if ($_FILES['userfile']['error'] === UPLOAD_ERR_OK) {
    $filename = basename($_FILES['userfile']['name']);
    $target = PENDING_DIR . "/" . $filename;
    move_uploaded_file($_FILES['userfile']['tmp_name'], $target);
    $POSTEDURL = PENDING_URL . "/" . $filename;
    $URLTOFILE = "tmp/" . myurlencode($POSTEDURL);
    exec("rm -f \"$URLTOFILE\"*");

    validate_and_respond();
  } else {
    $error_message = file_upload_error_message($_FILES['userfile']['error']);
    echo "<p>Sorry, we were not able to download your file.</p>";
    echo "<p>Reason: $error_message</p>";
    echo "<p>System admin has been notified. Please try later. ";
    echo "We will try to fix the problem as soon as we can.</p>";
    
    $subject = "olac registration error (file upload problem)";
    $msg = "File upload failed due to the following readon.\n\n";
    $msg .= "DB error msg: " . $error_message;
    mail_by_olac_admin($OLAC_SYS_ADMIN_EMAIL, $subject, $msg);
  }
}

else if ($_POST["action"] == "REGISTER NOW") {
  # Validation was successful so the user was offerred an
  # "regieter now" button which he clicked on.
  registration_response();
}

else if ($_POST["action"] == "CHANGE REGISTRATION") {
  change_baseurl();
}

else if ($_POST["action"] == "UPDATE REPOSITORY") {
  update_repository();
}

else if ($_POST["action"] == "NOTIFY REGISTRATION ERROR") {
  registration_response_for_system_error();
}

?>
<br><br>
Please report any problems to
<a href="mailto:<?=$OLAC_ADMIN_EMAIL?>"><?=$OLAC_ADMIN_EMAIL?></a>

<HR>
<A HREF="/">OLAC</A>

</BODY>
</HTML>
