<?php

$data;
if(isset($_GET['cmd']))  {
    switch (trim($_GET['cmd']))
    {
        case 'signup' : $data = array('cmd' => 'signup', 'username' => 'fabian', 'password' => 'banane'); break;
        case 'addfriend' : $data = array('cmd' => 'addFriend', 'user_id' => '16', 'friend_name' => 'len'); break;
        case 'removefriend' : $data = array('cmd' => 'removeFriend', 'user_id' => '16', 'friend_name' => 'len'); break;
        case 'gong' : $data = array('cmd' => 'gong', 'user_id' => '11'); break;
        case 'login' : $data = array('cmd' => 'login', 'username' => 'thomas', 'password' => 'banane'); break;
        case 'updateFriend' : $data = array('cmd' => 'updateFriend', 'user_id' => '15'); break;
    }
} else {
    $data = array('cmd' => 'signup', 'username' => 'fabian', 'password' => 'banane');
}

$url = 'http://localhost/gong/api.php';

// use key 'http' even if you send the request to https://...
$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ),
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$json = json_decode($result);
if(json_last_error() == JSON_ERROR_NONE) 
{ 
    echo 'json'.PHP_EOL;
} else {
    echo 'nojson'.PHP_EOL;
}
var_dump($result);
