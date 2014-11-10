<?php

//
// Start clean output buffer.
//

ob_end_clean();
ob_start();

//
// Prepare response.
//

var_dump($_SERVER);

echo "\n---\n";

//
// Finalize output and flush.
//

$size = ob_get_length();

header("Content-type: text/plain; charset=utf-8");
header("Content-Length: $size");
header("Connection: close");

ob_end_flush();
flush();

//
// Listen for progress and result.
//

$pd = fopen("php://input","r");

error_log("Flushed $pd");

while ($line = fgets($pd,128))
{
	error_log(trim($line));
}

fclose($pd);

error_log("Done $pd");

?>
