<?php
// GET変数の取得
$job = isset($_GET['job_id']) ? intval($_GET['job_id']) : null; // job_idを取得
$groupCount = isset($_GET['group_id']) ? intval($_GET['group_id']) : null; // group_idを取得
$rank = isset($_GET['rank']) ? intval($_GET['rank']) : null; // rankを取得

// 必須パラメータの確認
if ($job === null || $groupCount === null || $rank === null) {
    echo json_encode(['error' => 'Missing required GET parameters: job_id, group_id, rank']);
    exit;
}

// 動的にfilepathsを生成
$filepaths = [];
for ($group = 1; $group <= $groupCount; $group++) {
    $filepaths[$group] = "uploads/job_$job/group_$group/result_$rank";
}

// ファイル内容の比較処理
$contents = [];
foreach ($filepaths as $group => $path) {
    if (file_exists($path)) {
        $contents[$group] = file_get_contents($path);
    } else {
        echo "File not found: $path";
        $contents[$group] = null;
    }
}

// 多数決の内容を判定
$counts = array_count_values($contents);
$majorityContent = array_search(max($counts), $counts);

// is_majorityがtrueのgroupを取得
$matchingGroups = [];
foreach ($contents as $group => $content) {
    if ($content === $majorityContent) {
        $matchingGroups[] = $group;
    }
}

// 結果ファイルに多数決の内容を書き込む
$resultFilePath = "results/job_$job/result_$rank";
if (!is_dir(dirname($resultFilePath))) {
    mkdir(dirname($resultFilePath), 0777, true); // ディレクトリを作成
}
file_put_contents($resultFilePath, $majorityContent);

// 結果をJSONで出力
echo json_encode($matchingGroups);
?>
