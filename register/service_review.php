<?php

require_once("../lib/php/OLACDB.php");
$OLAC_ADMIN_EMAIL = olacvar('olac_admin_email');

###############
# Global data #
###############

$DB = new OLACDB();

$message[Accept] =
"Congratulations!
Your request has been accepted and posted on the following OLAC web page.

  http://www.language-archives.org/services.php

Thank you for registering your service.

Sincerely,

OLAC administraion team
";

$message[Reject] =
"Unfortunately, we can't procecess your request because ...

Regards,

OLAC administration team
";

$form = array( 0 => array('type' => 'input',
                          'name' => 'serviceName',
                          'display' => 'Name of service',
                          'feature' => 'size="80"'),
               1 => array('type' => 'input',
                          'name' => 'serviceURL',
                          'display' => 'URL',
                          'feature' => 'size="80"'),
               2 => array('type' => 'input',
                          'name' => 'institution',
                          'display' => 'Institution',
                          'feature' => 'size="80"'),
               3 => array('type' => 'input',
                          'name' => 'institutionURL',
                          'display' => 'Institution URL',
                          'feature' => 'size="80"'),
               4 => array('type' => 'input',
                          'name' => 'contactPerson',
                          'display' => 'Contact person',
                          'feature' => 'size="80"'),
               5 => array('type' => 'input',
                          'name' => 'contactEmail',
                          'display' => 'Contact email',
                          'feature' => 'size="80"'),
               6 => array('type' => 'textarea',
                          'name' => 'description',
                          'display' => 'Description',
                          'feature' => 'rows="8" cols="80"')
);

#############
# Functions #
#############

