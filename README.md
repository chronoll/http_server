# http_server
xamppのhtdocsに当たる部分に配置する (/opt/lampp/htdocs など)
apacheサーバの処理を記述する

## HTTPリクエストで動作するプログラム

### 主要なPHPスクリプト
- **send-object.php**
  - 配布リクエストを受け取る
  - 次に配布するジョブのバイナリを出力（送信）する
- **receive-result.php**
  - 計算結果ファイルを受信して格納
  - DBに格納された状態を更新する

## 補助プログラム（PHPスクリプト）

### データベース・共通機能
- **handler.php**
  - DB操作に関する関数群を定義
- **common.php**
  - 共通して利用するコード等を定義

### ジョブ管理
- **timer.php**
  - 指定したグループに対して状態を監視
  - 一定時間経過後に未完了のサブジョブがある場合は状態をリセット・結果ファイル削除

### 計算結果処理
- **計算結果のマージ**
  - **merge.php**
    - [EPプログラム用] 各ワーカからの結果ファイルから、計算結果の部分を抜き出して別ファイルに書き出し
  - **merge-matrix.php**
    - [matrixプログラム用] 各ワーカからの結果ファイルから計算結果の部分を抜き出して別ファイルに書き出し

- **多数決処理**
  - **majority.php**
    - マージされた計算結果の内容を比較し、一番多い内容を結果として書き出し
  - **m-first.php**
    - m-first多数決法に基づいて多数決結果を書き出し、必要に応じてDB更新

## バイナリ格納
- **programs/**
  - 配布するバイナリを格納するディレクトリ
