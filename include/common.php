<?php

function string_begins_with($string, $search)
{
	return (strncmp($string, $search, strlen($search)) == 0);
}

function boolToString($bool) {
	return $bool ? 'true' : 'false';
}

function escape($str) {
	return mysql_real_escape_string($str);
}

function escapePHP($str) {
	return addslashes($str);
}

function chash($str, $salt) {
	return hash('sha512', $salt . $str);
}

function remind_mail($subject, $body, $to) { //returns true=ok, false=notok
	$config = $GLOBALS['config'];
	$from = filter_email($config['email_address']);
	$subject = filter_name($subject);
	$to = filter_email($to);
	
	$headers = "From: $from\r\n";
	$headers .= "To: $to\r\n";
	$headers .= "Content-type: text/html\r\n";
	return mail($to, $subject, $body, $headers);
}

//returns an absolute path to the include directory, without trailing slash
function includePath() {
	$self = __FILE__;
	$lastSlash = strrpos($self, "/");
	return substr($self, 0, $lastSlash);
}

//returns a relative path to the oneapp/ directory, without trailing slash
function basePath() {
	$commonPath = __FILE__;
	$requestPath = $_SERVER['SCRIPT_FILENAME'];
	
	//count the number of slashes
	// number of .. needed for include level is numslashes(request) - numslashes(common)
	// then add one more to get to base
	$commonSlashes = substr_count($commonPath, '/');
	$requestSlashes = substr_count($requestPath, '/');
	$numParent = $requestSlashes - $commonSlashes + 1;
	
	$basePath = ".";
	for($i = 0; $i < $numParent; $i++) {
		$basePath .= "/..";
	}
	
	return $basePath;
}

function get_page($page, $args = array()) {
	//let pages use some variables
	extract($args);
	$config = $GLOBALS['config'];
	
	$basePath = basePath();
	
	$style = "default";
	$stylePath = $basePath . "/style/$style";
	$style_page_include = "$stylePath/page/$page.php";
	$page_include = $basePath . "/page/$page.php";
	
	if(file_exists("$stylePath/header.php")) {
		include("$stylePath/header.php");
	}
	
	if(file_exists($style_page_include)) {
		include($style_page_include);
	} else {
		include($page_include);
	}
	
	if(file_exists("$stylePath/footer.php")) {
		include("$stylePath/footer.php");
	}
}

function isAlphaNumeric($str) {
	return ctype_alnum($str);
}

function uid($length) {
	$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
	$string = "";	

	for ($p = 0; $p < $length; $p++) {
		$string .= $characters[mt_rand(0, strlen($characters) - 1)];
	}

	return $string;
}

function validEmail($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
	  $isValid = false;
   }
   else
   {
	  $domain = substr($email, $atIndex+1);
	  $local = substr($email, 0, $atIndex);
	  $localLen = strlen($local);
	  $domainLen = strlen($domain);
	  if ($localLen < 1 || $localLen > 64)
	  {
		 // local part length exceeded
		 $isValid = false;
	  }
	  else if ($domainLen < 1 || $domainLen > 255)
	  {
		 // domain part length exceeded
		 $isValid = false;
	  }
	  else if ($local[0] == '.' || $local[$localLen-1] == '.')
	  {
		 // local part starts or ends with '.'
		 $isValid = false;
	  }
	  else if (preg_match('/\\.\\./', $local))
	  {
		 // local part has two consecutive dots
		 $isValid = false;
	  }
	  else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	  {
		 // character not valid in domain part
		 $isValid = false;
	  }
	  else if (preg_match('/\\.\\./', $domain))
	  {
		 // domain part has two consecutive dots
		 $isValid = false;
	  }
	  else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
	  {
		 // character not valid in local part unless 
		 // local part is quoted
		 if (!preg_match('/^"(\\\\"|[^"])+"$/',
			 str_replace("\\\\","",$local)))
		 {
			$isValid = false;
		 }
	  }
	  if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
	  {
		 // domain not found in DNS
		 $isValid = false;
	  }
   }
   return $isValid;
}

