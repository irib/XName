<?
$title = "Currently hosted";
$content = "Primary : " . countPrimary() . "<br />
Secondary : " . countSecondary();
print $html->box($title,$content);
?>
