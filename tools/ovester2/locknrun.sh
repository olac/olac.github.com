#! /bin/sh

THISDIR=`dirname $0`
if [ -e /usr/bin/lockf -o -e /bin/lockf -o -e /usr/local/bin/lockf ] ; then
	LOCKFILE=$THISDIR/run.sh.lock
	lockf -k $LOCKFILE $THISDIR/run.sh
else
	echo "This message is to inform that a harvester process has run without locking."
	echo "If another harvester process has been running concurrently, the database"
	echo "may have been corrupted."
	echo
	echo "[`date`] Locking failed-- running harvester without locking..."
	echo
	exec $THISDIR/run.sh
	echo "[`date`] Harvesting process finished."
	echo
fi


