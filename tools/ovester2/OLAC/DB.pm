use strict;

#-------------------- DB ---------------------------------------------
#
# $self->{dbh}
# $self->{err}
# $self->{connected}
#
#---------------------------------------------------------------------

package OLAC::DB;
use OLAC::Utils;
use DBI;

sub new {
    my ($class, $dbinfofile) = @_;
    my $self = {};
    bless $self, $class;
    
    $self->_connect($dbinfofile);
    return $self unless $self->{err};
}

sub DESTROY
{
    my $self = shift;
    $self->{dbh}->disconnect if $self->{connected};
}

sub _connect {
    my ($self, $dbinfofile) = @_;
    if (open FH, $dbinfofile) {
	my $dbname = <FH>; OLAC::Utils::trim $dbname;
	my $user = <FH>;   OLAC::Utils::trim $user;
	my $pass = <FH>;   OLAC::Utils::trim $pass;
	$self->{dbh} = DBI->connect("dbi:mysql:$dbname", $user, $pass,
				    {PrintError=>0, AutoCommit=>1});
	if (DBI->errstr) {
	    $self->{err} = OLAC::Error->new
		('db error', "can't connect", DBI->errstr);
	} else {
	    $self->{connected} = 1;
	}
    } else {
	$self->{err} = OLAC::Error->new
	    ('db error', "can't open db info file <$dbinfofile>");
    }
    close FH;
}

sub insert {
    my ($self, $table, $record) = @_;
    my ($kk, $vv) = ('', '');
    foreach (keys %$record) {
	my $v = $record->{$_};
	next unless defined($v);
	$v =~ s/\'/\'\'/g;
        $v =~ s/\\/\\\\/g;
	$kk .= "$_,";
	$vv .= "'$v',";
    }
    chop $kk;
    chop $vv;
    $self->{sql} = "insert into $table ($kk) values ($vv)";
    $self->{dbh}->do($self->{sql}) if $kk;
    if ($self->{dbh}->errstr) {
	$self->{err} = OLAC::Error->new
	    ('db error', "query error <$self->{sql}>", $self->{dbh}->errstr);
    } else {
	return $self->{dbh}{mysql_insertid};
    }
}

sub update {
    my ($self, $table, $record, $cond) = @_;
    my $set = '';
    foreach (keys %$record) {
	my $v = $record->{$_};
	$v =~ s/\'/\'\'/g;
        $v =~ s/\\/\\\\/g;
	$set .= "$_='$v',";
    }
    chop $set;
    $self->{sql} = "update $table set $set";
    $self->{sql} .= " where $cond" if $cond;
    my $res = $self->{dbh}->do($self->{sql}) if $set;
    $self->{err} = $self->{dbh}->errstr;
    return $res;
}

#-------------------- ODB --------------------------------------------
#
# $self->{dbh}
# $self->{err}
# $self->{connected}
# $self->{extdb}
# $self->{tagdb}
# $self->{schemadb}
#
#---------------------------------------------------------------------
package OLAC::ODB;
use vars ('@ISA');
@OLAC::ODB::ISA = ('OLAC::DB');

sub switch_handlers {
    ($OLAC::ODB::start, $OLAC::ODB::end, $OLAC::ODB::char) = @_;
}

sub new {
    my ($class, $dbinfofile) = @_;
    my $self = new OLAC::DB($dbinfofile);
    bless $self, $class;

    $self->_init_extdb;
    return $self if $self->{err};
    $self->_init_tagdb;
    return $self if $self->{err};
    $self->_init_schemadb;
    return $self;
}

sub DESTROY
{
    my $self = shift;
    $self->{dbh}->disconnect if $self->{connected};
}

sub _init_extdb {
    my $self = shift;
    switch_handlers(\&_s_ext_main, \&_e_ext_main, \&_c_ext_main);
    $self->{parser} = XML::Parser->new
	(Handlers => {Start => sub {$OLAC::ODB::start->(@_);},
		      End   => sub {$OLAC::ODB::end->(@_);},
		      Char  => sub {$OLAC::ODB::char->(@_);}});
    my $sql = "select NS, Type, Extension_ID from EXTENSION";
    my $res = $self->{dbh}->selectall_arrayref($sql);
    if ($self->{dbh}->errstr) {
	$self->{err} = OLAC::Error->new
	    ('db error', "query failed <$sql>", $self->{dbh}->errstr);
	return;
    }
    foreach (@$res) {
	$self->{extdb}{$_->[0]}{$_->[1]} = $_->[2];
    }
}

