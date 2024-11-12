<?php

if (isset($_GET['RECEIVER'])) {
    $receiver = $_GET['RECEIVER'];
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=chat;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

    $sql = "SELECT * FROM messages WHERE receiver = :receiver";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':receiver', $receiver);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // var_dump($results);

    foreach ($results as $row) {
        echo "Sender: " . $row['sender'] . "\tMessage: " . $row['content'] . "\tcreated at: " . $row['created_at'] . "\n";
    }

    $pdo = null;
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

?>