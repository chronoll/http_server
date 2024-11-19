<?php
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

        $matrix_id = $result['id'];
        $table_name = "matrix"; // TODO: テーブル名を変数にする

        // matrixテーブルのstatusを1に更新
        $sql = "UPDATE matrix SET status = 1 WHERE id = :matrix_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':matrix_id', $matrix_id);
        $stmt->execute();

        // 初回配布時はtable_registryのstatus=1に更新
        if ($matrix_id == 1) {
            $sql = "UPDATE table_registry SET status = 1 WHERE table_name = :table_name";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':table_name', $table_name);
            $stmt->execute();
        }

        // progressテーブルに新規レコードを挿入
        // 配布直後: status=0
        $sql = "INSERT INTO progress (client_id, program_id, matrix_id, status) VALUES (:client_id, 6, :matrix_id, 0)"; // 6 is the ID of the matrix program
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->bindParam(':matrix_id', $matrix_id);

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

function saveProgress($matrix_id, $status) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        // トランザクションの開始
        $pdo->beginTransaction();

        // 1. matrix テーブルの更新
        $sql = "UPDATE matrix SET status = :status WHERE id = :matrix_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':matrix_id', $matrix_id);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        // 2. matrix テーブルのステータスを確認
        $checkSql = "SELECT 
                        COUNT(*) AS total, 
                        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS has_zero,
                        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS has_two
                     FROM matrix";
        $checkStmt = $pdo->query($checkSql);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // 3. 条件に応じて table_registry の更新
        if ($result['total'] > 0) {
            if ($result['has_zero'] == 0) { // 0 がない場合
                if ($result['has_two'] == $result['total']) { // 全てが 2 の場合
                    $updateRegistrySql = "UPDATE table_registry SET status = 3 WHERE table_name = 'matrix'";
                } else { // 0 がなく、2 以外もある場合
                    $updateRegistrySql = "UPDATE table_registry SET status = 2 WHERE table_name = 'matrix'";
                }
                $pdo->exec($updateRegistrySql);
            }
        }

        // トランザクションのコミット
        $pdo->commit();
    } catch(PDOException $e) {
        // エラー時のロールバック
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500); // Internal Server Error
        echo 'Connection failed: ' . $e->getMessage();
    } finally {
        $pdo = null;
        return null;
    }
}

