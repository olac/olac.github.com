#! /ldc/bin/python2.4

import MySQLdb
import datetime

today = str(datetime.date(1900,1,1).today())
base = "http://www.language-archives.org/archive_records/"
def url(archive):
    return """\
    <url>
        <loc>%s</loc>
        <lastmod>%s</lastmod>
    </url>""" % (base+archive,today)
    
con = MySQLdb.connect(read_default_file="/ldc/home/olac/.my.cnf")
cur = con.cursor()

cur.execute("select RepositoryIdentifier from OLAC_ARCHIVE")
print '<?xml version="1.0" encoding="UTF-8"?>'
print '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
for row in cur.fetchall():
    print url(row[0])
print '</urlset>'
