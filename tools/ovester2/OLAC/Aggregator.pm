package OLAC::Aggregator;

use OLAC::DB;
use XML::DOM;

my %errmsg = 
    ( "badArgument" =>
      "The request includes illegal arguments, is missing required " .
      "arguments, include a repeated argument, or values for arguments " .
      "have an illegal syntax.",
      
      "badResumptionToken" =>
      "The value of the resumptionToken argument is invalid or expired.",

      "badVerb" =>
      "Value of the verb argument is not a legal OAI-PMH verb, the verb " .
      "argument is missing, or the verb argument is repeated.",
      
      "cannotDisseminateFormat" =>
      "The metadata format identified by the value given for the " .
      "metadataPrefix argument is not supported by the item or by the " .
      "repository.",

      "idDoesNotExist" =>
      "The value of the identifier argument is unknown or illegal in " .
      "this repository.",
      
      "noRecordsMatch" =>
      "The combination of the values of the from, until, set and " .
      "metadataPrefix arguments results in an empty list.",
      
      "noMetadataFormats" => "There are no metadata formats available " .
      "for the specified item.",

      "noSetHierarchy" =>
      "The repository does not support sets."
    );

sub new {
    my ($class, $dbinfofile, $idxml) = @_;

    my $self = {};

    $self->{db} = new OLAC::ADB($dbinfofile);
    $self->{idxml} = $idxml;
    $self->{extdb} = $self->{db}->getExtDB;
    #$self->{tagpx} = $self->{db}->getTagPxDB;
    $self->{dctag} = $self->{db}->getDcTagDB;

    $self->{GetRecord} = \&serve_GetRecord;
    $self->{Identify} = \&serve_Identify;
    $self->{ListIdentifiers} = \&serve_ListIdentifiers;
    $self->{ListMetadataFormats} = \&serve_ListMetadataFormats;
    $self->{ListRecords} = \&serve_ListRecords;
    $self->{ListSets} = \&serve_ListSets;
    $self->{Query} = \&serve_Query;

    $self->{mdata_processors} = {
	olac         => [\&get_mdata_container_olac, \&get_mdata_olac],
	olac_display => [\&get_mdata_container_olac, \&get_mdata_olac_display],
	oai_dc       => [\&get_mdata_container_oaidc,\&get_mdata_oaidc]
    };
    bless $self, $class;
    return $self;
}

sub date_time {
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = gmtime(time);   
    return sprintf("%04d-%02d-%02dT%02d:%02d:%02dZ",
                   $year+1900, $mon+1, $mday, $hour, $min, $sec);
}

sub url_encode {
    my ($str) = @_;

    $str =~ s/%/%25/g;
    $str =~ s/\//%2F/g;
    $str =~ s/\?/%3F/g;
    $str =~ s/#/%23/g;
    $str =~ s/=/%3D/g;
    $str =~ s/&/%26/g;
    $str =~ s/:/%3A/g;
    $str =~ s/;/%3B/g;
    $str =~ s/ /%20/g;
    $str =~ s/\+/%2B/g;

    return $str;
}

sub serve_request {
    my ($self, $request) = @_;

    if ($request->{resumptionToken}) {
        my %reqcopy = %$request;
	delete $reqcopy{baseURL};
	delete $reqcopy{verb};
	delete $reqcopy{resumptionToken};
	if (%reqcopy) {
	    return create_error("badArgument", $request);
	}
	my $token = $request->{resumptionToken};
	unless (open(RESUME, "</tmp/$token")) {
	    return create_error("badResumptionToken", $request);
	}

	# restore previous request
	$request = { resumptionToken => 1 };
	while (my $key = <RESUME>) {
	    my $val = <RESUME>;
	    chomp $key;
	    chomp $val;
	    $request->{$key} = $val;
	}
	close(RESUME);
	unlink "/tmp/$token";
    }

    unless ($self->{$request->{verb}}) {
        return create_error("badVerb");
    }

    my $dom = $self->{$request->{verb}}->($self, $request);

    if ($request->{next}) {
	my $token = "olac:" . time();
	unless (open(RESUME, ">/tmp/$token")) {
	    $request->{error} = "server error";
	    return undef;
	}

	foreach my $key (keys %$request) {
	    print RESUME "$key\n";
	    print RESUME "$request->{$key}\n";
	}
	close(RESUME);

	my $rt;
	if ($request->{verb} eq "Query") {
	    $rt = $dom->getElementsByTagName("ListRecords")->item(0)
		->appendChild($dom->createElement("resumptionToken"));
	}
	else {
	    $rt = $dom->getElementsByTagName($request->{verb})->item(0)
		->appendChild($dom->createElement("resumptionToken"));
	}
	$rt->addText($token);
    }
    return $dom;
}

