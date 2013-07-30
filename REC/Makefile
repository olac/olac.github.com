# Build documents

XSLT = java org.apache.xalan.xslt.Process
OLAC_XSL = ../OLAC_doc.xsl
LATEST = ../document_latest.sh

### Documents in the OLAC Process

ACCESS     = access-20021119.html
BPR        = bpr-20071115.html bpr-20080229.html bpr-20080711.html
DISCOURSE  = discourse-20021121.html discourse-20021217.html discourse-20030127.html discourse-20060406.html discourse-20120204.html
EXTENSIONS = olac-extensions-20021130.html olac-extensions-20080222.html
FIELD      = field-20021127.html field-20030121.html field-20060406.html
LANGUAGE   = language-20010319.html language-20031213.html language-20071114.html language-20080222.html
ROLE       = role-20021119.html role-20030827.html role-20031010.html role-20060406.html
TYPE       = type-20010320.html type-20020612.html type-20020628.html type-20021121.html type-20021212.html type-20060406.html


HTML = $(ACCESS) $(BPR) $(DISCOURSE) $(EXTENSIONS) $(FIELD) $(LANGUAGE) $(ROLE) $(TYPE)

### Build

.SUFFIXES: .xml .html

.PHONY: all

all: $(HTML)
	$(LATEST) access     20021119
	$(LATEST) bpr        20080711
	$(LATEST) discourse  20120204
	$(LATEST) field      20060406
	$(LATEST) language   20080222
	$(LATEST) olac-extensions       20080222
	$(LATEST) role       20060406
	$(LATEST) type       20060406

.xml.html:
	$(XSLT) -in $< -xsl $(OLAC_XSL) > $@

