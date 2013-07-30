<?   $vars = array(
	  "contributor" => "contributor",
	  "coverage"=> "coverage",
	  "creator"=> "creator",
	  "datestamp" => "datestamp",
	  "description" => "description",
	  "format" => "format",
	  "format.cpu" => "format_cpu",
	  "format.encoding" => "format_encoding",
	  "format.markup" => "format_markup",
	  "format.os" => "format_os",
	  "format.sourcecode" => "format_sourcecode",
	  "identifier" => "identifier",
	  "language" => "language",
	  "publisher" => "publisher",
	  "relation" => "relation",
	  "source" => "source",
	  "subject" => "subject",
	  "Subject.language" => "Subject_language",
	  "title" => "title",
	  "type" => "type",
	  "type.data" => "type_data",
	  "type.functionality" => "type_functionality" );


#$attributes = array(
#	  "Subject.language" => "identifier"
#	  );
#


?>





<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Generate xml file for metadata records</title>
    <script type="text/javascript" src="/js/gatrack.js"></script>
  </head>

  <body bgcolor=white>
    
  <form  action="record.php4" enctype="multipart/form-data" method="post">


<?
	require('global.php4');
	require('globalvars');
	print_header();
    $keys = array_keys($vars);
	if ($submit)
	{
      $olac = $namespace['olac'];
	  $string = "<record>\n<header>\n<identifier>$OAI_ID</identifier>\n<datestamp>$Date</datestamp>\n";
	  $string .= "</header>\n<metadata>\n<olac xmlns=\"$olac\"\n";
      $string .= "xmlns:xsi=\"$xsi\"\nxsi:schemaLocation=\"$xmlns\"\n\"";
      $string .= $metadata_formats['olac']."\">\n";

      foreach ($keys as $tag)
         {  $name = $vars[$tag];
            $text = $$name;
            
            $values = explode(",", $text);
            foreach ($values as $val)
              {
                if (trim($val) != "")
                  {
                     $string .= "<$tag>$val</$tag>\n";
                  }
              }
         }

      $string .= "</olac>\n</metadata>\n</record>";

	  print "<TEXTAREA NAME=\"identify\" cols=\"70\" rows=\"40\" wrap=\"off\" >$string</textarea>";

	}
	
	
?>
	<h1>Generate xml file for metadata records</h1><br><br>

	<table border=0 cellPadding=0 cellSpacing=0>
       <tr><td><b>OAI Identifier of the record:&nbsp;&nbsp;&nbsp;&nbsp;</b></td>
             <td><input type="text" 
                        size="40" 
                        name="OAI_ID"
                        value="<?print $OAI_ID;?>";?></td></tr>
<? 
	


		 
		  foreach ($keys as $var)
		  {
               $name = $vars[$var];
		       $value = $$name;
               $label = ucfirst($var);
		       print "<tr><td><b>$label :</b></td><td><input type=\"text\" size=\"40\" name=\"$var\" value=\"$value\"></td>";



		  }
?>



	<tr><td></td><td><br><br><br><br><input type="submit" 
				   name="submit">	</td>	</tr>

	</table>
</form>
<?print_footer();?>
    <hr>
    <address><a href="mailto:ebanik@edgewood.ldc.upenn.edu">Eva Banik</a></address>
<!-- Created: Thu Jun 14 11:20:46 EDT 2001 -->
<!-- hhmts start -->
<!-- hhmts end -->
  </body>
</html>





