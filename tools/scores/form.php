<?

function archivesOptions($DB)
{

    $query = "select distinct RepositoryIdentifier from OLAC_ARCHIVE
                ORDER BY RepositoryIdentifier";

    ($result = $DB->sql($query)) or die ("Error creating archive option box")
;

        echo "<option value=\"\">-- All archives --";
    foreach($result as $arch)
    {
        echo "<option value='$arch[RepositoryIdentifier]'";
        if ($_GET[archive] == $arch[RepositoryIdentifier]) { echo " selected"
;}
        print "> $arch[RepositoryIdentifier]";
    }


    return;
}
?>
