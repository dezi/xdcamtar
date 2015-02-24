<?php

header("Content-type: text/plain; charset=utf-8");

$output = $_SERVER[ "SCRIPT_NAME" ];
if (substr($output,0,8) != "/output/") exit();

$output = "../out" . substr($output,7);
$outdir = pathinfo($output,PATHINFO_DIRNAME);

//
// Figure content length if given.
//

$outsize = -1;

if (isset($_SERVER[ "CONTENT_LENGTH" ]))
{
	$outsiz = intval($_SERVER[ "CONTENT_LENGTH" ]);
}
else
{
	$headers = getallheaders();
	
	if (isset($headers[ "MyContent-Length" ]))
	{
		$outsiz = intval($headers[ "MyContent-Length" ]);
	}
}

//
// Create output directory.
//

umask (0);

if (! file_exists($outdir)) mkdir($outdir,0777,true);

//
// Download stream into local file.
//

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

//
// Rename temp file.
//

if (file_exists($output . ".err")) 
{
	unlink($output . ".err");
}

if (($outsiz < 0) || ($outsiz == $xfer))
{
	rename($output . ".tmp",$output);	
}
else
{
	rename($output . ".tmp",$output . ".err");
}


?>
