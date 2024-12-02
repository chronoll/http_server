<?php

include 'handler.php';

$job_id = $_GET['job_id'];
$sub_job_id = $_GET['sub_job_id'];
$client_id = $_GET['client_id'];
$TIMEOUT = 3;

// ログ処理
$logFile = __DIR__ . '/test.log';
$requestMessage = "job_id: $job_id, sub_job_id: $sub_job_id, client_id: $client_id\n";
file_put_contents($logFile, $requestMessage, FILE_APPEND | LOCK_EX);

ignore_user_abort(true); // クライアント切断後もスクリプト実行を続ける
header("Content-Length: 0"); // 空のレスポンス
flush(); // レスポンスを送信

// 待機
sleep($TIMEOUT);

// 状態を確認
$status = getStatus($job_id, $sub_job_id, $client_id);

// 結果をログに書き込む
$logMessage = "expected: 2, status: $status\n";
file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

if ($status != SubJobStatus::ResultReceived->value) {
    resetStatus($job_id, $sub_job_id, $client_id);
}

// 状態を確認
$status = getStatus($job_id, $sub_job_id, $client_id);

// 結果をログに書き込む
$logMessage = "expected: 2, status: $status\n";
file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

?>
