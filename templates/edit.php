<?
    function pivot_and_refill($matrix, array $first_dimension_keys = [], array $second_dimension_keys = []): array
    {
        if (!is_array($matrix)) {
            return [];
        }
        
        if (count($second_dimension_keys) == 0) {
            $second_dimension_keys = array_keys($matrix);
        }
        if (count($first_dimension_keys) == 0) {
            foreach ($matrix as $subset) {
                $first_dimension_keys = array_merge($first_dimension_keys, array_keys($subset));
            }
            $first_dimension_keys = array_unique($first_dimension_keys);
        }
        
        $output_matrix = [];
        foreach ($first_dimension_keys as $key) {
            if (strlen($key) == 0) {
                continue;
            }
            $output_matrix[$key] = [];
            foreach ($second_dimension_keys as $subkey) {
                if (strlen($subkey) == 0) {
                    continue;
                }
                if (array_key_exists($subkey, $matrix) && array_key_exists($key, $matrix[$subkey])) {
                    $output_matrix[$key][$subkey] = $matrix[$subkey][$key];
                } else {
                    $output_matrix[$key][$subkey] = null;
                }
            }
        }
        return $output_matrix;
    }  
?><html>
<head>
    <title><? if ($id): ?><?=$id?> – <? endif; ?>Robin the bot</title>
    <link rel="icon" type="image/x-icon" href="assetts/favicon.ico" />
    <link rel="icon" type="image/png" sizes="512x512" href="assetts/favicon.png" />
    <link rel="stylesheet" type="text/css" media="screen" href="assetts/style.css?<?=time()?>" />
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
            <a href="https://google.com/search?q=<?=str_replace("&", "%26", str_replace(" ", "+", str_replace("/", " ", $id)))?>" target="_blank">
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
        
        <? $values = pivot_and_refill($values, $attributes, $locales); ?>
        <? if (count($values) > 0): ?>
        <table class="attributes">
            <thead>
                <tr>
                    <td>Атрибут</td>
                    <?
                        $first_row = reset($values);
                        $headers = is_array($first_row) ? array_keys($first_row) : [ ];
                    ?>
                    <? foreach ($headers as $header): ?>
                    <td><?=$header?></td>
                    <? endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <? foreach ($values as $name => $attributes): ?>
                <tr>
                    <th><?=Robin\Inflector::underscoreToWords($name)?></th>
                    <? if (is_array($attributes)): ?>
                    <? foreach ($attributes as $lang => $value): ?>
                    <td><input name="values[<?=htmlspecialchars($lang)?>][<?=htmlspecialchars($name)?>]" value="<?=htmlspecialchars($value)?>" /></td>
                    <? endforeach; ?>
                    <? endif; ?>
                </tr>
                <? endforeach; ?>
            </tbody>
        </table>
        <? endif; ?>
        <hr />
        
        <p>
            <label for="password">Пароль:</label>
            <input type="password" name="password" value="<?=htmlspecialchars($password)?>" id="password" />
        </p>
        <p>
            <button class="submit" type="submit">Сохранить</button>
        </p>
        
        <?=$result?>
    </div>
</body>
</html>