<?php

# Code adapted from the boolTok.php program at:
#	http://www.tc.umn.edu/~brams006/booltok.html
#

function boolTok($searchstring, $field_name, &$where){

    $me = "me";
    $i=0;
  // Debug mode: 1=ON, 0=OFF
  $DEBUG = 0;

  // Define a quote within a string as: \"
  $quote = "\\\"";

  // Initialize remaining variables.
  $tokenArray = array();
  $counter = 0;
  $subcounter = 0;
  $firstquote = 0;
  $secondquote = 0;
  $lastbool = 1;
  $currentbool = 0;

  // If debugging, draw a header
  if ($DEBUG ==1 ) {
    printf("</center><hr>");
    printf("<h3>Boolean debugger</h3>");
  }

  // Convert incoming to upper-case
  $searchstring = strtoupper ($searchstring);

  // Peel off empty spaces
  $searchstring = trim($searchstring);

  // Display incoming string
  if ($DEBUG == 1) printf("Search string was: %s<br>", $searchstring);

  // Initialize the WHERE clause
	$i = 0;
  $where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
  $where .= " AND " . "(MATCH({$me}$i.$field_name) ";

  #$i++;

  // Create the array of tokens
  //$tokenArray = split(" ", $searchstring);
  $tokenArray = tokenizeQuery( $searchstring, false, false );

  // Display number of tokens
  if ($DEBUG == 1) printf("Number of discrete tokens: %d<br>", count($tokenArray));

  // Test for double boolean error
  $testSearchstring = $searchstring;
  $testSearchstring = ereg_replace($quote, "", $testSearchstring);
  $testSearchstring = stripslashes($testSearchstring);
  if ($DEBUG == 1) printf ("Searchstring without quotes: %s<br>", $testSearchstring);
  $testArray = split(" ", $testSearchstring);
  $counter = 0;
  $foundBoolean = 0;
  $problem = 0;
  while ($counter < count($testArray)){
    switch ($testArray[$counter]){

    case "AND":
      if ($foundBoolean == 1) $problem = 1;
      $foundBoolean = 1;
      break;

                case "OR":
                        if ($foundBoolean == 1) $problem = 1;
                        $foundBoolean = 1;
                        break;

                case "NOT":
                        if ($foundBoolean) $problem = 1;
                        $foundBoolean = 1;
                        break;
    default:
      $foundBoolean = 0;
    }

    $counter++;
  }

  // Reset to zero
  $counter = 0;

  if ($DEBUG == 1) printf("Double boolean problem? %d<BR>", $problem);

        // Check for first boolean error
        $firstItem = $tokenArray[0];
        if ($firstItem == "AND" || $firstItem == "OR" || $firstItem == "NOT") $problem = 1;
        if ($DEBUG == 1) printf("First item was: %s<BR>", $firstItem);

  // Check for last item as boolean error
  $lastItem = $tokenArray[count($tokenArray) - 1];
  if ($lastItem == "AND" || $lastItem == "OR" || $lastItem == "NOT") $problem = 1;
  if ($DEBUG == 1) printf("Last item was: %s<BR>", $lastItem);

  // Check for empty string
  if (strlen($searchstring) < 1) {
    $problem = 1;
    if ($DEBUG == 1) printf("Empty search string!<BR>\n");
  }

  // Check for too short a string
  if (strlen($searchstring) < 3) $problem = 1;

  // Check for too long a string
  #if (strlen($searchstring) > 50) $problem = 1;

  // No initial parsing problems.  All systems go.
  if ($problem != 1){

	$prevField=0;
	$lastField=0;
	$FIELD="";
    // Sift through each token and build the logic
    while ($counter < count($tokenArray) && $subcounter <= count($tokenArray)){

      $subcounter = $counter;

      // If a left side quote is found
      if ($DEBUG == 1) printf("Probed for first quote: %s<br>", substr($tokenArray[$counter],0,2));
      if (substr($tokenArray[$counter],0,2) == $quote) {

        // Remove the quote and any escape characters
        $tokenArray[$counter] = ereg_replace($quote, "", $tokenArray[$counter]);
              $tokenArray[$counter] = stripslashes($tokenArray[$counter]);
        $firstquote = 1;

        // Look for a right side quote
        while (substr($tokenArray[$subcounter], -2) != $quote && $subcounter < count($tokenArray)){
          $tokenArray[$counter] .= " " . $tokenArray[$subcounter + 1];

          // Check to see if string has a right side quote, and remove it.
          if (substr($tokenArray[$subcounter], -2) == $quote){
                              // Remove the quote and any escape characters
                              $tokenArray[$subcounter] = ereg_replace($quote, "", $tokenArray[$subcounter]);
                              $tokenArray[$subcounter] = stripslashes($tokenArray[$subcounter]);
          }
          $secondquote = 1;
          $subcounter++;
        }

      }


      // Found the right side quote
            if (substr($tokenArray[$counter],-2) == $quote) {
        $secondquote = 1;
        // Remove the quote and any escape characters
        $tokenArray[$counter] = ereg_replace($quote, "", $tokenArray[$counter]);
        $tokenArray[$counter] = stripslashes($tokenArray[$counter]);
      }

      if ($DEBUG == 1) printf("PARSE TRACE: token# %d, subtoken# %d, leftquote? %s, rightquote? %s -->",
        $counter, $subcounter, $firstquote, $secondquote);

	/******* Check for inline syntax *****/
      if (( strpos($tokenArray[$counter], ":") !== FALSE )
	&&( strpos($tokenArray[$counter],":") == strlen($tokenArray[$counter])-1  ))
      {
	$prevField++;
	$FIELD = substr( $tokenArray[$counter] , 0,  strlen($tokenArray[$counter]) - 1); 
	if (($prevField>1) && ($currentbool ==0)) { $currentbool=0; $i++; }
	else if ( $lastbool == 0 && $currentbool == 0 ) { $currentbool=0; $i++;}
	else { $currentbool = 1; }
      }
      else
      {

      // Build the logic and display if debugging is on
      switch($tokenArray[$counter]){
        case "AND":
          if ($DEBUG == 1) printf("<B>Boolean AND</B><BR>");

	  $i++;
          ###$where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
          $where .= " AND " . "MATCH ({$me}{$i}.$field_name) ";
          $currentbool = 1;
          break;

        case "OR":
          if ($DEBUG == 1) printf("<B>Boolean OR</B><BR>");
          #                 $where .= " OR " . $field_name;
          ####$where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
          $where .= " OR " . "MATCH ({$me}{$i}.$field_name) ";
	  #### $i++; # for OR?
          $currentbool = 1;
          break;

        case "NOT":
          if ($DEBUG == 1) printf("<B>Boolean NOT</B><BR>");
                             if (count($tokenArray) >= $counter){
          #                  $where .= " AND " . $field_name . " NOT ";
          ####$where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
	  $i++;
          $where .= " AND " . "MATCH ({$me}{$i}.$field_name) " . " NOT ";
          }
          else {
            $where .= " NOT ";
          }
          $currentbool = 1;
          break;

        default:
          if ($DEBUG == 1) printf("%s<BR>", $tokenArray[$counter]);
                   // Check to make sure last item was a boolean token
                 if ($lastbool == 1) 
	  {
                   if (count($tokenArray) > 0 && $subcounter <= count($tokenArray) )
              	   {
			$where .= " AGAINST('" . $tokenArray[$counter] . "')";
                        #if (count($tokenArray) == 0) { $where .= " AGAINST('" . $tokenArray[$counter] . "')"; }
		   }
          }

          // If not, assume AND
          else {

            if ($DEBUG == 1) printf("PARSE TRACE: Syntax not clear, assuming <b>AND</b><br>");
	/*
	    if ($prevField==1)
	    {
              $where .= " AGAINST('" . $tokenArray[$counter] . "')";
	      $prevField=0;
	    }
	    else
	*/
            if (count($tokenArray) > 0 && $subcounter <= count($tokenArray))
              #$where .= " AND " . $field_name . " AGAINST('" . $tokenArray[$counter] . "')";
	    {
		$i++;
              #$where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
              $where .= " AND " . "MATCH({$me}{$i}.$field_name) " . " AGAINST('" . $tokenArray[$counter] . "')";
	    }
            if (count($tokenArray) == 0)
              #$where .= " AND " . $field_name . " AGAINST('" . $tokenArray[$counter] . "')";
	    {
		$i++;
              #$where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
              $where .= " AND " . "MATCH({$me}{$i}.$field_name) " . " AGAINST('" . $tokenArray[$counter] . "')";
	    }
          	#$where .= " and ar.Item_ID={$me}{$i}.Item_ID ";
          }

          // Setcurrent boolean to false
          $currentbool = 0;

            }

      } ///////////


      // Set lastboolean equal to current boolean
          $lastbool = $currentbool;

      //  Increment to next token
	$counter++;

      // If this is a multiple-entry token, increment by the word count
      if ($secondquote == 1){
        $counter = $subcounter + 1;
        $secondquote = 0;
        $firstquote = 0;
      }

	#if ($FIELD != "" && ($prevField!=1) )
	if ($lastField)
	{
	$FIELD="";
	$lastField=0;
	}
	if ($FIELD != "" && !$currentbool)
	{
	$where .= " and {$me}{$i}.TagName = '$FIELD' ";
	$lastField=1;
	}
    }

    $where .= ")";
    // Display the SQL
    if ($DEBUG == 1) printf("<b>Final generated WHERE: %s</b><br><hr><br>", $where);

  // Search string looked good.  End the algorithm.
  }

  // Syntax was problematic.  Set $where = "ERROR"
  else $where = "ERROR";

  #return $where;

  $k=0;
  while( $k <= $i )
  {
      $where .= " and ar.Item_ID={$me}{$k}.Item_ID ";
      $k++;
  }

   return $i;
}


