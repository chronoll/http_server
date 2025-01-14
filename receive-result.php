<?php
$program_start_time = microtime(true);

require_once 'handler.php';
require_once 'common.php';

if (isset($_GET['ID']) && isset($_GET['GROUP']) && isset($_GET['RANK']) && isset($_GET['JOB_ID']) && isset($_GET['SUB_JOB_ID'])) {
    $client_id = $_GET['ID'];
    $group_id = $_GET['GROUP'];
    $rank = $_GET['RANK'];
    $job_id = $_GET['JOB_ID'];
    $sub_job_id = $_GET['SUB_JOB_ID'];
} else {
    http_response_code(400); // Bad Request
    echo "No client_id specified.";
    exit;
}

$logFile = __DIR__ . '/logs/receive_result_' . $client_id . ".log";

writeSeparator($logFile);
writeLog("Program started", $logFile);

// client_idの検証
$get_client_id_start_time = microtime(true);

$expected_client_id = getClientID($job_id, $sub_job_id);

$get_client_id_end_time = microtime(true);
$formatted_time = number_format(($get_client_id_end_time - $get_client_id_start_time) * 1000, 3) . " ms";
writeLog("getClientID completed. Execution time: " . $formatted_time, $logFile);

if ($client_id != $expected_client_id) {
    http_response_code(408); // Request Timeout
    writeLog("Client ID mismatch. Expected: $expected_client_id, Actual: $client_id", $logFile);
    exit;
}

$uploadDir = "uploads/";
$jobDir = "job_" . $job_id . "/";
$groupDir = "group_" . $group_id . "/";
$fullDir = $uploadDir . $jobDir . $groupDir; // ファイルを保存するディレクトリ

$filename = $_SERVER['HTTP_X_FILENAME'] ?? 'default.txt'; // ファイル名
$filepath = $fullDir . $filename; // ファイルのパス

// 必要なディレクトリを作成
if (!is_dir($fullDir)) {
    mkdir($fullDir, 0777, true);
}

// ファイル内容を読み込む
$read_file_start_time = microtime(true);

$data = file_get_contents("php://input");

$read_file_end_time = microtime(true);
$formatted_time = number_format(($read_file_end_time - $read_file_start_time) * 1000, 3) . " ms";
writeLog("Read file completed. Execution time: " . $formatted_time, $logFile);

if ($data === false || strlen($data) === 0) {
    http_response_code(400);
    writeLog("No data received.", $logFile);
    exit;
}

// ファイルに書き込む
$write_file_start_time = microtime(true);

if (file_put_contents($filepath, $data) !== false) {
    http_response_code(200);
    writeLog("File saved successfully: " . $filepath, $logFile);
    $result = updateStatus($job_id, $sub_job_id, $client_id);
} else {
    http_response_code(500);
    writeLog("Failed to save file: " . $filepath, $logFile);
}

$write_file_end_time = microtime(true);
$formatted_time = number_format(($write_file_end_time - $write_file_start_time) * 1000, 3) . " ms";
writeLog("Write file completed. Execution time: " . $formatted_time, $logFile);

// グループ内の全サブジョブが完了したか確認
$isGroupCompleted = true;

// グループ内の全サブジョブの状態を取得
$get_group_status_start_time = microtime(true);

$groupStatus = getGroupStatus($job_id, $group_id);

$get_group_status_end_time = microtime(true);
$formatted_time = number_format(($get_group_status_end_time - $get_group_status_start_time) * 1000, 3) . " ms";
writeLog("getGroupStatus completed. Execution time: " . $formatted_time, $logFile);

foreach ($groupStatus as $status) {
    if ($status['status'] != SubJobStatus::ResultReceived) {
        $isGroupCompleted = false;
        writeLog("Group $group_id is not completed yet.", $logFile);
        break;
    }
}

// 全サブジョブ完了の場合
if ($isGroupCompleted) {
    // 結果をマージするスクリプトを実行
    $merge_request_start_time = microtime(true);

    $url = sprintf(
        "http://localhost/http_server/merge.php?job_id=%s&group_id=%s&rank=%s",
        urlencode($job_id),
        urlencode($group_id),
        urlencode(3) // TODO: 並列数を動的に変える
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_exec($ch);
    curl_close($ch);

    $merge_request_end_time = microtime(true);
    $formatted_time = number_format(($merge_request_end_time - $merge_request_start_time) * 1000, 3) . " ms";
    writeLog("Merge request completed. Execution time (not actual merge.php execution time, but just request): " . $formatted_time, $logFile);
}

// 全グループ完了の場合
if ($result['isJobCompleted']) {
    // 多数決スクリプトを実行
    $majority_request_start_time = microtime(true);

    $url = sprintf(
        // "http://localhost/http_server/majority.php?job_id=%s&group_count=%s",
        "http://localhost/http_server/m-first.php?job_id=%s&group_count=%s",
        urlencode($job_id),
        urlencode($result['group_count']),
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_exec($ch);
    curl_close($ch);

    $majority_request_end_time = microtime(true);
    $formatted_time = number_format(($majority_request_end_time - $majority_request_start_time) * 1000, 3) . " ms";
    writeLog("Majority request completed. Execution time (not actual script execution time, but just request): " . $formatted_time, $logFile);
}

$program_end_time = microtime(true);
$formatted_time = number_format(($program_end_time - $program_start_time) * 1000, 3) . " ms";
writeLog("Program completed. Total execution time: " . $formatted_time, $logFile);
writeSeparator($logFile);

?>
