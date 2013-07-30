use OLAC::DB;
use Net::HTTP;
use XML::Parser;
use DBI;
use vars ('@ISA');



#-------------------- NS2SL ------------------------------------------
#
#
#
#---------------------------------------------------------------------

package OLAC::NS2SL;

sub new
{
    my ($class) = @_;
    my $self = {};
    bless $self, $class;
    return $self;
}

sub update
{
    my ($self, %att) = @_;

    foreach (keys %att) {
	my $v = \$att{$_};
	if ($_ eq "schemaLocation") {
	    $$v =~ s/^\s*//;
	    %$self = split /\s+/, $$v;
	}
    }
}


#-------------------- OAI --------------------------------------------
#
# Base class for OAI harvester tasks.
#
# These should be provided:
#   $self->{baseURL}  - base url of the repo 
#   $self->{request}  - a hash of an OAI request
#   $self->{handlers} - a hash of SAX handlers
#
# These are set as a result:
#   $self->{reqURL}   - request URL
#   $self->{xml}      - response from the repo
#   $self->{err}      - error during harvesting
#
#---------------------------------------------------------------------

package OLAC::OAI;

sub new
{
    my ($class, %arg) = @_;
    my $self = \%arg;
    bless $self, $class;
    $self->_fetch;
    return $self;
}

sub _fetch
{
    my $self = shift;
    my $request = $self->{request};

    # make a request url
    my $url = "$self->{baseURL}?";
    foreach (keys %$request) {
	$url .= "$_=$request->{$_}&" if $request->{$_};
    }
    chop $url;
    $self->{reqURL} = $url;

    # fetch and parse
    my $http = new OLAC::HTTPStream($url);
    unless ($http) {
	$self->{err} = OLAC::Error->new
	    ('http error', "error response from the peer", $@);
	return;
    }

    my $parser = new XML::Parser(Handlers => $self->{handlers},
				 Namespaces => 1);
    eval { $parser->parse($http) };
    if ($@) {
	$self->{err} = OLAC::Error->new
	    ('parse error', "can't parse response from <$url>", $@);
    }
}



#-------------------- OAI_Identify -----------------------------------
#
# OAI harvester task for Identify verb
# Downloads Identify response from the given URL, then parses it to
# turn it into a hash record.
#
# These should be provided:
#   $self->{baseURL}  - base url of the repo 
#
# These are set as a result:
#   $self->{request}  - a hash of an OAI request
#   $self->{reqURL}   - request URL
#   $self->{xml}      - response from the repo
#   $self->{err}      - error during harvesting
#   $self->{hash}     - hash version of Identify response that fits to the
#                       OLAC_ARCHIVE table of olac db schema
#
#---------------------------------------------------------------------

package OLAC::OAI_Identify;
@ISA = ("OLAC::OAI");

# xml elements with the name in the keys of this hash will be harvested
# the values become keys of the result record ($self->{hash})
%tab = (
    # from <Identify>
    adminEmail           => AdminEmail,
    repositoryName       => RepositoryName,
    baseURL              => BaseURL,
    protocolVersion      => OaiVersion,

    # from <Identify><oai:description><olac-archive>
    archiveURL           => ArchiveURL,
    curator              => Curator,
    curatorEmail         => CuratorEmail,
    curatorTitle         => CuratorTitle,
    institution          => Institution,
    institutionURL       => InstitutionURL,
    shortLocation        => ShortLocation,
    location             => Location,
    synopsis             => Synopsis,
    access               => Access,

    # from <Identify><oai:description><oai-identifier>
    repositoryIdentifier => RepositoryIdentifier,

    # from <OAI-PMH>
    responseDate         => LastHarvested,

    # for error checking
    error                => error
);

%record;    # the record converted from Identify response

sub new {
    my ($class, $baseURL) = @_;

    %record = (
        AdminEmail => '',
        RepositoryName => '',
        BaseURL => '',
        OaiVersion => '',
        ArchiveURL => '',
        Curator => '',
        CuratorEmail => '',
        CuratorTitle => '',
        Institution => '',
        InstitutionURL => '',
        ShortLocation => '',
        Location => '',
        Synopsis => '',
        Access => '',
	RepositoryIdentifier => '',
	LastHarvested => ''
    );

    my $self = $class->SUPER::new
	(baseURL  => $baseURL,
	 request  => {verb => 'Identify'},
	 handlers => {Start => \&_Start,
		      End   => \&_End,
		      Char  => \&_Char});

    unless ($self->{err}) {
	if ($record{error}) {
	    $self->{err} = new OLAC::Error
		('OAI error',
		 "error response from <$self->{reqURL}>",
		 $record{error});
	} else {
	    $self->{hash} = \%record;
	}
    }

    bless $self, $class;
    return $self;
}

