<h2>Счет по четвертям</h2>
<table>
    <tr>
        <th> </th>
        <th>1</th>
        <th>2</th>
        <th>3</th>
        <th>4</th>
        <? if($home["ot"] != NULL): ?><td>OT</td><? endif; ?>
        <th>Итог</th>
    </tr>
    <tr>
        <td><a href="edit.php?<?=$home["team"]["composition_values"]?>"><?=$home["team"]["name"]?></a></td>
        <td><?=$home["q1"]?></td>
        <td><?=$home["q2"]?></td>
        <td><?=$home["q3"]?></td>
        <td><?=$home["q4"]?></td>
        <? if($home["ot"] != NULL): ?><td><?=$home["ot"]?></td><? endif; ?>
        <td><?=$home["total"]?></td>
    </tr>
    <tr>
        <td><a href="edit.php?<?=$away["team"]["composition_values"]?>"><?=$away["team"]["name"]?></a></td>
        <td><?=$away["q1"]?></td>
        <td><?=$away["q2"]?></td>
        <td><?=$away["q3"]?></td>
        <td><?=$away["q4"]?></td>
        <? if($away["ot"] != NULL): ?><td><?=$away["ot"]?></td><? endif; ?>
        <td><?=$away["total"]?></td>
    </tr>
</table>
<textarea class="copy" id="quarters">[table width=&quot;450&quot;]
,1,2,3,4,<? if($home["team"]["it"] != NULL): ?>OT,<? endif; ?>Итог
<?=$home["team"]["name"]?>,<?=$home["q1"]?>,<?=$home["q2"]?>,<?=$home["q3"]?>,<?=$home["q4"]?>,<? if($home["ot"] != NULL): ?><?=$home["ot"]?>,<? endif; ?><?=$home["total"]?><?=PHP_EOL?>
<?=$away["team"]["name"]?>,<?=$away["q1"]?>,<?=$away["q2"]?>,<?=$away["q3"]?>,<?=$away["q4"]?>,<? if($away["ot"] != NULL): ?><?=$away["ot"]?>,<? endif; ?><?=$away["total"]?><?=PHP_EOL?>
[/table]</textarea>
<button class="copy-text" data-target="quarters">Copy</button>