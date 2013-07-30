#
# CHANGES 2003-02-25 HL:
#
# METADATA_ELEM.Type varchar(30) --> varchar(20)
# EXTENSION.Extension_ID int not null --> int auto_increment not null
# added EXTENSION.Label
# METADATA_ELEM.Extension_ID=0 by default
#   and METADATA_ELEM.Code='' by default
#   meaning no extension and no code
#
# CHANGES 2003-02-21 HL:
#
# in ELEMENT_DEFN.TagName
#   Requires -> requires
#   Replaces -> replaces
# METADATA_ELEM.Type added
#
# CHANGES 2003-02-19 HL:  based on olac2_schema-20030120.sql
#
# OLAC_EXTENSION --> EXTENSION  (<olac-extension> is harvested)
#
# CHANGES 2003-01-20 SB:
#
# OLAC_ARCHIVE:shortLocation - added
# OLAC_ARCHIVE:curator - not null
# OLAC_ARCHIVE:institution - not null
# SCHEMA_VERSION - only version 1.0 supported
# METADATA_ELEM - Refine/Scheme -> Extension_ID
# ELEMENT_DEFN - removed all refined DC elements
# ELEMENT_DEFN:RefineVocab - deleted
# ELEMENT_DEFN:CodeVocab - deleted
# OLAC_VOCAB -> OLAC_EXTENSION
# VOCAB_TERM - deleted
#
# CHANGES 2003-02-05 SB:
#
# consistent capitalization of field names
# OLAC_EXTENSION - names changed, e.g. OLAC-Role -> olac:role
# INVERSE_DEFN - deleted
# ELEMENT_DEFN:Tag_ID - int -> smallint
# ELEMENT_DEFN:DcElement - added
# ELEMENT_DEFN:InverseElement - added
# ELEMENT_DEFN - insert statements modified with new fields
# ELEMENT_DEFN - added qualified DC elements
# CODE_DEFN - created (was VOCAB_TERM)

##################################################################
# Table               : OLAC_ARCHIVE
# Description         : 
# Archive_ID          : 
# RepositoryName      : Human readable name of the archive 
# RepositoryIdentifier : The unique identifier for the archive used in oai identifiers 
# BaseURL             : The base URL of the data provider 
# OaiVersion          : The version of the OAI protocol that is supported 
# FirstHarvested      : Date of first successful harvest 
# LastHarvested       : Date of last successful harvest 
# Curator
# CuratorTitle
# CuratorEmail
# Institution
# InstitutionURL
# ShortLocation
# Location
# Synopsis
# Access
# Copyright
##################################################################

create table OLAC_ARCHIVE (
	Archive_ID		int auto_increment not null,
	ArchiveURL		varchar(255),
	AdminEmail		varchar(255),
	Curator			varchar(255) not null,
	CuratorTitle		varchar(255),
	CuratorEmail		varchar(255),
	Institution		varchar(255) not null,
	InstitutionURL		varchar(255),
	ShortLocation		varchar(50) not null,
	Location		text,
	Synopsis		text,
	Access			text,
	Copyright		varchar(255),
	RepositoryName		varchar(255) not null,
	RepositoryIdentifier	varchar(50) not null,
	BaseURL			varchar(255) not null,
	OaiVersion		varchar(10) not null,
	FirstHarvested		date,
	LastHarvested		date,
	primary key (Archive_ID));
 

##################################################################
# Table               : SCHEMA_VERSION
# Description         : 
# Schema_ID           : 
# Xmlns               : The xmlns for the schema version 
# SchemaURL           : The URL for the schema version 
#
##################################################################


create table SCHEMA_VERSION (
	Schema_ID		int auto_increment primary key,
	SchemaName		varchar(10),
	Xmlns			varchar(255) not null,
	SchemaURL		varchar(255) not null
	);

insert into SCHEMA_VERSION values ('',
  '1.0',
  'http://www.language-archives.org/OLAC/1.0/',
  'http://www.language-archives.org/OLAC/1.0/olac.xsd');


##################################################################
# Table               : ARCHIVED_ITEM
# Description         : 
# Item_ID             : 
# OaiIdentifier       : The OAI identifier for the item 
# DateStamp           : The datestamp from the header of the harvested record 
# Archive_ID          : (Foreign Key)
# Schema_ID           : (Foreign Key)
##################################################################

create table ARCHIVED_ITEM (
	Item_ID			int auto_increment not null,
	OaiIdentifier		varchar(255) not null,
	DateStamp		date not null,
	Archive_ID		int,
	Schema_ID		int,
	primary key (Item_ID),
	foreign key (Archive_ID) references OLAC_ARCHIVE (Archive_ID)
        on delete set null
        on update cascade,
	foreign key (Schema_ID) references SCHEMA_VERSION (Schema_ID)
        on delete set null
        on update cascade);
 
create index ARCHIVED_ITEM_INDEX on ARCHIVED_ITEM (Archive_ID, Schema_ID);


