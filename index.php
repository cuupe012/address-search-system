<?php
require_once("search_address.php");
?>

<!DOCTYPE html>
<html lang="ja"> 
<head>
    <!-- 文字化けを防ぐ、レスポンシブデザインのコード -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Search System</title>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #fff;
            padding: 10px;
            box-sizing: border-box;
        }

        
        form {
            width: 100%;
            max-width: 800px;
        }

        .system-panel {
            width: 100%;
            height: 500px;
            border: 1px solid #333;
            background: linear-gradient(180deg, #fff 0%, #7f7f7f 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            padding: 20px;
        }

        .title {
            font-size: 40px;
            font-weight: bold;
            font-style: italic;
            color: #000;
            margin: 0;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
        }

        .subtitle {
            font-size: 15px;
            margin: 5px 0 35px 0;
            color: #222;
        }

        /* 郵便番号入力エリア */
        .input-group{
            display: flex;
            flex-direction: column;
            align-items: center;      
            justify-content: center;
            width: 300px;             
            padding: 6px 10px;        
            border: 1.5px solid #333;
            border-radius: 20px;     
            background-color: #f2f2f2;
            box-sizing: border-box;
        }

        /* 検索ボタン含むエリア */
        .search-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 45px;
            padding-left: 15px;
        }
        
        /* 〒マーク */
        .postal-mark {
            font-size: 25px;
            font-weight: bold;
        }

        /* 郵便番号入力欄 */
        .zip-input {
            width: 100%;
            max-width: 100%;
            font-size: 15px;
            border: none;
            background: transparent;
            text-align: center;
            outline: none;
            height: 20px;
        }

        /* 検索ボタン */
        .search-btn {
            background-color: #fff;
            border: 1.5px solid #333;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
            
        /* ボタンにカーソルを乗せたとき */
        .search-btn:hover {
            background-color: #f0f0f0;
        } 

        /* ボタンをクリックしたとき */
        .search-btn:active {
            background-color:  #8d8d8d;
            box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.3);
            transform: scale(0.95);
        }

        /* ハイフン不要 */
        .form-notice {
            display: block;     
            font-size: 10px;
            color: #666;
            margin: 2px 0 0 0;
            line-height: 1;
            pointer-events: none;
        }

        /* 検索結果 */
        .result-box {
            background-color: #fff;
            border: 2px solid #333;
            border-radius: 15px;
            width: 100%;
            max-width: 550px;
            height: 55px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 15px;
            color: #7f7f7f;
            box-sizing: border-box;
            overflow-x: auto;
            white-space: nowrap;
        }

        /* コピーボタンの配置 */
        .copy-container {
            width: 100%;
            max-width: 550px;
            text-align: left;
            margin-top: 10px;
            box-sizing: border-box;
        }

        /* コピーボタンの設定 */
        .copy-link {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            padding: 0;
        }

        /* スマホでの見た目調節 */
        @media (max-width: 500px) {
            .title {
                font-size: 25px; 
            }
            .subtitle {
                font-size: 15px; 
                margin-bottom: 25px;
            }

            .search-area {
                width: 100%;
                max-width: 320px;
                gap: 10px;
                padding-left: 0;
                margin-bottom: 30px;
            }

            .zip-input {
                width: 100%;
                flex: 1;
            }
        }




    </style>
</head>

<body>
    <!-- データをサーバーに送る-->
    <form method="POST" action="">
        <div class="system-panel">
            <h1 class="title">Address Search System</h1>
            <p class="subtitle">郵便番号から住所検索</p>

            <div class="search-area">
                <span class="postal-mark">〒</span>
                

                <!-- 文字入力欄 -->
                <div class="input-group">
                    <input type="text" name="zipcode" class="zip-input" placeholder="郵便番号を入力" maxlength="7" inputmode="numeric" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                           value="<?php echo isset($_POST['zipcode']) ? htmlspecialchars($_POST['zipcode'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                    <p class="form-notice">＊ハイフン不要</p>
                </div>

                <!-- 検索ボタン -->
                <button type="submit" class="search-btn">🔍</button>
            </div>

           <!-- 検索結果出力 -->
            <div class="result-box" id="target-text"><?php echo isset($display_result) ? htmlspecialchars($display_result, ENT_QUOTES, 'UTF-8') : '検索結果'; ?></div>

            <!-- コピー機能 -->
            <div class="copy-container">
                <button type="button" class="copy-link" onclick="copyText(event)">📃<u>Copy</u></button>
            </div>
        </div>
    </form>
<body>
    
<!-- コピー機能のJavaScript -->
<script>
    function copyText(event) {
        event.preventDefault();
        const boxElement = document.getElementById('target-text');            
        const fullText = boxElement.innerText;
        
        // コピーしない条件
        if(fullText === "検索結果" || fullText.includes("❌")) return;
        // コピーする文字の整理
        const addressText = fullText.replace('📍', '').trim();

        // クリップボードにコピーする
        navigator.clipboard.writeText(addressText).then(() => {
            
            const btn = document.querySelector('.copy-link');
            btn.innerHTML = '✅ <u>Copied!</u>';
            setTimeout(() => {
                btn.innerHTML = '📋 <u>Copy</u>';
            }, 1500);
        }).catch(err => {
            alert('コピーに失敗しました');
        });
    }
</script>
</body>
</html>