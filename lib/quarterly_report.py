#! /ldc/bin/python2.4

import MySQLdb
import datetime
import re
import smtplib
import math

to_19 = ["zero", "one", "two", "three", "four", "five", "six", "seven",
         "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen",
         "fifteen", "sixteen", "seventeen", "eighteen", "nineteen"]
tens = ["twenty", "thirty", "forty", "fifty", "sixty", "seventy", "eighty",
        "ninety"]

def to_written_form(n):
    n = int(round(n))
    if n >= 1000: return str(n)
    if n < 20: return to_19[n]
    d1 = n % 10
    d2 = (n - 20) / 10
    if d1 == 0:
        return tens[d2]
    else:
        return tens[d2] + " " + to_19[d1]

template = """\
Dear OLAC participant,

You are receiving this email by virtue of your association with the following archive that participates in OLAC:

%(nameOfArchive)s

(If you do not wish to receive these emails, please notify your OLAC system administrator, %(adminEmail)s.)


USAGE
These are statistics on the usage of your metadata records by visitors to the OLAC site, for the period %(usagePeriodBeg)s to %(usagePeriodEnd)s.

* %(hits)d page view%(pluralHits)s - records viewed on OLAC site
* %(clicks)d click-through%(pluralClicks)s - subsequent visits to your site


QUALITY METRICS
OLAC tracks two metrics as primary indicators of overall metadata quality in its aggregated catalog: number of archives with fresh catalogs (that is, updated within the last 12 months) and number of archives with five-star metadata (that is, fully conforming to best practice as agreed on by the community and without known data integrity problems). See http://www.language-archives.org/metrics for the current values of the metrics and a link to the document that explains them. The currency and quality scores for your archive are:

Last Updated: %(lastUpdated)s
Average Metadata Quality: %(score).2f (on a 10 point scale)
Overall Rating: %(starScore)d-star%(pluralStar)s %(starDeduction)s

%(feedbackOnUpdate)s


INTEGRITY PROBLEMS
%(feedbackOnIntegrity)s


COLLECTION METRICS
The following list reports the current metrics for the size, coverage, and cataloging of your collection. The parenthetical note reports the rank of your repository in comparison to all participants (where 1 is highest and %(numberOfArchives)d is lowest):

%(metricsTable)s

For the full table of comparative archive metrics see:
http://www.language-archives.org/metrics/compare


ARCHIVE DESCRIPTION
Please review your archive description at the following URL to ensure that all of the information you are supplying is up to date. Contact your OLAC system administrator (%(adminEmail)s) if you spot anything that should be changed:

http://www.language-archives.org/archive/%(archiveId)s

Thank you for your participation.

Best wishes,
Steven & Gary

___
Steven Bird, University of Melbourne and University of Pennsylvania
Gary Simons, SIL International and GIAL
OLAC Coordinators (www.language-archives.org)
"""


metricsTemplate = """\
* %d Total Resources (Rank %d)
* %d Resources Online (Rank %d)
* %d Distinct Languages (Rank %d)
* %d Distinct Linguistic Subfields (Rank %d)
* %d Distinct Linguistic Types (Rank %d)
* %d Distinct DCMI Types (Rank %d)
* %.1f Average Elements Per Record (Rank %d)
* %.1f Averate Encoding Schemes Per Record (Rank %d)"""

def generateMetricsTable(metrics, archiveId):
    """
    @param metrics: metrics table
    @param archiveId: sring archive id
    """
    row = metrics.findRow("RepositoryIdentifier", archiveId)
    fields = (
        "num_resources",
        "num_online_resources",
        "num_languages",
        "num_linguistic_fields",
        "num_linguistic_types",
        "num_dcmi_types",
        "avg_num_elements",
        "avg_xsi_types"
        )
    params = []
    for i in range(len(fields)):
        f = fields[i]
        v = row[f]
        if v is None: v=0
        params.append(v)
        params.append(metrics.rank(f,v))
    return metricsTemplate % tuple(params)