# if defined, it will be added to %record
$saved_tag = undef;

# ignore following elements and their children recursively
%skip_all_list = (friends => 1, gateway => 1);
$skip_all = undef;

sub _Start
{
    return if $skip_all;

    my ($expat, $tag, %att) = @_;
    $tag =~ s/([^:]+:)?//;

    if ($skip_all_list{$tag}) {
	$skip_all = 1;
	return;
    }

    $saved_tag = $tab{$tag};
}


sub _End
{
    my ($expat, $tag) = @_;
    $tag =~ s/([^:]+:)?//;

    if ($skip_all) {
	undef $skip_all if $skip_all_list{$tag};
	return;
    }
    elsif ($saved_tag) {
	OLAC::Utils::trim $saved_content;
	# put it in %record
	$record{$saved_tag} = $saved_content;
	# unset the flag
	undef $saved_tag;
	undef $saved_content;
    }
}

sub _Char {
    return unless $saved_tag;
    my ($expat, $char) = @_;
    $saved_content .= $char;
}

#-------------------- OAI_ListRecords --------------------------------
#
# OAI harvester task for ListRecords verb
#
# These should be provided:
#   $self->{baseURL}  - base url of the repo 
#   $self->{from}     - harvesting start date
#   $record_handler   - a function to process records
#
# These are set as a result:
#   $self->{request}  - a hash of an OAI request
#   $self->{reqURL}   - request URL
#   $self->{xml}      - response from the repo
#   $self->{err}      - error during harvesting
#   $self->{records}  - list version of ListRecords response containing
#                       a listref of ($header, $OLAC).  $header is
#                       a hashref of OaiIdentifier, DateStamp and SchemaNS.
#                       $OLAC is a listref of $metadata.  $metadata is
#                       a hashref of TagName, Content, Lang, Code and ext.
#                       $metadata->{ext} is a hashref of type, ns, naprefix
#                       and nsschema.
#
#---------------------------------------------------------------------

package OLAC::OAI_ListRecords;
@ISA = ("OLAC::OAI");

sub switch_handlers {
    ($start, $end, $char) = @_;
}

sub new
{
    my ($class, $rec_handler, $baseURL, $from) = @_;

    $record_handler = $rec_handler;
    switch_handlers(\&_s_main, \&_e_main, \&_c_main);

    $OLAC::OAI_ListRecords::rcount = 0;

    my $self = $class->SUPER::new
	(baseURL  => $baseURL,
	 request  => {verb           => 'ListRecords',
		      metadataPrefix => olac,
		      from           => $from},
	 handlers => {Start => sub {$start->(@_);},
		      End   => sub {$end->(@_);},
		      Char  => sub {$char->(@_);}});

    while ($resumptionToken && !$self->{err} && !$error_code) {
	$self->{request} = {verb            => 'ListRecords',
			    resumptionToken => $resumptionToken};
	undef $resumptionToken;
	$self->_fetch;
    }

    unless ($self->{err}) {
	if ($error_code) {
	    $self->{err} = OLAC::Error->new
		('OAI error', "error response from <$self->{reqURL}>",
		 "code=$error_code, message=$error_message");
	}
    }

    $self->{record_count} = $OLAC::OAI_ListRecords::rcount;
    bless $self, $class;
    return $self;
}



########################
# main handlers #
########################

# the xmlns and schemaLocation attributes of these elements will be processed
# note that OAI-PMH > ListRecords > record > metadata > olac
%xmlns_elt = (
    'OAI-PMH'   => 1,
    ListRecords => 1,
    record      => 1,
    metadata    => 1,
    olac        => 1
);

# namespace prefix <=> schema location (URL)
$nsdb = OLAC::NS2SL->new;

%handlers = (
    header          => [\&_Start_header, \&_End_header, \&_Char_header],
    olac            => [\&_Start_olac, \&_End_olac, \&_Char_olac],
    resumptionToken => [\&_Start_rt, \&_End_rt, \&_Char_rt],
    error           => [\&_Start_err, \&_End_err, \&_Char_err]
);
sub _s_main {
    my ($expat, $tag, %att) = @_;
    $nsdb->update(%att) if $xmlns_elt{$tag};
    my $handler = $handlers{$tag};
    if ($handler) {
	switch_handlers(@$handler);
	return;
    }
}
sub _e_main {}
sub _c_main {}


