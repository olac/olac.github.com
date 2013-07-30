/**
*
* 	- Keyword Search of OLAC Records
*
*/

-----------
I. OVERVIEW
-----------

This package contains a search engine over OLAC records.
It requires the creation of additional tables in the OLAC database containing
record scores and metadata element usage. In addition, the search engine
requires tables containing Google search terms, soundex data and Ethnologue
data. The instructions for installation describe how to set up
the search engine.

----------------
II. REQUIREMENTS
----------------

The search engine uses MySQL and PHP technologies.
It is assumed that the OLAC Harvester and Aggregator (available from
olac.sf.net) are installed to harvest OLAC metadata into a MySQL database.

----------------
III. INSTALLATION
----------------

See INSTALL for details on installation.

---------
IV. USAGE
---------

The interface to searching the OLAC database is in 
search/search.php.
