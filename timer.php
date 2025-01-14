<?php

include 'handler.php';

$job_id = $_GET['job_id'];
$sub_job_id = $_GET['sub_job_id'];
$group_id = $_GET['group_id'];
$client_id = $_GET['client_id'];
$TIMEOUT = 3;

ignore_user_abort(true); // クライアント切断後もスクリプト実行を続ける
header("Content-Length: 0"); // 空のレスポンス
flush(); // レスポンスを送信

// 待機
sleep($TIMEOUT);

// 状態を確認
$results = getGroupStatus($job_id, $group_id);

foreach ($results as $result) {
    if ($result['status'] == SubJobStatus::ResultPending) {
        resetGroupStatus($job_id, $group_id);
        deleteGroupDirectory($job_id, $group_id);
        break;
    }
}

function deleteGroupDirectory($job_id, $group_id) {
    // ディレクトリパスを生成
    $directoryPath = "uploads/job_$job_id/group_$group_id";

    // ディレクトリが存在するか確認
    if (!is_dir($directoryPath)) {
        echo "Directory does not exist: $directoryPath\n";
        return false;
    }

    // ディレクトリとその中身を削除
    $files = array_diff(scandir($directoryPath), ['.', '..']); // ファイルとディレクトリを取得

    foreach ($files as $file) {
        $filePath = "$directoryPath/$file";
        if (is_file($filePath)) {
            unlink($filePath); // ファイルを削除
        } elseif (is_dir($filePath)) {
            deleteGroupDirectory($job_id, "$group_id/$file"); // 再帰的に削除
        }
    }

    // 空のディレクトリを削除
    if (rmdir($directoryPath)) {
        echo "Directory deleted: $directoryPath\n";
        return true;
    } else {
        echo "Failed to delete directory: $directoryPath\n";
        return false;
    }
}

?>
