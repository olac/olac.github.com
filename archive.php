<?php
#
# Synopsis: Display the archive informaion from the olac database.
#

require_once("lib/php/OLACDB.php");
$DB = new OLACDB();

############################################################
# returns a hash of the following form
#
#   display string ==> value
############################################################
function get_archive_info($id)
{
	global $DB;

    $tab = $DB->sql("select *
                     from   OLAC_ARCHIVE
                     where  RepositoryIdentifier='$id'");
    if (! $tab) {
        print "Invalid archive id: $id";
		echo "\n</body>\n</html>";
		exit;
    }
    $archiveid = $tab[0]['Archive_ID'];
    $repoid = $tab[0]['RepositoryIdentifier'];
    $tab1 = $DB->sql("select count(Item_ID) as size
                      from   ARCHIVED_ITEM
                      where  Archive_ID=$archiveid");
    $tab2 = $DB->sql("select distinct SchemaName
                      from   ARCHIVED_ITEM as A, SCHEMA_VERSION as S
                      where  A.Archive_ID=$archiveid and A.Schema_ID=S.Schema_ID");
    $tab3 = $DB->sql("select Name, Role, Email
                      from   ARCHIVE_PARTICIPANT
                      where  Archive_ID=$archiveid");
    $tab4 = $DB->sql("select max(DateStamp) DateStamp
                      from ARCHIVED_ITEM
                      where Archive_ID=$archiveid");
    $tab5 = $DB->sql("select type from ARCHIVES where ID='$repoid'");
    $archivetype = $tab5[0]['type'];
    $r = $tab[0];

    $o[Size] = $tab1[0][size];
    $o["Repository Name"] = $r[RepositoryName];
    $i = $r[Institution];
    $iu = $r[InstitutionURL];
    if ($i) {
        if ($iu)
            $o[Institution] = "<a href=\"$iu\">$i</a>";
        else
            $o[Institution] = $i;
    } elseif ($iu)
       $o[Institution] = "<a href=\"$iu\">$iu</a>";
    else
        $o[Institution] = "";
    $o[ArchiveURL] = "<a href=\"$r[ArchiveURL]\">$r[ArchiveURL]</a>";
    $c = $r[Curator];
    $ce = str_replace('mailto:', '', $r[CuratorEmail]);
    if ($c) {
        if ($ce)
            $o[Curator] = "<a href=\"mailto:$ce\">$c</a>";
        else
            $o[Curator] = $c;
    } elseif ($ce)
       $o[Curator] = "<a href=\"mailto:$ce\">$ce</a>";
    else
        $o[Curator] = "";
    $o[Location] = $r[Location];
    $o['Short Location'] = $r[ShortLocation];
    $o[Synopsis] = $r[Synopsis];
    $o[Access] = $r[Access];
    $o["Submission Policy"] = $r[ArchivalSubmissionPolicy];
    $r[AdminEmail] = str_replace('mailto:', '', $r[AdminEmail]);
    $o[Administrator] = "<a href=\"mailto:$r[AdminEmail]\">$r[AdminEmail]</a>";
    if ($tab3) {
        $participant_arr = array();
        foreach ($tab3 as $p) {
            $participant_arr[] = "<a href=\"mailto:$p[Email]\">$p[Name]</a> ($p[Role])";
        }
        $o[Participants] = implode(", ", $participant_arr);
    }
    $o["Base URL"] = $r[BaseURL];
    $o["Repository ID"] = $r[RepositoryIdentifier];
    $o["OAI Version"] = $r[OaiVersion];
    if ($tab2) {
        foreach ($tab2 as $v) {
            $o["OLAC Version"] .= "$v[SchemaName] ";
        }
    }
    $o["Records in Archive"] = "<a href=\"/archive_records/$id\">http://www.language-archives.org/archive_records/$id</a>";
    $qs = urlencode('archive_facet:"' . $r["RepositoryName"] . '"');
    $link = 'http://search.language-archives.org/search.html?q=' . $qs;
    $o["Faceted search"] = "<a href=\"$link\">$link</a>";
    if ($archivetype == 'Dynamic')
        $o[Explore] = "<a href=\"http://re.cs.uct.ac.za/cgi-bin/Explorer/2.0-1.46/testoai?archive=$r[BaseURL]\">Visit archive with the Repository Explorer</a>";
    $o["Last Harvested"] = $r[LastHarvested];
    $o["Current As Of"] = $r[CurrentAsOf];
    if (count($tab4) > 0)
        $o["Latest Datestamp"] = $tab4[0]['DateStamp'];
    $o["Reports"] = "<a href=\"/metrics/$id\">Archive Metrics</a> and <a href=\"/checks/$id\">Integrity Checks</a>";

    return $o;
}

?>



<html>
<head>
<title>OLAC - Archive details</title>
<link rel="stylesheet" type="text/css" href="/olac.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="/js/gatrack.js"></script>
</head>
<body bgcolor=white>

<?php

$archiveid = $_GET[id];
if (!$archiveid) {
	$arr = explode('?', $_SERVER["REQUEST_URI"]);
	$archiveid = basename($arr[0]);
}
$record = get_archive_info($archiveid);
?>
<hr>
<table cellpadding=10>
<tr><td><a href="/"><img src="/images/olac100.gif" border=0></a></td>
<td><h1>Archive Details</h1></td></tr>
</table>
<hr>
<?php
echo "<h2>" . $record["Repository Name"] . "</h2>";
if (! $record["Current As Of"]) {
    echo "<p><b><font color=red>This repository doesn't conform to OLAC 1.1 standards.</font></b></p>";
}

echo "<table>";
foreach ($record as $k => $v) {
    if (! preg_match('/^\s*$/', $v)) {
        echo "<tr><td style=\"background-color:#DDDDEE\">$k</td>";
        echo "<td style=\"border-bottom: dotted; border-color: #DDDDEE; border-width: 1px\"><font color=blue>$v</font></td></tr>\n";
    }
}
echo "</table>";
?>

<br><hr>
<div class=timestamp>
<?=olacvar('baseurl') . $_SERVER["REQUEST_URI"]?><br>
Up-to-date as of: <?=date("D M j G:i:s T Y")?>
</div>

</body>
</html>
