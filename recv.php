<?php

if (isset($_POST['MESSAGE'])) {
    $message = $_POST['MESSAGE'];
}

if (isset($_POST['SENDER'])) {
    $sender = $_POST['SENDER'];
}

if (isset($_POST['RECEIVER'])) {
    $receiver = $_POST['RECEIVER'];
}

try {
    $dbh = new PDO('mysql:dbname=chat;host=localhost;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

    $sql = 'INSERT INTO messages (content, sender, receiver) VALUES (:message, :sender, :receiver)';
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':sender', $sender);
    $stmt->bindParam(':receiver', $receiver);
    $stmt->execute();
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

?>