sub _init_tagdb {
    my $self = shift;
    my $sql = "select TagName, Tag_ID from ELEMENT_DEFN";
    my $res = $self->{dbh}->selectall_arrayref($sql);
    if ($self->{dbh}->errstr) {
	$self->{err} = OLAC::Error->new
	    ('db error', "query failed <$sql>", $self->{dbh}->errstr);
	return;
    }
    foreach (@$res) {
	$self->{tagdb}{$_->[0]} = $_->[1];
    }
}

sub _init_schemadb {
    my $self = shift;
    my $sql = "select Xmlns, Schema_ID from SCHEMA_VERSION";
    my $res = $self->{dbh}->selectall_arrayref($sql);
    if ($self->{dbh}->errstr) {
	$self->{err} = OLAC::Error->new
	    ('db error', "query failed <$sql>", $self->{dbh}->errstr);
	return;
    }
    foreach (@$res) {
	$self->{schemadb}{$_->[0]} = $_->[1];
    }
}

sub get_tag_id {
    my ($self, $tag) = @_;
    return $self->{tagdb}{$tag};
}

sub get_schema_id {
    my ($self, $ns) = @_;
    return $self->{schemadb}{$ns};
}

sub get_ext_id {
    $OLAC::ODB::self  = shift;
    $OLAC::ODB::ext   = shift;  # keys: type, ns, nsprefix, nsschema
    $OLAC::ODB::extdb = $OLAC::ODB::self->{extdb};
    $OLAC::ODB::dbh   = $OLAC::ODB::self->{dbh};
    $OLAC::ODB::found =
	$OLAC::ODB::extdb
	->{$OLAC::ODB::ext->{ns}}{$OLAC::ODB::ext->{type}};

    return $OLAC::ODB::found if $OLAC::ODB::found;

    @OLAC::ODB::Q = ($OLAC::ODB::ext->{nsschema});
    $OLAC::ODB::ext->{nsschema} =~ /^(.+\/)(.*)$/;
    $OLAC::ODB::topxsd_base = $1;

    while (@OLAC::ODB::Q) {
	$OLAC::ODB::curxsd = shift @OLAC::ODB::Q;
	next unless $OLAC::ODB::curxsd;
	my $http = new OLAC::HTTPStream($OLAC::ODB::curxsd);
	next unless $http;
	eval { $OLAC::ODB::self->{parser}->parse($http)	};
	if ($@) {
	    $OLAC::ODB::self->{err} = OLAC::Error->new
		('parse error', "error during parsing <$OLAC::ODB::curxsd>", $@);
	}
    }

    unless ($OLAC::ODB::found) {
	$OLAC::ODB::found = {
	    Type         => $OLAC::ODB::ext->{type},
	    NS           => $OLAC::ODB::ext->{ns},
	    NSPrefix     => $OLAC::ODB::ext->{nsprefix},
	    NSSchema     => $OLAC::ODB::ext->{nsschema}
	};
	$OLAC::ODB::self->insert('EXTENSION', $OLAC::ODB::found);
	$OLAC::ODB::extdb->{$OLAC::ODB::ext->{ns}}{$OLAC::ODB::ext->{type}} =
	    $OLAC::ODB::dbh->{mysql_insertid};
    }

    return $OLAC::ODB::extdb->{$OLAC::ODB::ext->{ns}}{$OLAC::ODB::ext->{type}};
}

###################
# master handlers #
###################

sub _s_ext_main {
    my ($expat, $tag, %att) = @_;

    if ($tag =~ /:include$/) {
	my $xsd = $att{schemaLocation};
	unless ($xsd =~ /^http/) {
	    $xsd = $OLAC::ODB::topxsd_base . $xsd;
	}
	push @OLAC::ODB::Q, $xsd;
    }
    elsif ($tag eq 'olac-extension') {
	$OLAC::ODB::found = {};
	switch_handlers(\&_s_ext_ext, \&_e_ext_ext, \&_c_ext_ext);
    }
}

sub _e_ext_main {}
sub _c_ext_main {}

###########################
# olac-extension handlers #
###########################

sub _s_ext_ext {
    $OLAC::ODB::saved_content = '';
}

