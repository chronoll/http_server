<?php

function processProgram($client_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));
        $pdo->beginTransaction();

        // status=0の中で最もIDが小さいレコードを取得
        $sql = "SELECT * FROM programs WHERE status = 0 ORDER BY id ASC LIMIT 1 FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // レコードが存在しない場合、ロールバックしてnullを返す
        if (!$result) {
            $pdo->rollBack();
            // return null;
            http_response_code(404);
            echo "No Programs to distribute.";
            exit;
        }

        $program_id = $result['id'];

        // programsテーブルのstatusを1に更新
        $sql = "UPDATE programs SET status = 1 WHERE id = :program_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':program_id', $program_id);
        $stmt->execute();

        // progressテーブルに新規レコードを挿入
        $sql = "INSERT INTO progress (client_id, program_id, status) VALUES (:client_id, :program_id, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->bindParam(':program_id', $program_id);
        $stmt->execute();

        $pdo->commit();

        return $result;

    } catch (PDOException $e) {
        // エラーが発生した場合はロールバック
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500); // Internal Server Error
        echo 'Transaction failed: ' . $e->getMessage();
        return null;
    } finally {
        $pdo = null;
    }
}

function processMatrix($client_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));
        $pdo->beginTransaction();

        // status=0の中で最もIDが小さいレコードを取得
        $sql = "SELECT * FROM matrix WHERE status = 0 ORDER BY id ASC LIMIT 1 FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // レコードが存在しない場合、ロールバックしてnullを返す
        if (!$result) {
            $pdo->rollBack();
            http_response_code(404);
            echo "No Programs to distribute.";
            exit;
        }

        $rank_id = $result['id'];

        // matrixテーブルのstatusを1に更新
        $sql = "UPDATE matrix SET status = 1 WHERE id = :rank_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':rank_id', $rank_id);
        $stmt->execute();

        // // progressテーブルに新規レコードを挿入
        // $sql = "INSERT INTO progress (client_id, program_id, status) VALUES (:client_id, :program_id, 0)";
        // $stmt = $pdo->prepare($sql);
        // $stmt->bindParam(':client_id', $client_id);
        // $stmt->bindParam(':program_id', $program_id);
        // $stmt->execute();

        $pdo->commit();

        return $result;

    } catch (PDOException $e) {
        // エラーが発生した場合はロールバック
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500); // Internal Server Error
        echo 'Transaction failed: ' . $e->getMessage();
        return null;
    } finally {
        $pdo = null;
    }
}
?>
