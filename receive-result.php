<?php
include 'handler.php';

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

// client_idの検証
$expected_client_id = getClientID($job_id, $sub_job_id);
if ($client_id != $expected_client_id) {
    http_response_code(408); // Request Timeout
    echo "Your client_id is invalid.";
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
$data = file_get_contents("php://input");
if ($data === false || strlen($data) === 0) {
    http_response_code(400);
    echo "No data received.";
    exit;
}

// ファイルに書き込む
if (file_put_contents($filepath, $data) !== false) {
    http_response_code(200);
    echo "File saved successfully: " . htmlspecialchars($filepath);
    $isJobCompleted = updateStatus($job_id, $sub_job_id, $client_id);
    if ($isJobCompleted) {
        $url = sprintf(
            // "http://localhost/http_server/majority.php?job_id=%s&group_count=%s",
            "http://localhost/http_server/m-first.php?job_id=%s&group_count=%s",
            urlencode($job_id),
            urlencode(3), // TODO: group数を動的に変える
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_exec($ch);
        curl_close($ch);
    }
} else {
    http_response_code(500);
    echo "Failed to save file.";
}

$isGroupCompleted = true;
$groupStatus = getGroupStatus($job_id, $group_id);
foreach ($groupStatus as $status) {
    if ($status['status'] != SubJobStatus::ResultReceived->value) {
        $isGroupCompleted = false;
        break;
    }
}

if ($isGroupCompleted) {
    $url = sprintf(
        "http://localhost/http_server/merge.php?job_id=%s&group_id=%s&rank=%s",
        urlencode($job_id),
        urlencode($group_id), // TODO: group数を動的に変える
        urlencode(3) // TODO: 並列数を動的に変える
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_exec($ch);
    curl_close($ch);
}
?>
