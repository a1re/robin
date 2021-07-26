<h2>
    <img width="30" height="30" src="<?=$home_team["logo"]?>" class="inline-icon" />
    <?if ($home_team["rank"]):?><span class="rank">#<?=$home_team["rank"]?></span> <?endif;?><a href="edit.php?<?=$home_team["composition_values"]?>"><?=$home_team["name"]?></a>
    <?=$score?>
    <a href="edit.php?<?=$away_team["composition_values"]?>"><?=$away_team["name"]?></a> <?if ($away_team["rank"]):?> <span class="rank">#<?=$away_team["rank"]?></span><?endif;?>
    <img width="30" height="30" src="<?=$away_team["logo"]?>" class="inline-icon" />
    (<?=date("d.m H:i", $schedule_time)?>)
    <a href="https://www.youtube.com/results?search_query=<?=str_replace(" ", "+", str_replace("&", "%26", $away_team["original_name"]))?>+<?=str_replace(" ", "+", str_replace("&", "%26", $home_team["original_name"]))?>+<?=date("Y", $schedule_time)?>+highlights" target="_blank">
      <img src="https://img.icons8.com/color/48/000000/youtube-play.png" width="30" height="30" class="inline-icon" alt="Search highlights on Youtube"/>
    </a>
</h2>
<? if ($game_title): ?>
<p><?=$game_title?></p>
<? endif; ?>
<textarea class="copy" id="table-score">
[table class="table-score"<? if ($game_title): ?> caption="<?=$game_title?>"<? endif; ?>]
<img class="alignnone size-thumbnail" src="<?=$home_team["logo"]?>" width="75" height="75" /> <?=$home_team["name"]?><?if ($home_team["rank"]):?> (<?=$home_team["rank"]?>)<?endif;?>,<strong><?=$score?></strong>,<?=$away_team["name"]?><?if ($away_team["rank"]):?> (<?=$away_team["rank"]?>)<?endif;?> <img class="alignnone size-thumbnail" src="<?=$away_team["logo"]?>" width="75" height="75" />
[/table]
</textarea>
<button class="copy-text" data-target="table-score">Copy</button>