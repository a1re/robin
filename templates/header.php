<h2>
    <img width="30" height="30" src="<?=$home_team["logo"]?>" class="inline-icon" />
    <?if ($home_team["rank"]):?><span class="rank">#<?=$home_team["rank"]?></span> <?endif;?><a href="edit.php?<?=$home_team["composition_values"]?>"><?=$home_team["name"]?></a>
    <?=$score?>
    <a href="edit.php?<?=$away_team["composition_values"]?>"><?=$away_team["name"]?></a> <?if ($away_team["rank"]):?> <span class="rank">#<?=$away_team["rank"]?></span><?endif;?>
    <img width="30" height="30" src="<?=$away_team["logo"]?>" class="inline-icon" />
    (<?=date("d.m H:i", $schedule_time)?>)
</h2>

<textarea class="copy" id="table-score">
[table class="table-score"]
<img class="alignnone size-thumbnail" src="<?=$home_team["logo"]?>" width="75" height="75" /> <?=$home_team["name"]?><?if ($home_team["rank"]):?> (<?=$home_team["rank"]?>)<?endif;?>,<strong><?=$score?></strong>,<?=$away_team["name"]?><?if ($away_team["rank"]):?> (<?=$away_team["rank"]?>)<?endif;?> <img class="alignnone size-thumbnail" src="<?=$away_team["logo"]?>" width="75" height="75" />
[/table]
</textarea>
<button class="copy-text" data-target="table-score">Copy</button>