def determineFeedbackOnUpdate(lastUpdated, score, archiveId):
    """
    @param lastUpdated: yyyy-mm-dd
    @param score: ex) 0.74
    """
    feedback = []
    date = lastUpdated
    if date is None:
        d = None
    else:
        d = (date.today() - date).days / 365.25 * 12.0
    
    if d is not None and d <= 12.0 and score >= 9.0:
        feedback.append("These metrics indicate that your archive is an exemplary member of the community. Congratulations!")
    if d is not None and d > 12.0 and score >= 9.0:
        feedback.append("The quality of your metadata is exemplary. Congratulations!")
    if score < 7.0:
        feedback.append("Your average metadata quality could be improved. Doing so will improve the quality of search that can be offered for your records.")
    if score >= 7.0 and score < 9.0:
        feedback.append("Your average metadata quality is good, but could still be improved.")
    if score < 9.0:
        feedback.append("See the metadata quality analysis of your sample record at the following URL for ideas on what could be done to improve the quality of your metadata.\n\nhttp://www.language-archives.org/sample/%s" % archiveId)
    if d is not None and d > 12.0:
        feedback.append("Note that it is more than one year since your metadata repository was last updated. Even if your collection is static, you should verify the details in your <olac-archive> description and update the currentAsOf date. Please do so at your earliest convenience.")
    if d is not None and d <= 12.0 and d > 9.0:
        feedback.append("Note that it will soon be one year since your metadata repository was last updated; please update it by %s." % (lastUpdated+datetime.timedelta(356.25)))
    if d is None:
        feedback.append("We couldn't determine the last updated date of your archive. Please verify the details in your <olac-archive> description and update the currentAsOf date.")

    return "\n\n".join(feedback)

def determineFeedbackOnIntegrity(integrity_value, archiveId, deduction):
    """
    @param integrity_value: p.w where p is the number of errors and 0 <= w <= 2.
      If w > 0, there are warning(s).
    @param archiveId: sring archive id
    @param deduction: number of star deductions
    """
    feedback = []
    p = int(integrity_value)
    w = int((integrity_value % 1) * 10)
    if p == 0:
        if w == 0:
            return "Congratulations, there are no known integrity problems in your metadata."
        else:
            return "Automated checking has detected some potential problems in your metadata. They are not severe enough to count against your overall quality rating. Nevertheless, you are advised to visit the following link to see a listing of the potential problems:\n\nhttp://www.language-archives.org/checks/%s" % archiveId
    else:
        msg = "Automated checking has detected problems in your metadata such as broken links or invalid vocabulary terms. Visit the following link to see a listing of the problems that need to be fixed:\n\nhttp://www.language-archives.org/checks/%s" % archiveId
        if deduction > 0:
            plural = 's'
            if deduction == 1: plural = ''
            msg += "\n\nThe presence of these problems is causing %s star%s to be subtracted from your overall quality rating. The rating will improve when you correct these problems." % (to_written_form(deduction), plural)
        return msg

def normalizeEmailAddress(s):
    s = re.sub(r"^mailto:", '', s)
    s = re.sub(r"[,; ].*", '', s)
    s = s.strip()
    return s

