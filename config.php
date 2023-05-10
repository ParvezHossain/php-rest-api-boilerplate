<?php
// Database configuration
define('HOST', 'localhost');
define('USER', 'root');
define('PASSWORD', '');
define('DB', 'laravel');

// JWT configuration

define('SECRET_KEY', '68V0zWFrS72GbpPreidkQFLfj4v9m3Ti+DXc8OB0gcM=');
define('ISSUER_CLAIM', 'THE_ISSUER');
define('AUDIENCE_CLAIM', 'THE_AUDIENCE');
define('ISSUEDAT_CLAIM', time());
define('NOTBEFORE_CLAIM', time() + 10);
define('EXPIRE_CLAIM', time() + 3600);
define('HASH_ALGORITHM', 'HS512');