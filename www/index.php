<!doctype html>
<html>
<head>
<title>XDCAM-TAR-Status</title>
<script>

Kappa = new Object();

Kappa.StatusEvent = function(status)
{	
	var uploadsdiv = document.getElementById("uploads");
	
	uploadsdiv.innerHTML = "";
	
	for (var uploadinx in status.uploads)
	{
		var upload = status.uploads[ uploadinx ];

		var uploaddiv = document.createElement('div');
		uploaddiv.style.padding = "8px";
		
		var dirdiv = document.createElement('span');
		
		dirdiv.style.width = "30%";
		dirdiv.style.display = "inline-block";
		dirdiv.style.padding = "4px";
		dirdiv.innerHTML = upload.dir;
		
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
		
		uploaddiv.appendChild(dirdiv);
		uploaddiv.appendChild(kbsizediv);
		uploaddiv.appendChild(statusdiv);
		
		uploadsdiv.appendChild(uploaddiv);
	}
	
	window.setTimeout('Kappa.StatusCaller()',1000);
}

Kappa.StatusCaller = function()
{
    var script = document.createElement('script');
    script.src = '/status?rnd=' + Math.random();
    document.body.appendChild(script);
}

</script>
</head>
<body>
<h3><center>XDCAM-TAR-Status</center></h3>
<div id="uploads"></div>

<script>
Kappa.StatusCaller();
</script>
<?php
	include("../php/json.php");

	$shmid   = shmop_open(123456,"c",0644,64 * 1024);
	$shmsize = shmop_size($shmid);

	$shm_status = json_decdat(shmop_read($shmid,0,shmop_size($shmid)));
	
	//var_dump($shm_status);
	
	//shmop_delete($shmid);
	shmop_close($shmid);
?>
</body>

