#! /usr/bin/env perl

use OLAC::Harvester;
use Getopt::Std;

sub usage {
    print <<EOF;

usage: $0 [-hp] [-l <archive list>] -d <db info file>
  -h  show this message
  -p  purge database after harvest

  <archive list> is an xml file containing archive list, e.g.
    -----
    <?xml version="1.0" encoding="UTF-8"?>
    <archives number="27">
      <archive id="myrepo.example.com" baseURL="http://example.com/myrepo"/>
      <archive id="olac.ex.org" baseURL="http://ex.org/cgi-bin/olac.cgi"/>
      ...
    </archives>
    -----

  <db info file> should contain exactly 3 lines:
    first line - database name,
    second line - user name,
    third line - password.

EOF
    exit 1;
}

sub get_options {
    # set signal handler for getopts
    # print usage on unknown options
    $SIG{__WARN__} = 'usage';

    # get options
    my %opt;
    getopts('hvpl:d:', \%opt);

    # restore default signal handler
    $SIG{__WARN__} = 'DEFAULT';

    # check garbage (not checked by getopts) options
    if (scalar(@ARGV) || $opt{h} || !$opt{d}) {
	usage();
    }

    return \%opt;
}

sub _s_arc_list {
    my ($expat, $tag, %att) = @_;
    if ($tag eq 'archive') {
        $url = $att{baseURL};
        #$url =~ s/http:\/\//http:\/\/www.language-archives.org\/tools\/eth15filter.php\//;
	push @$_arc_list, [$att{id},$url];
    }
}
    
sub get_arc_list {
    my $file = shift;
    my $parser = XML::Parser->new(Handlers => {Start=>\&_s_arc_list});
    $_arc_list = [];
    if ($file) {
	$parser->parsefile($file);
    } else {
	my $strm = new OLAC::HTTPStream("http://www.language-archives.org/register/archive_list.php4");
	$parser->parse($strm);
    }
    return $_arc_list;
}

sub main {
    my $opt = get_options();
    my $arc_list = get_arc_list $opt->{l};
    my $ovester = new OLAC::Ovester($opt->{d}, $arc_list);
    $ovester->harvest;
    if ($opt->{p}) {
	print "****************************************\n";
	print "Purging...\n";
	$ovester->purge;
	print "\n";
    }
}

main;
