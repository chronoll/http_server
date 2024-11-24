<?php

// 各ジョブの状態 (:table_registry.status)
enum JobStatus: int
{
    case NoDistribution = 0; // SubJobStatus=0のみ(配布なし)
    case startedDistribution = 1; // SubJobStatus=0あり(配布開始)
    case ResultsPending = 2; // SubJobStatus=1と2(配布済み、結果待ちあり)
    case ResultsAllReceived = 3; // SubJobStatus=3のみ(全結果受信済み)
}

// 各サブジョブの状態 (:[jobのテーブル].status)
enum SubJobStatus: int
{
    case NoDistribution = 0; // 配布前
    case ResultPending = 1; // 配布済み、結果待ち
    case ResultReceived = 2; // 結果受信済み
}
?>
