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
		
		var bgcolor = '#cccccc';
		var trinn = '';
	
		trinn += '<td>' + upload.entry  +    '</td>';
		trinn += '<td>' + upload.path   +    '</td>';
		trinn += '<td style="text-align:right">' + upload.kbsize + ' kb</td>';
		trinn += '<td>' + upload.status +    '</td>';
		
		if ((upload.status == "taring...") && (upload.percent > 0))
		{
			trinn += '<td style="text-align:right">' + upload.percent + ' %</td>';
			bgcolor = "#ccffcc";
		}
		
		if ((upload.status == "encoding...") && (upload.percent > 0))
		{
			trinn += '<td style="text-align:right">' + upload.percent + ' %</td>';
			bgcolor = "#ccffcc";
		}
		
		inner += '<tr style="background-color:' + bgcolor + '">' + trinn + '</tr>';
	}
	
	uploadstbody.innerHTML = inner;
	
	var encoderstbody = document.getElementById("encoders");
	
	inner = "";
	
	for (var encoderinx in status.encoders)
	{
		var encoder = status.encoders[ encoderinx ];
		
		var bgcolor = '#cccccc';
		var trinn = '';
		
		if ((encoder.jobname == "encode") && encoder.progress)
		{
			var tarname = encoder.progress.input.split('.tar')[ 0 ] + '.tar';
			
			trinn += '<td>' + encoder.progress.docnum + '</td>';
			trinn += '<td>' + tarname + ' => ' + encoder.progress.clname + '</td>';
		}
		else
		{
			trinn += '<td></td>';
			trinn += '<td></td>';
		}
		
		trinn += '<td>'  
			   + encoder.encoder + " = " 
			   + encoder.remoteip + " - " 
			   + encoder.uname + "/" 
			   + encoder.cpu + " @ " 
			   + encoder.hostname + " ["
			   + encoder.instance.substring(0,8)
			   + ']'
			   +  '</td>'
			   ;
				
		if (encoder.jobname == "encode")
		{
			if (encoder.progress)
			{				
				trinn += '<td>encoding...</td>';
				trinn += '<td>' + encoder.progress.percent + '%</td>';
			}
			else
			{
				trinn += '<td>encoding...</td>';
				trinn += '<td></td>';
			}
			
			bgcolor = "#ccffcc";
		}
		else
		{
			trinn += '<td>' + encoder.jobname + '</td>';
			trinn += '<td></td>';
		}
		
		inner += '<tr style="background-color:' + bgcolor + '">' + trinn + '</tr>';
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
<body style="background-color:#0e2034;color:#ffffff">
<div><center><img src="spiegel-tv.png"/></center></div>
<h3><center>XDCAM-Processing-Status</center></h3>
<h4><center id="stamp"></center></h4>

<center style="margin:8px;font-size:14px;color:black"">
	<div style="width:1000px;padding-top:8px;border-bottom:1px solid black;font-size:24px;background-color:#cccccc">
		Disk Status
	</div>
	<table width="1000" border="0" cellpadding="8" style="background-color:#cccccc">
		<thead>
			<th width="5%" >Doknr<hr/></th>
			<th width="62%">Pfad<hr/></th>
			<th width="15%">Größe<hr/></th>
			<th width="10%">Status<hr/></th>
			<th width="5%" >Fertig<hr/></th>
		</thead>
		<tbody id="uploads">
		</tbody>
	</table>
</center>

<center style="margin:8px;font-size:14px;color:black">
	<div style="width:1000px;padding-top:8px;border-bottom:1px solid black;font-size:24px;background-color:#cccccc">
		Encoder Status
	</div>
	<table width="1000" border="0" cellpadding="8" style="background-color:#cccccc;white-space:nowrap">
		<thead>
			<th width="5%" >Doknr<hr/></th>
			<th width="35%">Pfad<hr/></th>
			<th width="40%">Kennung<hr/></th>
			<th width="10%">Status<hr/></th>
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

