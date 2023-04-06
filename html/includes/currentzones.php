<?
$title = "Currently hosted";
$content = "Primary : " . countPrimary($db) . "<br />
Secondary : " . countSecondary($db);
print $html->box($title,$content);
?>
