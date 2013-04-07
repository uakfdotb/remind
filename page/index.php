<html>
<head>
<title>Remind</title>

<script src="web/jquery.js"></script>
<script src="web/jstz.js"></script>
</head>

<body>
<h1>Remind</h1>

<? if(isset($message) && $message != "") { ?>
<p><b><i><?= htmlspecialchars($message) ?></i></b></p>
<? } ?>

<p>Welcome to Remind! Use the forms below to manage your reminders and/or your account.</p>

<h3>Create a new reminder</h3>

<p>Enter a subject and body for an e-mail to create a new reminder email to be sent at a specific time. The timezone is autodetected (if Javascript is enabled) but you may change it.</p>

<form method="post" action="index.php" />
<input type="hidden" name="action" value="create" />
Subject: <input type="text" name="subject" />
<br />Time: <input type="text" name="time" /> (most formats are accepted; ex: Jan 1, 2013 5:00 pm)
<br />Time zone: <input id="tztarget1" type="text" name="timezone" />
<br />Message: <textarea rows="6" cols="100" name="content"></textarea>
<br />Repeat: <select name="repeat">
	<option value="" selected>Off</option>
	<option value="hour">Hourly</option>
	<option value="day">Daily</option>
	<option value="week">Weekly</option>
	<option value="month">Monthly</option>
	</select>
<br /><input type="submit" value="Create reminder" />
</form>

<h3>Account management</h3>

<p>Change your password using the form below.</p>

<form method="post" action="index.php" />
<input type="hidden" name="action" value="password" />
Old password: <input type="password" name="password_old" />
<br />New password: <input type="password" name="password_new" /> leave blank to keep current password
<br />Confirm new password: <input type="password" name="password_conf" />
<br />Time zone: <input id="tztarget2" type="text" name="timezone" />
	<? if(isset($_SESSION['timezone'])) { ?>current timezone: <?= htmlspecialchars($_SESSION['timezone']) ?><? } ?>
<br /><input type="submit" value="Change password / timezone" />
</form>

<h3>Manage reminders</h3>

<p>Manage the reminders you have already set up below (only unsent reminders are saved).</p>

<table>
<tr>
	<th>Time</th>
	<th>Subject</th>
	<th>Delete</th>
</tr>
<? foreach($reminders as $reminder) { ?>
<tr>
	<td><?= date('j M Y H:i:s T', $reminder['time']) ?></td>
	<td><?= htmlspecialchars($reminder['subject']) ?></td>
	<td>
		<form method="post" action="index.php">
		<input type="hidden" name="action" value="delete" />
		<input type="hidden" name="id" value="<?= $reminder['id'] ?>" />
		<input type="submit" value="Delete" />
		</form>
	</td>
</tr>
<? } ?>
</table>

<p><a href="index.php?logout">Click here to logout.</a></p>

<script>
$().ready(function() {
	$("#tztarget1").val(jstz.determine().name());
	$("#tztarget2").val(jstz.determine().name());
});
</script>

</body>
</html>