sub get_template {
    my ($request) = @_;
    my ($doc, $OAI_PMH, $responseDate, $req_elem, $core);
    my $corename = $request->{verb};
    unless ($corename) {
	$corename = "unknown";
    }

    $doc = new XML::DOM::Document;
    $doc->setXMLDecl($doc->createXMLDecl("1.0","UTF-8"));
    
    $OAI_PMH = $doc->appendChild($doc->createElement("OAI-PMH"));
    $responseDate = $OAI_PMH->appendChild($doc->createElement("responseDate"));
    $req_elem = $OAI_PMH->appendChild($doc->createElement("request"));
    $core = $OAI_PMH->appendChild($doc->createElement($corename));

    $OAI_PMH->setAttribute("xmlns",
			   "http://www.openarchives.org/OAI/2.0/");
    $OAI_PMH->setAttribute("xmlns:xsi",
			   "http://www.w3.org/2001/XMLSchema-instance");
    $OAI_PMH->setAttribute("xsi:schemaLocation",
			   "http://www.openarchives.org/OAI/2.0/ " .
			   "http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd");
    $responseDate->addText(date_time());
    set_request($req_elem, $request);

    return ($doc, $core);
}

sub get_template2 {
    my ($request) = @_;
    my ($doc, $OAI_PMH, $responseDate, $req_elem);

    $doc = new XML::DOM::Document;
    $doc->setXMLDecl($doc->createXMLDecl("1.0","UTF-8"));
    
    $OAI_PMH = $doc->appendChild($doc->createElement("OAI-PMH"));
    $responseDate = $OAI_PMH->appendChild($doc->createElement("responseDate"));
    $req_elem = $OAI_PMH->appendChild($doc->createElement("request"));

    $OAI_PMH->setAttribute("xmlns",
			   "http://www.openarchives.org/OAI/2.0/");
    $OAI_PMH->setAttribute("xmlns:xsi",
			   "http://www.w3.org/2001/XMLSchema-instance");
    $OAI_PMH->setAttribute("xsi:schemaLocation",
			   "http://www.openarchives.org/OAI/2.0/ " .
			   "http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd");
    $responseDate->addText(date_time());
    set_request($req_elem, $request);

    return ($doc, $OAI_PMH);
}

sub create_error {
    my ($err_code, $request) = @_;
    my ($doc, $error) = get_template($request);

    $error->setTagName("error");
    $error->setAttribute("code", $err_code);
    $error->addText($errmsg{$err_code});

    return $doc;
}

sub create_error2 {
    my ($err_code_list, $request) = @_;
    my ($doc, $OAI_PMH) = get_template2($request);
    my $error, $err_code;

    my %dup = ();
    foreach $err_code (@$err_code_list) {
	if ($dup{$err_code}) {
	    next;
	} else {
	    $dup{$err_code} = 1;
	}
	$error = $OAI_PMH->appendChild($doc->createElement("error"));
	$error->setAttribute("code", $err_code);
	$error->addText($errmsg{$err_code});
    }
    return $doc;
}

