<?php
"This file contains the configuration for sending emails using SMTP. It defines constants for the SMTP host, user, password, port, application name, and base URL. These constants are used throughout the application to send emails and generate links."
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_USER', 'nepaltravel2006@gmail.com');
    define('SMTP_PASS', 'cgub ryfr ztgv gluu');
    define('SMTP_PORT', 587);

    define('APP_NAME', 'Nepal Travel');
    define('BASE_URL', 'http://localhost/Nepal-Travel/user');
}



