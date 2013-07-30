<?php

$fd = popen("lockf /tmp/olac-syschk.lock /home/olac/bin/syschk.sh", "r");
while (!feof($fd)) {
	$line = fgets($fd);
	echo $line."<br>";
	flush();
	ob_flush();
}

?>
