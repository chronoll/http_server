<?php

require_once '../common.php';

$GROUP = 5;
$RANK = 3;
$PROC = 7;

// Initialize
include("reset-matrix-table.php");

try {
    // 権限の付与
    $targetDir = "/home/kurotaka/http_client";
    if (!chdir($targetDir)) {
        throw new Exception("Failed to change directory to $targetDir");
    }

    // MPIでのリクエスト実行
    $command = "sh /home/kurotaka/http_client/exec.sh " . $PROC;
    exec($command, $output, $returnVar);
    if ($returnVar !== 0) {
        throw new Exception("Command failed:\n" . implode("\n", $output));
    }

    echo "Command succeeded:\n" . implode("\n", $output) . "\n";

    // データベース接続
    $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', [PDO::ATTR_PERSISTENT => true]);

    // テーブル数を計算
    $tableCount = ceil($PROC / ($GROUP * $RANK));

    // table_registry からすべての table_name を取得
    $sql = "SELECT * FROM table_registry ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $table_registry_record = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // テーブルごとのテスト処理
    for ($i = 0; $i < $tableCount; $i++) {
        $status = $table_registry_record[$i]['status'];
        $table_name = $table_registry_record[$i]["table_name"];

        if ($PROC >= ($GROUP * $RANK) * ($i + 1)) {
            // 全レコードをチェック
            if ($status != JobStatus::AllResultsReceived->value) {
                throw new Exception("Test failed at table_registry $i: Expected status " . JobStatus::AllResultsReceived->value . " but got $status.");
            }
            testTableRecords($pdo, $table_name, $GROUP * $RANK, SubJobStatus::ResultReceived->value);
        } else {
            // 部分的にレコードをチェック
            if ($status != JobStatus::DistributionStarted->value) {
                throw new Exception("Test failed at table_registry $i: Expected status " . JobStatus::DistributionStarted->value . " but got $status.");
            }
            $remainingCount = $PROC - ($GROUP * $RANK) * $i;
            testTableRecords($pdo, $table_name, $remainingCount, SubJobStatus::ResultReceived->value);
        }
    }

    echo "All tests passed successfully.\n";

    // データベース接続を閉じる
    $pdo = null;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * 指定されたテーブルのレコードをテストする
 *
 * @param PDO $pdo データベース接続
 * @param string $tableName テーブル名
 * @param int $recordCount チェックするレコード数
 * @param int $expectedStatus 期待されるステータス
 * @throws Exception テスト失敗時に例外をスロー
 */
function testTableRecords($pdo, $tableName, $recordCount, $expectedStatus)
{
    $sql = "SELECT * FROM `$tableName` ORDER BY id ASC LIMIT $recordCount";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $record) {
        if ($record['status'] != $expectedStatus) {
            throw new Exception("Test failed at $tableName: Expected status $expectedStatus but got " . $record['status'] . ".");
        }
    }
}
?>