##################################################################
# Table               : ELEMENT_DEFN
# Description         : 
# Tag_ID              : 
# TagName             : The generic identifier for the tag 
# Label               : A presentation label for the element 
# Rank                : A number that encodes the relative order of presentation (lowest first) 
# Vocab_ID            : (Foreign Key)
# Vocab_ID1           : (Foreign Key)
#
##################################################################

create table ELEMENT_DEFN (
	Tag_ID			smallint not null,
        DcElement               smallint not null,
        InverseElement          smallint not null,
	Rank			smallint not null,
	TagName			varchar(255) not null,
	Label			varchar(255) not null,
	primary key (Tag_ID));
 

insert into ELEMENT_DEFN values ( 100, 100,   0, 100, 'contributor', 'Contributor');
insert into ELEMENT_DEFN values ( 200, 200,   0, 200, 'coverage',    'Coverage');
insert into ELEMENT_DEFN values ( 300, 300,   0, 300, 'creator',     'Creator');
insert into ELEMENT_DEFN values ( 400, 400,   0, 400, 'date',        'Date');
insert into ELEMENT_DEFN values ( 500, 500,   0, 500, 'description', 'Description');
insert into ELEMENT_DEFN values ( 600, 600,   0, 600, 'format',      'Format');
insert into ELEMENT_DEFN values ( 700, 700,   0, 700, 'identifier',  'Identifier');
insert into ELEMENT_DEFN values ( 800, 800,   0, 800, 'language',    'Language');
insert into ELEMENT_DEFN values ( 900, 900,   0, 900, 'publisher',   'Publisher');
insert into ELEMENT_DEFN values (1000,1000,1000,1000, 'relation',    'Relation');
insert into ELEMENT_DEFN values (1100,1100,   0,1100, 'rights',      'Rights');
insert into ELEMENT_DEFN values (1200,1200,   0,1200, 'source',      'Source');
insert into ELEMENT_DEFN values (1300,1300,   0,1300, 'subject',     'Subject');
insert into ELEMENT_DEFN values (1400,1400,   0,   0, 'title',       'Title');
insert into ELEMENT_DEFN values (1500,1500,   0,1500, 'type',        'Type');

insert into ELEMENT_DEFN values (1401,1400,   0,  1,  'alternative',     'Alternative Title');
insert into ELEMENT_DEFN values ( 501, 500,   0, 501, 'tableOfContents', 'Table Of Contents');
insert into ELEMENT_DEFN values ( 502, 500,   0, 502, 'abstract',        'Abstract');
insert into ELEMENT_DEFN values ( 401, 400,   0, 401, 'created',         'Created');
insert into ELEMENT_DEFN values ( 402, 400,   0, 402, 'valid',           'Valid');
insert into ELEMENT_DEFN values ( 403, 400,   0, 403, 'available',       'Available');
insert into ELEMENT_DEFN values ( 404, 400,   0, 404, 'issued',          'Issued');
insert into ELEMENT_DEFN values ( 405, 400,   0, 405, 'modified',        'Modified');
insert into ELEMENT_DEFN values ( 406, 400,   0, 406, 'dateAccepted',    'Date Accepted');
insert into ELEMENT_DEFN values ( 407, 400,   0, 407, 'dateCopyrighted', 'Date Copyrighted');
insert into ELEMENT_DEFN values ( 408, 400,   0, 408, 'dateSubmitted',   'Date Submitted');
insert into ELEMENT_DEFN values ( 601, 600,   0, 601, 'extent',          'Extent');
insert into ELEMENT_DEFN values ( 602, 600,   0, 602, 'medium',          'Medium');
insert into ELEMENT_DEFN values (1001,1000,1002,1001, 'isVersionOf',     'Is Version Of');
insert into ELEMENT_DEFN values (1002,1000,1001,1002, 'hasVersion',      'Has Version');
insert into ELEMENT_DEFN values (1003,1000,1004,1003, 'isReplacedBy',    'Is Replaced By');
insert into ELEMENT_DEFN values (1004,1000,1003,1004, 'replaces',        'Replaces');
insert into ELEMENT_DEFN values (1005,1000,1006,1005, 'isRequiredBy',    'Is Required By');
insert into ELEMENT_DEFN values (1006,1000,1005,1006, 'requires',        'Requires');
insert into ELEMENT_DEFN values (1007,1000,1008,1007, 'isPartOf',        'Is Part Of');
insert into ELEMENT_DEFN values (1008,1000,1007,1008, 'hasPart',         'Has Part');
insert into ELEMENT_DEFN values (1009,1000,1010,1009, 'isReferencedBy',  'Is Referenced By');
insert into ELEMENT_DEFN values (1010,1000,1009,1010, 'references',      'References');
insert into ELEMENT_DEFN values (1011,1000,1012,1011, 'isFormatOf',      'Is Format Of');
insert into ELEMENT_DEFN values (1012,1000,1011,1012, 'hasFormat',       'Has Format');
insert into ELEMENT_DEFN values (1013,1000,1000,1013, 'conformsTo',      'Conforms To');
insert into ELEMENT_DEFN values ( 201, 200,   0, 201, 'spatial',         'Spatial Coverage');
insert into ELEMENT_DEFN values ( 202, 200,   0, 202, 'temporal',        'Temporal Coverage');