sub _e_ext_ext {
    my ($expat, $tag) = @_;

    if ($tag eq 'olac-extension') {
	switch_handlers(\&_s_ext_main, \&_e_ext_main, \&_c_ext_main);
	$OLAC::ODB::found->{Type} = $OLAC::ODB::found->{shortName};
	$OLAC::ODB::found->{DefiningSchema} = $OLAC::ODB::curxsd;
	$OLAC::ODB::found->{NS} = $OLAC::ODB::ext->{ns};
	$OLAC::ODB::found->{NSPrefix} = $OLAC::ODB::ext->{nsprefix};
	$OLAC::ODB::found->{NSSchema} = $OLAC::ODB::ext->{nsschema};
	delete $OLAC::ODB::found->{shortName};
	$OLAC::ODB::self->insert('EXTENSION', $OLAC::ODB::found);
	my $extid = $OLAC::ODB::self->{dbh}{mysql_insertid};
	$OLAC::ODB::extdb->{$OLAC::ODB::ext->{ns}}{$OLAC::ODB::found->{Type}} =
	    $OLAC::ODB::dbh->{mysql_insertid};
	my $coderec = {Extension_ID=>$extid, Code=>'', Label=>''};
	$OLAC::ODB::self->insert('CODE_DEFN', $coderec);
	return;
    }

    $OLAC::ODB::found->{$tag} = $OLAC::ODB::saved_content;
}

sub _c_ext_ext {
    $OLAC::ODB::saved_content .= $_[1];
}


#-------------------- ADB --------------------------------------------
#
# $self->{dbh}
# $self->{err}
# $self->{connected}
# $self->{extdb}
# $self->{tagdb}
# $self->{schemadb}
#
#---------------------------------------------------------------------

package OLAC::ADB;
use vars ('@ISA');
@OLAC::ADB::ISA = ('OLAC::DB');

sub new {
    my ($class, $dbinfofile) = @_;
    my $self = $class->SUPER::new($dbinfofile);
    bless $self, $class;

    $self->{GetRecord} = \&getTable_GetRecord;
    $self->{ListIdentifiers} = \&getTable_ListIdentifiers;
    $self->{ListMetadataFormats} = \&getTable_ListMetadataFormats;
    $self->{ListRecords} = \&getTable_ListRecords;
    $self->{Query} = \&getTable_Query;

    return $self;
}

sub DESTROY {
    my $self = shift;
    $self->{dbh}->disconnect if $self->{connected};
}

sub get_earliestDatestamp {
    my ($self) = @_;
    return $self->{dbh}->selectrow_arrayref
	("select min(DateStamp) from ARCHIVED_ITEM")->[0];
}

sub getTable {
    my ($self, $request) = @_;

    my $f = $self->{$request->{verb}};
    return $f ? $f->($self,$request) : undef;
}

sub getTable_GetRecord {
    my ($self, $request) = @_;

    my $archived_item = $self->{dbh}->selectrow_hashref
	("select * from ARCHIVED_ITEM " .
	 "where OaiIdentifier='$request->{identifier}'");

    my $header;
    if ($archived_item) {
	$header = [ $archived_item->{OaiIdentifier},
		    $archived_item->{DateStamp} ];
    }
    else {
	$header = undef;
    }

    my $metadata;

    $metadata = $self->{dbh}->selectall_arrayref
	("select TagName,Lang,Content,me.Extension_ID,cd.Code, " .
	 "       ex.Label,cd.Label " .
	 "from METADATA_ELEM me, EXTENSION ex, CODE_DEFN cd " .
	 "where Item_ID='$archived_item->{Item_ID}' " .
	 "and me.Extension_ID=ex.Extension_ID " .
	 "and me.Extension_ID=cd.Extension_ID " .
	 "and (cd.Code='' or me.Code=cd.Code) ");

    return ($header, $metadata);
}

sub getTable_ListIdentifiers {
    my ($self, $request) = @_;

    return undef if $request->{set};

    my $query = "select OaiIdentifier, DateStamp from ARCHIVED_ITEM ";

    my $conj = "where";
    if ($request->{resumptionToken}) {
	$query .= "$conj OaiIdentifier >= '$request->{next}' ";
	$conj = "and";
    }
    if ($request->{from}) {
	$query .= "$conj DateStamp >= '$request->{from}' ";
	$conj = "and";
    }
    if ($request->{until}) {
	$query .= "$conj DateStamp <= '$request->{until}'";
    }

    $query .= "order by OaiIdentifier limit 201";

    my $list = $self->{dbh}->selectall_arrayref($query);
    if (scalar(@$list) > 200) {
	$request->{next} = $list->[200]->[0];
	splice(@$list, 200);
    }
    elsif ($request->{next}) {
	delete $request->{next};
    }

    return $list;
}

sub getTable_ListMetadataFormats {
    my ($self, $request) = @_;

    return undef unless ($request->{identifier});

    return $self->{dbh}->selectrow_arrayref
	("select Item_ID from ARCHIVED_ITEM " .
	 "where OaiIdentifier='$request->{identifier}'");
}

