<?php
// ファイルを保存するディレクトリ
$uploadDir = "uploads/";
$filename = $_SERVER['HTTP_X_FILENAME'] ?? 'default.txt';

// // ディレクトリが存在しない場合は作成
// if (!is_dir($uploadDir)) {
//     mkdir($uploadDir, 0777, true);
// }

chmod($uploadDir, 0777);

// ファイルのフルパス
$filepath = $uploadDir . $filename;

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
} else {
    http_response_code(500);
    echo "Failed to save file.";
}
?>
