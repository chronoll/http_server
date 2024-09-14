<?php

$data = file_get_contents('php://input');

$file = 'data.txt';
file_put_contents($file, $data);

echo "Data received to $file";

?>