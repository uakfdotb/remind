<?php

$config = array();

# email settings

$config['email_address'] = 'remind@example.com';
$config['email_scheduling'] = false; //whether to enable scheduling by email

# imap settings, if email_scheduling = true
$config['imap_hostname'] = '';
$config['imap_username'] = 'remind@example.com';
$config['imap_password'] = 'remind';

# database settings

$config['db_name'] = 'remind';
$config['db_host'] = 'localhost';
$config['db_username'] = 'root';
$config['db_password'] = '';

?>
