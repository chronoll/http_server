<?php
if (!isset($_GET['job_id']) || !isset($_GET['group_id']) || !isset($_GET['rank'])) {
    http_response_code(400);
    echo "Missing required GET variables.";
    exit;
}

$job = intval($_GET['job_id']);
$group = intval($_GET['group_id']);
$rank = intval($_GET['rank']);

$mergedFileName = "merged";

$uploadDir = "uploads/";
$jobDir = "job_" . $job . "/";
$groupDir = "group_" . $group . "/";
$fullDir = $uploadDir . $jobDir . $groupDir;

// 必要なディレクトリが存在するか確認
if (!is_dir($fullDir)) {
    http_response_code(404);
    echo "Directory does not exist.";
    exit;
}

$mergedFilePath = $fullDir . $mergedFileName;

// `merged` ファイルを開く（新規作成または上書き）
$mergedFile = fopen($mergedFilePath, 'w');
if (!$mergedFile) {
    http_response_code(500);
    echo "Failed to create merged file.";
    exit;
}

// 各 `result_[rank]` ファイルを確認
for ($i = 0; $i < $rank; $i++) {
    $resultFilePath = $fullDir . "result_" . $i;
    if (!file_exists($resultFilePath)) {
        continue; // ファイルが存在しない場合はスキップ
    }

    // ファイルの内容を全て読み込む
    $lines = file($resultFilePath);
    if ($lines === false) {
        continue; // ファイルが読めない場合はスキップ
    }

    // 3行目（インデックス2）が "id0:" で始まるか確認
    if (isset($lines[2]) && strpos($lines[2], "id0:") === 0) {
        // ファイル内の全行を確認
        foreach ($lines as $line) {
            // "Sums =" で始まる行を見つけたら書き込む
            if (strpos($line, "Sums =") === 0) {
                fwrite($mergedFile, $line);
                break; // Sums = の行を見つけたら、このファイルの処理を終了
            }
        }
    }
}

// `merged` ファイルを閉じる
fclose($mergedFile);

http_response_code(200);
echo "Merged file created successfully: " . htmlspecialchars($mergedFilePath);
?>
