from mod_python import apache
import MySQLdb

def handler(req):
    con = MySQLdb.connect(read_default_file='/home/olac/.my.cnf')
    cur = con.cursor()

    req.content_type = "text/xml"
    req.send_http_header()
    req.write(
        '<?xml version="1.0" encoding="UTF-8"?>'
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')

    cur.execute("select RepositoryIdentifier, max(DateStamp) from OLAC_ARCHIVE oa, ARCHIVED_ITEM ai where oa.Archive_ID=ai.Archive_ID group by oa.Archive_ID")
    for row in cur.fetchall():
        repoid, date = row
        if date is None: date='2008-03-08'
        req.write("<url><loc>http://www.language-archives.org/archive_records/%s</loc><lastmod>%s</lastmod></url>" % (repoid, date))

    cur.execute("select distinct Code, max(DateStamp) from (METADATA_ELEM me, ISO_639_3, EXTENSION ex) left join ARCHIVED_ITEM ai on me.Item_ID=ai.Item_ID where ex.Type='language' and ex.Extension_ID=me.Extension_ID and Code=Id group by Id")
    for row in cur.fetchall():
        langid, date = row
        req.write("<url><loc>http://www.language-archives.org/language/%s</loc><lastmod>%s</lastmod></url>" % (langid, date))

    cur.execute("select OaiIdentifier, DateStamp from ARCHIVED_ITEM where DateStamp >= subdate(now(), interval 35 day)")
    for row in cur.fetchall():
        oaiid, date = row
        req.write("<url><loc>http://www.language-archives.org/item/%s</loc><lastmod>%s</lastmod></url>" % (oaiid, date))

    req.write("</urlset>")
    
    cur.close()
    con.close()
    return apache.OK
