<?php
// database
function init_database() {
	$host = $_ENV['DB_HOST'];
	$user = $_ENV['DB_USER'];
	$passwd = $_ENV['DB_PASSWD'];
	$database = $_ENV['DB_DATABASE'];
	return new PDO('mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8', $user, $passwd);
}

$db = init_database();
try {
	$r = $db->query("SELECT 1 FROM `ncuone` LIMIT 1");
	if ($r === false)
		throw new Exception("error");
	echo '{"status": "already"}';
} catch (Exception $e) {
	if (!$db->query('
		SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
		SET time_zone = "+00:00";
		CREATE TABLE `ncuone` (
			`id` int(11) NOT NULL,
			`url` text NOT NULL,
			`client_ip` tinytext,
			`forwarded_for` tinytext,
			`remote_addr` tinytext,
			`http_via` tinytext,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE = utf8_general_ci;
		ALTER TABLE `ncuone`
			ADD PRIMARY KEY (`id`);
		ALTER TABLE `ncuone`
			MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
	'))
		echo '{"status": "failed", "message": ' . json_encode($db->errorInfo()) . '}';
	else
		echo '{"status": "ok"}';
}

