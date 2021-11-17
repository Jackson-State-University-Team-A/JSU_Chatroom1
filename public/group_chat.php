<?php
session_start();
// We need to use sessions, so you should always start sessions using the below code.
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
?>
<!DOCTYPE html>
<html>
<head>
<title>JSU Chatroom</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css">
<script src="http://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.js"></script> 
<link rel="stylesheet" href="chat.css">
</head>
<body>
<div data-role="page" style="background:darkgrey;">
    <div data-role="content">
        <div class="main-content">
            <p><b>Your Chat Room : </b> <span id="room" class="namelite">&emsp;&emsp;</span></p>
            <select id="room-list" class="align-left"></select>
            <p><b>Your Name: </b> <span id="name" class="namelite"></span></p>
            <div id="chat"></div>
            <br>
            <input type="text" data-clear-btn="true" id="msg">
        </div>
    </div>
</div>
</body>
<script>

$(document).ready(function() { 
    chatname = "<?=$_SESSION['name']?>";
    if(chatname == null) { chatname = "Guest"; }
    $("#name").text(chatname);
    poll();
    get_room();
});

$('#msg').on("keyup", function(e) {           
    if (e.keyCode == 13) send();
});

$('#room-list').on("change", function() { 
    changed = $(this).find(":selected").val();
    if(changed == "create room") {
        get_room(true);
    } else {
        $("#room").text(changed);
    }
});

function send() {
    msg = $("#msg").val();
    if(msg.trim() == "") { $("#msg").val(""); return; }
    
    post_data = { action: "send", 
        room : $("#room").text(), 
        name: $("#name").text(), 
        msg: msg };
    console.log(post_data);
    
    $.ajax({
        url: "ajax-server.php",
        type: "POST",
        data: post_data,
        success: function(r) {
            $("#chat").html(r).scrollTop($('#chat')[0].scrollHeight);
            $("#msg").val("");
        },
    });
}

function poll() {
    room = $("#room").text();
    if(room.trim() != "") {
        $.ajax({
            url: "ajax-server.php",
            type: "POST",
            data: { action: "poll", room: room },
            success: function(r) {
                $("#chat").html(r);
            },
        });
    }
    setTimeout(poll, 1000);
}

function get_room(create = false) {
    post_data = { action: "room" };
    if(create) {
        new_room = $("#name").text();
        new_room = new_room.replaceAll(' ', '-');
        new_room += "-" + Math.floor(Math.random() * Math.floor(10000));
        post_data["new"] = new_room;
    }
    console.log(post_data);
    
    $.ajax({
        url: "ajax-server.php",
        type: "POST",
        data: post_data,
        success: function(r) {
            console.log(r);
            obj = JSON.parse(r);
            list_rooms = "";
            
            list_rooms += "<option value='<?=$classification?>'><?=$classification?></option>";
            list_rooms += "<option value='<?=$college?>'><?=$college?></option>";

            $("#room-list").html(list_rooms).selectmenu("refresh", true);
            room = $("#room").text();
            if(room.trim() == "") {
                $("#room").text("default-chat");
            }
            if(create) {
                alert("CREATE A NEW ROOM : " + new_room);
                $("#room").text($("#room-list").find(":selected").val());
            }
        },
    });
}
</script>

