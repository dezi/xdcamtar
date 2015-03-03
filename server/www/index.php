<!doctype html>
<html>
<head>
<title>XDCAM-Processing-Status</title>
<script>

Kappa = new Object();

Kappa.StatusEvent = function(status)
{	
	//console.log(status);
	
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
	
	var encodersdiv = document.getElementById("encoders");
	
	encodersdiv.innerHTML = "";
	
	for (var encoderinx in status.encoders)
	{
		var encoder = status.encoders[ encoderinx ];

		//console.log(encoder.instance);
		
		var encoderdiv = document.createElement('div');
		encoderdiv.style.padding = "8px";
		
		var instancediv = document.createElement('span');
		
		instancediv.style.width = "24%";
		instancediv.style.display = "inline-block";
		instancediv.style.padding = "4px";
		instancediv.innerHTML = encoder.instance;
		
		var remoteipdiv = document.createElement('span');
		remoteipdiv.style.width = "24%";
		remoteipdiv.style.display = "inline-block";
		remoteipdiv.style.padding = "4px";
		remoteipdiv.innerHTML = encoder.hostname + "@" + encoder.remoteip + "/" + encoder.uname;
		
		var jobnamediv = document.createElement('span');
		jobnamediv.style.width = "24%";
		jobnamediv.style.display = "inline-block";
		jobnamediv.style.padding = "4px";
		jobnamediv.innerHTML = encoder.jobname;
		
		var percentdiv = document.createElement('span');
		percentdiv.style.width = "24%";
		percentdiv.style.display = "inline-block";
		percentdiv.style.padding = "4px";
		
		if (encoder.jobname == "encode")
		{
			if (encoder.progress)
			{
				percentdiv.innerHTML = encoder.progress.percent;
				
				jobnamediv.innerHTML = "encoding => " + encoder.progress.docnum + "/" + encoder.progress.clname;
			}
		}
		
		encoderdiv.appendChild(instancediv);
		encoderdiv.appendChild(remoteipdiv);
		encoderdiv.appendChild(jobnamediv);
		encoderdiv.appendChild(percentdiv);
		
		encodersdiv.appendChild(encoderdiv);
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
<hr/>
<div id="uploads"></div>
<hr/>
<div id="encoders"></div>
<hr/>
<?php
	/*
	include("../php/json.php");

	$shmid   = shmop_open(123456,"c",0644,64 * 1024);
	$shmsize = shmop_size($shmid);

	$shm_status = json_decdat(shmop_read($shmid,0,shmop_size($shmid)));
	
	shmop_delete($shmid);
	shmop_close($shmid);
	*/
?>
<script>
Kappa.StatusCaller();
</script>
</body>

