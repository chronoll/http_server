<?php

$program_start_time = microtime(true);

require_once 'handler.php';
require_once 'common.php';

$job_id = $_GET['job_id'];
$sub_job_id = $_GET['sub_job_id'];
$group_id = $_GET['group_id'];
$client_id = $_GET['client_id'];
$TIMEOUT = 5;

ignore_user_abort(true); // クライアント切断後もスクリプト実行を続ける
header("Content-Length: 0"); // 空のレスポンス
flush(); // レスポンスを送信

$logFile = __DIR__ . '/logs/timer_' . $client_id . ".log";

writeSeparator($logFile);
writeLog("Program started: Sleep " . $TIMEOUT . "s...", $logFile);

// 待機
sleep($TIMEOUT);

writeLog("Sleep completed", $logFile);

// 状態を確認
$get_group_status_start_time = microtime(true);

$results = getGroupStatus($job_id, $group_id, $logFile);

$get_group_status_end_time = microtime(true);
$formatted_time = number_format(($get_group_status_end_time - $get_group_status_start_time) * 1000, 3) . " ms";
writeLog("getGroupStatus completed. Execution time: " . $formatted_time, $logFile);

foreach ($results as $result) {
    if ($result['status'] == SubJobStatus::ResultPending) {
        // 未完了のサブジョブがある場合リセット
        $reset_group_status_start_time = microtime(true);

        resetGroupStatus($job_id, $group_id, $logFile);

        $reset_group_status_end_time = microtime(true);
        $formatted_time = number_format(($reset_group_status_end_time - $reset_group_status_start_time) * 1000, 3) . " ms";
        writeLog("resetGroupStatus completed. Execution time: " . $formatted_time, $logFile);

        // 結果ファイルも削除
        $delete_group_directory_start_time = microtime(true);

        deleteGroupDirectory($job_id, $group_id);

        $delete_group_directory_end_time = microtime(true);
        $formatted_time = number_format(($delete_group_directory_end_time - $delete_group_directory_start_time) * 1000, 3) . " ms";
        writeLog("deleteGroupDirectory completed. Execution time: " . $formatted_time, $logFile);

        break;
    }
}

function deleteGroupDirectory($job_id, $group_id) {
    // ディレクトリパスを生成
    $directoryPath = "uploads/job_$job_id/group_$group_id";

    // ディレクトリが存在するか確認
    if (!is_dir($directoryPath)) {
        echo "Directory does not exist: $directoryPath\n";
        return false;
    }

    // ディレクトリとその中身を削除
    $files = array_diff(scandir($directoryPath), ['.', '..']); // ファイルとディレクトリを取得

    foreach ($files as $file) {
        $filePath = "$directoryPath/$file";
        if (is_file($filePath)) {
            unlink($filePath); // ファイルを削除
        } elseif (is_dir($filePath)) {
            deleteGroupDirectory($job_id, "$group_id/$file"); // 再帰的に削除
        }
    }

    // 空のディレクトリを削除
    if (rmdir($directoryPath)) {
        echo "Directory deleted: $directoryPath\n";
        return true;
    } else {
        echo "Failed to delete directory: $directoryPath\n";
        return false;
    }
}

$program_end_time = microtime(true);
$formatted_time = number_format(($program_end_time - $program_start_time) * 1000, 3) . " ms";
writeLog("Program completed. Total execution time: " . $formatted_time, $logFile);
writeSeparator($logFile);

?>
