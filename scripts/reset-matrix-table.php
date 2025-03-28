<?php
function resetMatrixTable($GROUP = 4, $RANK = 3) {
try {
    $pdo = new PDO('mysql:host=localhost;dbname=practice;charset=utf8', 'root', 'root', array(PDO::ATTR_PERSISTENT => true));

    // 外部キー制約を無効化
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // table_registry からすべての table_name を取得
    $sql = "SELECT table_name FROM table_registry";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $table_names = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 各 table_name に対して TRUNCATE TABLE を実行
    foreach ($table_names as $table_name) {
        $truncateSql = "TRUNCATE TABLE `$table_name`";
        $pdo->exec($truncateSql);
    }

    // 外部キー制約を再び有効化
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // table_registryのstatusを0に更新
    $sql = "UPDATE table_registry SET status = 0";
    $pdo->exec($sql);

    // トランザクションの開始
    $pdo->beginTransaction();

    // 複数の `INSERT` 文を実行
    foreach ($table_names as $table_name) {
        for ($group_id = 1; $group_id <= $GROUP; $group_id++) {
            for ($rank = 0; $rank < $RANK; $rank++) {
                $sql = "INSERT INTO `$table_name` (group_id, rank) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$group_id, $rank]);
            }
        }
    }

    // コミット
    $pdo->commit();

    echo "Records inserted successfully using transaction.";
    deleteDirectoryRecursively('../uploads/.');
} catch (PDOException $e) {
    // エラー時にはロールバック
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
}

function deleteDirectoryRecursively($directory)
{
    if (!is_dir($directory)) {
        echo "The path is not a directory: $directory\n";
        return;
    }

    // ディレクトリ内のすべてのファイルとサブディレクトリを取得
    $items = glob($directory . '/*');
    foreach ($items as $item) {
        if (is_file($item)) {
            // ファイルを削除
            unlink($item);
            echo "Deleted file: $item\n";
        } elseif (is_dir($item)) {
            // サブディレクトリを再帰的に削除
            deleteDirectoryRecursively($item);
        }
    }

    // 最終的に空になったディレクトリ自体を削除
    rmdir($directory);
    echo "Deleted directory: $directory\n";
}

?>