def composeEmail(archiveId, metrics, usageh, usagec):
    """
    @param metrics: metrics table
    @param archiveId: string archiveId
    """
    row = metrics.findRow("RepositoryIdentifier", archiveId)

    lastUpdated = row["last_updated"]
    score = row["metadata_quality"]
    orgStarScore = round(score/2.0)
    errors = int(row['integrity_problems'])
    records = row['num_resources']
    deduction = 0.0
    if records > 0:
        deduction = math.sqrt(float(errors) / records)
    starScore = max(round(score / 2.0 - deduction), 0.0)
    # integrity_problems is a float number in the database, say p.w where
    # p is the number of errors and w, if positive, means existence of warnings
    starDeduction = ""
    if orgStarScore > starScore:
        s = to_written_form(orgStarScore-starScore).replace(' ','-')
        starDeduction = "(with %s-star deducted for integrity problems)" % s
    pluralStar = 's'
    if starScore == 1: pluralStar=''

    rowHits = usageh.findRow("repoid", archiveId)
    rowClicks = usagec.findRow("repoid", archiveId)
    if rowHits:
        if rowClicks:
            usagePeriodBeg = min(rowHits["start_date"], rowClicks["start_date"])
            usagePeriodEnd = max(rowHits["end_date"], rowClicks["end_date"])
        else:
            usagePeriodBeg = rowHits["start_date"]
            usagePeriodEnd = rowHits["end_date"]
    else:
        if rowClicks:
            usagePeriodBeg = rowClicks["start_date"]
            usagePeriodEnd = rowClicks["end_date"]
        else:
            usagePeriodBeg = 'unknown'
            usagePeriodEnd = 'unknown'

    if rowHits:
        hits = rowHits["pageviews"]
    else:
        hits = 0
    if rowClicks:
        clicks = rowClicks["pageviews"]
    else:
        clicks = 0
    pluralHits = "s"
    pluralClicks = "s"
    if hits == 1: pluralHits=""
    if clicks == 1: pluralClicks=""
    
    params = {
        "archiveId": archiveId,
        "nameOfArchive": row["RepositoryName"],
        "lastUpdated": lastUpdated,
        "score": score,
        "starScore": starScore,
        "starDeduction": starDeduction,
        "pluralStar": pluralStar,
        "numberOfArchives": metrics.size(),
        "adminEmail": normalizeEmailAddress(row["AdminEmail"]),
        "feedbackOnUpdate": determineFeedbackOnUpdate(lastUpdated, score, archiveId),
        "feedbackOnIntegrity": determineFeedbackOnIntegrity(row['integrity_problems'], archiveId, orgStarScore - starScore),
        "metricsTable": generateMetricsTable(metrics, archiveId),
        "usagePeriodBeg": usagePeriodBeg,
        "usagePeriodEnd": usagePeriodEnd,
        "hits": hits,
        "clicks": clicks,
        "pluralHits": pluralHits,
        "pluralClicks": pluralClicks,
        }

    return template % params

def sendReport(msg, emails, bcc, archiveId, isTest):
    sender = "olac-admin@language-archives.org"
    subject = "Archive Report for %s" % archiveId
    if isTest: subject = "(testing) " + subject
    header = "From: %s\r\nTo: %s\r\nSubject: %s\r\n" % \
             (sender, ", ".join(emails), subject)
    msg = header + "\r\n" + msg
    server = smtplib.SMTP('mail.ldc.upenn.edu')
    server.helo()
    server.docmd("MAIL FROM:<olac-admin@language-archives.org>")
    if emails:
        for addr in emails:
            server.docmd("RCPT TO:<%s>" % addr)
    if bcc:
        for addr in bcc:
            server.docmd("RCPT TO:<%s>" % addr)
    server.docmd("DATA")
    server.docmd(msg + "\r\n.\r\n")
    server.docmd("QUIT")
    server.quit()


class Table:
    def findRow(self, field, value):
        for row in self.table:
            try:
                if row[field] == value:
                    return row
            except KeyError:
                # unknown database field
                return None

    def findCell(self, field, value, field2):
        row = self.findRow(field, value)
        if row:
            try:
                return row[field2]
            except KeyError:
                # unknown database field
                return None

    def getColumn(self, field):
        try:
            return [r[field] for r in self.table]
        except KeyError:
            # unknown field
            return None
    
    def size(self):
        return len(self.table)

    def rank(self, field, value):
        try:
            L = sorted([row[field] for row in self.table] + [value])
            L.reverse()
            return L.index(value) + 1
        except KeyError:
            # unknown field in row[field]
            return None


class Metrics(Table):
    def __init__(self):
        con = MySQLdb.connect(read_default_file="/ldc/home/olac/.my.cnf")
        cur = con.cursor(MySQLdb.cursors.DictCursor)
        cur.execute("select * from Metrics left join OLAC_ARCHIVE on Metrics.archive_id=OLAC_ARCHIVE.Archive_ID where Metrics.archive_id!=-1")
        self.table = cur.fetchall()
        
        cur.execute("select oa.RepositoryIdentifier, oa.AdminEmail, oa.CuratorEmail, ap.Email from OLAC_ARCHIVE oa left join ARCHIVE_PARTICIPANT ap on oa.Archive_ID=ap.Archive_ID")
        h = {}
        for row in cur.fetchall():
            archiveid = row['RepositoryIdentifier']
            if archiveid not in h:
                h[archiveid] = {}
            for i in ('AdminEmail','CuratorEmail','Email'):
                email = row[i]
                if email is not None and email.strip():
                    h[archiveid][normalizeEmailAddress(email)] = 1
        self._participants = {}
        for k,h2 in h.items():
            self._participants[k] = [x for x in h2.keys() if x.strip()]
           
        cur.close()
        con.close()

    def participants(self, archiveId):
        if archiveId in self._participants:
            return self._participants[archiveId]
        else:
            return []


