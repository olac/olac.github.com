#! /usr/bin/env perl

use OLAC::DB;
use Getopt::Std;
use XML::Parser;

sub usage {
    print <<EOF;

usage: $0 -s <database schema> -d <db info file>

EOF
    exit 1;
}

sub get_options {
    my %opt;

    $SIG{__WARN__} = 'usage';
    getopts('s:d:', \%opt);
    $SIG{__WARN__} = 'DEFAULT';

    usage if scalar(@ARGV) or !$opt{d};
    return \%opt;
}

sub switch_handlers {
    ($start, $end, $char) = @_;
}

#----- main handlers
sub _s_main {
    my ($expat, $tag, %att) = @_;
    
    if ($tag eq 'include') {
        print "*** ".$expat->base();
        $base = $att{'schemaLocation'};
        return unless ($base);
        $base=$expat->base()+"$base" if base !~ m/^http/;
        print "*** $base\n";
        my $strm = new OLAC::HTTPStream($att{'schemaLocation'});
        unless ($strm) {
            drop_all;
            exit 1;
        }
        $_s_tmp, $_e_tmp, $_c_tmp = $start, $end, $char;
        switch_handlers(\&_s_main, \&_e_main, \&_c_main);
        $parser->setHandlers(Start => sub {$_[1]=~s/^.*://;$start->(@_);},
                             End   => sub {$_[1]=~s/^.*://;$end->(@_);},
                             Char  => sub {$char->(@_);});
        $parser->parse($strm);
        $start, $end, $char = $_s_tmp, $_e_tmp, $_c_tmp;
        return;
    }

    if ($tag eq 'olac-extension') {
	switch_handlers(\&_s_ext, \&_e_ext, \&_c_ext);
	$ext_record = {SchemaURL=>$xsdurl, Extension_ID=>$ext_id};
	return;
    }

    if ($tag eq 'simpleType') {
	switch_handlers(\&_s_code, \&_e_code, \&_c_code);
	return;
    }
}
sub _e_main {}
sub _c_main {}

#----- olac-extension handlers
sub _s_ext {
    undef $saved_content;
}
sub _e_ext {
    my $tag = $_[1];
    if ($tag eq 'olac-extension') {
	$ext_record->{Type} = $ext_record->{shortName};
	$ext_record->{DefiningSchema} = $ext_record->{SchemaURL};
	$ext_record->{NS} = 'http://www.language-archives.org/OLAC/1.0/';
	$ext_record->{NSPrefix} = 'olac';
	$ext_record->{NSSchema} = 'http://www.language-archives.org/OLAC/1.0/olac.xsd';
	$ext_record->{Label} = $ext_record->{Type} unless $ext_record->{Label};
	delete $ext_record->{shortName};
	delete $ext_record->{SchemaURL};
	$db->insert('EXTENSION', $ext_record);
	if ($db->{err}) {
	    $db->{err}->log;
	    print "db error -- please try later\n";
	    drop_all();
	    exit 1;
	}
	switch_handlers(\&_s_main, \&_e_main, \&_c_main);
	return;
    }
    if ($ext_record->{$tag}) {
	$ext_record->{$tag} .= ",$saved_content";
    } else {
	$ext_record->{$tag} = $saved_content;
    }
}
sub _c_ext {
    $saved_content .= $_[1];
}

#----- code handlers
sub _s_code {
    my ($expat, $tag, %att) = @_;
    my $v = $att{value};
    if ($v) {
	$db->insert('CODE_DEFN',
		    {Extension_ID => $ext_id,
		     Code         => $v,
		     Label        => $v});
	if ($db->{err}) {
	    $db->print_err;
	    print "db error -- please try later\n";
	    drop_all();
	    exit 1;
	}
    }
}
sub _e_code {
    switch_handlers(\&_s_main, \&_e_main, \&_c_main) if $_[1] eq 'simpleType';
}
sub _c_code {}


sub drop_all {
    $db->{dbh}->do('drop table OLAC_ARCHIVE, SCHEMA_VERSION, ARCHIVED_ITEM, ELEMENT_DEFN, METADATA_ELEM, OLAC_EXTENSION, CODE_DEFN, EXTENSION');
}


sub main {
    my $opt = get_options;

    $db = new OLAC::DB($opt->{d});
    if ($db->{err}) {
	print $db->{err} . "\n";
	exit 1;
    }

    unless (open FH, $opt->{s}) {
	print "can't open $opt->{s}\n";
	print "db initialization aborted -- please try again\n";
	exit 1;
    }

    my $str = '';
    while (<FH>) {
	$str .= $_;
    }
    $str =~ s/#[^\n]*\n//g;
    my @SQL = split /\s*;\s*/, $str;
    drop_all;
    foreach (@SQL) {
	next unless $_;
	$db->{dbh}->do($_);
	if ($db->{dbh}->errstr) {
	    print $db->{dbh}->errstr . "\n";
	    exit 1;
	}
    }

    my $parser = XML::Parser->new;
    $ext_id = 1;

    foreach $xsdurl (
	#'http://www.language-archives.org/OLAC/1.0/olac-access.xsd',
	'http://www.language-archives.org/OLAC/1.0/olac-discourse-type.xsd',
	'http://www.language-archives.org/OLAC/1.0/olac-language.xsd',
	'http://www.language-archives.org/OLAC/1.0/olac-linguistic-field.xsd',
	'http://www.language-archives.org/OLAC/1.0/olac-linguistic-type.xsd',
        'http://www.language-archives.org/OLAC/1.0/olac-role.xsd')
    {
print $xsdurl . "\n";
	my $strm = new OLAC::HTTPStream($xsdurl);
	unless ($strm) {
	    drop_all;
	    exit 1;
	}
	switch_handlers(\&_s_main, \&_e_main, \&_c_main);
	$parser->setHandlers(Start => sub {$_[1]=~s/^.*://;$start->(@_);},
			     End   => sub {$_[1]=~s/^.*://;$end->(@_);},
			     Char  => sub {$char->(@_);});
        $base = $xsdurl;
        $base =~ s![^/]+$!!;
        $parser->base($base);
	$parser->parse($strm);
	$ext_id++;
    }
}


main;

