#! /bin/sh


#######################################
#       configuration variables       #
# (edit them to fit your environment) #
#######################################

# harvest log
HARVEST_LOG=harvest_log.txt

# mysql db account info file
DBINFO=/home/olac/.dbinfo_olac2

# ovester directory
ODIR=/ldc/web/language-archives/tools/ovester2

# perl module location
PERL5LIB=/ldc/web/language-archives/tools/ovester2

# which perl to use
PERL=/ldc/bin/perl
LD_LIBRARY_PATH=/ldc/lib
export LD_LIBRARY_PATH

# admin email address
#ADMIN_EMAIL="olac-admin@language-archives.org haejoong@ldc.upenn.edu"
ADMIN_EMAIL="sb@csse.unimelb.edu.au Gary_Simons@sil.org haejoong@ldc.upenn.edu"

# which php?
PHP=/pkg/ldc/freebsd/pkg/php-5.0.3/bin/php

# xml dump directory
XMLDUMPDIR=$ODIR

# static record pages directory
SRECDIR=/ldc/web/language-archives/static-records

#########################
# DO NOT EDIT FROM HERE #
#########################

TMP_LOG=${HARVEST_LOG}-tmp
OVESTER="$PERL -I $PERL5LIB $ODIR/ovester.pl -pd $DBINFO"
CWD=`pwd`; cd $ODIR

( echo; echo; echo "** `date`"; echo ) | \
	/usr/bin/tee $TMP_LOG >> $HARVEST_LOG

( $OVESTER 2>&1 ;  $PERL -I $PERL5LIB $ODIR/cleanup.pl $DBINFO ) | \
	/usr/bin/tee -a $TMP_LOG >> $HARVEST_LOG

new_records=`grep "new record(s)" $TMP_LOG | awk '{sum+=$1} END {print sum}'`
if [ ${new_records:-0} -gt 0 ] ; then
    (
        (
        echo
	echo "Updating search database..."
	echo
        cd ../../tools/reports/lib
	echo "  - Updating tag usage table..."
        $PHP createTagUsageTable.php
	echo "  - Updating soundex table..."
	$PHP createSoundexTable.php
	echo "  - Updating item scores table..."
	$PHP createItemScoresTable.php
	cd ..
	echo "  - Generating report..."
	$PHP generateReports.php
	)

        echo
        echo "Creating an XML dump and static record pages..."
        echo
        dumpnam=$XMLDUMPDIR/ListRecords-`date +%Y%m%d-%H%M%S`.xml.gz
	PATH=/ldc/app/i386/pkg/sqlite-3.3.5/bin:/ldc/app/i386/pkg/mysql-5.0.22/bin:$PATH ./olacaxmldump.py /home/olac/.my.cnf.olac2 $dumpnam $SRECDIR
        ln -sf $dumpnam $XMLDUMPDIR/ListRecords.xml.gz
	(
	cd $SRECDIR
	find . -name "*.xml" | sort | sed -E -e 's@^./(.*)@<li><a href="./\1">\1</a>@' > index.html
	)
	
	/ldc/bin/python2.4 /web/language-archives/devel/olac07/compute_olac_metrics.py

    ) | /usr/bin/tee -a $TMP_LOG >> $HARVEST_LOG
fi


######## BEGIN: ISO 630 report generation ########
(
	echo
	echo "Generating ISO 639 conversion report..."
	for u in `/usr/local/bin/curl "http://www.language-archives.org/register/archive_list.php4" 2> /dev/null | grep baseURL |sed -E -e 's/.*baseURL="//' -e 's/".*//'` ; do
		echo $u
		u2=`echo $u | sed -E -e 's#http://#http://www.language-archives.org/tools/eth15filter/gen_report.php/#' -e 's#$#?verb=ListRecords\&metadataPrefix=olac#'`
		/usr/local/bin/curl "$u2" > /dev/null 2>&1
	done
	echo
) | /usr/bin/tee -a $TMP_LOG >> $HARVEST_LOG
######## END: ISO 630 report generation ########


cat $TMP_LOG | /usr/bin/mail -s "OLAC Harvester Log: `date +\"%b %e, %Y\"`" $ADMIN_EMAIL

rm -f $TMP_LOG

cd $CWD

