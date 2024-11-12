<?php
$GROUP = 4;
$RANK = 3;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

    // matrixレコードを削除, AUTO_INCREMENT をリセット
    $sql = "TRUNCATE TABLE matrix";
    $pdo->exec($sql);

    // progressレコードを削除, AUTO_INCREMENT をリセット
    $sql = "TRUNCATE TABLE progress";
    $pdo->exec($sql);

    // トランザクションの開始
    $pdo->beginTransaction();

    // 複数の `INSERT` 文を実行
    for ($group_id = 1; $group_id <= $GROUP; $group_id++) {
        for ($rank = 0; $rank < $RANK; $rank++) {
            $sql = "INSERT INTO matrix (group_id, rank) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$group_id, $rank]);
        }
    }

    // コミット
    $pdo->commit();

    echo "Records inserted successfully using transaction.";
} catch (PDOException $e) {
    // エラー時にはロールバック
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}

$directory = '../uploads/';

if (!is_dir($directory)) {
    echo "Error: $directory is not a directory.\n";
    return;
}

// メインディレクトリ内のすべてのファイルを削除
$files = glob($directory . '/*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
        echo "Deleted file: $file\n";
    } elseif (is_dir($file)) {
        // サブディレクトリ内のすべてのファイルを削除
        $subFiles = glob($file . '/*');
        foreach ($subFiles as $subFile) {
            if (is_file($subFile)) {
                unlink($subFile);
                echo "Deleted file: $subFile\n";
            }
        }
        // サブディレクトリ自体を削除
        rmdir($file);
        echo "Deleted subdirectory: $file\n";
    }
}

?>
