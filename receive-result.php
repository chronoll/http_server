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

// ファイルを保存するディレクトリ
$uploadDir = "uploads/";
$jobDir = $job_id . "/";     // $job_idのディレクトリ
$groupDir = $group_id . "/"; // $group_idのディレクトリ
$fullDir = $uploadDir . $jobDir . $groupDir; // 完全なディレクトリパス
$filename = $_SERVER['HTTP_X_FILENAME'] ?? 'default.txt';
$filename = $group_id . "_" . $rank . "_" . $filename;

// 必要なディレクトリを作成
if (!is_dir($fullDir)) {
    mkdir($fullDir, 0777, true);
}

// パーミッションの設定
chmod($uploadDir, 0777);
chmod($uploadDir . $jobDir, 0777); // $job_idのディレクトリ
chmod($fullDir, 0777);            // $group_idのディレクトリ

// ファイルのフルパス
$filepath = $fullDir . $filename;

// ファイルのフルパス
// $filepath = $uploadDir . $filename;
$filepath = $fullDir . $filename;

// php://input からファイル内容を読み込む
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
    updateStatus($job_id, $sub_job_id);
} else {
    http_response_code(500);
    echo "Failed to save file.";
}
?>
