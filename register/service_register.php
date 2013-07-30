<?php

require_once("../lib/php/OLACDB.php");

###############
# Global data #
###############

$form = array( 0 => array('type' => 'input',
		          'name' => 'name',
			  'display' => 'Name of service',
			  'feature' => 'size="80"'),
               1 => array('type' => 'input',
                          'name' => 'baseURL',
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
$required_fields = array(0,1,4,5);
$session_marker = $_POST[tries];


#############
# Functions #
#############

function draw_form()
{
  global $form;
  global $required_fields;
  global $session_marker;

  print <<<END
<FORM ACTION="service_register.php" METHOD="post">
<TABLE CELLPADDING="5">
END;

  for ($i=0; $i < 7; $i++) {
    $display = $form[$i][display];
    $type = $form[$i][type];
    $name = $form[$i][name];
    $feature = $form[$i][feature];
    $val = $_POST[$name];

    if ($_POST[tries] && in_array($i, $required_fields) && $val=="")
      $display = '<font color="red">*</font>' . $display;

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
<td><$type name="$name" value="$val" $feature></$type></td>
</tr>
END;
    }
  }

  print <<<END
</TABLE>
<INPUT NAME="register" TYPE="submit" VALUE="Register"> <BR>
<input name="tries" type="hidden" value="$session_marker">
</FORM>
END;
}

function register_service()
{
  $odb = new OLACDB();
  if ($odb->saw_error())
    return $odb->get_error_message();

  $odb->sql("
      insert into SERVICES
          (serviceName, serviceURL, institution, institutionURL,
           contactPerson, contactEmail, description)
      values
          ('$_POST[name]', '$_POST[baseURL]', '$_POST[institution]',
           '$_POST[institutionURL]', '$_POST[contactPerson]',
           '$_POST[contactEmail]', '$_POST[description]')
  ");

  if ($odb->saw_error())
    return $odb->get_error_message() . "<br>" .
           $odb->get_error_sql();

  $table = $odb->sql("select LAST_INSERT_ID() as num");
  unset($odb);

  return array("okay", $table[0][num]);
}

?>

<html>
<head>
<title>OLAC Service Provider Registration</title>
<script type="text/javascript" src="/js/gatrack.js"></script>
<link rel="stylesheet" type="text/css" href="../olac.css">
</head>
<body>

<TABLE BORDER="1" WIDTH="100%" BGCOLOR="#c0c0c0" CELLPADDING="10">
<TBODY>
<TR>
<TD BGCOLOR="#eeeeee"> <H2><A NAME="register"></A>Register Your Service </H2>

<?php

if (!$_POST[tries]) {

  // new registration session started
  $session_marker = tempnam("/tmp","olac-service");
print <<<END
<P>You should have already read the informational page on <A href
="service.html">OLAC Service Provider Registration</A>.
Now that you understand all that is involved in registration, enter
information about your service in the form below and click
&quot;Register&quot;.&nbsp; You'll receive
<A HREF="service.html#confirmation">confirmation</A> shortly.</P>
END;
  draw_form();

} else {

  $flag = FALSE;
  // check if required fields are filled out
  foreach ($required_fields as $field) {
    $name = $form[$field][name];
    $display = $form[$field][display];
    if ($_POST[$name] == "") {
      echo "<font color='red'>\"$display\"</font> is required field<br>";
      $flag = TRUE;
    }
  }

  if ($flag)    // found some errors in entry
    draw_form();
  else if (file_exists($_POST[tries])) {
    // current session is still alive
    $result = register_service();
    if ($result[0] == "okay") {
      // registration was successful
      unlink($_POST[tries]);
      echo "Thank you for registering!<br>";
      echo "Your request has been accepted for review.";
      mail(olacvar('olac_admin_email'),
           "New OLAC service registration request",
           "Hi,\n\nThere is a new OLAC service registration request arrived:\n\n".
           "Service name: $_POST[name]\n".
           "Service URL:  $_POST[baseURL]\n\n".
           "You can review the request from the following URL:\n\n".
           olacvar('baseurl') . "/register/service_review.php?id=$result[1].\n\n".
           "To review all requests use the following URL:\n\n".
           olacvar('baseurl') . "/register/service_review.php.\n\n".
           "Thanks,\n".
           "service_register.php\n",
           "From: " . olacvar('olac_admin_email') . "\r\n".
           "Reply-To: " . olacvar('olac_admin_email') . "\r\n".
           "X-Mailer: PHP/" . phpversion());
    }
    else {
      // registration failed
      print <<<END
The following system error occurred during the registration.
Please try later. Sorry for the inconvenience. <br>
<table border='1' width='80%' cellpadding='5'>
  <td><font color='red'>system error</font><br>
    $result
  </td>
</table>
END;
      draw_form();
    }
  }
  else {
    echo "You registeration session has been expired.<br>";
  }
}

?>

</TD>
</TR>
</TBODY>
</TABLE>
<P>&nbsp;</P>
<P>Questions: <A HREF="mailto:olac-admin@language-archives.org">olac-admin@language-archives.org</A>
</P>
</BODY>
</HTML>
 
