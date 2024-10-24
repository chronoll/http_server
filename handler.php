<?php

// status=0 のうち最もIDが小さいレコードのidとfilenameを返す
function getNextFilename() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));
        
        $sql = "SELECT * FROM programs WHERE status = 0 ORDER BY id ASC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        // 最初のレコードを取得
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo = null;

        // レコードが存在する場合は返す
        if ($result) {
            return $result;
        } else {
            return null;
        }
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        return null;
    }
}

// progressに新規レコード挿入
function insertProgress($client_id, $program_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $sql = "INSERT INTO progress (client_id, program_id, status) VALUES (:client_id, :program_id, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->bindParam(':program_id', $program_id);
        $stmt->execute();
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
}

// programsのstatus=1に更新
function updateProgramsStatus($program_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $sql = "UPDATE programs SET status = 1 WHERE id = :program_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':program_id', $program_id);
        $stmt->execute();
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
}

?>