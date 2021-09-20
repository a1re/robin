<h3>Таблицы</h3>
<? foreach ($table_list as $table_id=>$table): ?>
<h2><?= $table["title"] ?></h2>
<? foreach ($table["divisions"] as $division): ?>
<table>
  <tr>
    <th><? if (array_key_exists("name", $division)): ?><?= $division["name"] ?><? endif; ?></th>
    <th style="text-align: center;">Рез-т в конф.</th>
    <th style="text-align: center;">Общий рез-т</th>
    <th style="text-align: center;">Дома</th>
    <th style="text-align: center;">На выезде</th>
    <th style="text-align: center;">Серия</th>
  </tr>
  <? foreach ($division["rows"] as $row): ?>
  <tr>
    <td>
      <img src="<?= $row["logo"]?>" width="15" height="15" alt="<?= $row["team"] ?>" />
      <?= $row["team"] ?>
      <? if ($row["rank"]): ?>(<?= $row["rank"] ?>)<? endif; ?>
    </td>
    <td style="text-align: center;"><?= $row["conference"] ?></td>
    <td style="text-align: center;"><?= $row["overall"] ?></td>
    <td style="text-align: center;"><?= $row["home"] ?></td>
    <td style="text-align: center;"><?= $row["away"] ?></td>
    <td style="text-align: center;"><?= $row["streak"] ?></td>
  </tr>
  <? endforeach; ?>
</table>
<? endforeach; ?>
<textarea class="copy" id="table-<?= $table_id ?>">&lt;h2&gt;<?= $table["title"] ?>&lt;/h2&gt;<? foreach ($table["divisions"] as $division): ?>

[table colwidth=&quot;50|10|10|10|10|10&quot;<? if (array_key_exists("name", $division)): ?> caption=&quot;<?= $division["name"] ?>&quot;<? endif; ?>]
,Рез-т в конф.,Общий рез-т,Дома,На выезде,Серия
<? foreach ($division["rows"] as $row): ?>
&lt;img class=&quot;alignnone wp-image-120212 size-thumbnail&quot; src=&quot;<?= $row["logo"]?>&quot; alt=&quot;&quot; width=&quot;15&quot; height=&quot;15&quot; /&gt; <?= $row["team"] ?><? if ($row["rank"]): ?> (<?= $row["rank"] ?>)<? endif; ?>, <?= $row["conference"] ?>, <?= $row["overall"] ?>, <?= $row["home"] ?>, <?= $row["away"] ?>, <?= $row["streak"] ?>

<? endforeach; ?>[/table]
<? endforeach; ?></textarea>
<button class="copy-text" data-target="table-<?= $table_id ?>">Copy</button>
<? endforeach; ?>