sub is_identifier_okay {
    if ($_[0] =~ 'oai:[a-zA-Z][a-zA-Z0-9\-]*(\.[a-zA-Z][a-zA-Z0-9\-]+)+:[a-zA-Z0-9\-_\.!~\*&apos;\(\);/\?:@&amp;=\+$,%]+') {
	return 1;
    } else {
	return 0;
    }
}

sub set_request {
    my ($e, $request) = @_;

    my @atts = ("verb","identifier","metadataPrefix","from","until","set",
		# for Query verb (this breaks compatibility)
		"elements", "count", "sql");
    my ($att, $val);

    $val = $request->{resumptionToken};
    if ($val) {
	$e->setAttribute("resumptionToken", url_encode($val));
    }
    else {
	foreach $att (@atts) {
	    $val = $request->{$att};
	    if ($val) {
		$e->setAttribute($att, url_encode($val));
	    }
	}
    }

    $e->addText($request->{baseURL});
}

sub set_oai_atts {
    my ($e, $request) = @_;
    my $prefix = "http://www.openarchives.org/OAI/1.1/OAI_";
    my $verb = $request->{verb};
    if ($verb eq "Query") {
	$verb = "ListRecords";
    }
    my $xmlns =	"$prefix$verb";
    my $schema = "$prefix$verb.xsd";

    $e->setAttribute("xmlns", $xmlns);
    $e->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
    $e->setAttribute("xsi:schemaLocation", "$xmlns $schema");
}

sub set_olac_atts {
    my ($e) = @_;
    my @px = ("xmlns:dc",
	      "xmlns:dcterms",
	      "xmlns:olac");
    my @ns = ("http://purl.org/dc/elements/1.1/",
	      "http://purl.org/dc/terms/",
	      "http://www.language-archives.org/OLAC/1.0/");
    my $schemaLoc = "
http://purl.org/dc/elements/1.1/
http://www.language-archives.org/OLAC/1.0/dc.xsd
http://purl.org/dc/terms/
http://www.language-archives.org/OLAC/1.0/dcterms.xsd
http://www.language-archives.org/OLAC/1.0/
http://www.language-archives.org/OLAC/1.0/olac.xsd
";

    foreach my $i (0..2) {
	$e->setAttribute($px[$i], $ns[$i]);
    }
    $e->setAttribute("xsi:schemaLocation", $schemaLoc);
}

sub set_oaidc_atts {
    my ($e) = @_;
    my @px = ("xmlns:dc",
	      "xmlns:oai_dc");
    my @ns = ("http://purl.org/dc/elements/1.1/",
	      "http://www.openarchives.org/OAI/2.0/oai_dc/");
    my $schemaLoc = "
http://purl.org/dc/elements/1.1/
http://www.language-archives.org/OLAC/1.0/dc.xsd
http://www.openarchives.org/OAI/2.0/oai_dc/
http://www.openarchives.org/OAI/2.0/oai_dc.xsd
";

    foreach my $i (0..1) {
	$e->setAttribute($px[$i], $ns[$i]);
    }
    $e->setAttribute("xsi:schemaLocation", $schemaLoc);
}

