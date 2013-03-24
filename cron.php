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

if($config['email_scheduling']) {
	$inbox = @imap_open($config['imap_hostname'], $config['imap_username'], $config['imap_password']);
	
	if($inbox !== false) {
		$emails = imap_search($inbox, 'UNSEEN');
		
		if($emails) {
			foreach($emails as $email_i) {
				$overview = imap_fetch_overview($inbox, $email_i);
				$overview = $overview[0];
				$message = imap_fetchbody($inbox, $email_i, 1.2);
				
				# delete message
				imap_delete($inbox, $email_i);
				
				if(isset($overview->to) && isset($overview->from) && isset($overview->subject)) {
					$to = imap_rfc822_parse_adrlist($overview->to, "example.com");
					$to = $to[0]->mailbox; //don't care about host, assume it's whatever it's supposed to be
					$from = imap_rfc822_parse_adrlist($overview->from, "example.com");
					$from = $from[0]->mailbox . '@' . $from[0]->host;
					
					$headers = array();
					
					if(isset($overview->message_id)) {
						$headers['In-Reply-To'] = $overview->message_id;
					}
					
					//find the user
					$user_id = getUserIdByEmail($from);
					
					if($user_id !== false) {
						if(substr($to, 0, 2) == "at" || substr($to, 0, 5) == "after") {
							if(substr($to, 0, 2) == "at") {
								$time = str_replace(".", " ", substr($to, 2));
							} else {
								$time = "+" . str_replace(array(".", "hr"), array(" ", "hour"), substr($to, 5));
							}
							
							$result = createReminder($user_id, $time, false, $overview->subject, $message);
							
							if($result !== true) {
								remind_mail("Re: " . $overview->subject, $result, $from, $headers);
							}
						}
					}
				}
			}
		}
		
		//expunge deleted messages
		imap_expunge($inbox);
	} else {
		echo "IMAP connection failed: " . imap_last_error();
	}
	
	//flush errors so they don't get outputted
	imap_errors();
	imap_alerts();
}

?>
