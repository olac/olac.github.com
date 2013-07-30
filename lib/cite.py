#! /ldc/bin/python2.4 -u

import sys
import re
import MySQLdb

def normalize1(s):
    if s is None: return ""
    return re.sub(r"\s+", " ", s.strip())

def find(data, **kw):
    for row in data:
        for k,v in kw.items():
            if row[k] != v:
                break
        else:
            yield row
            
def get_authors(data):
    L = []
    for row in find(data,TagName='creator'):
        s = normalize1(row['Content'])
        if s: L.append(s)
    if len(L) == 0:
        for row in find(data,TagName='contributor',Code='author'):
            s = normalize1(row['Content'])
            if s: L.append(s)
    if len(L) == 0:
        for row in find(data,TagName='contributor'):
            role = normalize1(row['Code'])
            s = normalize1(row['Content'])
            if s:
                if role:
                    L.append("%s (%s)" % (s, role))
                else:
                    L.append(s)
    if len(L) == 0:
        return 'n.a.'
    else:
        return '; '.join(L)

def get_date(data):
    for tag in ('date', 'issued', 'dateCopyrighted', 'created','available',
                'dateAccepted', 'dateSubmitted', 'modified', 'valid'):
        try:
            row = find(data,TagName=tag).next()
            if row['Type'] == 'W3CDTF':
                return row['Content'].strip().split('-')[0]
            else:
                return row['Content'].strip()
        except StopIteration:
            pass
    return 'n.d.'

def get_publisher(data):
    for row in find(data,TagName='isPartOf'):
        if row['Type'] != 'URI':
            return row['Content'].strip()

    for row in find(data,TagName='publisher'):
        return row['Content'].strip()

def get_citation(cur, oaiid):
    sql = """
    select * from ARCHIVED_ITEM ai, METADATA_ELEM me
    where ai.OaiIdentifier=%s and ai.Item_ID=me.Item_ID
    """
    cur.execute(sql, oaiid)
    if cur.rowcount == 0: return

    data = cur.fetchall()

    authors = get_authors(data)
    date = get_date(data)
    publisher = get_publisher(data)

    if publisher is None:
        sql = """
        select * from OLAC_ARCHIVE oa, ARCHIVED_ITEM ai
        where ai.OaiIdentifier=%s and ai.Archive_ID=oa.Archive_ID
        """
        cur.execute(sql, oaiid)
        publisher = cur.fetchone()['RepositoryName']

    if authors[-1] == '.':
        s = authors + ' '
    else:
        s = authors + '. '
    if date[-1] == '.':
        s += date + ' '
    else:
        s += date + '. '
    if publisher[-1] == '.':
        s += publisher
    else:
        s += publisher + '.'

    return s.encode('utf-8')

if __name__ == "__main__":
    con = MySQLdb.connect(read_default_file="/home/olac/.my.cnf", db="olac2",
                          use_unicode=True, charset='utf8')
    cur = con.cursor(MySQLdb.cursors.DictCursor)

    line = sys.stdin.readline()
    while line:
        oaiid = line.strip()
        s = get_citation(cur, oaiid)
        if s:
            print s
        else:
            print
        line = sys.stdin.readline()

    cur.close()
    con.close()
    

