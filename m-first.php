<?php

include 'handler.php';

// GET変数の取得
$job = isset($_GET['job_id']) ? intval($_GET['job_id']) : null; // job_idを取得
$groupCount = isset($_GET['group_count']) ? intval($_GET['group_count']) : null; // group_countを取得

// 必須パラメータの確認
if ($job === null || $groupCount === null) {
    echo json_encode(['error' => 'Missing required GET parameters: job_id, group_count']);
    exit;
}

$REQUIRED_COUNT = 2;

// // 動作確認用固定値
// $job = 1;
// $groupCount = 3;

// 必須パラメータの確認
if ($job === null || $groupCount === null) {
    echo json_encode(['error' => 'Missing required GET parameters: job_id, group_id, rank']);
    exit;
}

$mergedFileName = "merged";

$allResults = []; // 全体の結果を保持する配列
$majorityContents = []; // 多数決の内容を保持する配列

    // 動的にfilepathsを生成
    $filepaths = [];
    for ($group = 1; $group <= $groupCount; $group++) {
        $filepaths[$group] = "uploads/job_$job/group_$group/$mergedFileName";
    }

    // ファイル内容の比較処理
    $contents = [];
    foreach ($filepaths as $group => $path) {
        if (file_exists($path)) {
            $contents[$group] = file_get_contents($path);
        } else {
            echo "File not found: $path\n";
            $contents[$group] = null;
        }
    }

    // 多数決の内容を判定
    $counts = array_count_values($contents);

    if (max($counts) < $REQUIRED_COUNT) {
        addGroup($job);
        echo "Majority not reached, added a new group\n";
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
    if (!is_dir(dirname($resultFilePath))) {
        mkdir(dirname($resultFilePath), 0777, true); // ディレクトリを作成
    }
    file_put_contents($resultFilePath, $majorityContent);

// 全ての結果をJSONで出力
echo json_encode($matchingGroups);
?>
