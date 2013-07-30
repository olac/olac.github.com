<?php

require_once "olacdb.php";

$DB = new OLACDB();

$createTable = "
create table GoogleTerms
(
	Term varchar(25) NOT NULL,
	LangID char(3) NOT NULL,
	LangName varchar(25) NOT NULL,
	PRIMARY KEY( Term, LangID )
)";

$DB->insertQuery($createTable);

$DB->insertQuery("INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('language', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('dictionary', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('description', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('linguistics', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('lexicon', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('grammar', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('alphabet', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('text', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('speech', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('recording', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('documentation', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('corpus', 'ENG', 'English')");


$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('syntax', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('morphology', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('phonology', 'ENG', 'English')");

$DB->insertQuery("
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES('discourse', 'ENG', 'English')");

?>
