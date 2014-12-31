<?php

// Configuration file for api.php

$config = array(
    // These are the settings for development mode
    'development' => array(
        'db' => array(
            'host'     => 'localhost',
            'dbname'   => 'gong',
            'username' => 'root',
            'password' => 'root',
            ),
        ),

    // These are the settings for production mode
    'production' => array(
        'db' => array(
            'host'     => 'localhost',
            'dbname'   => 'pushchat',
            'username' => 'pushchat',
            'password' => 'password',
            ),
        ),
    );
