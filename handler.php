<?php

require_once 'common.php';

function distributeJob($client_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));
        $pdo->beginTransaction();

        // status=0, 1のジョブをIDの昇順で取得
        $sql = "SELECT * FROM table_registry WHERE status <= :status ORDER BY id ASC FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', JobStatus::startedDistribution->value, PDO::PARAM_INT);
        $stmt->execute();
        $table_registry_record = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // レコードが存在しない場合、ロールバックしてnullを返す
        if (!$table_registry_record) {
            $pdo->rollBack();
            http_response_code(404);
            echo "No Job to distribute.";
            exit;
        }

        // 取得ジョブのテーブルを昇順に走査して、status=0のサブジョブがあれば返却
        foreach ($table_registry_record as $job) {
            $search_table_name = $job["table_name"];
            $sql = "SELECT * FROM `$search_table_name` WHERE status = :status ORDER BY id ASC LIMIT 1 FOR UPDATE";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':status', SubJobStatus::NoDistribution->value, PDO::PARAM_INT);
            $stmt->execute();
            $job_table_record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($job_table_record) {
                // table_registryからの情報
                $job_id = $job['id'];
                $table_name = $job['table_name'];
                $filename = $job['filename'];
                $rank_count = $job['rank_count'];
                // ジョブテーブルからの情報
                $sub_job_id = $job_table_record['id'];
                $rank = $job_table_record['rank'];
                $group_id = $job_table_record['group_id'];
                break;
            }
        }

        // レコードが存在しない場合、ロールバックしてnullを返す
        if (!$job_table_record) {
            $pdo->rollBack();
            http_response_code(404);
            echo "No Sub-Job to distribute.";
            exit;
        }

        // jobテーブルのstatusを1に更新, client_idを登録
        $sql = "UPDATE `$table_name` SET status = :status, client = :client_id WHERE id = :sub_job_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', SubJobStatus::ResultPending->value, PDO::PARAM_INT);
        $stmt->bindValue(':client_id', $client_id, PDO::PARAM_INT);
        $stmt->bindParam(':sub_job_id', $sub_job_id);
        $stmt->execute();

        // 初回配布時はtable_registryのstatus=1に更新
        if ($sub_job_id == 1) {
            $sql = "UPDATE table_registry SET status = :status WHERE table_name = :table_name";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':status', JobStatus::startedDistribution->value, PDO::PARAM_INT);
            $stmt->bindParam(':table_name', $table_name);
            $stmt->execute();
        }

        $pdo->commit();

        $result = [
            'job_id' => $job_id,
            'filename' => $filename,
            'rank_count' => $rank_count,
            'sub_job_id' => $sub_job_id,
            'rank' => $rank,
            'group_id' => $group_id
        ];

        return $result;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo 'Transaction failed: ' . $e->getMessage();
        return null;
    } finally {
        $pdo = null;
    }
}

function getGroupStatus($job_id, $group_id) {
    try {
        // データベース接続
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        // テーブル名の取得
        $getTableNameSql = "SELECT table_name FROM table_registry WHERE id = :job_id";
        $getTableNameStmt = $pdo->prepare($getTableNameSql);
        $getTableNameStmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
        $getTableNameStmt->execute();
        $table_registry_record = $getTableNameStmt->fetch(PDO::FETCH_ASSOC);

        if (!$table_registry_record) {
            http_response_code(500);
            echo "No table found with the given job_id.";
            exit;
        }

        $table_name = $table_registry_record['table_name'];

        // 指定groupの全statusを取得
        $sql = "SELECT status FROM `$table_name` WHERE group_id = :group_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            http_response_code(404);
            echo "No status found for the given client_id.";
            exit;
        }

        return $results;
    } catch(PDOException $e) {
        http_response_code(500);
        echo 'Connection failed: ' . $e->getMessage();
    } finally {
        $pdo = null;
    }
}

function updateStatus($job_id, $sub_job_id, $client_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $pdo->beginTransaction();

        // テーブル名の取得
        $getTableNameSql = "SELECT table_name FROM table_registry WHERE id = :job_id FOR UPDATE";
        $getTableNameStmt = $pdo->prepare($getTableNameSql);
        $getTableNameStmt->bindParam(":job_id", $job_id);
        $getTableNameStmt->execute();
        $table_registry_record = $getTableNameStmt->fetch(PDO::FETCH_ASSOC);

        if (!$table_registry_record) {
            $pdo->rollBack();
            http_response_code(500);
            echo "No table found with the given job_id.";
            exit;
        }

        $table_name = $table_registry_record['table_name'];

        // 1. sub_job テーブルの更新
        $sql = "UPDATE `$table_name` SET status = :status WHERE id = :sub_job_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', SubJobStatus::ResultReceived->value, PDO::PARAM_INT);
        $stmt->bindParam(':sub_job_id', $sub_job_id);
        $stmt->execute();

        // 2. sub_job テーブルのステータスを確認
        $checkSql = "SELECT 
                        COUNT(*) AS total, 
                        SUM(CASE WHEN status = :status0 THEN 1 ELSE 0 END) AS has_zero,
                        SUM(CASE WHEN status = :status2 THEN 1 ELSE 0 END) AS has_two
                     FROM `$table_name`";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindValue(':status0', SubJobStatus::NoDistribution->value, PDO::PARAM_INT);
        $checkStmt->bindValue(':status2', SubJobStatus::ResultReceived->value, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // 3. 条件に応じて table_registry の更新
        if ($result['total'] > 0) {
            if ($result['has_zero'] == 0) {
                if ($result['has_two'] == $result['total']) {
                    $updateRegistrySql = "UPDATE table_registry SET status = :status WHERE id = :job_id";
                    $updateStmt = $pdo->prepare($updateRegistrySql);
                    $updateStmt->bindValue(':status', JobStatus::ResultsAllReceived->value, PDO::PARAM_INT);
                } else {
                    $updateRegistrySql = "UPDATE table_registry SET status = :status WHERE id = :job_id";
                    $updateStmt = $pdo->prepare($updateRegistrySql);
                    $updateStmt->bindValue(':status', JobStatus::ResultsPending->value, PDO::PARAM_INT);
                }
                $updateStmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
                $updateStmt->execute();
            }
        }

        $pdo->commit();
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo 'Connection failed: ' . $e->getMessage();
    } finally {
        $pdo = null;
    }
}

function resetGroupStatus($job_id, $group_id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        // テーブル名の取得
        $getTableNameSql = "SELECT table_name FROM table_registry WHERE id = :job_id FOR UPDATE";
        $getTableNameStmt = $pdo->prepare($getTableNameSql);
        $getTableNameStmt->bindParam(":job_id", $job_id);
        $getTableNameStmt->execute();
        $table_registry_record = $getTableNameStmt->fetch(PDO::FETCH_ASSOC);

        if (!$table_registry_record) {
            $pdo->rollBack();
            http_response_code(500);
            echo "No table found with the given job_id.";
            exit;
        }

        $table_name = $table_registry_record['table_name'];

        // groupの全statusを0に更新
        $sql = "UPDATE `$table_name` SET status = :status WHERE group_id = :group_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', SubJobStatus::NoDistribution->value, PDO::PARAM_INT);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();

        $pdo->commit();
    } catch(PDOException $e) {
        http_response_code(500);
        echo 'Connection failed: ' . $e->getMessage();
    } finally {
        $pdo = null;
    }
}
