<?php

require_once 'common.php';

function distributeJob($client_id, $logFile) {
    try {
        $mysql_connect_start_time = microtime(true);

        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));
        $pdo->beginTransaction();

        $mysql_connect_end_time = microtime(true);
        $formatted_time = number_format(($mysql_connect_end_time - $mysql_connect_start_time) * 1000, 3) . " ms";
        writeLog("[distributeJob] MySQL connection established. Execution time: " . $formatted_time, $logFile);

        // status=0, 1のジョブをIDの昇順で取得
        $get_job_start_time = microtime(true);

        $sql = "SELECT * FROM table_registry WHERE status <= :status ORDER BY id ASC FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', JobStatus::StartedDistribution, PDO::PARAM_INT);
        $stmt->execute();
        $table_registry_record = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $get_job_end_time = microtime(true);
        $formatted_time = number_format(($get_job_end_time - $get_job_start_time) * 1000, 3) . " ms";
        writeLog("[distributeJob] SELECT * FROM table_registry WHERE status <= :status ORDER BY id ASC FOR UPDATE / Execution time: " . $formatted_time, $logFile);

        // レコードが存在しない場合、ロールバックしてnullを返す
        if (!$table_registry_record) {
            $pdo->rollBack();
            http_response_code(404);
            echo "No Job to distribute.";
            exit;
        }

        // 取得ジョブのテーブルを昇順に走査して、status=0のサブジョブがあれば返却
        foreach ($table_registry_record as $job) {
            $search_subjob_start_time = microtime(true);

            $search_table_name = $job["table_name"];
            $sql = "SELECT * FROM `$search_table_name` WHERE status = :status ORDER BY id ASC LIMIT 1 FOR UPDATE";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':status', SubJobStatus::NoDistribution, PDO::PARAM_INT);
            $stmt->execute();
            $job_table_record = $stmt->fetch(PDO::FETCH_ASSOC);

            $search_subjob_end_time = microtime(true);
            $formatted_time = number_format(($search_subjob_end_time - $search_subjob_start_time) * 1000, 3) . " ms";
            writeLog("[distributeJob] SELECT * FROM `$search_table_name` WHERE status = :status ORDER BY id ASC LIMIT 1 FOR UPDATE / Execution time: " . $formatted_time, $logFile);

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
        $update_status_start_time = microtime(true);

        $sql = "UPDATE `$table_name` SET status = :status, client = :client_id WHERE id = :sub_job_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', SubJobStatus::ResultPending, PDO::PARAM_INT);
        $stmt->bindValue(':client_id', $client_id, PDO::PARAM_INT);
        $stmt->bindParam(':sub_job_id', $sub_job_id);
        $stmt->execute();

        $update_status_end_time = microtime(true);
        $formatted_time = number_format(($update_status_end_time - $update_status_start_time) * 1000, 3) . " ms";
        writeLog("[distributeJob] UPDATE `$table_name` SET status = :status, client = :client_id WHERE id = :sub_job_id / Execution time: " . $formatted_time, $logFile);

        // 初回配布時はtable_registryのstatus=1に更新
        if ($sub_job_id == 1) {
            $update_registry_start_time = microtime(true);

            $sql = "UPDATE table_registry SET status = :status WHERE table_name = :table_name";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':status', JobStatus::StartedDistribution, PDO::PARAM_INT);
            $stmt->bindParam(':table_name', $table_name);
            $stmt->execute();

            $update_registry_end_time = microtime(true);
            $formatted_time = number_format(($update_registry_end_time - $update_registry_start_time) * 1000, 3) . " ms";
            writeLog("[distributeJob] UPDATE table_registry SET status = :status WHERE table_name = :table_name / Execution time: " . $formatted_time, $logFile);
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

