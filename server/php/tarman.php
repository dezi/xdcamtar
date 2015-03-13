<?php

include("./json.php");
include("./tard.php");

header("Content-type: text/plain; charset=utf-8");

$tarball = $_SERVER[ "SCRIPT_NAME" ];
if (substr($tarball,0,7) != "/tarman") exit();

$tarball = "../tmp" . substr($tarball,7);
$tartpos = strpos($tarball,".tar/");

if ($tartpos === false)
{
	$directory = get_tar_content($tarball);
	
	//
	// Prepare response for client.
	//

	echo "Kappa.TarmanEvent(\n";
	echo json_encdat($directory) . "\n";
	echo ");\n";
}
else
{
	set_time_limit(0);

	$tarcont = substr($tarball,$tartpos + 5);
	$tarball = substr($tarball,0,$tartpos + 4);

	get_tar_content($tarball,$tarcont);
}

?>