function authenticate()
{
  global $DB;

  $auth = FALSE;

  if (isset($_SERVER[PHP_AUTH_USER]) && isset($_SERVER[PHP_AUTH_PW])) {
    $res = $DB->sql("select pass from admin_auth
                     where user='$_SERVER[PHP_AUTH_USER]'");
    if ($DB->saw_error()) {
      echo "<p>system error";
      exit;
    }
    $pass = $res[0][pass];
    $res = $DB->sql("select password(\"$_SERVER[PHP_AUTH_PW]\") as pass");
    if ($DB->saw_error()) {
      echo "<p>system error";
      exit;
    }
    $htpass = $res[0][pass];

    if ($pass == $htpass)
      $auth = TRUE;
  }

  if (!$auth) {
    header('WWW-Authenticate: Basic realm="OLAC admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Authorization Required.";
    exit;
  }
}

function draw_summary()
{
  global $DB;

  $tab = $DB->sql("select   Service_ID as id,
                            serviceName as name,
			    serviceURL as url
		   from     SERVICES
                   where    dateApproved is NULL
                   order by name");
  if ($DB->saw_error()) { return FALSE; }
  $tab2 = $DB->sql("select  Service_ID as id,
                            serviceName as name,
                            serviceURL as url
                   from     SERVICES
                   where    dateApproved is not NULL
                   order by name");

  echo "<html><head><title>OLAC Service Review</title>";
  echo '<script type="text/javascript" src="/js/gatrack.js"></script>';
  echo "</head></body>";
  echo "<table border='1' width='100%'>\n";
  echo "<tr><th></th><th bgcolor='#ffffdd'>Pending Requests</th><th width='1%'></th></tr>\n";
  if ($tab) foreach ($tab as $row) {
    print <<<END
<tr>
<td bgcolor="red"><blink>pending</blink></td>
<td bgcolor="white"><a href="$row[url]">$row[name]</a></td>
<td bgcolor="white"><a href="service_review.php?id=$row[id]">review</a></td>
</tr>
END;
  }
  echo "<tr><th></th><th bgcolor='#ffffdd'>Approved Services</th><th></th></tr>\n";
  if ($tab2) foreach ($tab2 as $row) {
    print <<<END
<tr>
<td></td>
<td><a href="$row[url]">$row[name]</a></td>
<td><a href="service_review.php?id=$row[id]&op=edit">edit</a></td>
</tr>
END;
  }
  echo "</table></body></html>\n";
  return TRUE;
}

function draw_detail($id)
{
  global $DB;
  global $form;

  $table = $DB->sql("select *
                     from   SERVICES
                     where  Service_ID=$id");
  $row = $table[0];

  print <<<END
<FORM ACTION="service_review.php" METHOD="post">
<TABLE CELLPADDING="5">
END;

  foreach ($form as $item) {
    $display = $item[display];
    $type = $item[type];
    $name = $item[name];
    $feature = $item[feature];
    $val = $row[$name];

    if ($type == "textarea") {
      print <<<END
<tr>
<td>$display:</td>
<td><$type name="$name" $feature>$val</$type></td>
</tr>
END;
    } else {
      print <<<END
<tr>
<td>$display:</td>
<td><$type name="$name" $feature value="$val"></$type></td>
</tr>
END;
    }
  }

  if ($_GET[op]) {
    $button_a = "Update";
    $button_b = "Delete";
  } else {
    $button_a = "Accept";
    $button_b = "Reject";
  }

  print <<<END
</TABLE>
<INPUT NAME="decision" TYPE="submit" VALUE="$button_a">
<input name="decision" type="submit" value="$button_b">
<input name="id" type="hidden" value="$row[Service_ID]">
</FORM>

<a href="service_review.php">Go back to summary page</a>
END;
}

function draw_emailform($cond)
{
  global $message;
  $local_msg = "Dear $_POST[contactPerson],\n\n$message[$cond]";

  print <<<END
<form action="service_review.php" method="post">
<table>
<tr>
<td>To:</td>
<td><input name="to" value="$_POST[contactPerson] <$_POST[contactEmail]>" size="50"></td>
</tr>
<tr>
<tr>
<td>Subject:</td>
<td><input name="subject" value="OLAC service registration" size="50"></td>
</tr>
<td></td>
<td><textarea name="email" rows="10" cols="75">$local_msg</textarea></td>
</tr>
</table>
<input name="send" type="submit" value="Send">
</form>
<a href="service_review.php">Go back to summary page</a>
END;
}

################################
##                            ##
##  Main routine starts here  ##
##                            ##
################################

authenticate();

switch ($_POST[decision]) {

  case "Accept":
    $setstr = "";
    foreach ($form as $item) {
      $field = $item[name];
      $setstr .= "$field='$_POST[$field]',";
    }
    $setstr .= "dateApproved='" . date("Y-m-d") . "'";
    $DB->sql("update SERVICES set $setstr
              where Service_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system error";
      #echo "<p>" . $DB->get_error_message();
      #echo "<p>" . $DB->get_error_sql();
      break;
    }
    draw_emailform($_POST[decision]);
    break;

  case "Reject":
    $DB->sql("delete from SERVICES where Service_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system_error";
      break;
    }
    draw_emailform($_POST[decision]);
    break;

  case "Update":
    $setstr = "";
    foreach ($form as $item) {
      $field = $item[name];
      $setstr .= "$field='$_POST[$field]',";
    }
    $setstr = substr($setstr, 0, -1);
    $DB->sql("update SERVICES set $setstr
              where Service_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system error";
      #echo "<p>" . $DB->get_error_message();
      #echo "<p>" . $DB->get_error_sql();
      break;
    }
    draw_summary();
    break;

  case "Delete":
    $DB->sql("delete from SERVICES where Service_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system_error";
      break;
    }
    draw_summary();
    break;

  default:
    if ($_GET[id]) {
      draw_detail($_GET[id]);
    }
    else if ($_POST[send]) {
      $subject = $_POST[subject];
      $content = $_POST[email];
      if (get_magic_quotes_gpc()) {
        $subject = stripslashes($subject);
        $content = stripslashes($content);
      }
      mail($_POST[to], $subject, $content,
           "From: $OLAC_ADMIN_EMAIL\r\n".
           "Cc: $OLAC_ADMIN_EMAIL\r\n".
           "Reply-To: $OLAC_ADMIN_EMAIL\r\n".
           "X-Mailer: PHP/" . phpversion());
      draw_summary();
      echo "<p>Email has been sent to $_POST[to].";
    }
    else {
      draw_summary();
    }
}

?>

