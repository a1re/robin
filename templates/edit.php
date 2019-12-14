<html>
<head>
    <title>Robin the bot</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon.png" />
    <link rel="stylesheet" type="text/css" media="screen" href="/assetts/style.css" />
</head>
<body>
    <div id="wrapper">
        <?
            if (!$referer && array_key_exists("HTTP_REFERER", $_SERVER) && $_SERVER['HTTP_REFERER'] != null) {
                $referer = $_SERVER["HTTP_REFERER"];
            }
        ?>
        <? if ($referer): ?>
        <p>
            <a href="<?=$referer?>">&larr; Назад</a>
        </p>
        <? endif; ?>
        <? if ($id): ?>
        <h1>
            <?=str_replace("/", " &mdash; ", $id)?>
            <a href="https://google.com/search?q=<?=str_replace("/", " ", $id)?>" target="_blank">
                <img src="https://img.icons8.com/color/48/000000/google-logo.png" width="20" height="20" style="vertical-align:middle" />
            </a>
        </h1>
        <? endif; ?>
        <form action="edit.php?<?=$_SERVER["QUERY_STRING"]?>" method="post">
        <input type="hidden" name="id" value="<?=$id?>" />
        <input type="hidden" name="type" value="<?=$type?>" />
        <? if ($referer): ?>
        <input type="hidden" name="referer" value="<?=$referer?>" />
        <? endif; ?>
        <?=$result?>
        <label for="content">Настройка:</label>
        <textarea name="content" id="content" class="big"><?=$content?></textarea>
        <p>
            <label for="password">Пароль:</label>
            <input type="password" name="password" value="<?=$password?>" id="password" />
        </p>
        <p>
            <button class="submit" type="submit">Сохранить</button>
        </p>
    </div>
</body>
</html>