###########################
# header element handlers #
###########################
%tab_header = (
    identifier => OaiIdentifier,
    datestamp  => DateStamp
);

sub _Start_header {
    $_[1] =~ s/^.*://;
    my $tmp = $tab_header{$_[1]};
    $saved_tag = $tmp if $tmp;
}

sub _End_header {
    $_[1] =~ s/^.*://;
    if ($_[1] eq 'header') {
	switch_handlers(\&_s_main, \&_e_main, \&_c_main);
	return;
    }

    return unless $saved_tag;
    OLAC::Utils::trim $saved_content;

    $header->{$saved_tag} = OLAC::Utils::trim $saved_content;

    undef $saved_tag;
    undef $saved_content;
}

sub _Char_header {
    return unless $saved_tag;
    $saved_content .= $_[1];
}


#########################
# olac element handlers #
#########################

sub _Start_olac {
    my ($expat, $tag, %att) = @_;
    $saved_att = \%att;
}

sub _End_olac {
    my ($expat, $tag) = @_;
    if ($tag eq 'olac') {
	$header->{SchemaNS} = $expat->namespace($tag);  # for schema version
	$record_handler->($header, $OLAC);
	++$OLAC::OAI_ListRecords::rcount;
	$header = {};
	$OLAC   = [];
	switch_handlers(\&_s_main, \&_e_main, \&_c_main);
	return;
    }

    my $metadata = {};
    OLAC::Utils::trim $saved_content;
    $metadata->{TagName} = OLAC::Utils::trim $tag;
    $metadata->{Content} = OLAC::Utils::trim $saved_content if $saved_content;
    $metadata->{Lang} = OLAC::Utils::trim $saved_att->{'xml:lang'} if $saved_att->{'xml:lang'};

    my $xsitype = $saved_att->{type};
    if ($xsitype) {
	$xsitype =~ /^((.+):)?(.+)$/;
	$metadata->{ext}{type}     = $3;
	$metadata->{ext}{ns}       = $expat->expand_ns_prefix($2);
	$metadata->{ext}{nsprefix} = $2;
	$metadata->{ext}{nsschema} = $nsdb->{$metadata->{ext}{ns}};
        if ($saved_att->{code}) {
            $metadata->{Code} = OLAC::Utils::trim $saved_att->{code};
        }
    }

    push @$OLAC, $metadata;

    undef $saved_att;
    undef $saved_content;
}

sub _Char_olac {
    return unless $saved_att;
    $saved_content .= $_[1];
}

###########################
# resumptionToken handler #
###########################

sub _Start_rt {
    $resumptionToken = '';
}
sub _End_rt {
    switch_handlers(\&_s_main, \&_e_main, \&_c_main);
}
sub _Char_rt {
    $resumptionToken .= $_[1];
}

#########################
# error element handler #
#########################

sub _Start_err {
    my ($expat, $tag, %att) = @_;
    $error_message = '';
    $error_code    = $att{code};
}
sub _End_err {
    switch_handlers(\&_s_main, \&_e_main, \&_c_main);
}
sub _Char_err {
    $error_message .= $_[1];
}





#-------------------- Ovester ----------------------------------------
#
#
#
#---------------------------------------------------------------------

package OLAC::Ovester;

%days = ('01'=>31,'02'=>28,'03'=>31,'04'=>30,'05'=>31,'06'=>30,
	 '07'=>31,'08'=>31,'09'=>30,'10'=>31,'11'=>30,'12'=>31);

sub next_day {
    my ($year, $month, $day) = split /-/, shift;
    $days{'02'} = $year % 4 ? 29 : 28;
    if (++$day > $days{$month}) {
	$day = 1;
	if (++$month > 12) {
	    $month = '01';
	    $year++;
	}
    }
    return sprintf "%04d-%02d-%02d", $year, $month, $day;
}


sub new {
    my ($class, $dbinfofile, $arclist) = @_;
    my $self = {
	archive_list => $arclist,
	db           => new OLAC::ODB($dbinfofile)
    };
    bless $self, $class;
    return $self;
}

