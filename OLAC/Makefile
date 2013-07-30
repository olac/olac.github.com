# Build documents

XSLT = java org.apache.xalan.xslt.Process
OLAC_XSL = ../OLAC_doc.xsl
LATEST = ../document_latest.sh

### Documents in the OLAC Process

METADATA = metadata-20021211.html metadata-20030531.html metadata-20031208.html metadata-20060405.html\
        metadata-20080531.html

PROCESS = process-20010507.html process-20010521.html process-20021209.html process-20021210.html\
        process-20030625.html process-20030708.html process-20060405.html

REPOSITORIES = repositories-20021211.html repositories-20030528.html repositories-20030716.html\
repositories-20030917.html repositories-20080531.html repositories-20080728.html

OLACMS = olacms-20010221.html olacms-20010406.html olacms-20010425.html olacms-20010616.html\
        olacms-20011022.html

PROTOCOL = protocol-20011210.html protocol-20021209.html

HTML = $(METADATA) $(PROCESS) $(REPOSITORIES) $(OLACMS) $(PROTOCOL)


### Build

.SUFFIXES: .xml .html

.PHONY: all

all: $(HTML)
	$(LATEST) metadata      20080531
	$(LATEST) process       20060405
	$(LATEST) repositories  20080728
	$(LATEST) olacms        20011022
	$(LATEST) protocol      20021209

.xml.html:
	$(XSLT) -in $< -xsl $(OLAC_XSL) > $@

