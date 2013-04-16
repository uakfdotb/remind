<?php

function getPreference($user_id, $k, $default = false) {
	$user_id = escape($user_id);
	$k = escape($k);
	
	$result = mysql_query("SELECT v FROM preferences WHERE user_id = '$user_id' AND k = '$k'");
	
	if($row = mysql_fetch_array($result)) {
		return $row[0];
	} else {
		return $default;
	}
}

function setPreference($user_id, $k, $v) {
	$user_id = escape($user_id);
	$k = escape($k);
	$v = escape($v);
	
	//update preference if it exists already
	$result = mysql_query("SELECT id FROM preferences WHERE user_id = '$user_id' AND k = '$k'");
	
	if($row = mysql_fetch_array($result)) {
		mysql_query("UPDATE preferences SET v = '$v' WHERE id = '{$row[0]}'");
	} else {
		mysql_query("INSERT INTO preferences (user_id, k, v) VALUES ('$user_id', '$k', '$v')");
	}
}

?>
