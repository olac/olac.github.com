/**
*
*	Keyword Search of OLAC Records
*	Archive Report Cards
*
*/

------------
I. File List
------------

	-- Querying and displaying web results --
	searchWorking.php
	olacdb.php
	lookup.php	
	olac.css

	-- Building tables upon which searchWorking.php depends --
	ethnologue.sql
	ethnologueTables.sql
	searchTables.sql
	LanguageIndex.tab
	LanguageCodes.tab
	CountryCodes.tab
	ChangeHistory.tab

	-- Populating/Updating tables upon which searchWorking.php depends --
	metadataScoring.php
	createTagUsageTable.php
	createSoundexTable.php
	createItemScoresTable.php

	-- Images --
	star0.gif
	star1.gif
	star2.gif
	star3.gif
	star4.gif
	star5.gif
	olac100.gif

	-- Aggregate information about archives and metadata --
	quantAnalysis.php
	form.php
	archiveReport.php

	-- Documentation --
	documentation.html
	docStyle.css
	ExplainReport.html
	README.txt
	MANIFEST

----------------
II. INSTALLATION
----------------

  1. Set up the OLAC harvester to store data in a MySQL database.

  2. Change the value of $dbName on line 7 of olacdb.php to name of the OLAC 
	database. 
     Change $userName, $password and $hostname on lines 9 to 11 of olacdb.php
	to the appropriate values for connecting to the OLAC database.

  3. Create Ethnologue tables:

	Move the following files into the database directory:
		LanguageIndex.tab
		LanguageCodes.tab
		ChangeHistory.tab
		CountryCodes.tab

	Then run:

	mysql> source ethnologue.sql
	
		[ NOTE: 
			The .tab files need to reside in the database directory
			for the above command to work.
			Else, make all files readable by all and submit each
			command below using full path names

		mysql> source ethnologueTables.sql
		mysql> LOAD DATA INFILE "ChangeHistory.tab" INTO TABLE
        		ChangeHistory(LangID, Action, Date, Description);

		mysql> LOAD DATA INFILE "LanguageCodes.tab" 
			INTO TABLE LanguageCodes;

		mysql> LOAD DATA INFILE "LanguageIndex.tab" 
			INTO TABLE LanguageIndex;

		mysql> LOAD DATA INFILE "CountryCodes.tab" 
			INTO TABLE CountryCodes;
		]

  4. Create soundex and ranking tables:

	mysql> source searchTables.sql

		[ Creates the tables:
			SOUNDEX_TABLE
			TAG_USAGE
			ITEM_SCORES
			LanguageSoundex - and populates with soundex values

		  Creates the indices:
			SOUNDEX_INDEX on SOUNDEX_TABLE
			LangSoundexIndex on LanguageSoundex
			metadataContentIndex - FULLTEXT index 
					       on METADATA_ELEM(Content)
		]

  5. Populate soundex and ranking tables:

	?> php createTagUsageTable.php
	?> php createSoundexTable.php
	?> php createItemScoresTable.php
			[ depends on metadataScoring.php ]

	These scripts can be set to run at the time of harvesting to update
	the tables which searchWorking.php depends upon.

	The createSoundexTable.php script is very expensive to run, as it looks
	through all of the content tags in the database. Since the
	probability of finding new distinct words in newly harvested
	records is low with a large database, this script may not need to be
	run after every harvest.

	