sub getTable_ListRecords {
    my ($self, $request) = @_;

    return undef if $request->{set};

    my ($query, $header, $meta);
    my ($f, $u);  # from, until

    #####
    # prepare query for $header
    $query = "select OaiIdentifier, DateStamp, Item_ID from ARCHIVED_ITEM ";

    my $conj = "where";
    if ($request->{resumptionToken}) {
	$query .= "$conj Item_ID >= $request->{next} ";
	$conj = "and";
    }
    if ($request->{from}) {
	$query .= "$conj DateStamp >= '$request->{from}'";
	$conj = "and";
    }
    if ($request->{until}) {
	$query .= "$conj DateStamp <= '$request->{until}'";
    }

    $query .= "order by Item_ID limit 201";

    $header = $self->{dbh}->selectall_arrayref($query);
    if (scalar(@$header) > 200) {
	$request->{next} = $header->[200]->[2];
	splice(@$header, 200);
    }
    elsif ($request->{next}) {
	delete $request->{next};
    }

    #####
    # prepare query for $meta
    $query = "
select TagName,  Lang,     Content, me.Extension_ID, cd.Code,
       ex.Label, cd.Label, Item_ID
from   METADATA_ELEM me, EXTENSION ex, CODE_DEFN cd
where  me.Extension_ID=ex.Extension_ID
and    me.Extension_ID=cd.Extension_ID
and    (cd.Code='' or me.Code=cd.Code) ";

    if ($request->{next}) {
	my $first = $header->[0]->[2];
	my $last = $header->[199]->[2];
	$query .= "and Item_ID >= $first and Item_ID <= $last ";
    }
    if ($request->{from}) {
	$f = "DateStamp >= '$request->{from}'";
	$query .= "and $f ";
    }
    if ($request->{until}) {
	$u = "DateStamp <= '$request->{until}'";
	$query .= "and $u ";
    }
    $query .= "order by Item_ID";

    $meta = $self->{dbh}->selectall_arrayref($query);

    return ($header, $meta);
}

sub getTable_Query {
    my ($self, $request) = @_;

    my ($query, $header, $meta, $meta1);

    # prepare query for $header
    $query =
	"select distinct OaiIdentifier, DateStamp, a.Item_ID " .
	"from ARCHIVED_ITEM as a ";

    my ($i, $where, $conj);
    $conj = "";

    for ($i=1; $i <= $request->{elements}; ++$i) {
	$query .= ", METADATA_ELEM as e$i ";
	$where .= "$conj a.Item_ID=e$i.Item_ID ";
	$conj = "and";
    }

    if ($request->{resumptionToken}) {
	$where .= "$conj a.Item_ID >= $request->{next} ";
	$conj = "and";
    }

    my $count = $request->{count} ? $request->{count} : 20;
    my $count1 = $count + 1;

    $query .= "where $where $conj $request->{sql} " .
	      "order by a.Item_ID limit $count1";

    $header = $self->{dbh}->selectall_arrayref($query);
    unless ($header) {
        return (undef, undef);         # indicates an exception
    }

    if (scalar(@$header) > $count) {
        $request->{next} = $header->[$count]->[2];
	splice(@$header, $count);
    }
    elsif ($request->{next}) {
	delete $request->{next};
    }

    foreach my $item (@$header) {
	# prepare query for $meta
	$query = "
select TagName,  Lang,     Content, me.Extension_ID, cd.Code,
       ex.Label, cd.Label, Item_ID
from   METADATA_ELEM me, EXTENSION ex, CODE_DEFN cd
where  Item_ID=$item->[2]
and    me.Extension_ID=ex.Extension_ID
and    me.Extension_ID=cd.Extension_ID
and    (cd.Code='' or me.Code=cd.Code) ";

	$meta1 = $self->{dbh}->selectall_arrayref($query);
	push (@$meta, $meta1);
    }

    return ($header, $meta);
}

sub getExtDB {
    my $self = shift;
    my $res = $self->{dbh}->selectall_arrayref
	("select Extension_ID, NS, Type from EXTENSION");
    my $h = {};
    map {$h->{$_->[0]} = [$_->[1], $_->[2]];} @$res;
    return $h;
}

sub getDcTagDB {
    my $self = shift;
    my $h = {};
    my $res = $self->{dbh}->selectall_arrayref("
select e1.TagName, e2.TagName
from   ELEMENT_DEFN e1, ELEMENT_DEFN e2
where  e1.DcElement = e2.Tag_ID");
    map { $h->{$_->[0]} = $_->[1] } @$res;
    return $h;
}

	
1;
