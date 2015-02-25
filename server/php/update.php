<?php

header("Content-type: text/plain; charset=utf-8");

$update = $_SERVER[ "SCRIPT_NAME" ];
if (substr($update,0,8) != "/update/") exit();

$update = "../../encoder/" . substr($update,7);

readfile($update);
?>