##### serve_GetRecord
# input:
#   - oai request
# output:
#   - reference to an XML::DOM::Element object conforming to
#     http://www.openarchives.org/OAI/1.1/OAI_GetRecord
#   - undef on errors
# description:
#   serves GetRecord request
#   this func knows about olac database
##
sub serve_GetRecord {
    my ($self, $request) = @_;

    my @err_code_list = ();
    unless ($request->{identifier} && $request->{metadataPrefix}) {
        push @err_code_list, "badArgument";
    }
    if ($request->{identifier}) {
	unless ($request->{identifier} =~ 'oai:[a-zA-Z][a-zA-Z0-9\-]*(\.[a-zA-Z][a-zA-Z0-9\-]+)+:[a-zA-Z0-9\-_\.!~\*&apos;\(\);/\?:@&amp;=\+$,%]+') {
            push @err_code_list, "badArgument";
        }
    } else {
	push @err_code_list, "badArgument";
    }
    if ($request->{metadataPrefix}) {
	unless ($request->{metadataPrefix} eq "olac" ||
		$request->{metadataPrefix} eq "olac_display" ||
		$request->{metadataPrefix} eq "oai_dc") {
	    return create_error("cannotDisseminateFormat", $request);
	}
    } else {
	push @err_code_list, "badArgument";
    }
    my %reqcopy = %$request;
    delete $reqcopy{baseURL};
    delete $reqcopy{verb};
    delete $reqcopy{identifier};
    delete $reqcopy{metadataPrefix};
    if (%reqcopy) {
	push @err_code_list, "badArgument";
    }
    if (@err_code_list) {
	return create_error2(\@err_code_list, $request);
    }

    my ($head, $meta) = $self->{db}->getTable($request);

    # tag lang content ext_id code
    # --- ---- ------- ------ ----
    #  0    1     2       3     4
    #

    unless ($head) {
	return create_error("idDoesNotExist", $request);
    }

    # GetRecord {
    #   record {
    #     header [status] {
    #       identifier
    #       datestamp
    #     }
    #     metadata? {
    #       olac
    #     }
    #   }
    # }
    my ($doc, $gr, $r, $h, $i, $d, $m, $o, $me);

    # build skeleton
    ($doc, $gr) = get_template($request);

    $r = $gr->appendChild($doc->createElement("record"));
    $h = $r->appendChild($doc->createElement("header"));
    $i = $h->appendChild($doc->createElement("identifier"));
    $d = $h->appendChild($doc->createElement("datestamp"));
    $i->addText($head->[0]);
    $d->addText($head->[1]);

    my ($get_container, $get_metadata) =
	@{$self->{mdata_processors}{$request->{metadataPrefix}}};

    $m = $r->appendChild($doc->createElement("metadata"));
    $o = $m->appendChild($get_container->($doc));
    map { $o->appendChild($get_metadata->($self, $doc, $_)) } @$meta;

    # check: we don't harvest 'about' elem. it that ok?
    # check: we don't harvest 'status' att of 'record' elem. ok?

    return $doc;
}

##### serve_Identify
# input:
#   - archive id ('Archive_ID' of olac db)
# output:
#   - reference to an XML::DOM::Element object conforming to
#     http://www.openarchives.org/OAI/1.1/OAI_Identify
#   - undef on errors
# description:
#   serves Identify request
#   this function knows about olac database
## 
sub serve_Identify {
    my ($self, $request) = @_;
    my $doc = XML::DOM::Parser->new->parsefile($self->{idxml});

    # OAI-PMH/reponseDate
    $doc->getElementsByTagName("responseDate")->item(0)->addText(&date_time);

    # OAI-PMH/request
    set_request($doc->getElementsByTagName("request")->item(0), $request);

    # OAI-PMH/Identify/baseURL
    $doc->getElementsByTagName("baseURL")->item(0)->
	addText($request->{baseURL});

    # OAI-PMH/Identify/earliestDatestamp
    $doc->getElementsByTagName("earliestDatestamp")->item(0)->
	addText($self->{db}->get_earliestDatestamp());

    return $doc;
}