//filter email name
function filter_name( $input ) {
	$rules = array( "\r" => '', "\n" => '', "\t" => '', '"'  => "'", '<'  => '[', '>'  => ']' );
	$name = trim( strtr( $input, $rules ) );
	return $name;
}

//filter email address
function filter_email( $input ) {
	$rules = array( "\r" => '', "\n" => '', "\t" => '', '"'  => '', ','  => '', '<'  => '', '>'  => '' );
	$email = strtr( $input, $rules );
	return $email;
}

// BEGIN DATABASE STUFF
// todo: move to separate file when this one gets too big ;)

//true: success
//-1: email address in use
//-2: invalid email address
function register($email) {
	$email = escape(htmlspecialchars($email));
	
	//validate email address
	if(!validEmail($email)) {
		return -2;
	}
	
	//make sure email not registered
	//todo: look for duplicating cases, like additional periods?
	$result = mysql_query("SELECT COUNT(*) FROM users WHERE email = '$email'");
	$row = mysql_fetch_array($result);
	
	if($row[0] > 0) {
		return -1;
	}
	
	//make random password and hash it
	$password = uid(12);
	$salt = secure_random_bytes(32);
	$saltHex = bin2hex($salt);
	$passwordHex = chash($password, $saltHex);
	
	//input to database
	mysql_query("INSERT INTO users (email, password, salt) VALUES ('$email', '$passwordHex', '$saltHex')");
	
	//send email to user
	remind_mail("Remind account registration", "Here are the login details for your new Remind account:<br /><br />E-mail: $email<br />Password: $password<br /><br />If you did not request an account, please ignore this message (no further action is needed). Otherwise, please login and then immediately change your password. Thanks,<br />- Remind", $email);
	
	return true;
}

//integer: success, user id
//false: failure
function login($email, $password) {
	$email = escape($email);
	
	$result = mysql_query("SELECT id, password, salt FROM users WHERE email = '$email'");
	
	if($row = mysql_fetch_array($result)) {
		if($row[1] == chash($password, $row[2])) {
			return $row[0];
		} else {
			return false;
		}
	} else {
		return false;
	}
}

//true: success
//string: error message
function passwordChange($user_id, $password_old, $password_new, $password_conf) {
	if($password_new != $password_conf) {
		return "passwords do not match";
	}

	$user_id = escape($user_id);
	$result = mysql_query("SELECT password, salt FROM users WHERE id = '$user_id'");
	
	if($row = mysql_fetch_array($result)) {
		if($row[0] != chash($password_old, $row[1])) {
			return "invalid password";
		}
	} else {
		return "invalid password";
	}
	
	$salt = secure_random_bytes(32);
	$saltHex = bin2hex($salt);
	$passwordHex = chash($password_new, $saltHex);
	
	mysql_query("UPDATE users SET password = '$passwordHex', salt = '$saltHex' WHERE id = '$user_id'");
	return true;
}

//array of ('id' => reminder id, 'subject' => reminder subject, 'time' => send time)
function getReminders($user_id) {
	$user_id = escape($user_id);
	
	$result = mysql_query("SELECT id, title, time FROM reminders WHERE user_id = '$user_id'");
	$reminders = array();
	
	while($row = mysql_fetch_array($result)) {
		$reminders[] = array('id' => $row[0], 'subject' => $row[1], 'time' => $row[2]);
	}
	
	return $reminders;
}

//true: success
//string: error message
function createReminder($user_id, $time, $timezone, $subject, $content) {
	date_default_timezone_set($timezone);
	$user_id = escape($user_id);
	$time = strtotime($time);
	$subject = escape($subject);
	$content = escape($content);
	
	if($time < time()) {
		return "time needs to be in the future";
	} else if(strlen($subject) > 256) {
		return "subject is too long";
	} else if(strlen($content) > 10000) {
		return "content is too long";
	}
	
	mysql_query("INSERT INTO reminders (user_id, time, title, content) VALUES ('$user_id', '$time', '$subject', '$content')");
	return true;
}

