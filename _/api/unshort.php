<?php
require_once(dirname(__FILE__) . '/.utils.inc.php');

$uri = end(array_filter(explode('/', $_SERVER['REQUEST_URI'])));

$db = Utils::init_database();
$stmt = $db->prepare('SELECT `url` FROM `ncuone` WHERE `id` = ?');
$stmt->execute(array(Utils::to_base10($uri)));
$data = $stmt->fetch();
if ($data === false) {
	header("HTTP/1.1 302 Found");
	header("Location: " . Utils::HOST);
	@exit();
}
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $data['url']);