function getGroupStatus($job_id, $group_id, $logFile) {
    try {
        // データベース接続
        $mysql_connect_start_time = microtime(true);

        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $mysql_connect_end_time = microtime(true);
        $formatted_time = number_format(($mysql_connect_end_time - $mysql_connect_start_time) * 1000, 3) . " ms";
        writeLog("[getGroupStatus] MySQL connection established. Execution time: " . $formatted_time, $logFile);

        // テーブル名の取得
        $get_table_name_start_time = microtime(true);

        $getTableNameSql = "SELECT table_name FROM table_registry WHERE id = :job_id";
        $getTableNameStmt = $pdo->prepare($getTableNameSql);
        $getTableNameStmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
        $getTableNameStmt->execute();
        $table_registry_record = $getTableNameStmt->fetch(PDO::FETCH_ASSOC);

        $get_table_name_end_time = microtime(true);
        $formatted_time = number_format(($get_table_name_end_time - $get_table_name_start_time) * 1000, 3) . " ms";
        writeLog("[getGroupStatus] SELECT table_name FROM table_registry WHERE id = :job_id / Execution time: " . $formatted_time, $logFile);

        if (!$table_registry_record) {
            http_response_code(500);
            echo "No table found with the given job_id.";
            exit;
        }

        $table_name = $table_registry_record['table_name'];

        // 指定groupの全statusを取得
        $get_group_status_start_time = microtime(true);

        $sql = "SELECT status FROM `$table_name` WHERE group_id = :group_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $get_group_status_end_time = microtime(true);
        $formatted_time = number_format(($get_group_status_end_time - $get_group_status_start_time) * 1000, 3) . " ms";
        writeLog("[getGroupStatus] SELECT status FROM `$table_name` WHERE group_id = :group_id / Execution time: " . $formatted_time, $logFile);

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

function updateStatus($job_id, $sub_job_id, $client_id, $logFile) {
    $result['isJobCompleted'] = false; // ジョブ完了フラグを更新したかどうか
    try {
        $mysql_connect_start_time = microtime(true);

        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $pdo->beginTransaction();

        $mysql_connect_end_time = microtime(true);
        $formatted_time = number_format(($mysql_connect_end_time - $mysql_connect_start_time) * 1000, 3) . " ms";
        writeLog("[updateStatus] MySQL connection established. Execution time: " . $formatted_time, $logFile);

        // テーブル名の取得
        $get_table_name_start_time = microtime(true);

        $getTableNameSql = "SELECT table_name FROM table_registry WHERE id = :job_id FOR UPDATE";
        $getTableNameStmt = $pdo->prepare($getTableNameSql);
        $getTableNameStmt->bindParam(":job_id", $job_id);
        $getTableNameStmt->execute();
        $table_registry_record = $getTableNameStmt->fetch(PDO::FETCH_ASSOC);

        $get_table_name_end_time = microtime(true);
        $formatted_time = number_format(($get_table_name_end_time - $get_table_name_start_time) * 1000, 3) . " ms";
        writeLog("[updateStatus] SELECT table_name FROM table_registry WHERE id = :job_id FOR UPDATE / Execution time: " . $formatted_time, $logFile);

        if (!$table_registry_record) {
            $pdo->rollBack();
            http_response_code(500);
            echo "No table found with the given job_id.";
            exit;
        }

        $table_name = $table_registry_record['table_name'];

        // 1. sub_job テーブルの更新
        $update_sub_job_start_time = microtime(true);

        $sql = "UPDATE `$table_name` SET status = :status WHERE id = :sub_job_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', SubJobStatus::ResultReceived, PDO::PARAM_INT);
        $stmt->bindParam(':sub_job_id', $sub_job_id);
        $stmt->execute();

        $update_sub_job_end_time = microtime(true);
        $formatted_time = number_format(($update_sub_job_end_time - $update_sub_job_start_time) * 1000, 3) . " ms";
        writeLog("[updateStatus] UPDATE `$table_name` SET status = :status WHERE id = :sub_job_id / Execution time: " . $formatted_time, $logFile);

        // 2. sub_job テーブルのステータスを確認
        $check_sub_job_start_time = microtime(true);

        $checkSql = "SELECT 
                        COUNT(*) AS total, 
                        SUM(CASE WHEN status = :status0 THEN 1 ELSE 0 END) AS has_zero,
                        SUM(CASE WHEN status = :status2 THEN 1 ELSE 0 END) AS has_two
                     FROM `$table_name`";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindValue(':status0', SubJobStatus::NoDistribution, PDO::PARAM_INT);
        $checkStmt->bindValue(':status2', SubJobStatus::ResultReceived, PDO::PARAM_INT);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $check_sub_job_end_time = microtime(true);
        $formatted_time = number_format(($check_sub_job_end_time - $check_sub_job_start_time) * 1000, 3) . " ms";
        writeLog("[updateStatus] SELECT COUNT(*) AS total, SUM(...) FROM `$table_name` / Execution time: " . $formatted_time, $logFile);

        // 3. 条件に応じて table_registry の更新
        if ($checkResult['total'] > 0) {
            if ($checkResult['has_zero'] == 0) {
                $update_registry_start_time = microtime(true);

                if ($checkResult['has_two'] == $checkResult['total']) {
                    $updateRegistrySql = "UPDATE table_registry SET status = :status WHERE id = :job_id";
                    $updateStmt = $pdo->prepare($updateRegistrySql);
                    $updateStmt->bindValue(':status', JobStatus::ResultsAllReceived, PDO::PARAM_INT);
                    $result['isJobCompleted'] = true; // ジョブ完了フラグを立てる

                    // 最後尾のグループ番号を取得
                    $get_last_group_id_start_time = microtime(true);

                    $getLastGroupIdSql = "SELECT group_id FROM `$table_name` ORDER BY id DESC LIMIT 1";
                    $getLastGroupIdStmt = $pdo->prepare($getLastGroupIdSql);
                    $getLastGroupIdStmt->execute();
                    $lastGroupRecord = $getLastGroupIdStmt->fetch(PDO::FETCH_ASSOC);

                    $get_last_group_id_end_time = microtime(true);
                    $formatted_time = number_format(($get_last_group_id_end_time - $get_last_group_id_start_time) * 1000, 3) . " ms";
                    writeLog("[updateStatus] SELECT group_id FROM `$table_name` ORDER BY id DESC LIMIT 1 / Execution time: " . $formatted_time, $logFile);

                    $result['group_count'] = $lastGroupRecord['group_id'];
                } else {
                    $updateRegistrySql = "UPDATE table_registry SET status = :status WHERE id = :job_id";
                    $updateStmt = $pdo->prepare($updateRegistrySql);
                    $updateStmt->bindValue(':status', JobStatus::ResultsPending, PDO::PARAM_INT);
                }
                $updateStmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
                $updateStmt->execute();

                $update_registry_end_time = microtime(true);
                $formatted_time = number_format(($update_registry_end_time - $update_registry_start_time) * 1000, 3) . " ms";
                writeLog("[updateStatus] UPDATE table_registry SET status = :status WHERE id = :job_id / Execution time: " . $formatted_time, $logFile);
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

    return $result;
}

function resetGroupStatus($job_id, $group_id) {
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

        // groupの全statusを0に更新
        $sql = "UPDATE `$table_name` SET status = :status WHERE group_id = :group_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', SubJobStatus::NoDistribution, PDO::PARAM_INT);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();

        // groupの全clientをNULLに更新
        $sqlClient = "UPDATE `$table_name` SET client = NULL WHERE group_id = :group_id";
        $stmtClient = $pdo->prepare($sqlClient);
        $stmtClient->bindParam(':group_id', $group_id);
        $stmtClient->execute();

        // ジョブ全体のstatusを確認
        $checkSql = "SELECT COUNT(*) AS total, 
                            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS has_zero 
                     FROM `$table_name`";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // 条件に応じて table_registry を更新
        if ($result['total'] > 0) {
            $newStatus = ($result['has_zero'] == $result['total']) ? 0 : 1;

            $updateRegistrySql = "UPDATE table_registry SET status = :status WHERE id = :job_id";
            $updateStmt = $pdo->prepare($updateRegistrySql);
            $updateStmt->bindValue(':status', $newStatus, PDO::PARAM_INT);
            $updateStmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
            $updateStmt->execute();
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

function getClientID($job_id, $sub_job_id, $logFile) {
    try {
        $mysql_connect_start_time = microtime(true);

        // データベース接続
        $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $mysql_connect_end_time = microtime(true);
        $formatted_time = number_format(($mysql_connect_end_time - $mysql_connect_start_time) * 1000, 3) . " ms";
        writeLog("[getClientID] MySQL connection established. Execution time: " . $formatted_time, $logFile);

        // テーブル名の取得
        $get_table_name_start_time = microtime(true);

        $getTableNameSql = "SELECT table_name FROM table_registry WHERE id = :job_id";
        $getTableNameStmt = $pdo->prepare($getTableNameSql);
        $getTableNameStmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
        $getTableNameStmt->execute();
        $table_registry_record = $getTableNameStmt->fetch(PDO::FETCH_ASSOC);

        $get_table_name_end_time = microtime(true);
        $formatted_time = number_format(($get_table_name_end_time - $get_table_name_start_time) * 1000, 3) . " ms";
        writeLog("[getClientID] SELECT table_name FROM table_registry WHERE id = :job_id / Execution time: " . $formatted_time, $logFile);

        if (!$table_registry_record) {
            http_response_code(500);
            echo "No table found with the given job_id.";
            exit;
        }

        $table_name = $table_registry_record['table_name'];

        // 指定groupの全statusを取得
        $get_group_status_start_time = microtime(true);

        $sql = "SELECT client FROM `$table_name` WHERE id = :sub_job_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':sub_job_id', $sub_job_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $get_group_status_end_time = microtime(true);
        $formatted_time = number_format(($get_group_status_end_time - $get_group_status_start_time) * 1000, 3) . " ms";
        writeLog("[getClientID] SELECT client FROM `$table_name` WHERE id = :sub_job_id / Execution time: " . $formatted_time, $logFile);

        if (!$result) {
            http_response_code(404);
            echo "No status found for the given _id.";
            exit;
        }

        return $result['client'];
    } catch(PDOException $e) {
        http_response_code(500);
        echo 'Connection failed: ' . $e->getMessage();
    } finally {
        $pdo = null;
    }
}

function addGroup($job_id) {
    try {
        // データベース接続
        $pdo =  new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

        $pdo->beginTransaction();

        // テーブル名の取得
        $getTableNameSql = "SELECT table_name, rank_count FROM table_registry WHERE id = :job_id";
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
        $rank_count = $table_registry_record['rank_count'];

        // 最後尾のgroup_idを取得
        $getLastGroupIdSql = "SELECT group_id FROM `$table_name` ORDER BY id DESC LIMIT 1";
        $getLastGroupIdStmt = $pdo->prepare($getLastGroupIdSql);
        $getLastGroupIdStmt->execute();
        $lastGroupRecord = $getLastGroupIdStmt->fetch(PDO::FETCH_ASSOC);

        $next_group_number = intval($lastGroupRecord['group_id']) + 1; // 次のgroup_id
        
        for ($rank = 0; $rank < $rank_count; $rank++) {
            $sql = "INSERT INTO `$table_name` (group_id, rank) VALUES (:group_id, :rank)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':group_id', $next_group_number, PDO::PARAM_INT);
            $stmt->bindParam(':rank', $rank, PDO::PARAM_INT);
            $stmt->execute();
        }

        // ジョブ全体のstatusを確認
        $checkSql = "SELECT COUNT(*) AS total, 
                            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS has_zero 
                     FROM `$table_name`";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // 条件に応じて table_registry を更新
        if ($result['total'] > 0) {
            $newStatus = ($result['has_zero'] == $result['total']) ? 0 : 1;

            $updateRegistrySql = "UPDATE table_registry SET status = :status WHERE id = :job_id";
            $updateStmt = $pdo->prepare($updateRegistrySql);
            $updateStmt->bindValue(':status', $newStatus, PDO::PARAM_INT);
            $updateStmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
            $updateStmt->execute();
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