function deleteReminder($user_id, $id) {
	$user_id = escape($user_id);
	$id = escape($id);
	mysql_query("DELETE FROM reminders WHERE user_id = '$user_id' AND id = '$id'");
}

//secure_random_bytes from https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP
/*
* The function is providing, at least at the systems tested :),
* $len bytes of entropy under any PHP installation or operating system.
* The execution time should be at most 10-20 ms in any system.
*/
function secure_random_bytes($len = 10) {
 
   /*
* Our primary choice for a cryptographic strong randomness function is
* openssl_random_pseudo_bytes.
*/
   $SSLstr = '4'; // http://xkcd.com/221/
   if (function_exists('openssl_random_pseudo_bytes') &&
       (version_compare(PHP_VERSION, '5.3.4') >= 0 ||
substr(PHP_OS, 0, 3) !== 'WIN'))
   {
      $SSLstr = openssl_random_pseudo_bytes($len, $strong);
      if ($strong)
         return $SSLstr;
   }

   /*
* If mcrypt extension is available then we use it to gather entropy from
* the operating system's PRNG. This is better than reading /dev/urandom
* directly since it avoids reading larger blocks of data than needed.
* Older versions of mcrypt_create_iv may be broken or take too much time
* to finish so we only use this function with PHP 5.3 and above.
*/
   if (function_exists('mcrypt_create_iv') &&
      (version_compare(PHP_VERSION, '5.3.0') >= 0 ||
       substr(PHP_OS, 0, 3) !== 'WIN'))
   {
      $str = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
      if ($str !== false)
         return $str;	
   }


   /*
* No build-in crypto randomness function found. We collect any entropy
* available in the PHP core PRNGs along with some filesystem info and memory
* stats. To make this data cryptographically strong we add data either from
* /dev/urandom or if its unavailable, we gather entropy by measuring the
* time needed to compute a number of SHA-1 hashes.
*/
   $str = '';
   $bits_per_round = 2; // bits of entropy collected in each clock drift round
   $msec_per_round = 400; // expected running time of each round in microseconds
   $hash_len = 20; // SHA-1 Hash length
   $total = $len; // total bytes of entropy to collect

   $handle = @fopen('/dev/urandom', 'rb');
   if ($handle && function_exists('stream_set_read_buffer'))
      @stream_set_read_buffer($handle, 0);

   do
   {
      $bytes = ($total > $hash_len)? $hash_len : $total;
      $total -= $bytes;

      //collect any entropy available from the PHP system and filesystem
      $entropy = rand() . uniqid(mt_rand(), true) . $SSLstr;
      $entropy .= implode('', @fstat(@fopen( __FILE__, 'r')));
      $entropy .= memory_get_usage();
      if ($handle)
      {
         $entropy .= @fread($handle, $bytes);
      }
      else
      {	
         // Measure the time that the operations will take on average
         for ($i = 0; $i < 3; $i ++)
         {
            $c1 = microtime(true);
            $var = sha1(mt_rand());
            for ($j = 0; $j < 50; $j++)
            {
               $var = sha1($var);
            }
            $c2 = microtime(true);
     $entropy .= $c1 . $c2;
         }

         // Based on the above measurement determine the total rounds
         // in order to bound the total running time.
         $rounds = (int)($msec_per_round*50 / (int)(($c2-$c1)*1000000));

         // Take the additional measurements. On average we can expect
         // at least $bits_per_round bits of entropy from each measurement.
         $iter = $bytes*(int)(ceil(8 / $bits_per_round));
         for ($i = 0; $i < $iter; $i ++)
         {
            $c1 = microtime();
            $var = sha1(mt_rand());
            for ($j = 0; $j < $rounds; $j++)
            {
               $var = sha1($var);
            }
            $c2 = microtime();
            $entropy .= $c1 . $c2;
         }
            
      }
      // We assume sha1 is a deterministic extractor for the $entropy variable.
      $str .= sha1($entropy, true);
   } while ($len > strlen($str));
   
   if ($handle)
      @fclose($handle);
   
   return substr($str, 0, $len);
}

?>
