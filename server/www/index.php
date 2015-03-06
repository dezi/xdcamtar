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
		
		if ((upload.status == "taring...") && (upload.percent > 0))
		{
			inner += '<td>' + upload.percent + ' %</td>';
		}
		
		if ((upload.status == "encoding...") && (upload.percent > 0))
		{
			inner += '<td>' + upload.percent + ' %</td>';
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
		inner += '<td>'  + encoder.encoder + " = " + encoder.remoteip + " - " + encoder.uname + " @ " + encoder.hostname +  '</td>';
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

<center style="margin:8px;font-size:14px">
	<table width="1200" border="0" cellpadding="8" style="background-color:#cccccc">
		<thead>
			<th width="5%" >Doknr<hr/></th>
			<th width="50%">Path<hr/></th>
			<th width="12%">Größe<hr/></th>
			<th width="25%">Status<hr/></th>
			<th width="5%" >Fertig<hr/></th>
		</thead>
		<tbody id="uploads">
		</tbody>
	</table>
</center>

<center style="margin:8px;font-size:14px">
	<table width="1200" border="0" cellpadding="8" style="background-color:#cccccc;white-space:nowrap">
		<thead>
			<th width="40%">Kennung<hr/></th>
			<th width="27%">Instance<hr/></th>
			<th width="25%">Status<hr/></th>
			<th width="5%" >Fertig<hr/></th>
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

