<h2>Ход игры</h2>
<table>
<? foreach($scoring_events as $e): ?>
    <tr>
        <td><?=$e["quarter"]?></td>
        <td><?=$e["scoring_method"]?></td>
        <td><strong><?=$e["team"]["abbr"]?></strong></td>
        <td>
            <? if ($e["author"] != NULL): ?><a href="edit.php?<?=$e["author"]["composition_values"]?>"><?=$e["author"]["full_name"]?></a> <? endif; ?>
            <?=$e["type"]?>
            <? if ($e["passer"] != NULL): ?><a href="edit.php?<?=$e["passer"]["composition_values"]?>"><?=$e["passer"]["full_name"]?></a><? endif; ?>
            <? if ($e["extra"] != NULL): ?>
                (<?=$e["extra"]["result"] ?><? if ($e["extra"]["author"] != NULL):?> <a href="edit.php?<?=$e["extra"]["author"]["composition_values"]?>"><?=$e["extra"]["author"]["full_name"]?></a><? endif; ?><? if(strlen($e["extra"]["type"]) > 0): ?> <?=$e["extra"]["type"]; ?><? endif; ?><? if ($e["extra"]["passer"] != NULL): ?> <a href="edit.php?<?=$e["extra"]["passer"]["composition_values"]?>"><?=$e["extra"]["passer"]["full_name"]?></a><? endif; ?>)
            <? endif; ?>
        </td>
        <td><?=$e["home_score"]?>:<?=$e["away_score"]?></td>
    </tr>
<? endforeach; ?>
</table>
<textarea class="copy" id="scoring">[table caption="Ход игры" th="0"]
<? foreach($scoring_events as $e): ?>
<?=$e["quarter"]?>
,<?=$e["scoring_method"] ?>
,<strong><?=$e["team"]["abbr"]?></strong>,<? if ($e["author"] != NULL) echo $e["author"]["full_name"]." "; ?>
<?=$e["type"]?>
<? if ($e["passer"] != NULL) echo " ".$e["passer"]["full_name"]; ?>
<? if ($e["extra"] != NULL): ?>
 (<?=$e["extra"]["result"] ?>
<? if ($e["extra"]["author"] != NULL) echo " ".$e["extra"]["author"]["full_name"]; ?>
<? if(strlen($e["extra"]["type"]) > 0) echo " ".$e["extra"]["type"]; ?>
<? if ($e["extra"]["passer"] != NULL) echo " ".$e["extra"]["passer"]["full_name"]; ?>
)<? endif; ?>
,<?=$e["home_score"]?>:<?=$e["away_score"]?>
<?=PHP_EOL?>
<? endforeach; ?>
[/table]</textarea>
<button class="copy-text" data-target="scoring">Copy</button>