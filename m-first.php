<?php

$program_start_time = microtime(true);

require_once 'handler.php';
require_once 'common.php';

$REQUIRED_COUNT = 3;

// GET変数の取得
$job = isset($_GET['job_id']) ? intval($_GET['job_id']) : null; // job_idを取得
$groupCount = isset($_GET['group_count']) ? intval($_GET['group_count']) : null; // group_countを取得

// 必須パラメータの確認
if ($job === null || $groupCount === null) {
    echo json_encode(['error' => 'Missing required GET parameters: job_id, group_count']);
    exit;
}

// 必須パラメータの確認
if ($job === null || $groupCount === null) {
    echo json_encode(['error' => 'Missing required GET parameters: job_id, group_id, rank']);
    exit;
}

$logFile = __DIR__ . '/logs/m-first_job_' . $job . ".log";
writeSeparator($logFile);
writeLog("Program started: REQUIRED_COUNT=" . $REQUIRED_COUNT, $logFile);

$mergedFileName = "merged";

$allResults = []; // 全体の結果を保持する配列
$majorityContents = []; // 多数決の内容を保持する配列

// 動的にfilepathsを生成
$filepaths = [];
for ($group = 1; $group <= $groupCount; $group++) {
    $filepaths[$group] = "uploads/job_$job/group_$group/$mergedFileName";
}

echo "group_count: " . json_encode($groupCount) . "\n";

// ファイル内容の比較処理
$compare_start_time = microtime(true);

$contents = [];
foreach ($filepaths as $group => $path) {
    if (file_exists($path)) {
        $contents[$group] = file_get_contents($path);
    } else {
        echo "File not found: $path\n";
        $contents[$group] = null;
    }
}

$compare_end_time = microtime(true);
$formatted_time = number_format(($compare_end_time - $compare_start_time) * 1000, 3) . " ms";
writeLog("File comparison completed. Execution time: " . $formatted_time, $logFile);

// 多数決の内容を判定
$counts = array_count_values($contents);

echo "Counts: " . json_encode(max($counts)) . "\n";

if (max($counts) < $REQUIRED_COUNT) {
    // グループ追加による再配布
    $add_group_start_time = microtime(true);

    addGroup($job, $logFile);

    $add_group_end_time = microtime(true);
    $formatted_time = number_format(($add_group_end_time - $add_group_start_time) * 1000, 3) . " ms";
    writeLog("Added a new group. Execution time: " . $formatted_time, $logFile);

    echo "Majority not reached, added a new group\n";
    writeLog("Majority not reached, added a new group", $logFile);
    exit;
}

$majorityContent = array_search(max($counts), $counts);

// is_majorityがtrueのgroupを取得
$matchingGroups = [];
foreach ($contents as $group => $content) {
    if ($content === $majorityContent) {
        $matchingGroups[] = $group;
    }
}

// 結果ファイルに多数決の内容を書き込む
$resultFilePath = "results/job_$job/m-result";

$write_file_start_time = microtime(true);
if (!is_dir(dirname($resultFilePath))) {
    mkdir(dirname($resultFilePath), 0777, true); // ディレクトリを作成
}
file_put_contents($resultFilePath, $majorityContent);

$write_file_end_time = microtime(true);
$formatted_time = number_format(($write_file_end_time - $write_file_start_time) * 1000, 3) . " ms";
writeLog("Write majority content to result file completed. Execution time: " . $formatted_time, $logFile);

// 全ての結果をJSONで出力
echo json_encode($matchingGroups);
writeLog("Matching groups: " . json_encode($matchingGroups), $logFile);

$program_end_time = microtime(true);
$formatted_time = number_format(($program_end_time - $program_start_time) * 1000, 3) . " ms";
writeLog("Program completed. Total execution time: " . $formatted_time, $logFile);
writeSeparator($logFile);
?>
