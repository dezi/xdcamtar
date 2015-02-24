<?php

header("Content-type: text/plain; charset=utf-8");

$output = $_SERVER[ "SCRIPT_NAME" ];
if (substr($output,0,8) != "/output/") exit();

$output = "../out" . substr($output,7);
$outdir = pathinfo($output,PATHINFO_DIRNAME);
$outsiz = intval($_SERVER[ "HTTP_CONTENT_SIZE" ]);

umask (0);

if (! file_exists($outdir)) mkdir($outdir,0777,true);

$pd = fopen("php://input","r");
$fp = fopen($output . ".tmp","w");

$xfer = 0;

while ($data = fread($pd,32 * 1024))
{
	$yfer = fwrite($fp,$data);
	
	if ($yfer === false) break;
	
	$xfer += $yfer;
}

fclose($fp);
fclose($pd);

if (file_exists($output . ".err")) 
{
	unlink($output . ".err");
}

if ($outsiz == $xfer)
{
	rename($output . ".tmp",$output);	
}
else
{
	rename($output . ".tmp",$output . ".err");
}


?>
