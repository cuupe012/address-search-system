<?php

require_once("db_connect.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['zipcode'])) {

    $target_postnumber = str_replace("-", "", trim($_POST['zipcode']));
    
    $api_url = "https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=" . $target_postnumber;
    $response = file_get_contents($api_url);

    if ($response !== false) {
        $data = json_decode($response, true);
        
        
        if(isset($data["response"]["location"]) && !empty($data["response"]["location"])){
            $result = $data["response"]["location"][0];

            $pref_name   = $result["prefecture"]; 
            $city_name   = $result["city"];       
            $street_name = $result["town"];       

             try {
                $stmt = $pdo->prepare("SELECT prefecture_id FROM prefecture WHERE prefecture = ?");
                $stmt->execute([$pref_name]);
                $pref = $stmt->fetch();

                if(!$pref){
                    $stmt = $pdo->prepare("INSERT INTO prefecture (prefecture) VALUES (?)");
                    $stmt->execute([$pref_name]);
                    $prefecture_id = $pdo->lastInsertId();
                }else{
                    $prefecture_id = $pref["prefecture_id"];
                }
                
                $stmt = $pdo->prepare("SELECT city_id FROM city WHERE city = ?");
                $stmt->execute([$city_name]);
                $city = $stmt->fetch();

                if(!$city){
                    $stmt = $pdo->prepare("INSERT INTO city (city) VALUES (?)");
                    $stmt->execute([$city_name]);
                    $city_id = $pdo->lastInsertId();
                }else{
                    $city_id = $city["city_id"];
                }

                $stmt = $pdo->prepare("SELECT post_number FROM address WHERE post_number = ?");
                $stmt->execute([$target_postnumber]);

                if($stmt->fetch() === false){
                    $stmt = $pdo->prepare("INSERT INTO address (post_number, prefecture_id, city_id, street) VALUES (?,?,?,?)");
                    $stmt->execute([$target_postnumber, $prefecture_id, $city_id, $street_name]);
                    
                    echo "指定APIからデータを取得し、データベースへの格納に成功しました！<br>";
                    echo "【格納データ】 〒{$target_postnumber} : {$pref_name}{$city_name}{$street_name}";
                }else{
                    echo "この郵便番号(〒{$target_postnumber})は、既にデータベースに格納されています。";
                }

            }catch(PDOException $e){
                echo "データベースへの格納中にエラーが発生しました.....<br>";
                echo "エラー内容:" .$e->getMessage();
            }
        
        }else{
            echo "APIから正しい住所データを取得できませんでした。郵便番号を確認してください。";
        }
    }
}
?>
