SRC = olac-dynamic-repository.xml olac-static-repository.xml
XSL = schematron-basic.xsl

all: $(SRC:.xml=.xsl)

%.xsl: %.xml
	xsltproc $(XSL) $< > $@

.PHONY: clean

clean:
	rm -f $(SRC:.xml=.xsl)