def previous_quarter():
    today = datetime.datetime.today()
    q = (today.month-1) / 3
    y = today.year
    if q == 0:
        beg = datetime.datetime(y-1, 10, 1)
        end = datetime.datetime(y, 1, 1)
    else:
        beg = datetime.datetime(y, (q-1)*3+1, 1)
        end = datetime.datetime(y, q*3+1, 1)
    return beg, end

usageSQL = """\
select repoid,
       min(start_date) start_date,
       max(end_date) end_date,
       sum(pageviews) pageviews,
       sum(unique_pageviews) unique_pageviews,
       sum(time_on_page) time_on_page,
       sum(bounce_rate*pageviews)/sum(pageviews) bounce_rate,
       sum(percent_exit*pageviews)/sum(pageviews) percent_exit,
       avg(value_index) value_index from GoogleAnalyticsReports
where type=%s and start_date>=%s and end_date<%s
group by type, repoid
order by type, repoid
"""

class GA(Table):
    def __init__(self, typ):
        """
        @param typ: 'hits' or 'clicks'
        """
        con = MySQLdb.connect(read_default_file="/ldc/home/olac/.my.cnf")
        cur = con.cursor(MySQLdb.cursors.DictCursor)
        beg, end = previous_quarter()
        cur.execute(usageSQL, (typ, beg, end))
        self.table = cur.fetchall()
        cur.close()
        con.close()

        
if __name__ == "__main__":
    import sys
    from optparse import OptionParser

    usage = """\
usage: %prog -h
       %prog [-s] [-t <email>] [-b <email>] <OAI repository ID>
       %prog [-s] [-t <email>] [-b <email>] -a"""
    op = OptionParser(usage)
    op.add_option("-a", "--all", dest="all",
                  action="store_true", default=False,
                  help="process all archives")
    op.add_option("-t", "--to", dest="receipient",
                  metavar="EMAIL",
                  help="specify receipient of reports; ignored unless -s option is used")
    op.add_option("-b", "--bcc", dest="blindcc",
                  metavar="EMAIL",
                  help="specify bcc list; ignored in the absence of -s option")
    op.add_option("-s", "--send", dest="send",
                  action="store_true", default=False,
                  help="send reports; by default they are printed on screen")

    opts, args = op.parse_args()

    def usage(msg):
        op.print_usage()
        print
        print msg
        print
        sys.exit(1)
        
    if opts.all == False:
        if len(args) != 1:
            usage("specify repository identifier")
        else:
            metrics = Metrics()
            archiveIds = [args[0]]
    else:
        metrics = Metrics()
        archiveIds = metrics.getColumn("RepositoryIdentifier")
    usageh = GA('hits')
    usagec = GA('clicks')

    if opts.receipient:
        L = re.split(r"[,; ]+", opts.receipient)
        test_receipient = [normalizeEmailAddress(x) for x in L]
    else:
        test_receipient = None   # the real curator email address is used
    if opts.blindcc:
        L = re.split(r"[,; ]+", opts.blindcc)
        bcc = [normalizeEmailAddress(x) for x in L]
    else:
        bcc = None
    sendemail = opts.send
    
    for archiveId in archiveIds:
        msg = composeEmail(archiveId, metrics, usageh, usagec)
        if sendemail:
            row = metrics.findRow('RepositoryIdentifier',archiveId)
            repoName = row["RepositoryName"]
            if test_receipient:
                sendReport(msg, test_receipient, bcc, repoName, True)
            else:
                receipient = metrics.participants(archiveId)
                if receipient:
                    sendReport(msg, receipient, bcc, repoName, False)
        else:
            print "-" * 79
            print msg
            print
