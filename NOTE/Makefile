# Build documents

XSLT = java org.apache.xalan.xslt.Process
OLAC_XSL = ../OLAC_doc.xsl
LATEST = ../document_latest.sh

### Documents in the OLAC Process

DISPLAY = olac_display-20020810.html olac_display-20030215.html olac_display-20060515.html\
olac_display-20090723.html
QUERY   = query-20021102.html query-20030729.html
VISER   = viser-20021102.html viser-20030729.html
THIRD   = third-party-extensions-20021204.html
TOOLBOX = toolbox-20060928.html
USAGE   = usage-20071115.html usage-20080229.html usage-20080531.html usage-20080711.html
METRICS = metrics-20071117.html metrics-20080207.html metrics-20080421.html\
metrics-20080711.html metrics-20081016.html metrics-20090218.html metrics-20090629.html

HTML = $(DISPLAY) $(QUERY) $(VISER) $(THIRD) $(TOOLBOX) $(USAGE) $(METRICS)


### Build

.SUFFIXES: .xml .html

.PHONY: all

all: $(HTML)
	$(LATEST) metrics                 20090629
	$(LATEST) olac_display            20090723
	$(LATEST) query                   20030729
	$(LATEST) third-party-extensions  20021204
	$(LATEST) toolbox                 20060928
	$(LATEST) usage                   20080711
	$(LATEST) viser                   20030729


.xml.html:
	$(XSLT) -in $< -xsl $(OLAC_XSL) > $@

