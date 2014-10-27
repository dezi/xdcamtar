<!doctype html>
<html>
<head>
<title>XDCAM-Processing-Status</title>
<script>

Kappa = new Object();

Kappa.StatusEvent = function(status)
{	
	var stampspan = document.getElementById("stamp");
	var now = new Date();
	stampspan.innerHTML = now.toLocaleString();
	
	var uploadsdiv = document.getElementById("uploads");
	
	uploadsdiv.innerHTML = "";
	
	for (var uploadinx in status.uploads)
	{
		var upload = status.uploads[ uploadinx ];

		var uploaddiv = document.createElement('div');
		uploaddiv.style.padding = "8px";
		
		var entrydiv = document.createElement('span');
		
		entrydiv.style.width = "30%";
		entrydiv.style.display = "inline-block";
		entrydiv.style.padding = "4px";
		entrydiv.innerHTML = upload.entry;
		
		var kbsizediv = document.createElement('span');
		kbsizediv.style.width = "30%";
		kbsizediv.style.display = "inline-block";
		kbsizediv.style.padding = "4px";
		kbsizediv.innerHTML = upload.kbsize;
		
		var statusdiv = document.createElement('span');
		statusdiv.style.width = "30%";
		statusdiv.style.display = "inline-block";
		statusdiv.style.padding = "4px";
		statusdiv.innerHTML = upload.status;
		
		uploaddiv.appendChild(entrydiv);
		uploaddiv.appendChild(kbsizediv);
		uploaddiv.appendChild(statusdiv);
		
		uploadsdiv.appendChild(uploaddiv);
	}
	
	window.setTimeout('Kappa.StatusCaller()',1000);
}

Kappa.StatusScript = null;

Kappa.StatusCaller = function()
{
	if (Kappa.StatusScript)
	{
		document.body.removeChild(Kappa.StatusScript);
		Kappa.StatusScript = null;
	}
	
	Kappa.StatusScript = document.createElement('script');
    Kappa.StatusScript.src = '/status?rnd=' + Math.random();
    document.body.appendChild(Kappa.StatusScript);
}

</script>
</head>
<body>
<h4><center id="stamp"></center></h4>
<h3><center>XDCAM-Processing-Status</center></h3>
<div id="uploads"></div>
<?php
	include("../php/json.php");

	$shmid   = shmop_open(123456,"c",0644,64 * 1024);
	$shmsize = shmop_size($shmid);

	$shm_status = json_decdat(shmop_read($shmid,0,shmop_size($shmid)));
	
	//var_dump($shm_status);
	
	shmop_delete($shmid);
	shmop_close($shmid);
?>
<script>
Kappa.StatusCaller();
</script>
</body>

