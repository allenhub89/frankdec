<!--
##########
# Header #
##########
-->

<head>
<title>
"MInotaur"
</title>
<link rel="STYLESHEET" type="text/css" href="css_dir/style.css">
<link rel="STYLESHEET" type="text/css" href="css_dir/buttonStyle.css">
<link rel="icon" type="image/ico" href="images/favicon.ico"/>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>

</head>
<center>

<!-- for coordinates -->
<script language="JavaScript">
$( document ).ready(function() {

	$( "#dialog" ).dialog({
		autoOpen: false,
		buttons: [ { text: "Okay", click: function() { $( this ).dialog( "close" ); } } ],
		show: {
			effect: "puff",
			duration: 250
		},
		hide: {
			effect: "puff",
			duration: 250
		},
		width: 450,
		height: 450,
	});

});

function point_it(event){
	allow = 1;
	
	pos_x = event.offsetX?(event.offsetX):event.pageX-document.getElementById("pointer_div").offsetLeft;
	pos_y = event.offsetY?(event.offsetY):event.pageY-document.getElementById("pointer_div").offsetTop;
	
	if (allow == 1){
		/* For the record, I think this is ugly */
		if(pos_y >= 3 && pos_y <= 102 && pos_x >= 172 && pos_x <= 252)
		{
			//FASTQ FILE
			// # = id ; $(<x>) = reference to element
			// #dialog = ref to dialog element
			$( "#dialog" ).dialog( "open" );
			$("#dialog p").text("FASTQ format is a text-based format for storing both a biological sequence and its corresponding quality scores. The FASTQ file is the input format for the pipeline.");
			//make dialog box an option box, set the title to 'Fastq file'
			$('#dialog').dialog('option', 'title', 'Fastq File');
			return;
		}
		
		if(pos_x >= 90 && pos_x <= 210)
		{
			if(pos_y >= 145 && pos_y <= 200)
			{
				//Tophat
				$( "#dialog" ).dialog( "open" );
				$("#dialog p").text("Fastq to Fasta ");
				$('#dialog').dialog('option', 'title', 'Fastq To Fasta');
				return;
			}
			else if (pos_y >= 240 && pos_y <= 300)
			{
				//CUFFLINKS
				$( "#dialog" ).dialog( "open" );
				$("#dialog p").text("Now, we have fasta reads ready to map");
				$('#dialog').dialog('option', 'title', 'Fasta');
				return;
			}
		
		
			if(pos_y >= 340 && pos_y <= 405)
			{
				//Mapped Reads
				$( "#dialog" ).dialog( "open" );
				$("#dialog p").text("Now we can map fasta reads with mapper script");
				$('#dialog').dialog('option', 'title', 'Map Reads');
				return;
			}
		
			if(pos_y >= 440 && pos_y <= 505)
			{
				//Mapped Reads
				$( "#dialog" ).dialog( "open" );
				$("#dialog p").text("Now we have the reads mapped to the genome");
				$('#dialog').dialog('option', 'title', 'Mapped Reads');
				return;
			}
		
		
			if(pos_y >= 540 && pos_y <= 600)
			{
				//Mapped Reads
				$( "#dialog" ).dialog( "open" );
				$("#dialog p").text("Now we can view the mapped reads and determine which represent novel or known microRNAs");
				$('#dialog').dialog('option', 'title', 'Viewing mapped reads');
				return;
			}
		}	
	}
}
</script>



</head>
<body>
<center>
<!--
###########################
# Formatting Box & Legend #
###########################
-->
<style type="text/css">
    .fieldset-auto-width {
         display: inline-block;
    }
</style>
<div>
<fieldset class="fieldset-auto-width">
<legend>
<h3>
The fRNAkenstein Pipeline
</h3>
</legend>
<h4>Click on an element for more information.</h4><br>
<div id="pointer_div" onclick="point_it(event)" style = "background-image:url('/minotaur/images/test_final_minotaur_I.png');height:645;width:298">
</div>
<br><div class='container'>
<font size="2">Click inside "Tophat and Cufflinks" or "DiffExpress" for stage instructions.</font></div>
<br> <br> <br> <br>
<form action="menu.php">
    <input align = "bottom" type="submit" value="Return to Menu">
</form>


</fieldset>

<div id="dialog" style="display:none;" title="">
  <p></p>
</div>


</link>

</fieldset>
</body>
