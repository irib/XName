<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

require 'libs/xname.php';


$config = new Config();

$html = new Html($config);

print $html->header('Warranty & Disclaimer');

// ********************************************************
// WRITE YOUR OWN DISCLAIMER !
// ********************************************************


print '<table border="0" width="100%" class="top">
<tr class="top"><td class="top"><div align="center">Warranty & Disclaimer</div></td>
</tr></table>';


print '

<table border="0" width="100%">
<tr class="boxtext"><td class="boxtext">
<div align=center><table border="0"><tr><td>
<pre>
                               NO WARRANTY

BECAUSE THIS SERVICE IS FREE OF CHARGE, IT IS PROVIDED AS IS, WITHOUT 
WARRANTY OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR 
A PARTICULAR PURPOSE.  SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME 
THE COST OF ALL NECESSARY SERVICING, REPAIR OR CORRECTION.

XNAME IS NEITHER RESPONSIBLE OF INFORMATION IN DNS ZONES, NOR IN ZONE 
HOSTED. IN  CASE  OF COMPLAINT, EMAIL ADDRESS IN SOA SHOULD  BE USED.
REGARDING SECONDARIES, EMAIL ADDRESS USED TO REGISTER SECONDARY  ZONE 
WILL BE GIVEN TO ANY LAW PEOPLE ASKING FOR IT. 

BECAUSE THIS SERVICE IS FREE OF CHARGE, XNAME SERVICE CAN BE  STOPPED 
AT ANY  TIME,  WITHOUT  ANY  KIND  OF  COMPENSATION.  IN SUCH CASE, A 
COURTESY WARNING TO SUBSCRIBERS WILL BE SEND.

BY REGISTERING A ZONE ON XNAME.ORG YOU CERTIFY THAT :
- YOU OWN THE ZONE THAT YOU ARE REGISTERING,
- YOU HAVE RIGHTS TO USE DATA YOU WILL USE IN YOUR RECORDS 
      (IP ADDRESSES, EMAIL ADDRESSES, HOSTNAMES, ETC...)

BY USING XNAME SERVICE YOU ACCEPT THAT YOU ARE THE  SOLE  RESPONSIBLE.
IN CASE OF LAW VIOLATION, XNAME CAN NOT BE RESPONSIBLE  IN  ANY  WAYS.

</pre>
</td></tr></table></div>
</td></tr>
</table>
';




print '</body></html>';
?>
