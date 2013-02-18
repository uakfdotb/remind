<?php

include("config.php");
include("include/common.php");
include("include/session.php");
include("include/dbconnect.php");

$time = time();
$result = mysql_query("SELECT reminders.id, users.email, reminders.title, reminders.content FROM reminders LEFT JOIN users ON reminders.user_id = users.id WHERE reminders.time < $time");

while($row = mysql_fetch_array($result)) {
	remind_mail("[Remind] " . $row[2], $row[3], $row[1]);
	mysql_query("DELETE FROM reminders WHERE id = '" . $row[0] . "'");
}

?>
