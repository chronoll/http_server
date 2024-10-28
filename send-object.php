<?php

include 'handler.php';

if (isset($_GET['ID'])) {
    $client_id = $_GET['ID'];
} else {
    echo "No client_id specified.";
    exit;
}

$result = processProgram($client_id);
$filename = $result['filename'];

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
    } else {
        echo "File not found.";
    }
    exit;

} else {
    echo "filename not found.";
}

?>