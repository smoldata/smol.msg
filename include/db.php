<?php

function db_setup() {

	global $db;

	$config_path = dirname(__DIR__) . '/config.php';
	if (! file_exists($config_path)) {
		die('You forgot config.php!');
	}
	if (! empty($db)) {
		return $db;
	}

	include $config_path;

	if ($db_host == 'localhost') {
		$db_host = '127.0.0.1';
	}
	$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
	try {
		$db = new PDO($dsn, $db_user, $db_pass);
	} catch (Exception $e) {
		$error = $e->getMessage();
		die("Error connecting to database: $error");
	}
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

	return $db;
}

function db_insert($tbl, $hash) {

	$db = db_setup();

	$columns = array_keys($hash);
	$columns = implode(', ', $columns);
	$placeholders = array_map(function($input) {
		return '?';
	}, $hash);
	$placeholders = implode(', ', $placeholders);
	$values = array_values($hash);

	$rsp = db_write("
		INSERT INTO $tbl
		($columns)
		VALUES ($placeholders)
	", $values);
	$rsp['insert_id'] = $db->lastInsertId();

	return $rsp;
}

function db_insert_bulk($tbl, $hashes) {

	$ok = 1;
	$insert_ids = array();
	foreach ($hashes as $hash) {
		$rsp = db_insert($tbl, $hash);
		if (! $rsp['ok']) {
			$ok = 0;
			break;
		}

		$insert_ids[] = $rsp['insert_id'];
	}

	if ($ok) {
		return array(
			'ok' => 1,
			'insert_ids' => $insert_ids,
			'row_count' => count($insert_ids)
		);
	} else {
		$rsp['insert_ids'] = $insert_ids;
		$rsp['row_count'] = count($insert_ids);
		return $rsp;
	}
}

function db_update($tbl, $hash, $where) {

	$assignments = array();
	foreach ($hash as $key => $value) {
		$assignments[] = "`$key` = ?";
	}
	$assignments = implode(', ', $assignments);
	$values = array_values($hash);

	return db_write("
		UPDATE $tbl
		SET $assignments
		WHERE $where
	", $values);
}

function db_fetch($sql, $values = null) {

	$rsp = db_query($sql, $values);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$query = $rsp['query'];
	$rsp['rows'] = $query->fetchAll();

	return $rsp;
}

function db_column($sql, $values = null, $column_num = 0) {

	$rsp = db_query($sql, $values);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$query = $rsp['query'];

	$column = array();
	while ($row = $query->fetchColumn($column_num)) {
		$column[] = $row;
	}
	$rsp['column'] = $column;

	return $rsp;
}

function db_single($sql, $values = null) {
	$rsp = db_query($sql, $values);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$query = $rsp['query'];
	$rsp['row'] = $query->fetchObject();

	return $rsp;
}

function db_value($sql, $values = null, $column_num = 0) {
	$rsp = db_query($sql, $values);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$query = $rsp['query'];
	$rsp['value'] = $query->fetchColumn($column_num);

	return $rsp;
}

function db_write($sql, $values = null) {

	$db = db_setup();

	$rsp = db_query($sql, $values);
	if (! $rsp['ok']) {
		return $rsp;
	}

	$query = $rsp['query'];
	$rsp['row_count'] = $query->rowCount();

	return $rsp;
}

function db_query($sql, $values = null) {

	$db = db_setup();

	if (empty($values)) {
		$query = $db->query($sql);
	} else {
		$query = $db->prepare($sql);
	}

	if (! $query) {
		$error = $db->errorInfo();
		$error = implode(' ', $error);
		return array(
			'ok' => 0,
			'error' => $error,
			'sql' => $sql,
			'values' => $values
		);
	}

	if (! empty($values)) {
		$query->execute($values);
	}

	$error_code = $query->errorCode();
	$ok = ($error_code == '00000');

	if ($ok) {
		return array(
			'ok' => 1,
			'query' => $query
		);
	} else {
		$error = $query->errorInfo();
		$error = implode(' ', $error);

		return array(
			'ok' => 0,
			'error' => $error,
			'sql' => $sql,
			'values' => $values
		);
	}
}
