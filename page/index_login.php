<html>
<head>
<title>Remind</title>
</head>

<body>
<h1>Remind</h1>

<? if(isset($message) && $message != "") { ?>
<p><b><i><?= htmlspecialchars($message) ?></i></b></p>
<? } ?>

<p>Remind is a service that lets you request reminder emails at a certain time. At that time an email you specify will be sent.</p>

<h2>Login</h2>

<form method="post" action="index.php">
<input type="hidden" name="action" value="login" />
Email: <input type="text" name="email" />
<br />Password: <input type="password" name="password" />
<br /><input type="submit" value="Login" />
</form>

<h2>Register</h2>

<form method="post" action="index.php">
<input type="hidden" name="action" value="register" />
Email: <input type="text" name="email" />
<input type="submit" value="Register" />
</form>
</body>
</html>
