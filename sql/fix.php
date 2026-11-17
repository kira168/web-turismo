<?php
$content = file_get_contents('C:\xampp\htdocs\turismo3\sql\inserts.sql');
// The file is utf-16le, let's convert to utf-8
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');

// Fix commas before INSERT INTO
$content = preg_replace('/,\s*INSERT INTO/i', ";\n\nINSERT IGNORE INTO", $content);
$content = str_ireplace('INSERT INTO', 'INSERT IGNORE INTO', $content);
// Also replace any hanging commas at the end of the file
$content = preg_replace('/,\s*$/', ";\n", $content);

file_put_contents('C:\xampp\htdocs\turismo3\sql\inserts_fixed.sql', $content);
echo "Fixed!";
?>
