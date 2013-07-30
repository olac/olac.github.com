use strict;

package OLAC::Utils;
use LWP;
use Net::HTTP;
@OLAC::Utils::EXPORT = qw(LWP_fetch trim);

# fetch a file specified by $url, and store it in $$str
# return undef on success, error message on failure
sub LWP_fetch
{
    my ($url, $str) = @_;

    my $ua = LWP::UserAgent->new;
    $ua->agent("ovester");
    my $req = HTTP::Request->new(GET => $url);
    my $res = $ua->request($req);
    if ($res->is_success) {
	$$str = $res->content;
	return undef;
    } else {
	return Error->new
	    ('fetch error', "can't download from <$url>", $res->message);
    }
}

sub trim {
    $_[0] =~ s/^\s*//;
    $_[0] =~ s/\s*$//;
    return $_[0];
}

#-------------------- HTTPStream -------------------------------------
#
#
#
#---------------------------------------------------------------------

package OLAC::HTTPStream;
@OLAC::HTTPStream::ISA = ('Net::HTTP');

sub new {
    my ($class, $url) = @_;
    my ($host, $path) = ('', '');
    my ($code, $mess, %head);
    my $self = {};
    bless $self, $class;

    do {
        if ($url =~ '^http://([^/]+)(.*)$') {
            $host = $1;
            $path = $2 ? $2 : '/';
        } else {
            $self->{err} = OLAC::Error->new('connection error',
					    "bad url <$url>",
					    $@);
            return $self;
        }

        $self = $class->SUPER::new(Host=>$host);
        unless ($self) {
	    $self->{err} = OLAC::Error->new('connection error',
					    "can't connect $host",
					    $@);
	    return $self;
        }
        $self->write_request(GET=>$path, 'User-Agent'=>'ovester');
        ($code, $mess, %head) = $self->read_response_headers;
    } while ($url = $head{'Location'});
    
    if ($code == 200) {
        return $self;
    } else {
        return;
    }
}

sub read {
    my $self = $_[0];
    my $buf = \$_[1];
    my $len = $_[2];
    $self->read_entity_body($$buf, $len);
}


#-------------------- Error ------------------------------------------
#
#
#
#---------------------------------------------------------------------

package OLAC::Error;

sub new {
    my ($class, $type, $msg, $msg2) = @_;
    my $self = { type => $type,
		 msg  => $msg,
		 msg2 => $msg2 };
    bless $self, $class;
    $self->log;
    return $self;
}

sub log {
    my $self = shift;
    if ($self->{msg2}) {
	printf "[$self->{type}] $self->{msg}\n($self->{msg2})\n";
    } else {
	printf "[$self->{type}] $self->{msg}\n";
    }
}

1;
