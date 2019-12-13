<html>
<head>
    <title>Robin the bot</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon.png" />
    <link rel="stylesheet" type="text/css" media="screen" href="/assetts/style.css" />
</head>
<body>
    <div id="wrapper">
        <h1>Robin the URL parser</h1>
        <form method="get" action="index.php">
            <input type="text" value="<?=$url?>" name="url" id="url" placeholder="Enter URL to be parsed" class="url" />
            <button type="submit">Parse</button>
        </form>
        <?=$body?>
    </div>
    <script type="text/javascript">
        var buttons = document.querySelectorAll('.copy-text');
        buttons.forEach(function(button){
            button.addEventListener("click", function(e){                
                try {
                    var textarea_id = button.dataset.target;
                    var textarea = document.getElementById(textarea_id);
                    textarea.select();
                
                    document.execCommand('copy');

                    var temp = button.innerHTML;
                    button.innerHTML = "Copied!"
                    
                    setTimeout(function(){
                        button.innerHTML = temp;
                    }, 1000);
                } catch(e) {
                    button.classList.add('error');

                    var temp = button.innerHTML;
                    button.innerHTML = "Error"
                    
                    setTimeout(function(){
                        button.innerHTML = temp;
                        button.classList.remove('error');
                    }, 1000);
                }
            });
        });
    </script>
</body>
</html>