import os
import sys
from mod_python import apache
import MySQLdb
from Cheetah.Template import Template

def handler(req):
    # get repository id
    if req.path_info:
        args = req.path_info.split('/')
        if len(args) == 2:
            repo_id = args[1]
            download = False
        elif len(args) == 3:
            repo_id = args[1]
            if args[2] == 'download':
                download = True
            else:
                return apache.HTTP_FORBIDDEN
        else:
            return apache.HTTP_FORBIDDEN
    else:
        return displayPickPage(req)

    if not repo_id:
        return displayPickPage(req)

    # check if repository by the id exists
    con = MySQLdb.connect(read_default_file='/home/olac/.my.cnf')
    cur = con.cursor()
    sql = "select Archive_ID from OLAC_ARCHIVE where RepositoryIdentifier=%s"
    cur.execute(sql, repo_id)
    row = cur.fetchone()
    if row:
        archive_id = row[0]
    else:
        cur.close()
        con.close()
        return apache.HTTP_NOT_FOUND

    # print out the error report
    sql1 = """\
select
    Label, ic.Problem_Code, Severity, Value, ai.OaiIdentifier
from
    ARCHIVED_ITEM ai, METADATA_ELEM me,
    INTEGRITY_CHECK ic, INTEGRITY_PROBLEM ip
where
    ai.Archive_ID=%s and me.Item_ID=ai.Item_ID and
    ic.Problem_Code=ip.Problem_Code and
    ip.Applies_To='I' and ic.Object_ID=ai.Item_ID

union

select
    Label, ic.Problem_Code, Severity, Value, ai.OaiIdentifier
from
    ARCHIVED_ITEM ai, METADATA_ELEM me,
    INTEGRITY_CHECK ic, INTEGRITY_PROBLEM ip
where
    ai.Archive_ID=%s and me.Item_ID=ai.Item_ID and
    ic.Problem_Code=ip.Problem_Code and
    ip.Applies_To='E' and ic.Object_ID=me.Element_ID

order by Problem_Code
"""

    sql2 = """\
select
    Label, ic.Problem_Code, Severity, Value, Value
from
    INTEGRITY_CHECK ic, INTEGRITY_PROBLEM ip
where
    ic.Problem_Code=ip.Problem_Code and
    ip.Applies_To='A' and ic.Object_ID=%s
order by ic.Problem_Code
"""
    cur.execute(sql1, (archive_id,archive_id))
    tab1 = cur.fetchall()
    cur.execute(sql2, archive_id)
    tab2 = cur.fetchall()
    tab = tab1+tab2
    
    errors = []
    warnings = []
    for row in tab:
        severity = row[2]
        if severity == 'E':
            errors.append(row)
        elif severity == 'W':
            warnings.append(row)

    if download:
        return displayAsTsv(req, repo_id, errors, warnings)
    else:
        return displayAsHtml(req, repo_id, errors, warnings)


def get_base_dir(req):
    # compute the current directory (base)
    c = req.get_config()
    scriptname = c['PythonHandler'] + ".py"
    try:
        paths = eval(c['PythonPath'])
    except KeyError:
        paths = sys.path
    for d in paths:
        if os.path.exists(os.path.join(d, scriptname)):
            base = d
            break
    else:
        base = ''  # the program will crash later
    return base


def get_archives_list():
    con = MySQLdb.connect(read_default_file='/home/olac/.my.cnf')
    cur = con.cursor()
    sql = """select RepositoryIdentifier from OLAC_ARCHIVE
    order by RepositoryIdentifier"""
    cur.execute(sql)
    repoids = [row[0] for row in cur.fetchall()]
    cur.close()
    con.close()
    return repoids


def displayAsHtml(req, repo_id, errors, warnings):
    base = get_base_dir(req)
    repoids = get_archives_list()

    t = Template(file=os.path.join(base,"templates/integrity_checks.tmpl"))
    t.repoid = repo_id
    t.errors = errors
    t.warnings = warnings
    t.repoids = repoids
    t.baseurl = '/' + os.path.basename(req.filename)
    req.content_type = 'text/html'
    req.send_http_header()
    req.write(str(t))
    return apache.OK


def displayAsTsv(req, repo_id, errors, warnings):
    filename = 'olac_integrity_checks-%s.tsv' % repo_id
    req.content_type = 'text/tab-separated-values'
    req.headers_out['Content-Disposition'] = 'attachement; filename=%s' % filename
    req.send_http_header()
    for row in errors:
        req.write("\t".join(row[1:5]) + "\r\n")
    for row in warnings:
        req.write("\t".join(row[1:5]) + "\r\n")
    return apache.OK

def displayPickPage(req):
    base = get_base_dir(req)
    repoids = get_archives_list()
    
    # display the html page
    t = Template(file=os.path.join(base,"templates/integrity_checks.tmpl"))
    t.repoids = repoids
    t.baseurl = '/' + os.path.basename(req.filename)
    req.content_type = 'text/html'
    req.send_http_header()
    req.write(str(t))
    return apache.OK
