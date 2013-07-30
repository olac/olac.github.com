#! /bin/sh

doc=$1
date=$2

### Usage

if test -z "$doc" -o -z "$date" ; then
  cat <<END
usage: $0 <doc> <date>

  <doc>  the basename of the document (e.g. "metadata")
  <date> the date of the latest version (e.g. "20040201")

  Run this from the document directory
  (OLAC/, REC/, NOTE/)

END
  exit 1
fi

### Link latest doc

cp -f $doc-$date.xml $doc.xml
cp -f $doc-$date.html $doc.html