sub serve_ListIdentifiers {
    my ($self, $request) = @_;

    my ($doc, $li, $h, $i, $d);
    # ListIdentifiers {
    #   header {
    #     identifier+
    #     datestamp
    #   }
    #   resumptionToken?
    # }

    my @error_code_list = ();
    unless ($request->{metadataPrefix}) {
	push @error_code_list, "badArgument";
    }
    if ($request->{from}) {
	unless ($request->{from} =~ /^\d{4}-\d{2}-\d{2}$/) {
	    push @error_code_list, "badArgument";
	}
    }
    if ($request->{until}) {
	unless ($request->{until} =~ /^\d{4}-\d{2}-\d{2}$/) {
	    push @error_code_list, "badArgument";
	}
    }
    unless ($request->{metadataPrefix} eq "olac" ||
	    $request->{metadataPrefix} eq "olac_display" ||
	    $request->{metadataPrefix} eq "oai_dc") {
	push @error_code_list, "cannotDisseminateFormat";
    }
    if ($request->{set}) {
	push @error_code_list, "noSetHierarchy";
    }
    if (@error_code_list) {
	return create_error2(\@error_code_list, $request);
    }

    my $list = $self->{db}->getTable($request);
    # identifier dateStamp
    # ---------- ---------
    #     .....

    unless (@$list) {
	return create_error("noRecordsMatch", $request);
    }

    ($doc, $li) = get_template($request);

    foreach $row (@$list) {
	$h = $li->appendChild($doc->createElement("header"));
        $i = $h->appendChild($doc->createElement("identifier"));
        $d = $h->appendChild($doc->createElement("datestamp"));
	$i->addText($row->[0]);
        $d->addText($row->[1]);
    }

    return $doc;
}

sub serve_ListMetadataFormats {
    my ($self, $request) = @_;

    if ($request->{identifier} && !$self->{db}->getTable($request)) {
	return create_error("idDoesNotExist", $request);
    }
	
    # ListMetadataFormats {
    #   metadataFormat+ {
    #     metadataPrefix
    #     schema
    #     metadataNamespace
    #   }
    # }

    my ($doc, $lmf, $mf, $mp, $s, $mn, $text);

    ($doc, $lmf) = get_template($request);

    $mf = $lmf->appendChild($doc->createElement("metadataFormat"));
    $mp = $mf->appendChild($doc->createElement("metadataPrefix"));
    $mp->addText("olac");
    $s = $mf->appendChild($doc->createElement("schema"));
    $s->addText("http://www.language-archives.org/OLAC/1.0/olac.xsd");
    $mn = $mf->appendChild($doc->createElement("metadataNamespace"));
    $mn->addText("http://www.language-archives.org/OLAC/1.0/");

    $mf = $lmf->appendChild($doc->createElement("metadataFormat"));
    $mp = $mf->appendChild($doc->createElement("metadataPrefix"));
    $mp->addText("olac_display");
    $s = $mf->appendChild($doc->createElement("schema"));
    $s->addText("http://www.language-archives.org/OLAC/1.0/olac.xsd");
    $mn = $mf->appendChild($doc->createElement("metadataNamespace"));
    $mn->addText("http://www.language-archives.org/OLAC/1.0/");

    $mf = $lmf->appendChild($doc->createElement("metadataFormat"));
    $mp = $mf->appendChild($doc->createElement("metadataPrefix"));
    $mp->addText("oai_dc");
    $s = $mf->appendChild($doc->createElement("schema"));
    $s->addText("http://www.openarchives.org/OAI/2.0/oai_dc.xsd");
    $mn = $mf->appendChild($doc->createElement("metadataNamespace"));
    $mn->addText("http://www.openarchives.org/OAI/2.0/oai_dc/");

    return $doc;
}

