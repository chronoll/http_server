<?php
$program_start_time = microtime(true);

require_once 'handler.php';
require_once 'common.php';

if (!isset($_GET['ID'])) {
    http_response_code(400); // Bad Request
    echo "No client_id specified.";
    exit;
}
$client_id = $_GET['ID'];

$logFile = __DIR__ . '/logs/send_object_' . $client_id . ".log";

writeSeparator($logFile);
writeLog("Program started", $logFile);

// 配布するジョブ情報を取得
$distribute_start_time = microtime(true);

$result = distributeJob($client_id);

$distribute_end_time = microtime(true);
$formatted_time = number_format(($distribute_end_time - $distribute_start_time) * 1000, 3) . " ms";
writeLog("Distribute job completed. Execution time: " . $formatted_time, $logFile);

$filename = $result["filename"];
if (file_exists($filename)) {
    // バッファリングをオフ
    $clean_buffer_start_time = microtime(true);

    if (ob_get_level()) {
        ob_end_clean();
    }

    $clean_buffer_end_time = microtime(true);
    $formatted_time = number_format(($clean_buffer_end_time - $clean_buffer_start_time) * 1000, 3) . " ms";
    writeLog("Clean buffer completed. Execution time: " . $formatted_time, $logFile);
    
    // ヘッダー情報を配列に格納してループ処理で送信
    $header_start_time = microtime(true);

    $headers = [
        'Content-Length' => filesize($filename),
        'MPI-Rank' => $result['rank'],
        'Group-ID' => $result['group_id'],
        'Job-ID' => $result['job_id'],
        'Sub-Job-ID' => $result['sub_job_id']
    ];

    writeLog("Sending headers:", $logFile);
    foreach ($headers as $name => $value) {
        writeLog("  $name: $value", $logFile);
        header("$name: $value"); // ヘッダ情報を送信
    }

    $header_end_time = microtime(true);
    $formatted_time = number_format(($header_end_time - $header_start_time) * 1000, 3) . " ms";
    writeLog("Send headers completed. Execution time: " . $formatted_time, $logFile);
    
    // バイナリモードで開く
    $fopen_start_time = microtime(true);

    $fp = fopen($filename, 'rb');

    $fopen_end_time = microtime(true);
    $formatted_time = number_format(($fopen_end_time - $fopen_start_time) * 1000, 3) . " ms";
    writeLog("Open file completed. Execution time: " . $formatted_time, $logFile);

    // ファイル内容を出力
    $output_start_time = microtime(true);

    if ($fp) {
        fpassthru($fp);
        fclose($fp);
    } else {
        http_response_code(500); // Internal Server Error
        echo "バイナリファイルを開けませんでした" . htmlspecialchars($filename);
        exit;
    }

    $output_end_time = microtime(true);
    $formatted_time = number_format(($output_end_time - $output_start_time) * 1000, 3) . " ms";
    writeLog("Output file completed. Execution time: " . $formatted_time, $logFile);

    // グループ内最後尾のサブジョブの場合
    if ($result['rank'] == $result['rank_count'] - 1) {
        // タイマースクリプトを実行
        $timer_request_start_time = microtime(true);

        $url = sprintf(
            "http://localhost/http_server/timer.php?job_id=%s&sub_job_id=%s&group_id=%s&client_id=%s",
            urlencode($result['job_id']),
            urlencode($result['sub_job_id']),
            urlencode($result['group_id']),
            urlencode($client_id)
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_exec($ch);
        curl_close($ch);

        $timer_request_end_time = microtime(true);
        $formatted_time = number_format(($timer_request_end_time - $timer_request_start_time) * 1000, 3) . " ms";
        writeLog("Timer request completed. Execution time (not actual timer.php execution time, but just request): " . $formatted_time, $logFile);
    }

} else {
    http_response_code(500); // Internal Server Error
    echo "DBから取得したファイル名が不正です" . htmlspecialchars($filename);
    exit;
}

$program_end_time = microtime(true);
$formatted_time = number_format(($program_end_time - $program_start_time) * 1000, 3) . " ms";
writeLog("Program completed. Total execution time: " . $formatted_time, $logFile);
writeSeparator($logFile);

?>
