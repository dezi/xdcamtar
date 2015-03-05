<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<title>XDCAM-Processing-Status</title>
<script>

Kappa = new Object();

Kappa.StatusEvent = function(status)
{	
	var inner;
	//console.log(status);
	
	var stampspan = document.getElementById("stamp");
	var now = new Date();
	stampspan.innerHTML = now.toLocaleString();
	
	var uploadstbody = document.getElementById("uploads");
	
	inner = "";
	
	for (var uploadinx in status.uploads)
	{
		var upload = status.uploads[ uploadinx ];
		
		inner += '<tr>';
		inner += '<td>' + upload.entry  +    '</td>';
		inner += '<td>' + upload.path   +    '</td>';
		inner += '<td>' + upload.kbsize + ' kb</td>';
		inner += '<td>' + upload.status +    '</td>';
		
		if ((upload.status == "taring...") && (upload.tarsize > 0))
		{
			var percent = Math.round((upload.tarsize / upload.kbsize) * 100);
			
			inner += '<td>' + percent + ' %</td>';
		}
		
		inner += '</tr>';
	}
	
	uploadstbody.innerHTML = inner;
	
	var encoderstbody = document.getElementById("encoders");
	
	inner = "";
	
	for (var encoderinx in status.encoders)
	{
		var encoder = status.encoders[ encoderinx ];
		
		inner += '<tr>';
		inner += '<td>' + encoder.hostname + "@" + encoder.remoteip + "/" + encoder.uname   +    '</td>';
		inner += '<td>' + encoder.instance  +    '</td>';
		
		if (encoder.jobname == "encode")
		{
			if (encoder.progress)
			{				
				inner += '<td>' + "encoding => " + encoder.progress.docnum + "/" + encoder.progress.clname + '</td>';
				inner += '<td>' + encoder.progress.percent + '%</td>';
			}
			else
			{
				inner += '<td>' + "encoding" + '</td>';
			}
		}
		else
		{
			inner += '<td>' + encoder.jobname + '</td>';
		}
		
		inner += '</tr>';
	}
	
	encoderstbody.innerHTML = inner;
	
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

<center style="margin:8px">
	<table width="1000" border="0" cellpadding="8" style="background-color:#cccccc">
		<thead>
			<th>Doknr<hr/></th>
			<th>Path<hr/></th>
			<th>Größe<hr/></th>
			<th>Status<hr/></th>
			<th>Fertig<hr/></th>
		</thead>
		<tbody id="uploads">
		</tbody>
	</table>
</center>

<center style="margin:8px">
	<table width="1000" border="0" cellpadding="8" style="background-color:#cccccc">
		<thead>
			<th>Kennung<hr/></th>
			<th>Instance<hr/></th>
			<th>Status<hr/></th>
			<th>Fertig<hr/></th>
		</thead>
		<tbody id="encoders">
		</tbody>
	</table>
</center>

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

