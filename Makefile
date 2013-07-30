# OLAC Makefile

#WEB = sb@login.ldc.upenn.edu:language-archives
WEB = olac.ldc.upenn.edu:/olac/www
INDEXES = document_list.xml document_headers.xsl document_index.xsl documents_by_status.xsl documents_by_date.xsl

RSYNC_OPTS = -alrvz -e ssh -C --relative --no-implied-dirs --include-from=publish.txt 
XSLT = java org.apache.xalan.xslt.Process

.PHONY: standards recommendations notes indexes rsync

all: standards recommendations notes indexes

standards:
	$(MAKE) -C OLAC all

recommendations:
	$(MAKE) -C REC all

notes:
	$(MAKE) -C NOTE all

indexes: $(INDEXES)
#	$(XSLT) -in document_list.xml -xsl document_headers.xsl > document_headers.xml
	$(XSLT) -in document_list.xml -xsl document_index.xsl > documents.html
	$(XSLT) -in document_list.xml -xsl documents_by_status.xsl > documents_by_status.html
	$(XSLT) -in document_list.xml -xsl documents_by_date.xsl > documents_by_date.html

rsync_docs:
	rsync $(RSYNC_OPTS) OLAC REC NOTE wg *.html *.xsl $(WEB)

rsync_top:
	rsync $(RSYNC_OPTS) *.html $(WEB)