sub serve_ListRecords {
    my ($self, $request) = @_;

    my @error_code_list = ();
    unless ($request->{metadataPrefix}) {
	push @error_code_list, "badArgument";
    }
    if ($request->{from}) {
	unless ($request->{from} =~ /^\d{4}-\d{2}-\d{2}$/) {
	    push @error_code_list, "badArgument";
	}
    }
    if ($request->{until}) {
	unless ($request->{until} =~ /^\d{4}-\d{2}-\d{2}$/) {
	    push @error_code_list, "badArgument";
	}
    }
    unless ($request->{metadataPrefix} eq "olac" ||
	    $request->{metadataPrefix} eq "olac_display" ||
	    $request->{metadataPrefix} eq "oai_dc") {
	push @error_code_list, "cannotDisseminateFormat";
    }
    if ($request->{set}) {
	push @error_code_list, "noSetHierarchy";
    }
    if (@error_code_list) {
	return create_error2(\@error_code_list, $request);
    }

    # ListRecords {
    #   record+ {
    #     header {
    #       identifier
    #       datestamp
    #     }
    #     metadata? {
    #       olac
    #     }
    #   }
    #   resumptionToken?
    # }

    my ($doc, $lr, $r, $h, $i, $d, $m, $o, $me);

    my ($head, $meta) = $self->{db}->getTable($request);
    # $head
    # identifier dateStamp Item_ID
    # ---------- --------- -------
    #      ....      ....      ..
    #       ...      ....      ..
    #
    # $meta
    # tag lang content ext_id code ext_lbl code_lbl Item_ID
    # --- ---- ------- ------ ---- ------- -------- -------
    #  0    1     2       3     4     5        6       7
    #

    unless (scalar(@$head)) {
	return create_error("noRecordsMatch", $request);
    }

    ($doc, $lr) = get_template($request);

    my ($get_container, $get_metadata) =
	@{$self->{mdata_processors}{$request->{metadataPrefix}}};

    my $mcount = 0;
    foreach $item (@$head) {

	$r = $lr->appendChild($doc->createElement("record"));
	$h = $r->appendChild($doc->createElement("header"));
	$i = $h->appendChild($doc->createElement("identifier"));
	$i->addText($item->[0]);
	$d = $h->appendChild($doc->createElement("datestamp"));
	$d->addText($item->[1]);

	$m = $r->appendChild($doc->createElement("metadata"));
	$o = $m->appendChild($get_container->($doc));
	while ($mcount < scalar(@$meta) and
	       $meta->[$mcount]->[7] eq $item->[2]) {
	    my $row = $meta->[$mcount++];
	    $o->appendChild($get_metadata->($self, $doc, $row));
	}
    }
    
    return $doc;
}

sub serve_ListSets {
    my ($self, $request) = @_;

    # ListSets {
    #   set+ {
    #     setSpec
    #     setName
    #   }
    #   resumptionToken?
    # }

    return create_error("noSetHierarchy", $request);
}

sub serve_Query {
    my ($self, $request) = @_;

    if ($request->{elements} eq undef || $request->{elements} =~ /[^0-9]+/) {
        return create_error("badArgument", $request);
    }

    # ListRecords {
    #   record+ {
    #     header {
    #       identifier
    #       datestamp
    #     }
    #     metadata? {
    #       olac
    #     }
    #   }
    #   resumptionToken?
    # }

    my ($doc, $lr, $r, $h, $i, $d, $m, $o, $me);

    my ($head, $meta) = $self->{db}->getTable($request);
    if ($head eq undef && $meta eq undef) {
        return create_error("badArgument",  $request);
    }
    # $head
    # identifier dateStamp Item_ID
    # ---------- --------- -------
    #      ....      ....     ....
    #       ...      ....     ....
    #
    # $meta
    # tag lang content ext_id code ext_lbl code_lbl
    # --- ---- ------- ------ ---- ------- --------
    #  0    1     2       3     4     5        6
    #

    unless (scalar(@$head)) {
	return create_error("noRecordsMatch", $request);
    }

    ($doc, $lr) = get_template($request);
    $lr->setTagName("ListRecords");
    $request->{metadataPrefix} = "olac";

    my ($get_container, $get_metadata) =
	@{$self->{mdata_processors}{$request->{metadataPrefix}}};

    foreach $item (@$head) {

	$r = $lr->appendChild($doc->createElement("record"));
	$h = $r->appendChild($doc->createElement("header"));
	$i = $h->appendChild($doc->createElement("identifier"));
	$i->addText($item->[0]);
	$d = $h->appendChild($doc->createElement("datestamp"));
	$d->addText($item->[1]);

	$m = $r->appendChild($doc->createElement("metadata"));
	$o = $m->appendChild($get_container->($doc));

	my $mdata = shift @$meta;
	map { $o->appendChild($get_metadata->($self, $doc, $_)) } @$mdata;
    }

    delete $request->{metadataPrefix};
    return $doc;
}