sub harvest {
    my $self = shift;
    foreach my $tup (@{$self->{archive_list}}) {
	my $url = $tup->[1];
	print "========================================\n";
	print "Harvesting from $url\n";
	my ($arcid, $lastdate) = $self->proc_Identify($url);
	unless ($arcid) {
	    print "Harvest aborted.\n";
	    next;
	}
	my $from = $lastdate if $lastdate;
	$url =~ s/http:\/\//http:\/\/www.language-archives.org\/tools\/eth15filter.php\//;
	my $rcount = $self->proc_ListRecords($arcid, $url, $from);
	my $since = $lastdate ? "since $lastdate" : "(first harvest)";
	if (defined($rcount)) {
	    print "$rcount new record(s) $since\n";
	} else {
	    print "Harvest incomplete.\n";
	}
	print "\n";
    }
}

sub proc_Identify
{
    my ($self, $url) = @_;
    my $db = $self->{db};

    # get Identify response in a record ($rec)
    my $response = new OLAC::OAI_Identify($url);
    return undef if $response->{err};
    my $rec = $response->{hash};

    # check existence of the repo
    my $sql = "select * from OLAC_ARCHIVE where RepositoryIdentifier='$rec->{RepositoryIdentifier}' and BaseURL='$rec->{BaseURL}'";
    my $r = $db->{dbh}->selectrow_hashref($sql);
    if ($db->{dbh}->errstr) {
	OLAC::Error->new('db error', "query failed <$sql>", $db->{dbh}->errstr)->log;
	return undef;
    }

    my $archive_id;
    my $last_harvested = undef;

    if (%$r) {  # if exists, update
	$archive_id = $r->{Archive_ID};
	$last_harvested = $r->{LastHarvested};
	$db->update('OLAC_ARCHIVE', $rec, "Archive_ID='$archive_id'");
    } else {    # if not, create new one
	$rec->{FirstHarvested} = $rec->{LastHarvested};
	$archive_id = $db->insert('OLAC_ARCHIVE', $rec);
    }

    # $last_harvested==undef  means  this is a new archive
    return ($archive_id, $last_harvested);
}

sub proc_ListRecords
{
    my ($self, $arcid, $url, $from) = @_;

    local $DB = $self->{db};
    local $ARCID = $arcid;
    my $LR = new OLAC::OAI_ListRecords(\&proc_Record, $url, $from);
    if ($LR->{err}) {
	return undef;
    } else {
	return $LR->{record_count};
    }
}

sub proc_Record {
    my ($header, $olac) = @_;
    $header->{Archive_ID} = $ARCID;
    $header->{Schema_ID} = $DB->get_schema_id($header->{SchemaNS});
    delete $header->{SchemaNS};
    $DB->insert('ARCHIVED_ITEM', $header);
    my $itemid = $DB->{dbh}->{mysql_insertid};
    
    foreach my $mdata (@$olac) {
	$mdata->{Item_ID} = $itemid;
	$mdata->{Tag_ID} = $DB->get_tag_id($mdata->{TagName});
	if ($mdata->{ext}) {
	    $mdata->{Type} = $mdata->{ext}{type};
	    $mdata->{Extension_ID} = $DB->get_ext_id($mdata->{ext});
	    delete $mdata->{ext};
	}
	$DB->insert('METADATA_ELEM', $mdata);
    }
}

sub purge {
    my $self = shift;
    my $db = $self->{db};

    my $res = $db->{dbh}->selectall_arrayref
	("select Archive_ID, RepositoryIdentifier, BaseURL from OLAC_ARCHIVE");
    my %h;
    map {$h{$_->[0]}=[$_->[1],$_->[2]];} @$res;
    foreach my $tup (@{$self->{archive_list}}) {
	$res = $db->{dbh}->selectcol_arrayref
	    ("select Archive_ID from OLAC_ARCHIVE
              where RepositoryIdentifier='$tup->[0]'
              and BaseURL='$tup->[1]'");
	map {delete $h{$_};} @$res;
    }

    foreach my $aid (keys %h) {
	my $repoid = $h{$aid}[0];
	my $baseurl = $h{$aid}[1];
	print "* $repoid  $baseurl\n";

	my $iids = $db->{dbh}->selectcol_arrayref
	    ("select Item_ID from ARCHIVED_ITEM where Archive_ID='$aid'");
	$db->{dbh}->do
	    ("delete from OLAC_ARCHIVE where Archive_ID='$aid'");
	$db->{dbh}->do
	    ("delete from ARCHIVED_ITEM where Archive_ID='$aid'");
	foreach my $iid (@$iids) {
	    $db->{dbh}->do
		("delete from METADATA_ELEM where Item_ID='$iid'");
	}
    }
}

1;
