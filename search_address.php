<?php
require_once("db_connect.php");

// 画面から送られてきた郵便番号をキャッチ
$search_post_number = isset($_POST['zipcode']) ? $_POST['zipcode'] : "";

// ハイフン（- や ー）をすべて消去して数字だけの状態にする
$search_post_number = str_replace(['-', 'ー'], '', $search_post_number);

if (strlen($search_post_number) === 7) {

    try {
        // ==========================================
        // 🌐 1. 【課題①】まずは速攻でAPIから最新の住所データを取得する！
        // ==========================================
        $api_url = "https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=" . $search_post_number;
        $response = @file_get_contents($api_url);
        
        if ($response !== false) {
            $data = json_decode($response, true);

            // APIから無事に住所が返ってきた場合
            if ($data && isset($data['response']['location'])) {
                $location = $data['response']['location'][0];
                
                $api_pref  = $location['prefecture']; // 例：東京都
                $api_city  = $location['city'];       // 例：新宿区
                $api_town  = $location['town'];       // 例：西新宿

                // ==========================================
                // 🗄️ 2. 取得した住所をあなたの自作テーブル群に流し込む（保存）
                // ==========================================
                
                // --- ① prefecture テーブルに登録 or 既に登録済ならID取得 ---
                $p_stmt = $pdo->prepare("SELECT prefecture_id FROM prefecture WHERE prefecture = ?");
                $p_stmt->execute([$api_pref]);
                $p_row = $p_stmt->fetch();
                
                if ($p_row) {
                    $prefecture_id = $p_row['prefecture_id'];
                } else {
                    $ins_p = $pdo->prepare("INSERT INTO prefecture (prefecture) VALUES (?)");
                    $ins_p->execute([$api_pref]);
                    $prefecture_id = $pdo->lastInsertId();
                }

                // --- ② city テーブルに登録 or 既に登録済ならID取得 ---
                $c_stmt = $pdo->prepare("SELECT city_id FROM city WHERE city = ?");
                $c_stmt->execute([$api_city]);
                $c_row = $c_stmt->fetch();
                
                if ($c_row) {
                    $city_id = $c_row['city_id'];
                } else {
                    $ins_c = $pdo->prepare("INSERT INTO city (city) VALUES (?)");
                    $ins_c->execute([$api_city]);
                    $city_id = $pdo->lastInsertId();
                }

                // --- ③ address テーブルに登録（重複エラー防止のため、なければ入れる形にすると安全です） ---
                $a_stmt = $pdo->prepare("SELECT post_number FROM address WHERE post_number = ?");
                $a_stmt->execute([$search_post_number]);
                if (!$a_stmt->fetch()) {
                    $ins_a = $pdo->prepare("INSERT INTO address (post_number, prefecture_id, city_id, street) VALUES (?, ?, ?, ?)");
                    $ins_a->execute([$search_post_number, $prefecture_id, $city_id, $api_town]);
                }

                // ==========================================
                // 🎯 3. 【真骨頂】保存されたあなたの自作DBから、JOINのSQLで住所を引っ張る！
                // ==========================================
                $sql = "SELECT a.post_number, p.prefecture, c.city, a.street FROM address AS a
                        JOIN prefecture AS p ON a.prefecture_id = p.prefecture_id
                        JOIN city AS c ON a.city_id = c.city_id
                        WHERE a.post_number = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$search_post_number]);
                $result = $stmt->fetch();

                if ($result) {
                    // 相方さんのデザインに合わせた形で画面に出力！
                    echo "住所:" . htmlspecialchars($result["prefecture"] . $result["city"] . $result["street"], ENT_QUOTES, "UTF-8");

                    // ==========================================
                    // 🌟 4. 【課題②】検索履歴データ（search_logs）をDBに蓄積する
                    // ==========================================
                    try {
                        $log_sql = "INSERT INTO search_logs (post_number) VALUES (?)";
                        $log_stmt = $pdo->prepare($log_sql);
                        $log_stmt->execute([$search_post_number]); 
                    } catch (PDOException $e) {
                        // 履歴保存のエラーは画面を壊さないようにスルー
                    }
                }

            } else {
                echo "該当する郵便番号の住所は見つかりませんでした。";
            }
        } else {
            echo "APIへの接続に失敗しました。";
        }

    } catch (PDOException $e) {
        echo "データベースエラーが発生しました。";
    }

} else {
    echo "郵便番号は7桁の数字で入力してください。";
}

