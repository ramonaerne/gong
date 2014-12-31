<?php

// This is the server API. The app sends a HTTP POST
// request to our URL. The POST data contains a field "cmd"
// that indicates what API command should be executed.

try
{
    // set Apache VirtualHost to work instead of putting in development mode manually
    if (!defined('APPLICATION_ENV'))
        define('APPLICATION_ENV', getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');
    //define('APPLICATION_ENV', 'development');
    // Critical PHP errors will still be logged 
    // in the PHP and Apache error logs
    if (APPLICATION_ENV == 'development')
    {
        error_reporting(E_ALL|E_STRICT);
        ini_set('display_errors', 'on');
    }
    else
    {
        error_reporting(0);
        ini_set('display_errors', 'off');
    }

    // Load configure file
    require_once 'api_config.php';
    $config = $config[APPLICATION_ENV];

    // Fake a delay in development mode
    if (APPLICATION_ENV == 'development')
        //sleep(2);

    $api = new API($config);
    $api->handleCommand();
}
catch (Exception $e)
{
    if (APPLICATION_ENV == 'development')
        var_dump($e);
    else
        exitWithHttpError(500);
}

////////////////////////////////////////////////////////////////////////////////

function exitWithHttpError($error_code, $message = '')
{
    exitWithErrorMessage("{$error_code} ". $message);
}

function exitWithErrorMessage($msg)
{
    echo json_encode(array('error_msg' => $msg));
    exit;
}

function isValidUtf8String($string, $maxLength, $allowNewlines = false)
{
    if (empty($string) || strlen($string) > $maxLength)
        return false;

    if (mb_check_encoding($string, 'UTF-8') === false)
        return false;

    // Don't allow control characters, except possibly newlines 
    for ($t = 0; $t < strlen($string); $t++)
    {
        $ord = ord($string{$t});

        if ($allowNewlines && ($ord == 10 || $ord == 13))
            continue;

        if ($ord < 32)
            return false;
    }

    return true;
}

////////////////////////////////////////////////////////////////////////////////

class API
{
    const MAX_USERNAME_LENGTH = 20;
    private $pdo;

    function __construct($config)
    {
        // Create a connection to the database.
        $this->pdo = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'], 
            $config['db']['username'], 
            $config['db']['password'],
            array());

        // Throws an exception if unable to connect
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // We want the database to handle all strings as UTF-8.
        $this->pdo->query('SET NAMES utf8');
    }

    function handleCommand()
    {
        if (isset($_POST['cmd']))
        {
            // TODO: with all except login check if token is same as in db, otherwise logout
            // if login, then renew token
            switch (trim($_POST['cmd']))
            {
                case 'gong': $this->gong(); return;
                case 'updateFriend': $this->updateFriendlist(); return;
                case 'signup': $this->handleSignup(); return;
                case 'login': $this->handleLogin(); return;
                case 'addFriend': $this->addFriend(); return;
                case 'removeFriend': $this->removeFriend(); return;
            }
        }

        exitWithHttpError(400, 'Unknown command');
    }

    // receives following post request
    //  - username: unique name, check if not already in use
    //  - password: strong enough
    // returns:
    //  - error_msg: if something failed
    //  - user_id: if successful
    function handleSignup()
    {
        $userName = $this->getString('username', self::MAX_USERNAME_LENGTH);
        $userPwd = $this->getString('password', 255);

        // check if username valid
        if(!isValidUtf8String($userName, self::MAX_USERNAME_LENGTH)) {
            exitWithErrorMessage("Characters in username are not valid");
        }

        // check if unique username
        $sql = "SELECT user_id FROM users WHERE user_name=?";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array($userName));

        if($statement->rowCount() > 0) {
            exitWithErrorMessage("Username already exists");
        }

        // hash the password
        $hash = password_hash($userPwd, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (user_id, user_name, user_hash, user_token) VALUES (NULL, ?, ?, NULL);";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($userName, $hash));

        // return id as json object if successful, otherwise error is caught
        $id = $this->pdo->lastInsertId();
        echo json_encode(array('user_id' => $id));
    }

    // receives following post request
    //  - username
    //  - password
    // returns:
    //  - error_msg if not successful, or user_id if successful
    function handleLogin() 
    {
        $userName = $this->getString('username', self::MAX_USERNAME_LENGTH);
        $userPwd = $this->getString('password', 255);

        // get password of this user
        $sql = "SELECT user_hash, user_id FROM users WHERE user_name = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($userName));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // then compare hash and password
        if (sizeof($row) > 0 && password_verify($userPwd, $row['user_hash'])) {
            // login successful return user_id
            echo json_encode(array('user_id' => $row['user_id']));
        } else {
            exitWithErrorMessage("Login not successful");
        }
    }

    // receives following post request
    //  - user_id
    //  - friend_name
    // returns:
    //  - status: 0=pending, 1=friend added (mutually)
    function addFriend()
    {
        $user_id = $_POST['user_id'];
        $friendName = $this->getString('friend_name', self::MAX_USERNAME_LENGTH);

        // search for friend
        $sql = "SELECT user_id FROM users WHERE user_name = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($friendName));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $friend_id;
        if(sizeof($row) <= 0) 
            exitWithErrorMessage("Username not found");
        
        $friend_id = $row['user_id'];

        // check existing friendship
        $sql = "SELECT friends_id FROM friends WHERE user_1 = ? AND user_2 = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($user_id, $friend_id));
        $pending = $stmt->rowCount();
        $stmt->execute(array($friend_id, $user_id));
        $waitingForAnswer = $stmt->rowCount();

        if($pending <= 0) {    // not friends yet
            // insert friendship one way into db
            $sql = "INSERT INTO friends (friends_id, user_1, user_2) VALUES (NULL, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array($user_id, $friend_id));
        }

        if($waitingForAnswer > 0) {
            // friendship is mutual now
            // return 'status' = 1
            echo json_encode(array('status' => 1));
        } else {
            // friendship is pending
            echo json_encode(array('status' => 0));
        }
    }

    // receives following post request
    //  - user_id
    //  - friend_name
    // returns:
    //  - succcess: 1 if successful, error_msg if not
    function removeFriend()
    {
        $user_id = $_POST['user_id'];
        $friendName = $this->getString('friend_name', self::MAX_USERNAME_LENGTH);
        
        // search for friend
        $sql = "SELECT user_id FROM users WHERE user_name = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($friendName));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $friend_id;
        if(sizeof($row) <= 0) 
            exitWithErrorMessage("Username not found");
        
        $friend_id = $row['user_id'];

        // delete friendship one way
        $sql = "DELETE FROM friends WHERE user_1 = ? AND user_2 = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($user_id, $friend_id));

        if($stmt->rowCount() == 0) {
            exitWithErrorMessage("friendship not found");
        } else {
            // otherwise friend deleted
            echo json_encode(array('success' => 1));
        }
    }

    // receives following post request
    //  - user_id
    // returns:
    //  - error_msg or success=1
    function gong()
    {
        $user_id = $_POST['user_id'];
        // select all friends
        $sql = "SELECT ul.user_id FROM"
        . "( SELECT U.user_id, U.user_name FROM users U, friends F"
        . " WHERE F.user_1 = ? AND F.user_2 = U.user_id"
        . ") ul INNER JOIN"
        . "( SELECT U.user_id FROM users U, friends F"
        . " WHERE F.user_1 = U.user_id AND F.user_2 = ?"
        . ") ur" 
        . " ON ul.user_id = ur.user_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($user_id, $user_id));
        $this->pdo->beginTransaction();
        $sql = "INSERT INTO notification_queue (queue_id, user_id, friend_id, timestamp) VALUES (NULL, ?, ?, NULL)";
        $addQueue = $this->pdo->prepare($sql);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $friend_id = $row['user_id'];
            // store friend_id as user_id in queue since he is the user to be notified
            $addQueue->execute(array($friend_id, $user_id));
        }
        $this->pdo->commit();   // if error would throw exception and therefore an error_msg

        echo json_encode(array('success' => 1));
    }

    // receives post parameters
    //  - user_id
    // returns following
    //  - name: json_array with user_id of friend as array index and name of friend as value
    //  - status: json_array with user_id of friend as array index and status as friend
    //      0: request, 1: friend (mutually)
    function updateFriendlist()
    {
        $user_id = $_POST['user_id'];
        // select all friends and requests: request have ur.user_id=NULL
        $sql = "SELECT ul.user_id, ul.user_name, ur.user_id FROM"
        . "( SELECT U.user_id, U.user_name FROM users U, friends F"
        . " WHERE F.user_1 = U.user_id AND F.user_2 = ?"
        . ") ul LEFT JOIN"
        . "( SELECT U.user_id FROM users U, friends F"
        . " WHERE F.user_1 = ? AND F.user_2 = U.user_id"
        . ") ur" 
        . " ON ul.user_id = ur.user_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($user_id,$user_id));

        $friendList = array();

        while($row = $stmt->fetch(PDO::FETCH_NUM)) {    
            // $row[0] index, $row[1] name, $row[2] NULL or index
            $friendList['name'][$row[0]] = $row[1];
            $friendList['status'][$row[0]] = ($row[2]==NULL) ? 0 : 1;
        }

        echo json_encode($friendList);
    }

    function getString($name, $maxLength, $allowNewlines = false)
    {
        if (!isset($_POST[$name]))
            exitWithHttpError(400, "Missing $name");

        $string = trim($_POST[$name]);
        if (!isValidUtf8String($string, $maxLength, $allowNewlines))
            exitWithHttpError(400, "Invalid $name");

        return $string;
    }
}
