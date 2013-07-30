<?php

require_once("../lib/php/OLACDB.php");
require_once("../lib/php/OLAC_general.php");
#$OLAC_ADMIN_EMAIL = "haejoong@ldc.upenn.edu";

###############
# Global data #
###############

$DB = new OLACDB("olac2");
$EXPLORER = "http://re.cs.uct.ac.za/cgi-bin/Explorer/2.0-1.46/testoai";

$subject[Accept] = "OLAC registration accepted";
$message[Accept] =
"Congratulations!
Your request has been accepted. Your archive will be listed on the following
OLAC web page within 24 hours:

  http://www.language-archives.org/archives.php4

Thank you for registering your archive.

Sincerely,

OLAC administration team
";

$subject[Finish] = $subject[Accept];
$message[Finish] = $message[Accept];

$subject[Reject] = "OLAC registration rejected";
$message[Reject] =
"Unfortunately, we can't procecess your request because ...

Regards,

OLAC administration team
";

$form = array( 0 => array('type' => 'input',
                          'name' => 'ID',
                          'display' => 'Repository ID',
                          'feature' => 'size="80"'),
               1 => array('type' => 'input',
                          'name' => 'BASEURL',
                          'display' => 'Repository URL',
                          'feature' => 'size="80"'),
               2 => array('type' => 'input',
		          'name' => 'contactEmail',
                          'display' => 'Contact email',
			  'feature' => 'size="80"'),
               3 => array('type' => 'input',
		          'name' => 'dateApproved',
                          'display' => 'Approved Date',
			  'feature' => 'size="80" readonly')
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
  global $EXPLORER;

  $tab = $DB->sql("select   Archive_ID as sn,
                            type,
                            ID,
			    BASEURL
		   from     ARCHIVES
                   where    dateApproved is NULL
                   order by ID");
  if ($DB->saw_error()) { return FALSE; }
  $tab2 = $DB->sql("select  Archive_ID as sn,
                            type,
			    ID,
                            BASEURL
                   from     ARCHIVES
                   where    dateApproved is not NULL
                   order by ID");

  echo "<table border='1' width='100%'>\n";
  echo "<tr><th></th><th bgcolor='#ffffdd' colspan='2'>Pending Requests</th><th width='1%'></th></tr>\n";
  if ($tab) foreach ($tab as $row) {
    print <<<END
<tr>
<td bgcolor="red"><blink>pending</blink></td>
<td bgcolor="white">$row[ID]</td>
END;
    if ($row[type] == "Static") {
      print <<<END
<td bgcolor="white">$row[BASEURL]</td>
END;
    } else {
      print <<<END
<td bgcolor="white"><a href="$EXPLORER?archive=$row[BASEURL]">$row[BASEURL]</a></td>
END;
    }
print <<<END
<td bgcolor="white"><a href="$_SERVER[PHP_SELF]?id=$row[sn]">review</a></td>
</tr>
END;
  }
  echo "<tr><th></th><th bgcolor='#ffffdd' colspan='2'>Approved Archives</th><th></th></tr>\n";
  if ($tab2) foreach ($tab2 as $row) {
    echo "<tr><td></td><td>$row[ID]</td>";
    if ($row[type] == 'Dynamic') {
      echo "<td><a href=\"$EXPLORER?archive=$row[BASEURL]\">$row[BASEURL]</a></td>";
    } else {
      echo "<td>$row[BASEURL]</td>";
    }
    echo "<td><a href=\"$_SERVER[PHP_SELF]?id=$row[sn]\">edit</a></td></tr>";
  }
  echo "</table>\n";
  return TRUE;
}

function draw_sr_reg_form($row)
{
  global $EXPLORER;

  #$gwurl = preg_replace("'^http://'","http://www.language-archives.org/sr/",$row[BASEURL]);

  print <<<END
<p>
Repository information:
<table border="1px">
<tr><td>Repository id</td><td>$row[ID]</td></tr>
<tr><td>Base URL</td><td>$row[BASEURL]</td></tr>
<tr><td>Repository admin email</td><td>$row[contactEmail]</td></tr>
</table>
</p>

<hr>
<p>If you accept the request, please register the repository with our gateway then press finish button:</p>

<ul>
<li>
Step 1:
<!--
<a href="$EXPLORER?archive=$gwurl">Inspection using Repository Explorer</a>
(optional)
-->
Inspection using Repository Explorer (this is not supported currently)
</li>

<form method="post">
<li>
Step 2:
<input type="submit" name="decision" value="Finish"/>
</li>
<input type="hidden" name="id" value="$row[Archive_ID]"/>
<input type="hidden" name="ID" value="$row[ID]"/>
<input type="hidden" name="baseurl" value="$row[BASEURL]"/>
<input type="hidden" name="contactEmail" value="$row[contactEmail]"/>
</ul>

<hr>
If you want to reject the request:
<input type="submit" name="decision" value="Reject"/>
</form>

END;

}

function draw_detail($id)
{
  global $DB;
  global $form;
  global $EXPLORER;

  $table = $DB->sql("select *
                     from   ARCHIVES
                     where  Archive_ID=$id");
  $row = $table[0];

  if ($row[type] == "Static") {
    draw_sr_reg_form($row);
    return;
  }

  print <<<END
<FORM ACTION="$_SERVER[PHP_SELF]" METHOD="post">
<TABLE CELLPADDING="5">
<tr><td colspan="2"><b>Repository information</b></td></tr>
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

  if ($row[dateApproved]) {  # edit
    $button_a = "Update";
    $button_b = "Delete";
  } else {          # review
    $button_a = "Accept";
    $button_b = "Reject";
  }

  print <<<END
</TABLE>
<INPUT NAME="decision" TYPE="submit" VALUE="$button_a">
<input name="decision" type="submit" value="$button_b">
<input name="id" type="hidden" value="$row[Archive_ID]">
</FORM>

<hr>
<p><b>Tools</b></p>

<li>
<a href="$EXPLORER?archive=$row[BASEURL]">Inspect with RE</a>
</li>

<form action="register.php4" method="post">
<li>
<input type="submit" value="OLAC DP Validation"/>
</li>
<input type="hidden" name="url" value="$row[BASEURL]"/>
</form>

<hr>
<a href="$_SERVER[PHP_SELF]">Go back to summary page</a>
END;
}

function draw_emailform($cond)
{
  global $subject, $message;
  $local_msg = "Dear $_POST[ID] administrator,\n\n$message[$cond]";

  print <<<END
<form action="$_SERVER[PHP_SELF]" method="post">
<table>
<tr>
<td>To:</td>
<td><input name="to" value="$_POST[contactEmail]" size="50"></td>
</tr>
<tr>
<tr>
<td>Subject:</td>
<td><input name="subject" value="$subject[$cond]" size="50"></td>
</tr>
<td></td>
<td><textarea name="email" rows="10" cols="75">$local_msg</textarea></td>
</tr>
</table>
<input name="send" type="submit" value="Send">
</form>
<a href="$_SERVER[PHP_SELF]">Go back to summary page</a>
END;
}

function unregister_gateway($url)
{
  $base = "../cgi-bin/gateway/db";
  $url = preg_replace("'^http://www.language-archives.org/sr'", "", $url);
  $fp = fopen("$base/gateway.conf", "r");
  $fp2 = fopen("$base/gateway.conf2", "w");
  while (!feof($fp)) {
    $line = fgets($fp);
    if (preg_match("'^$url\s.*$'", $line)) {
      $a = preg_split("'\s+'", $line);
      unlink($a[1]);
    }
    else {
      fwrite($fp2, $line);
    }
  }
  fclose($fp);
  fclose($fp2);
  unlink("$base/gateway.conf");
  rename("$base/gateway.conf2", "$base/gateway.conf");
  chmod("$base/gateway.conf", 0660);
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
    $DB->sql("update ARCHIVES set $setstr
              where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system error";
      #echo "<p>" . $DB->get_error_message();
      #echo "<p>" . $DB->get_error_sql();
      break;
    }
    draw_emailform($_POST[decision]);
    break;

  case "Finish":
    $setstr = "BASEURL='$_POST[baseurl]',";
    #$setstr .= "type='Gateway',";
    $setstr .= "dateApproved='" . date("Y-m-d") . "'";
    $DB->sql("update ARCHIVES set $setstr
              where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system error";
      #echo "<p>" . $DB->get_error_message();
      #echo "<p>" . $DB->get_error_sql();
      break;
    }
    draw_emailform($_POST[decision]);
    break;

  case "Reject":
    $DB->sql("delete from ARCHIVES where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system_error";
      #echo "<p>" . $DB->get_error_message();
      #echo "<p>" . $DB->get_error_sql();
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
    $DB->sql("update ARCHIVES set $setstr
              where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system error";
      echo "<p>" . $DB->get_error_message();
      echo "<p>" . $DB->get_error_sql();
      break;
    }
    draw_summary();
    break;

  case "Delete":
    $tab = $DB->sql("select * from ARCHIVES where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system_error";
      break;
    }
    $repotype = $tab[0][type];
    if ($repotype == 'Gateway') {
      unregister_gateway($tab[0][BASEURL]);
    } 
    $DB->sql("delete from ARCHIVES where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system_error";
      break;
    }
    $DB->sql("delete ai.*, me.* from ARCHIVED_ITEM ai, METADATA_ELEM me where ai.Archive_ID=$_POST[id] and ai.Item_ID=me.Item_ID");
    if ($DB->saw_error()) {
      echo "system_error";
      break;
    }
    $DB->sql("delete from ARCHIVE_PARTICIPANT where Archive_ID=$_POST[id]");
    if ($DB->saw_error()) {
      echo "system_error";
      break;
    }
    $DB->sql("delete from OLAC_ARCHIVE where Archive_ID=$_POST[id]");
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
      $subj = $_POST[subject];
      $content = $_POST[email];
      if (get_magic_quotes_gpc()) {
        $subj = stripslashes($subj);
        $content = stripslashes($content);
      }
      mail($_POST[to], $subj, $content,
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
