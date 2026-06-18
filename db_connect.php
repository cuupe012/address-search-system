<?php

$dns = "mysql:host=localhost;dbname=address_search;charset=utf8mb4";
$user = "root";
$password = "";

try{
    $pdo = new PDO($dns, $user, $password,[
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "データベースの接続に成功しました！！！<br>";

}catch(PDOException $e){
    echo "データベースへの接続に失敗しました.....<br>";
    echo "エラー内容：" . $e->getMessage();
    exit;
}