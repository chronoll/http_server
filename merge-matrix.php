<?php
if (!isset($_GET['job_id']) || !isset($_GET['group_id']) || !isset($_GET['rank_count'])) {
    http_response_code(400);
    echo "Missing required GET variables.";
    exit;
}

$job = intval($_GET['job_id']);
$group = intval($_GET['group_id']);
$rank = intval($_GET['rank_count']);

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

// 各 `result_[rank]` ファイルから必要な行を取得して `merged` に書き込む
for ($i = 0; $i < $rank; $i++) {
    $resultFilePath = $fullDir . "result_" . $i;
    if (!file_exists($resultFilePath)) {
        http_response_code(404);
        echo "File not found: " . htmlspecialchars($resultFilePath);
        fclose($mergedFile);
        exit;
    }

    // ファイルを1行ずつ読み込む
    $file = fopen($resultFilePath, 'r');
    if (!$file) {
        http_response_code(500);
        echo "Failed to open file: " . htmlspecialchars($resultFilePath);
        fclose($mergedFile);
        exit;
    }

    // 指定の行を取得
    $line = null;
    for ($j = 0; $j <= $i; $j++) {
        $line = fgets($file);
        if ($line === false) {
            break; // 行が存在しない場合、ループを終了
        }
    }
    fclose($file);

    // `merged` に行を書き込む
    if ($line !== null) {
        fwrite($mergedFile, $line);
    } else {
        fwrite($mergedFile, "\n"); // 行がなければ空行を追加
    }
}

// `merged` ファイルを閉じる
fclose($mergedFile);

http_response_code(200);
echo "Merged file created successfully: " . htmlspecialchars($mergedFilePath);

?>
