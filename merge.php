<?php

$program_start_time = microtime(true);

require_once 'common.php';

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

$logFile = __DIR__ . '/logs/merge_job_' . $job . "_group_" . $group. ".log";
writeSeparator($logFile);
writeLog("Program started", $logFile);

// 必要なディレクトリが存在するか確認
if (!is_dir($fullDir)) {
    http_response_code(404);
    echo "Directory does not exist.";
    exit;
}

$mergedFilePath = $fullDir . $mergedFileName;

// `merged` ファイルを開く（新規作成または上書き）
$fopen_start_time = microtime(true);

$mergedFile = fopen($mergedFilePath, 'w');
if (!$mergedFile) {
    http_response_code(500);
    echo "Failed to create merged file.";
    exit;
}

$fopen_end_time = microtime(true);
$formatted_time = number_format(($fopen_end_time - $fopen_start_time) * 1000, 3) . " ms";
writeLog("Open merged file completed. Execution time: " . $formatted_time, $logFile);

$isExistID0 = false;
$isExistSums = false;

$search_start_time = microtime(true);

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
        $isExistID0 = true;
        // ファイル内の全行を確認
        foreach ($lines as $line) {
            // "Sums =" で始まる行を見つけたら書き込む
            if (strpos($line, "Sums =") === 0) {
                $isExistSums = true;
                $write_file_start_time = microtime(true);

                fwrite($mergedFile, $line);

                $write_file_end_time = microtime(true);
                $formatted_time = number_format(($write_file_end_time - $write_file_start_time) * 1000, 3) . " ms";
                writeLog("Write line completed. Execution time: " . $formatted_time, $logFile);

                break; // Sums = の行を見つけたら、このファイルの処理を終了
            }
        }
    }
}

$search_end_time = microtime(true);
$formatted_time = number_format(($search_end_time - $search_start_time) * 1000, 3) . " ms";
writeLog("Search result files completed. Execution time: " . $formatted_time, $logFile);

if (!$isExistID0) {
    writeLog("ID0 not found in any result files.", $logFile);
}

if (!$isExistSums) {
    writeLog("Sums not found in any result files.", $logFile);
}

// `merged` ファイルを閉じる
$fclose_start_time = microtime(true);

fclose($mergedFile);

$fclose_end_time = microtime(true);
$formatted_time = number_format(($fclose_end_time - $fclose_start_time) * 1000, 3) . " ms";
writeLog("Close merged file completed. Execution time: " . $formatted_time, $logFile);

http_response_code(200);
echo "Merged file created successfully: " . htmlspecialchars($mergedFilePath);

$program_end_time = microtime(true);
$formatted_time = number_format(($program_end_time - $program_start_time) * 1000, 3) . " ms";
writeLog("Program completed. Total execution time: " . $formatted_time, $logFile);
writeSeparator($logFile);
?>
