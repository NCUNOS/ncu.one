<?php
const host = 'https://ncu.one/';

// convert a num from base 10 to base 62
function toBase_62($num) {
	// $base = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	$base = "1QqAazZswCdDvEfYrT4R7F6V5BtUgHbnNMmKjOuW0X9G8iIoJpPLlk23eSxcyh";
	$res = "";
	while ($num) {
		$res = $base[$num % 62] . $res;
		$num = floor($num / 62);
	}
	return $res;
}

// url checking
const URL_REGEXP = "/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
function check_url($url) {
	return preg_match(URL_REGEXP, $url);
}

// reCAPTCHA
const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
function recaptcha_verify($secret, $response) {
	$data = http_build_query(array('secret' => $secret, 'response' => $response));
	$options = array(
		'http' => array(
			'header'  => implode("\r\n", array(
				"Content-type: application/x-www-form-urlencoded",
				sprintf("Content-Length: %d", strlen($data))
			)),
			'method'  => 'POST',
			'content' => $data,
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents(RECAPTCHA_VERIFY_URL, false, $context);
	if ($result === false) { // cannot connect
		return array('success' => false);
	} else {
		$result = json_decode($result);
		return array('success' => $result->success);
	}
}

// database
function init_database() {
	$host = $_ENV['DB_HOST'];
	$user = $_ENV['DB_USER'];
	$passwd = $_ENV['DB_PASSWD'];
	$database = $_ENV['DB_DATABASE'];
	return new PDO('mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8', $user, $passwd);
}

$request = array('url' => $_POST['url'], 'token' => $_POST['captchaToken']);
if (check_url($request['url']))
	$result = recaptcha_verify($_ENV['grecaptcha_secret'], $request['token']);
else
	$result = array('success' => false);

if ($result['success'] === true) {
	$db = init_database();

	// check if it has been shortened before
	$result['shorten'] = (function ($db, $url) {
		$stmt = $db->prepare('SELECT `id` FROM `ncuone` WHERE `url` = ?');
		$stmt->execute(array($url));
		$id = $stmt->fetch();
		if ($id === false)
			return null;
		return toBase_62($id);
	})($db, $request['url']);

	// generate a short url if we could not find it in table
	// then insert it into table
	if ($result['shorten'] === null) {
		$guess_id = (function ($db) {
			$stmt = $db->prepare('SELECT MAX(`id`) FROM `ncuone`'); // count the max id in table
			$stmt->execute();
			$id = (int)$stmt->fetch();
			return toBase_62($id + 1);
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
				return toBase_62($db->lastInsertId());
			})($db, $request['url']);
		}
	}

	if ($result['shorten'] === null)
		$result['success'] = false;
}

echo json_encode(array(
	'status' => $result['success'] ? 'ok' : 'failed',
	'url' => host . $result['shorten'],
	'code' => $result['shorten']
));
