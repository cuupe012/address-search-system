<?php

require_once("db_connect.php");

$search_post_number = "0000000";

try{
    $sql = "SELECT a.post_number, p.prefecture, c.city, a.street FROM address AS a
            JOIN prefecture AS p ON a.prefecture_id = p.prefecture_id
            JOIN city AS c ON a.city_id = c.city_id
            WHERE a.post_number = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search_post_number]);
    $result = $stmt->fetch();

    if($result){
        echo "<h2>データベース検索結果</h2>";
        echo "郵便番号:〒".htmlspecialchars($result["post_number"], ENT_QUOTES, "UTF-8")."<br>";
        echo "住所:".htmlspecialchars($result["prefecture"].$result["city"].$result["street"], ENT_QUOTES, "UTF-8")."<br>";
    }else{
        echo"しかし、該当する郵便番号の住所は見つかりませんでした。<br>";
    }
}catch(PDOException $e){
    echo "データベース検索中にエラーが発生しました。<br>";
    echo "エラー内容:".$e->getMessage();
}


//sdfsfsdf