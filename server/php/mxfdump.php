<?php


$fd = fopen("../tmp/xdcam/original/magazin/XDCAM/PROAV/CLPR/C0002/C0002A01.MXF","rb");

while (! feof($fd))
{
	$oidmgc = fread($fd,1);
	
	if (($oidmgc === false) || (strlen($oidmgc) == 0)) break;
	
	if (ord($oidmgc[ 0 ]) != 0x06) break;
	
	$oidlen = fread($fd,1);
	
	if (ord($oidlen[ 0 ]) != 0x0e) break;
	
	$oidval = fread($fd,14);
	
	$payber = fread($fd,1);
	
	if ((ord($payber[ 0 ]) & 0x80) == 0x80)
	{
		$paylen = ord($payber[ 0 ]) & 0x7f;
		
		$contraw = fread($fd,$paylen);
		
		$contlen = 0;
			
		for ($inx = 0; $inx < $paylen; $inx++)
		{
			$contlen = ($contlen << 8) + ord($contraw[ $inx ]);
		}
	}
	else
	{
		$contlen = ord($payber[ 0 ]) & 0x7f;
	}
	
	echo bin2hex($oidval) . " => " . $contlen . "\n";
	
	fread($fd,$contlen);
}

?>