sub get_mdata_container_olac {
    my $doc = shift;
    my $c = $doc->createElement("olac:olac");
    set_olac_atts($c);
    return $c;
}

sub get_mdata_container_oaidc {
    my $doc = shift;
    my $c = $doc->createElement("oai_dc:dc");
    set_oaidc_atts($c);
    return $c;
}

sub get_mdata_olac {
    my ($self, $doc, $row) = @_;
    # $row : 0=tag, 1=lang, 2=content, 3=ext_id, 4=code
    my $tag = $row->[0];
    my $dctag = $self->{dctag}{$tag};
    my $me;
    if ($tag eq $dctag) {
	$me = $doc->createElement("dc:$tag");
    } else {
	$me = $doc->createElement("dcterms:$tag");
    }
    $row->[1] && $me->setAttribute('xml:lang', $row->[1]);
    $row->[2] && $me->addText($row->[2]);
    my ($ns,$tt) = @{$self->{extdb}{$row->[3]}};
    if ($ns eq 'http://www.language-archives.org/OLAC/1.0/') {
	$me->setAttribute('xsi:type', "olac:$tt");
	$row->[4] && $me->setAttribute('olac:code', $row->[4]);
    }
    elsif ($ns eq 'http://purl.org/dc/terms/') {
	$me->setAttribute('xsi:type', "dcterms:$tt");
    }
    return $me;
}

sub get_mdata_olac_display {
    my ($self, $doc, $row) = @_;
    # $row : 0=tag,     1=lang,     2=content, 3=ext_id, 4=code,
    #        5=ext_lbl, 6=code_lbl
    my $tag = $row->[0];
    my $dctag = $self->{dctag}{$tag};
    my $me;
    if ($tag eq $dctag) {
	$me = $doc->createElement("dc:$tag");
    } else {
	$me = $doc->createElement("dcterms:$tag");
    }
    $row->[1] && $me->setAttribute('xml:lang', $row->[1]);
    my $content = '';
    my $padding = '';
    my ($ns,$tt) = @{$self->{extdb}{$row->[3]}};
    if ($ns eq 'http://www.language-archives.org/OLAC/1.0/') {
	$me->setAttribute('xsi:type', "olac:$tt");
	if ($row->[4]) {
	    $me->setAttribute('olac:code', $row->[4]);
	    $content = "[$row->[5] = $row->[6]]";
	} else {
	    $content = "[$row->[5]]";
	}
	$padding = ' ';
    }
    elsif ($ns eq 'http://purl.org/dc/terms/') {
	$me->setAttribute('xsi:type', "dcterms:$tt");
	$content = "[$row->[5]]";
	$padding = ' ';
    }
    if ($row->[2]) {
	$content .= $padding . $row->[2];
    }
    $content && $me->addText($content);
    return $me;
}

sub get_mdata_oaidc {
    my ($self, $doc, $row) = @_;
    # $row : 0=tag,     1=lang,     2=content, 3=ext_id, 4=code,
    #        5=ext_lbl, 6=code_lbl
    my $tag = $row->[0];
    my $dctag = $self->{dctag}{$tag};
    my $me = $doc->createElement("dc:$dctag");
    $row->[1] && $me->setAttribute('xml:lang', $row->[1]);
    my $content = '';
    my $padding = '';
    my ($ns,$tt) = @{$self->{extdb}{$row->[3]}};
    if ($ns eq 'http://www.language-archives.org/OLAC/1.0/') {
	my $code = $row->[4];
	if ($code) {
	    $content = "[$row->[5] = $row->[6]]";
	} else {
	    $content = "[$row->[5]]";
	}
	$padding = ' ';
    }
    elsif ($ns eq 'http://purl.org/dc/terms/') {
	$content = "[$row->[5]]";
	$padding = ' ';
    }
    if ($row->[2]) {
	$content .= $padding . $row->[2];
	$padding = ' ';
    }
    if ($tag ne $dctag) {
	$content .= $padding . "[$tag]";
    }
    $content && $me->addText($content);
    return $me;
}

1;
