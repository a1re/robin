<h2>Лидеры статистики</h2>
<table>
    <tr>
        <th>Категория</th>
        <th><?=$home_team["name"]?></th>
        <th><?=$away_team["name"]?></th>
    </tr>
    <tr>
        <td>Пас</td>
        <td><a href="edit.php?<?=$passing_leader["home_team"]["composition_values"]?>"><?=$passing_leader["home_team"]["first_name"] . " " . $passing_leader["home_team"]["last_name"]?></a> – <?=$passing_leader["home_team"]["stats"]?></td>
        <td><a href="edit.php?<?=$passing_leader["away_team"]["composition_values"]?>"><?=$passing_leader["away_team"]["first_name"] . " " . $passing_leader["away_team"]["last_name"]?></a> – <?=$passing_leader["away_team"]["stats"]?></td>
    </tr>
    <tr>
        <td>Вынос</td>
        <td><a href="edit.php?<?=$rushing_leader["home_team"]["composition_values"]?>"><?=$rushing_leader["home_team"]["first_name"] . " " . $rushing_leader["home_team"]["last_name"]?></a> – <?=$rushing_leader["home_team"]["stats"]?></td>
        <td><a href="edit.php?<?=$rushing_leader["away_team"]["composition_values"]?>"><?=$rushing_leader["away_team"]["first_name"] . " " . $rushing_leader["away_team"]["last_name"]?></a> – <?=$rushing_leader["away_team"]["stats"]?></td>
    </tr>
    <tr>
        <td>Прием</td>
        <td><a href="edit.php?<?=$receiving_leader["home_team"]["composition_values"]?>"><?=$receiving_leader["home_team"]["first_name"] . " " . $receiving_leader["home_team"]["last_name"]?></a> – <?=$receiving_leader["home_team"]["stats"]?></td>
        <td><a href="edit.php?<?=$receiving_leader["away_team"]["composition_values"]?>"><?=$receiving_leader["away_team"]["first_name"] . " " . $receiving_leader["away_team"]["last_name"]?></a> – <?=$receiving_leader["away_team"]["stats"]?></td>
    </tr>
</table>
<textarea class="copy" id="leaders">[table caption=&quot;Лидеры статистики&quot;]
Категория,<?=$home_team["name"]?>,<?=$away_team["name"]?><?=PHP_EOL?>
Пас,<?=mb_substr($passing_leader["home_team"]["first_name"],0,1) . ". " . $passing_leader["home_team"]["last_name"]?> – <?=$passing_leader["home_team"]["stats"]?>,<?=mb_substr($passing_leader["away_team"]["first_name"],0,1) . ". " . $passing_leader["away_team"]["last_name"]?> – <?=$passing_leader["away_team"]["stats"]?><?=PHP_EOL?>
Вынос,<?=mb_substr($rushing_leader["home_team"]["first_name"],0,1) . ". " . $rushing_leader["home_team"]["last_name"]?> – <?=$rushing_leader["home_team"]["stats"]?>,<?=mb_substr($rushing_leader["away_team"]["first_name"],0,1) . ". " . $rushing_leader["away_team"]["last_name"]?> – <?=$rushing_leader["away_team"]["stats"]?><?=PHP_EOL?>
Прием,<?=mb_substr($receiving_leader["home_team"]["first_name"],0,1) . ". " . $receiving_leader["home_team"]["last_name"]?> – <?=$receiving_leader["home_team"]["stats"]?>,<?=mb_substr($receiving_leader["away_team"]["first_name"],0,1) . ". " . $receiving_leader["away_team"]["last_name"]?> – <?=$receiving_leader["away_team"]["stats"]?><?=PHP_EOL?>
[/table]
</textarea>
<button class="copy-text" data-target="leaders">Copy</button>