CREATE TABLE LanguageCodes (
   LangID      char(3) NOT NULL,        -- Three-letter code
   CountryID   char(2) NOT NULL,        -- Main country where used
   LangStatus  char(1) NOT NULL,        -- L(iving), N(early extinct),
                                        -- E(xtinct)
   Name        varchar(75) NOT NULL);    -- Primary name in that country

CREATE TABLE CountryCodes (
   CountryID   char(2) NOT NULL,        -- Two-letter code from ISO3166
   Name        varchar(75) NOT NULL );   -- Country name

CREATE TABLE LanguageIndex (
   LangID      char(3) NOT NULL,        -- Three-letter code for language
   CountryID   char(2) NOT NULL,        -- Country where this name is used
   NameType    char(2) NOT NULL,        -- L(anguage), LA(lternate),
                                        -- D(ialect), DA(lternate)
   Name        varchar(75) NOT NULL );   -- The name

CREATE TABLE ChangeHistory (
   LangID      char(3) NOT NULL,        -- The code that has changed
   Date        datetime NOT NULL,  -- Date change was released
   Action      char(1) NOT NULL,        -- C(reated), E(xtended),
                                        -- U(pdated), R(etired)
   Description varchar(200) NOT NULL );  -- Description of change

