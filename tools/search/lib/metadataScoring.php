<?php

/**

The class ItemReport is used to calculate a score out of 10 for a given
	OLAC record based on metadata quality

 Using best practice guidelines from:

       http://www.language-archives.org/OLAC/olacms_recs.html
       http://www.language-archives.org/REC/olac-extensions.html

CVS Info: $Header: /cvsroot/olac/olac_suite/mu_tools/lib/metadataScoring.php,v 1.2 2004/12/06 00:22:11 badenh Exp $

*/

require_once "olacdb.php";
$DB = new OLACDB();


#  Debugging info if run from command-line 
$DEBUG = 0;

# Used to give score of single record
#if ($argc>1) { scoreRecord( $argv, $argc ); }

function scoreRecord( $args, $argNum )
{
    global $DB;

    $item = new ItemReport( $args[1], $DB  );
    print "SCORE: " . $item->getScore() . "\n";
    return;
}

function scoreArchive( $args, $argNum)
{
    global $DB;
    $archive = new ArchiveReport( $args[1] );
    $archive->printReport($DB);
    return;
}






class ItemReport
{
    var $id;
    var $DB;
    var $codeUsage = array();

var $codeExistsWeight;		## Points allocated for code usage and
#var $codeBestPracticeWeight;	## following best practice guidelines
var $tagAbsentWeight;		## subtracted from bestpractice score


var $maxScore = 10;
var $maxUsageRank = 3;

    var $tags;

    function ItemReport( $itemID, $db )
    {
	# Points are lost if any of the tags below are absent from a record
	$this->tags = Array( "title", 
				"description",
				"subject",
				"date",
				"identifier");
        $this->id = $itemID;
	$this->DB = $db;

	$this->codeExistsWeight = (1/1);
	#$this->codeBestPracticeWeight = (1/3);
	$this->tagAbsentWeight = (1/5);
        return;
    }

    function getCodeUsage() { return $this->codeUsage; }

    function getDB() { return $this->DB; }

    function getID() { return $this->id; }


    function getScore()
    {
	$DB = $this->DB;
	global $DEBUG;

        $recordScore = 0;

	#####
	# Use of code values
	####

        $usageQuery = $this->buildTagUsageQuery();
        $codeUsageRes = $DB->sql( $usageQuery );
    

	if ($DEBUG) { echo "$usageQuery\n"; }

	    $codeBestPracticeScore = 0;
	$recordBestPractice = 0;
	$codeExistsScore = 0;
        foreach ($codeUsageRes as $row)
        {
            if ($DEBUG)
	    {
		echo "$row[TagName]: "
                . $row[codeExists] * $this->codeExistsWeight 
                . " "
                .  $row[bestPractice] * $this->codeBestPracticeWeight
                . "\n";
	    }
	    
	    $codeExistsScore += $row['codeExists'];
	    $codeBestPracticeScore += $row['bestPractice'];

	    $this->codeUsage['codeExists']  = $row['codeExists'];
	    $this->codeUsage['bestPractice'] = $row['bestPractice'];
        }
	
  	    if ($DEBUG) { echo "\n"; }

	    $numElems = sizeof($codeUsageRes);
	    if ($numElems == 0 ) { $numElems = 1; }

	    $recordBestPractice /= ($numElems);
	    $codeExistsScore /= $numElems;
	    $codeBestPracticeScore /= $numElems;

	######
	# Absent tags
	######

	$elemAbsentQuery = $this->buildElemAbsentQuery();
        $elemAbsentRes = $DB->sql( $elemAbsentQuery );

	$tagAbsentScore = 0;
	foreach( $elemAbsentRes as $elem )
	{
	    if ($DEBUG)
	    {
		echo "$elem[TagName]: $elem[tagAbsent] $elem[Rank] ";
		echo " --> " 	
		.  ($elem[tagAbsent] * $elem[Rank])/3
			. "\n";
	    }
	    
	    if ( array_key_exists('Rank', $elem) && $elem['Rank'] != 0)
	    {
		$tagAbsentScore += $elem[tagAbsent] * $elem[Rank];
			#($elem[tagAbsent] * $elem[Rank])/$this->maxUsageRank;
	    }
	}

	/* # The total number of points  that could be lost for absent tags
	   # Used when deducting points relative to usage statistics

	$totalTagPointsQuery = "select sum(Rank/{$this->maxUsageRank}) 
					as maxTagAbsent
				from TAG_USAGE";

	$tagPointsRes = $DB->sql( $totalTagPointsQuery );

		$tagAbsentScore = ($tagAbsentScore * $this->tagAbsentWeight)
			/ ($tagPointsRes[0][maxTagAbsent]);
	*/

	# Deduction for an absent core tag is the fraction of core elements
	#	which are absent.

	$tagAbsentScore = (sizeof($this->tags) - sizeof($elemAbsentRes))
				/ (sizeof($this->tags));
	$recordScore = max( 0,
		round ($this->maxScore 
			* ($codeExistsScore*$this->codeExistsWeight
				- $tagAbsentScore*$this->tagAbsentWeight) 
		) );

		if ($DEBUG)
		{
		echo "CodeExistsScore:\t\t $codeExistsScore\n";
		echo "CodeExistsWeight:\t$this->codeExistsWeight\n";
		echo "CodeBestPracticeScore:\t$codeBestPracticeScore\n";
		echo "CodeBestPracticeWeight:\t$this->codeBestPracticeWeight\n";
		echo "TagAbsentscore:\t\t $tagAbsentScore\n";
	 	echo "TagAbsentWeight\t\t $this->tagAbsentWeight\n";
		echo "MaxScore:\t\t $this->maxScore\n";
		}

        return $recordScore;
    }

