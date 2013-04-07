<?php

include("config.php");
include("include/common.php");
include("include/session.php");
include("include/dbconnect.php");

if(isset($_GET['logout'])) {
	session_unset();
	get_page("index_login", array('message' => 'You are now logged out.'));
} else if(isset($_SESSION['id'])) {
	//user is logged in
	$message = "";
	
	if(isset($_REQUEST['message'])) {
		$message = htmlspecialchars($_REQUEST['message']);
	}
	
	if(isset($_POST['action'])) {
		if($_POST['action'] == "create" && isset($_POST['time']) && isset($_POST['timezone']) && isset($_POST['subject']) && isset($_POST['content']) && isset($_POST['repeat'])) {
			$result = createReminder($_SESSION['id'], $_POST['time'], $_POST['timezone'], $_POST['subject'], $_POST['content'], $_POST['repeat']);
			
			if($result === true) {
				$message = "Reminder added successfully!";
			} else {
				$message = "Error while adding reminder: " . $result . ".";
			}
		} else if($_POST['action'] == "delete" && isset($_POST['id'])) {
			deleteReminder($_SESSION['id'], $_POST['id']);
			$message = "Reminder has been deleted!";
		} else if($_POST['action'] == "password" && isset($_POST['password_old']) && isset($_POST['password_new']) && isset($_POST['password_conf']) && isset($_POST['timezone'])) {
			$result = passwordChange($_SESSION['id'], $_POST['password_old'], $_POST['password_new'], $_POST['password_conf'], $_POST['timezone']);
			
			if($result === true) {
				$message = "Preferences changed successfully.";
			} else {
				$message = "Error while changing password: " . $result . ".";
			}
		}
		
		header('Location: index.php?message=' . urlencode($message));
	}
	
	if(isset($_SESSION['timezone'])) {
		@date_default_timezone_set($_SESSION['timezone']);
	}
	
	$reminders = getReminders($_SESSION['id']);
	get_page("index", array('reminders' => $reminders, 'message' => $message));
} else if(isset($_POST['action'])) {
	if($_POST['action'] == "register" && isset($_POST['email'])) {
		$result = register($_POST['email']);
		
		if($result === true) {
			get_page("index_login", array('message' => 'Your account has been registered! Check your email for the login details.'));
		} else if($result == -2) {
			get_page("index_login", array('message' => 'Error: invalid email address!'));
		} else if($result == -1) {
			get_page("index_login", array('message' => 'Error: email address in use!'));
		}
	} else if($_POST['action'] == "login" && isset($_POST['email']) && isset($_POST['password'])) {
		$result = login($_POST['email'], $_POST['password']);
		
		if($result !== false) {
			$_SESSION['id'] = $result;
			header('Location: index.php');
		} else {
			get_page("index_login", array('message' => 'Error: login failed.'));
		}
	}
} else {
	get_page("index_login", array());
}

?>
