#!/bin/bash

# programs ディレクトリにあるすべての .c ファイルをループで処理
for file in programs/*.c
do
    # ファイル名から拡張子を取り除いた部分を抽出
    filename=$(basename "$file" .c)
    
    # gcc コマンドでコンパイルし、objects ディレクトリに出力
    gcc -o objects/"$filename" programs/"$filename".c
done

echo "コンパイルが完了しました。"
