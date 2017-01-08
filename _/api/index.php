<?php
require_once(dirname(__FILE__) . '/.utils.inc.php');
require_once(dirname(__FILE__) . '/.init.inc.php');

$type = $_POST['type'];
$result = array();

if ($type == 'short_it') {
	$request = array('url' => $_POST['url']);
	if (substr($request['url'], 0, 4) != "http")
		$request['url'] = 'http://' . $request['url'];

	$shorten = null;
	if (Utils::check_url($request['url'])) {
		$db = Utils::init_database();
		$shorten = Utils::add_record($db, $url);
	}

	$result = array(
		'status' => $shorten === null ? 'failed' : 'ok',
		'url' => Utils::HOST . $shorten,
		'code' => $shorten
	);
} else {
	$result['status'] = 'failed';
	$result['message'] = 'invalid type';
}

echo json_encode($result);
