<?
$title = "Contact";
$content = 'For bugs regarding XName Software, contact us at <a
href="mailto:bugs@xname.org" class="linkcolor">bugs@xname.org</a>.<br />
';
print $html->box($title,$content);

$title = "Contribute";
$content = 'If you like this software, you can <a href="http://www.xname.org/contribute.php" 
class="linkcolor">contribute !</a> and/or submit your patches (<a href="mailto:xname@xname.org">xname@xname.org</a>).<br />
XName Software is under <a href="http://www.gnu.org/copyleft/gpl.html" class="linkcolor">GPL License</a>';

print $html->box($title,$content);
?>