function tokenizeQuery($queryString, $matchTags, $keepBooleans)
{
    global $DB;
    
    $tokens = array();
    $booleanKeywords = Array ( "and", "or", "not" );

    $separators = " \t";
    if ( $matchTags )
    {
        $separators .=":";
    }

    #if ($_GET[phrasemode])
    if ( array_key_exists( 'phrasemode', $_GET ) )
    {
        $tokens[0]= trim($queryString);
        return $tokens;
    }

    $tok = strtok($queryString, $separators);
    $i=0;
    while ($tok)
    {
        #$tokens[$i] = str_replace( "*", "%", $tok ); #Replace wildcard

        if ( in_array( $tok, $booleanKeywords ) && (!$keepBooleans) )
        {
                # FIXME
                #$i--;
        	$tok = strtok( $separators );
		continue;
        }

        # Deal with inline syntax
        else if ( ($pos = strpos( $tok, ":" )) !== FALSE )
        {
            if ($pos != (strlen($tok)-1))
            {
            $tokens[$i] = substr($tok, 0, $pos+1);
            $i++;
            $tokens[$i] = substr($tok, $pos+1);
            }
            else
            {
            $tokens[$i] = substr($tok, 0, $pos+1);
            }
        }
        else
        {
        $tokens[$i] = $tok;
        }
        $tok = strtok( $separators );
        $i++;
    }

    $j=0;
    foreach($tokens as $token)
    {
	$tokens[$j] = mysql_escape_string($token);
	$j++;
    }
    return $tokens;
}


?>
