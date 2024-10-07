<?php

if (isset($_GET['RECEIVER'])) {
    $receiver = $_GET['RECEIVER'];
    $filename = 'objects/hello' . $receiver;
}

if (file_exists($filename)) {
    // バッファリングをオフ
    if (ob_get_level()) {
        ob_end_clean();
    }
    // header('Content-Description: File Transfer');
    // header('Content-Type: application/octet-stream');
    // header('Content-Disposition: attachment; filename="' . $filename);
    // header('Expires: 0');
    // header('Cache-Control: must-revalidate');
    // header('Pragma: public');
    // header('Content-Length: ' . filesize($filename));
    
    // ファイルをバイナリモードで開いて送信
    readfile($filename);
    exit;

} else {
    echo "filename not found.";
}

?>