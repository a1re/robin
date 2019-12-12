<h1>
    <img width="30" height="30" src="<?=$home_team["logo"]?>" style="vertical-align: middle" />
    <?=$home_team["name"]?>
    <?=$home_team_score?>&hyphen;<?=$away_team_score?>
    <?=$away_team["name"]?>
    <img width="30" height="30" src="<?=$away_team["logo"]?>" style="vertical-align: middle"  />
    (<?=date("d.m H:i", $schedule_time)?>)
</h1>

<textarea class="copy" id="table-score">
[table class="table-score"]
<img class="alignnone size-thumbnail" src="<?=$home_team["logo"]?>" width="75" height="75" /> <?=$home_team["name"]?>,<?=$home_team_score?>&hyphen;<?=$away_team_score?>,<?=$away_team["name"]?> <img class="alignnone size-thumbnail" src="<?=$away_team["logo"]?>" width="75" height="75" />
[/table]
</textarea>
<button class="copy-text" data-target="table-score">Copy</button>