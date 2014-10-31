<?php

header("Content-type: text/plain; charset=utf-8");

$output = $_SERVER[ "SCRIPT_NAME" ];
if (substr($output,0,8) != "/output/") exit();

$output = "../out" . substr($output,7);
$outdir = pathinfo($output,PATHINFO_DIRNAME);

umask (0);

if (! file_exists($outdir)) mkdir($outdir,0777,true);

$pd = fopen("php://input","r");
$fp = fopen($output,"w");

while ($data = fread($pd,32 * 1024))
{
	fwrite($fp,$data);
}

fclose($fp);
fclose($pd);

?>
