<html>
<head>
    <title>Robin the bot</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon.png" />
    <style type="text/css">
        body { font-family:sans-serif; font-size: 10pt; }
        #wrapper { margin:0 auto; width:100%; min-width:300px; max-width:800px; }
        #url { width:300px; }
        #url + button { cursor:pointer; }
        td, th { padding:5px 10px; border:#ddd 1px solid; }
        th { text-align:left; }
        textarea.copy { width:100%; height:100px; background:#eee; font-family:'Courier New', monospace; font-weight:400; padding:5px; font-size:1em; border:#ddd 1px solid; border-radius:5px; }
        button { margin:5px 0; padding:5px; cursor:pointer; background:transparent; border:#ccc 2px solid; border-radius:5px; font-weight:700; color:#bbb; text-transform:uppercase; outline:none; }
        button:active { background:#ffffc8; border-color:#c1c289; color:#c1c289; padding-top:6px; padding-bottom:4px; }
        button.error { color:red; border-color:red; }
    </style>
</head>
<body>
    <div id="wrapper">
        <form method="post">
            <label>
                ESPN Game URL:
                <input type="text" value="<?=$url?>" name="url" id="url" />
                <button type="submit">Parse</button>
            </label>
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