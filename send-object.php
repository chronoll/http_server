<?php

include 'handler.php';

if (isset($_GET['ID'])) {
    $client_id = $_GET['ID'];
} else {
    http_response_code(400); // Bad Request
    echo "No client_id specified.";
    exit;
}

$result = distributeJob($client_id);
$filename = $result["filename"];

if (file_exists($filename)) {
    // バッファリングをオフ
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Length: ' . filesize($filename));
    header('MPI-Rank: ' . $result['rank']);
    header('Group-ID: ' . $result['group_id']);
    header('Job-ID:'. $result['job_id']);
    header('Sub-Job-ID: ' . $result['sub_job_id']); // 各jobテーブルのid
    
    // バイナリモードで開く
    $fp = fopen($filename, 'rb');

    if ($fp) {
        // ファイル内容を出力
        fpassthru($fp);
        fclose($fp);
    } else {
        http_response_code(500); // Internal Server Error
        echo "バイナリファイルを開けませんでした" . htmlspecialchars($filename);
        exit;
    }
    exit;

} else {
    http_response_code(500); // Internal Server Error
    echo "DBから取得したファイル名が不正です" . htmlspecialchars($filename);
    exit;
}

?>
