<?php
class Utils {
	const HOST = 'https://ncu.one/';
	
	const BASE62_HASH = "1QqAazZswCdDvEfYrT4R7F6V5BtUgHbnNMmKjOuW0X9G8iIoJpPLlk23eSxcyh";
	public static function to_base62($base10) {
		$base10 = (int)$base10;
		$result = "";
		while ($base10 > 0) {
			$result = self::BASE62_HASH[$base10 % 62] . $result;
			$base10 = floor($base10 / 62);
		}
		return $result;
	}
	public static function to_base10($base62) {
		$result = 0;
		while ($base62) {
			$result = $result * 62 + strrpos(self::BASE62_HASH, $base62[0]);
			$base62 = substr($base62, 1);
		}
		return $result;
	}

	const URL_REGEXP = "/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
	public static function check_url($url) {
		return preg_match(self::URL_REGEXP, $url);
	}

	const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
	public static function recaptcha_verify($secret, $response) {
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
		$result = file_get_contents(self::RECAPTCHA_VERIFY_URL, false, $context);
		if ($result === false) { // cannot connect
			return array('success' => false);
		} else {
			$result = json_decode($result);
			return array('success' => $result->success);
		}
	}

	public static function init_database() {
		$host = $_ENV['DB_HOST'];
		$user = $_ENV['DB_USER'];
		$passwd = $_ENV['DB_PASSWD'];
		$database = $_ENV['DB_DATABASE'];
		return new PDO('mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8', $user, $passwd);
	}

	public static function fetch_shorten_by_url($db, $url) {
		$stmt = $db->prepare('SELECT `id` FROM `ncuone` WHERE `url` = ? LIMIT 1');
		$stmt->execute(array($url));
		$id = $stmt->fetch();
		if ($id === false)
			return null;
		return self::to_base62($id['id']);
	}

	public static function add_record($db, $url) {
		// check if it has been shortened before
		$shorten = self::fetch_shorten_by_url($db, $url);
		if ($shorten !== null)
			return $shorten;
		
		// guess the shorten code length of this new record
		$guess_id = (function ($db) {
			$stmt = $db->prepare('SELECT MAX(`id`) as id FROM `ncuone`'); // count the max id in table
			$stmt->execute();
			$id = $stmt->fetch()['id'];
			return self::to_base62($id + 1);
		})($db);

		if (strlen($url) <= strlen(self::HOST) + strlen($guess_id))
			return null;

		$stmt = $db->prepare('INSERT INTO `ncuone`
			(`id`, `url`, `client_ip`, `forwarded_for`, `remote_addr`, `http_via`, `created_at`) VALUES 
			(NULL, ?, ?, ?, ?, ?, NULL)');
		if (!$stmt->execute(array($url, $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_VIA']))) {
			error_log("insert error: " . $stmt->errorInfo()[2]);
			return null;
		}
		return self::to_base62($db->lastInsertId());
	}
}
