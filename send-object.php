<?php

include 'handler.php';

// 次に配布するファイル名を取得
$record = getNextFilename();
if ($record) {
    $program_id = $record['id'];
    $filename = $record['filename'];
} else {
    echo "No valid record found.";
    exit;
}

if (isset($_GET['ID'])) {
    $client_id = $_GET['ID'];
} else {
    echo "No client_id specified.";
    exit;
}

if (file_exists($filename)) {
    // バッファリングをオフ
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Length: ' . filesize($filename));
    
    // バイナリモードで開く
    $fp = fopen($filename, 'rb');

    if ($fp) {
        // ファイル内容を出力
        fpassthru($fp);
        fclose($fp);

        // 配布状況を更新
        insertProgress($client_id, $program_id);
        updateProgramsStatus($program_id);
    } else {
        echo "File not found.";
    }
    exit;

} else {
    echo "filename not found.";
}

?>