##################################################################
# Table               : METADATA_ELEM
# Description         : 
# Element_ID          : 
# TagName             : The tag for the metadata element 
# Lang                : The value of the lang attribute 
# Type                : The type of extension (value of <shortName>)
# Code                : The value of the code attribute 
# Content             : The content of the metadata element 
#				MEDIUMTEXT: max 16777215 (2^24 - 1) characters. 
#				LONGTEXT: max 4294967295 (2^32 - 1) characters.
#
# Extension_ID        : (Foreign Key)
# Item_ID             : (Foreign Key)
# Tag_ID              : (Foreign Key)
##################################################################


create table METADATA_ELEM (
    Element_ID      int auto_increment not null,
    TagName         varchar(255) not null,
    Lang            varchar(255),
    Content         text,
    Extension_ID    int default 0,
    Type            varchar(20),
    Code            varchar(255) default '',
    Item_ID         int,
    Tag_ID          int,
	primary key (Element_ID),
	foreign key (Extension_ID) references EXTENSION (Extension_ID)
        on delete set null 
        on update cascade,
	foreign key (Item_ID) references ARCHIVED_ITEM (Item_ID)
        on delete set null
        on update cascade,
	foreign key (Tag_ID) references ELEMENT_DEFN (Tag_ID)
        on delete set null
        on update cascade);

create index METADATA_ELEM_INDEX on METADATA_ELEM (Item_ID, Tag_ID);


##################################################################
# Table               : OLAC_EXTENSION
# Description         : 
# Extension_ID        : 
# ShortName           : The name of the controlled vocabulary 
# LongName            : The long of the controlled vocabulary 
# VersionDate         : The version date
# Description         : A prose description of the vocabulary
# AppliesTo           : A list of DC Elements this extension applies to
# Documentation       : URL for the documentation
# SchemaURL           : The location of the XML Schema for the vocabulary
# 
# This table will be populated by reading the <extension> elements
# from http://www.language-archives.org/REC/olac-extensions.xml
# to discover the schema location, then reading the schema and
# parsing the <olac-extension> element.
#
# !!! PROBLEM - appliesTo is a repeatable XML element
#
##################################################################

#create table OLAC_EXTENSION (
#	Extension_ID		int not null,
#	ShortName		varchar(20) not null,
#	LongName		varchar(50) not null,
#        VersionDate             date not null,
#        Description             varchar(255) not null,
#        AppliesTo               varchar(255) not null,
#        Documentation           varchar(255) not null,
#	SchemaURL		varchar(255) not null,
#	primary key (Extension_ID));


##################################################################
# Table               : CODE_DEFN
# Description         : 
# Code                : The coded value for a term 
# Label               : A presentation form of the term 
# Vocab_ID            : (Foreign Key)
#
# This table will be populated by reading the schema for the OLAC
# vocabulary
#
# !!! PROBLEM - the schema doesn't give us labels for the codes
#     Solution 1. Label <- Code
#
##################################################################


create table CODE_DEFN (
	Extension_ID    int not null,
	Code            varchar(255) not null,
	Label           varchar(255),
        primary key (Extension_ID, Code),
	foreign key (Extension_ID) references OLAC_EXTENSION (Extension_ID)
        on delete set null
        on update cascade);

insert into CODE_DEFN (Extension_ID, Code, Label) values (0, '', '');

##################################################################
# Table               : EXTENSION
# Description         : 
#
# Extension_ID        : 
# Type                : The name of the controlled vocabulary
#                       (<olac-extension><shortName>)
# DefiningSchema      : The location of the XML Schema for the vocabulary
# NS                  : The XML namespace for this vocabulary
# NSPresix            : A namespace prefix for the namespace
# NSSchema            : XML Schema URL for the namespace
# Label               : A label for Type
# LongName            : The long of the controlled vocabulary 
# VersionDate         : The version date
# Description         : A prose description of the vocabulary
# AppliesTo           : A list of DC Elements this extension applies to
# Documentation       : URL for the documentation
# 
# This table will be populated by reading the <extension> elements
# from http://www.language-archives.org/REC/olac-extensions.xml
# to discover the schema location, then reading the schema and
# parsing the <olac-extension> element.
#
# !!! PROBLEM - appliesTo is a repeatable XML element
#     SOLUTION 1. use comma-separated string
#
# !!! PROBLEM - the schema doesn't provide labels for Type
#     SOLUTION 1. Label <- Type
#
##################################################################

create table EXTENSION (
	Extension_ID		int auto_increment not null,

	Type			varchar(20) not null,
	DefiningSchema		varchar(255),
	NS			varchar(255) not null,
	NSPrefix		varchar(20),
	NSSchema		varchar(255),

	Label			varchar(50),
	LongName		varchar(50),
        VersionDate             date,
        Description             varchar(255),
        AppliesTo               varchar(255),
        Documentation           varchar(255),

	primary key (Extension_ID)
);

insert into EXTENSION (Type,NS) values ('','');
update EXTENSION set Extension_ID=0;

