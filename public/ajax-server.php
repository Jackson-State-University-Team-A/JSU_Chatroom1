<?php
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.html');
    exit;
}
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'jsu_chatroom';
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}
// We don't have the password or email info stored in sessions so instead we can get the results from the database.
$stmt = $con->prepare('SELECT password, email, classification, college FROM accounts WHERE id = ?');
// In this case we can use the account ID to get the account info.
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($password, $email, $classification, $college);
$stmt->fetch();
$stmt->close();
$chat_dir = "chat";
if(!file_exists($chat_dir)) mkdir($chat_dir);

$default = "default-chat.log";
if(!file_exists("$chat_dir/$default")) file_put_contents("$chat_dir/$default", "");

switch($_POST["action"]) {
    case "send":
        $chatfile = "$chat_dir/" . $_POST["room"] . ".log";
        writeChat($chatfile);
        echo chatLog($chatfile);
        break;
    
    case "poll":
        $chatfile = "$chat_dir/" . $_POST["room"] . ".log";
        echo chatLog($chatfile);
        break;
        
    case "room":
        echo getRoom($chat_dir);
        break;     
}

function writeChat($f) {
    $format = "<p>%s&nbsp;<span class='namelite'>%s</span>&nbsp;%s</p>";
    $str = sprintf($format, date("H:i"), $_POST["name"], $_POST["msg"]);
    file_put_contents($f, "$str\n", FILE_APPEND | LOCK_EX);
}

function chatLog($f) {
    if(file_exists($f)) $log = file_get_contents($f); else $log = "";
    return $log;
}

function getRoom($dir) {    
    $ffs = preg_grep('/^([^.])/', scandir($dir));
    $ffs = array_values($ffs);
    foreach($ffs as $key=>$ff) {
        $ffs[$key] = explode(".", $ff)[0];
    }
    //$new = $_POST['new'];
    if(isset($_POST['new'])) {
        $new = $_POST['new'];
        file_put_contents("$dir/$new" . ".log", "");
        $ffs = array_merge([ $new ], $ffs);
    }
    return json_encode($ffs);    
}
?>