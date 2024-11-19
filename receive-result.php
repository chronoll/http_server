<?php
include 'handler.php';

if (isset($_GET['ID']) && isset($_GET['GROUP']) && isset($_GET['RANK']) && isset($_GET['MATRIX_ID'])) {
    $client_id = $_GET['ID'];
    $group_id = $_GET['GROUP'];
    $rank = $_GET['RANK'];
    $matrix_id = $_GET['MATRIX_ID'];
} else {
    http_response_code(400); // Bad Request
    echo "No client_id specified.";
    exit;
}

// ファイルを保存するディレクトリ
$uploadDir = "uploads/";
$groupDir = $group_id . "/";
$fullDir = $uploadDir . $groupDir;
$filename = $_SERVER['HTTP_X_FILENAME'] ?? 'default.txt';
$filename = $group_id . "_" . $rank . "_" . $_SERVER['HTTP_X_FILENAME'];

// // ディレクトリが存在しない場合は作成
// if (!is_dir($uploadDir)) {
//     mkdir($uploadDir, 0777, true);
// }

// groupDirが存在しない場合は作成
if (!is_dir($fullDir)) {
    mkdir($fullDir, 0777, true);
}

chmod($uploadDir, 0777);
chmod($fullDir, 0777);

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
    saveProgress($matrix_id, 2); // 2 is the status code for "file received" in the matrix table
} else {
    http_response_code(500);
    echo "Failed to save file.";
}
?>
