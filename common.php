<?php

// 各ジョブの状態 (:table_registry.status)
class JobStatus
{
    const NoDistribution = 0;      // SubJobStatus=0のみ(配布なし)
    const StartedDistribution = 1; // SubJobStatus=0あり(配布開始)
    const ResultsPending = 2;      // SubJobStatus=1と2(配布済み、結果待ちあり)
    const ResultsAllReceived = 3;  // SubJobStatus=3のみ(全結果受信済み)
}

class SubJobStatus
{
    const NoDistribution = 0;  // 配布前
    const ResultPending = 1;   // 配布済み、結果待ち
    const ResultReceived = 2;  // 結果受信済み
}

// ログ関数の定義
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // ログディレクトリが存在しない場合は作成
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    // ログ書き込み
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// 区切り線を書き込む関数
function writeSeparator($logFile) {
    $separator = PHP_EOL . "===========================================================". PHP_EOL . PHP_EOL;
    file_put_contents($logFile, $separator, FILE_APPEND);
}

?>
