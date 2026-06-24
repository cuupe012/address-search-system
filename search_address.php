<?php
require_once("db_connect.php");

$display_result = "検索結果";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['zipcode'])) {

    $zip_converted = mb_convert_kana(trim($_POST['zipcode']), "n", "UTF-8");
    $search_post_number = str_replace(["-", "ー", "－"], "", $zip_converted);
    
    if (preg_match("/^[0-9]{7}$/", $search_post_number)) {
        try {
           
            $log_sql = "INSERT INTO search_logs (post_number) VALUES (?)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([$search_post_number]);

           
            $sql = "SELECT a.post_number, p.prefecture, c.city, a.street FROM address AS a
                    JOIN prefecture AS p ON a.prefecture_id = p.prefecture_id
                    JOIN city AS c ON a.city_id = c.city_id
                    WHERE a.post_number = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$search_post_number]);
            $result = $stmt->fetch();

            if ($result) {
                
                $display_result = "📍 " . $result["prefecture"] . $result["city"] . $result["street"];
            } else {
               
                $api_url = "https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=" . $search_post_number;

                
                $response = @file_get_contents($api_url);

                if ($response !== false) {
                    $data = json_decode($response, true);
                    
                   
                    if (isset($data["response"]["location"][0])) {
                        $api_result = $data["response"]["location"][0]; 

                        $pref_name   = isset($api_result["prefecture"]) ? $api_result["prefecture"] : ""; 
                        $city_name   = isset($api_result["city"]) ? $api_result["city"] : "";       

                        $town_name   = isset($api_result["town"]) ? $api_result["town"] : "";
                        $sub_street  = isset($api_result["street"]) ? $api_result["street"] : "";
                        $street_name = $town_name . $sub_street; 


                        $stmt = $pdo->prepare("SELECT prefecture_id FROM prefecture WHERE prefecture = ?");
                        $stmt->execute([$pref_name]);
                        $pref = $stmt->fetch();

                        if (!$pref) {
                            $stmt = $pdo->prepare("INSERT INTO prefecture (prefecture) VALUES (?)");
                            $stmt->execute([$pref_name]);
                            $prefecture_id = $pdo->lastInsertId();
                        } else {
                            $prefecture_id = $pref["prefecture_id"];
                        }
                        
                       
                        $stmt = $pdo->prepare("SELECT city_id FROM city WHERE city = ?");
                        $stmt->execute([$city_name]);
                        $city = $stmt->fetch();

                        if (!$city) {
                            $stmt = $pdo->prepare("INSERT INTO city (city) VALUES (?)");
                            $stmt->execute([$city_name]);
                            $city_id = $pdo->lastInsertId();
                        } else {
                            $city_id = $city["city_id"];
                        }

                        $stmt = $pdo->prepare("SELECT post_number FROM address WHERE post_number = ?");
                        $stmt->execute([$search_post_number]);
                        if (!$stmt->fetch()) {
                            $stmt = $pdo->prepare("INSERT INTO address (post_number, prefecture_id, city_id, street) VALUES (?,?,?,?)");
                            $stmt->execute([$search_post_number, $prefecture_id, $city_id, $street_name]);
                        }
                        
                        $display_result = "📍 " . $pref_name . $city_name . $street_name;
                    } else {
                        $display_result = "❌ 該当する郵便番号が見つかりませんでした";
                    }
                } else {
                    $display_result = "❌ API通信エラーが発生しました";
                }
            }
        } catch (PDOException $e) {
        
            $display_result = "❌ DBエラー: " . $e->getMessage();
        }
    } else {
        $display_result = "❌ 郵便番号を7桁の数字で入力してください";
    }
}
?>