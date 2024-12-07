<?php

include 'handler.php';

$job_id = $_GET['job_id'];
$sub_job_id = $_GET['sub_job_id'];
$group_id = $_GET['group_id'];
$client_id = $_GET['client_id'];
$TIMEOUT = 3;

ignore_user_abort(true); // クライアント切断後もスクリプト実行を続ける
header("Content-Length: 0"); // 空のレスポンス
flush(); // レスポンスを送信

// 待機
sleep($TIMEOUT);

// 状態を確認
$results = getGroupStatus($job_id, $group_id);

foreach ($results as $result) {
    if ($result['status'] == SubJobStatus::ResultPending->value) {
        resetGroupStatus($job_id, $group_id);
        break;
    }
}

?>
