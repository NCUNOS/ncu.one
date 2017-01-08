<?php
require_once(dirname(__FILE__) . '/.utils.inc.php');
require_once(dirname(__FILE__) . '/.init.inc.php');

$request = array('url' => $_POST['url'], 'token' => $_POST['captchaToken']);
if (substr($request['url'], 0, 4) != "http")
	$request['url'] = 'http://' . $request['url'];

if (Utils::check_url($request['url']))
	$result = Utils::recaptcha_verify($_ENV['grecaptcha_secret'], $request['token']);
else
	$result = array('success' => false);

if ($result['success'] === true) {
	$db = Utils::init_database();

	$result['shorten'] = Utils::add_record($db, $url);
	if ($result['shorten'] === null)
		$result['success'] = false;
}

echo json_encode(array(
	'status' => $result['success'] ? 'ok' : 'failed',
	'url' => Utils::HOST . $result['shorten'],
	'code' => $result['shorten']
));
