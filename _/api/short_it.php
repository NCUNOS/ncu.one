<?php
require_once(dirname(__FILE__) . '/.utils.inc.php');
require_once(dirname(__FILE__) . '/.init.inc.php');

$request = array('url' => $_POST['url'], 'token' => $_POST['captchaToken']);
if (Utils::check_url($request['url']))
	$result = Utils::recaptcha_verify($_ENV['grecaptcha_secret'], $request['token']);
else
	$result = array('success' => false);

if (substr($request['url'], 0, 4) != "http")
	$request['url'] = 'http://' . $request['url'];

if ($result['success'] === true) {
	$db = Utils::init_database();

	// check if it has been shortened before
	$result['shorten'] = (function ($db, $url) {
		$stmt = $db->prepare('SELECT `id` FROM `ncuone` WHERE `url` = ? LIMIT 1');
		$stmt->execute(array($url));
		$id = $stmt->fetch();
		if ($id === false)
			return null;
		return Utils::to_base62($id['id']);
	})($db, $request['url']);

	// generate a short url if we could not find it in table
	// then insert it into table
	if ($result['shorten'] === null) {
		$guess_id = (function ($db) {
			$stmt = $db->prepare('SELECT MAX(`id`) as id FROM `ncuone`'); // count the max id in table
			$stmt->execute();
			$id = $stmt->fetch()['id'];
			return Utils::to_base62($id + 1);
		})($db);

		if (strlen($request['url']) > strlen(host) + strlen($guess_id)) {
			$result['shorten'] = (function ($db, $url) {
				$stmt = $db->prepare('INSERT INTO `ncuone`
					(`id`, `url`, `client_ip`, `forwarded_for`, `remote_addr`, `http_via`, `created_at`) VALUES 
					(NULL, ?, ?, ?, ?, ?, NULL)');
				if (!$stmt->execute(array($url, $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_VIA']))) {
					error_log("insert error: " . $stmt->errorInfo()[2]);
					return null;
				}
				return Utils::to_base62($db->lastInsertId());
			})($db, $request['url']);
		}
	}

	if ($result['shorten'] === null)
		$result['success'] = false;
}

echo json_encode(array(
	'status' => $result['success'] ? 'ok' : 'failed',
	'url' => Utils::HOST . $result['shorten'],
	'code' => $result['shorten']
));
