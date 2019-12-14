<h2>
    <img width="30" height="30" src="<?=$home_team["logo"]?>" class="inline-icon" />
    <a href="edit.php?<?=$home_team["composition_values"]?>"><?=$home_team["name"]?></a>
    <?=$score?>
    <a href="edit.php?<?=$away_team["composition_values"]?>"><?=$away_team["name"]?></a>
    <img width="30" height="30" src="<?=$away_team["logo"]?>" class="inline-icon"  />
    (<?=date("d.m H:i", $schedule_time)?>)
</h2>

<textarea class="copy" id="table-score">
[table class="table-score"]
<img class="alignnone size-thumbnail" src="<?=$home_team["logo"]?>" width="75" height="75" /> <?=$home_team["name"]?>,<strong><?=$score?></strong>,<?=$away_team["name"]?> <img class="alignnone size-thumbnail" src="<?=$away_team["logo"]?>" width="75" height="75" />
[/table]
</textarea>
<button class="copy-text" data-target="table-score">Copy</button>