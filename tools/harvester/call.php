<?php
ignore_user_abort();
include('functions.php');
include('JSON.php');

$json = new Services_JSON();

$P = array();
foreach ($_POST as $k => $v) {
  if (is_array($v)) {
    $arr = array();
    foreach ($v as $vv) {
      $arr[] = urldecode($vv);
    }
    $P[$k] = $arr;
  } else {
    $P[$k] = urldecode($v);
  }
}

if ($P['func']) {
  header("Content-type: plain/text");

  switch ($P['func']) {
    
  case 'harvest':
    echo $json->encode(harvest($P['token']));
    exit; break;

  case 'register_request':
    echo $json->encode(register_request($P['repoid']));
    exit; break;

  case 'ping':
    echo $json->encode(TRUE);
    exit; break;
  }
}
?>