    function buildTagUsageQuery()
    {
        return "select me.TagName, ex.AppliesTo, me.Content,
            me.Code as meCode, me.Extension_ID as meExtensionID,
                cd.Extension_ID as cdExtensionID,
            cd.Code as cdCode,
            me.Type as meType, ex.Type exType,

            ((cd.Code is not null or me.Extension_ID > 6) 
		and (me.Code != '' and me.Extension_ID !=0)
		and (me.Content is null))
			as bestPractice,
					#ie, the code used matches with an
					# accepted value from the controlled
					# vocabulary for the given extension
					# and, for language extensions, the
					# content is left blank
            (me.Code != '' and me.Extension_ID != 0) as codeExists

            from EXTENSION ex, METADATA_ELEM_MYISAM me
            LEFT OUTER JOIN CODE_DEFN cd
            on (cd.Extension_ID = me.Extension_ID
                and me.Code = cd.Code)
            where me.Item_ID = '"
            . $this->id
            . "' and ex.AppliesTo LIKE CONCAT('%', me.TagName, '%')
            group by me.Element_ID";

    }

    function buildElemAbsentQuery()
    {
	$returnQuery = "select me.Tag_ID, me.TagName
        		from METADATA_ELEM_MYISAM me
        		where me.Item_ID = " .  $this->id . "
			and me.TagName in (";

	$i=0;
	foreach($this->tags as $tag)
	{
		if ($i!=0) { $returnQuery .= ","; } 
		$returnQuery .= " '$tag' ";
		$i++;
	}

	$returnQuery .= ") group by me.Tag_ID";

	/*
        $returnQuery =  "select ed.TagName, ed.Tag_ID,
                (temp.Tag_ID is null) as tagAbsent, tu.Rank
            from ELEMENT_DEFN ed LEFT OUTER JOIN
                    (select Tag_ID, TagName
                    from METADATA_ELEM_MYISAM me
                    where Item_ID = '"
            . $this->id . "')
                as temp
            using (Tag_ID),
            TAG_USAGE tu
            where tu.Tag_ID = ed.Tag_ID
            group by ed.Tag_ID";
	*/

	return $returnQuery;
    }

}



class ArchiveReport
{

var $archiveID;

function ArchiveReport($id)
{
    $this->archiveID = $id;
    return;
}

function printReport($DB)
{
$sum = 0;

$allRecsQuery = "select Item_ID 
		from ARCHIVED_ITEM
		where Archive_ID = " . $this->archiveID;

$allRecs = $DB->sql( $allRecsQuery );

foreach ($allRecs as $item)
{
    echo $item[Item_ID] . ":\n";
    $itemRec = new ItemReport( $item[Item_ID] );

    print "\nSCORE: \t" . $itemRec->getScore() . "\n-------------\n";
}

    echo "\nNumber of records: " . sizeof( $allRecs ) . "\n";
    echo "Average record score: $sum\n";

}



}


?>
