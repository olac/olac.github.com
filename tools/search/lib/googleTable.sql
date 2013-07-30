create table GoogleTerms
(
	Term varchar(25) NOT NULL,
	LangID char(3) NOT NULL,
	LangName varchar(25) NOT NULL,
	PRIMARY KEY( Term, LangID )
);

INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("language", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("dictionary", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("description", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("linguistics", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("lexicon", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("grammar", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("alphabet", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("text", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("speech", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("recording", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("documentation", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("corpus", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("syntax", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("morphology", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("phonology", "ENG", "English");
INSERT INTO GoogleTerms(Term, LangID, LangName) 
 VALUES("discourse", "ENG", "English");
