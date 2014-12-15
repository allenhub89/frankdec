<!--
######################################
# fRNAkenstein                       #
#   by Allen Hubbard & Wayne Treible #
#                                    #
# Version 0.10 Updated 6/17/2014     #
######################################
-->

<?php

$ini = parse_ini_file("../config.ini.php", true);
$admin = $ini['login']['admin'];
$def_path = $ini['login']['default'];
$subdirectories = $ini['filepaths']['subdirectories'];

session_start();

####################################
# Required File Structure:         #
#                                  #
# subdirectories/                  #
#   --fastq_to_be_crunched/        #
#   --fasta_directory/             #
#   --annotation_directory/        #
#   --temp_output/                 #
#   --bash_scripts/                #
#   --mapcount_output/             #
#   --logs/                        #
#                                  #
# Modify $subdirectories to change #
#   the root of the file system    #
####################################

?>

<head>
<title>
fRNAkenstein - Visualization
</title>
<link rel="STYLESHEET" type="text/css" href="css_dir/style.css">
<link rel="icon" type="image/ico" href="images/favicon.ico"/>
<html lang="en">
<meta charset="utf-8">
<title>jQuery UI Autocomplete - Default functionality</title>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<link rel="stylesheet" href="/resources/demos/style.css">

<script>
$(function() {
var availableTags = <?php echo json_encode(scandir("$subdirectories/diffexpress_output")) ?>;
 $( "#tags" ).autocomplete({
source: availableTags, minLength:2, delay:0
});
});


</script>

<!--
###################################################
#make sure the analysis name is an actual analysis#
###################################################
-->
<script>
$(function(){
	var analyses = <?php echo json_encode(scandir("$subdirectories/diffexpress_output")) ?>;
	
	$("#tags").click(function(){ 
		var inp = $("#tags");
		var name = inp.val();	
		if ( inp.val().length == 0 )
		{
			test  = jQuery.inArray(analyses, name) ;
		
		}
		
		if(inp.val().length > 0)
		{
			if(jQuery.inArray(name, analyses) == -1)
			{
				alert(name + "is not a proper analysis, please choose another name.  this has been changed");
				event.preventDefault();

			}
		
		}
	});
});
</script>

<?php
if(empty($_POST['analysis'])){
	echo '<form method="POST" action="visualize.php"';	
	echo '<h4>Analysis Name:</h4>';	
	echo '<span class="ui-widget">';
	echo'<input id="tags" name="analysis">';
	echo'</span><button id="submit" type="submit">Select Analysis</button></form>';	
	exit('');
}

$analysis = htmlspecialchars($_POST['analysis']);

$analysispath = "$subdirectories/diffexpress_output/$analysis";
$mytableViewLink = "results.php?analysis=$analysis";

$db = new PDO("sqlite:$analysispath/cuffdiff_output/cuffData.db");
$arr = array();

foreach($db->query('select gene_short_name from genes;') as $row)
{
	if($row[0] != null) {
		array_push($arr, $row[0]);
	}
}

?>

<script>
$(function() {
var availableTags = <?php echo json_encode($arr);?>;
 $( "#tags" ).autocomplete({
source: availableTags, minLength:2, delay:0
});
});

</script>

<!--
#######################
# Gene Checkbox Adder #
#######################
-->


<script>
$(document).ready(function(){
	var anno_genes = <?php echo json_encode($arr); ?>;
	var added = [];

	$("#adder").click(function(){
		var inp = $("#tags");
		if(inp.val().length > 0) {
			var gene = inp.val();
			if(jQuery.inArray(gene, anno_genes) != -1)
			{
				var ctrl = $('<input>').attr({ type: 'checkbox', name:'genes[]', value: ''+gene}).addClass("adder");
				$("#ctrlholder").append(ctrl);
				$("#ctrlholder").append(gene);
				$("#ctrlholder").append("<br>");
				anno_genes = jQuery.grep(anno_genes, function(value) {
					added.push(value);
					return value != gene;
				});
				$("#frame").animate({height:'+=36'},500);
			}
			else if(jQuery.inArray(gene, added) != -1)
			{
				alert("Gene \'" + gene + "\' is already added.");
			}
			else {
				alert("Gene \'" + gene + "\' is not in the annotation file.");
			}

		}
		else {
			alert("Please enter a gene name.");
		}

	});

	jQuery('#loading-image').hide();

	$("#crunch").click(function(){
        	jQuery('#loading-image').show();
		$("#page-cover").show().css("opacity",0.6);
		//document.getElementById('crunch').className = "disabled";
                //document.getElementById('crunch').disabled = 1
	});


});

/*
get determine whether or not the user wants the genes in a single figure or not
*/
</script>

</head>

<body>

<!--
#################
# Loading image #
#################
-->

<div id="loading-image">
        <img id="loading" src="images/spinner.gif" alt="Loading..." /> 
</div>

<center>
<!--
############################
# Beginning of submit form #
############################
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
fRNAkenstein - DiffVis
</h3>
</legend>
<form class="go-bottom" id='submitform' onsubmit="return valthisform(this);" action='visualize_response.php' method='post' target='formresponse'>

<!--
Send the analysis path also!
-->

<input type="hidden" value="<?php echo $analysispath; ?>" name="analysispath" />
<input type="hidden" value="<?php echo $analysis; ?>" name="analysis" />
<!--
################################
# Beginning of alignment table #
################################
-->

<table style="margin: 0px;">


<!--
###########################
# Type of Figure Selector #
###########################
-->
<tr>
<td>
<h4> Figure Layout: </h4>
<div class="frnakRadio">
<div class="checkname">All</div>
<input type="radio" id="frnakRadioInput" name="figureType" value="all" checked>
<label for="frnakRadioInput"></label></div>

<div class="frnakRadio">
<div class="checkname">Separate</div>
<input type="radio" id="frnakRadioInput2" name="figureType" value="separate" >
<label for="frnakRadioInput2"></label></div>

<!--
#######################
Gene or Isoforms Level#
#######################
-->

<h4> Gene or Isoform Level: </h4>
<div class="frnakRadio">
<div class="checkname">Gene</div>
<input type="radio" id="frnakRadioInput3" name="geneOrIsoform" value="gene" checked>
<label for="frnakRadioInput3"></label></div>

<div class="frnakRadio">
<div class="checkname">Isoform</div>
<input type="radio" id="frnakRadioInput4" name="geneOrIsoform" value="isoform" >
<label for="frnakRadioInput4"></label></div>

<!--
######################
# Error Bars Or Not? #
######################
-->
<h4> Show error bars?: </h4>
<div class="frnakRadio">
<div class="checkname">Yes</div>
<input type="radio" id="frnakRadioInput5" name="errorBars" value="T" checked>
<label for="frnakRadioInput5"></label></div>

<div class="frnakRadio">
<div class="checkname">No</div>
<input type="radio" id="frnakRadioInput6" name="errorBars" value="F" >
<label for="frnakRadioInput6"></label></div>

<!--
##############################
# Line or Bar Graph Selector #
##############################
-->
<h4> Bar Or Line: </h4>
<div class="frnakRadio">
<div class="checkname">Bar</div>
<input type="radio" id="frnakRadioInput7" name="barOrLine" value="expressionBarplot" checked>
<label for="frnakRadioInput7"></label></div>

<div class="frnakRadio">
<div class="checkname">Line</div>
<input type="radio" id="frnakRadioInput8" name="barOrLine" value="expressionPlot" >
<label for="frnakRadioInput8"></label></div>


<!--
###########################
#Add gene from annotation #
###########################
-->

<h4>Add Gene from Annotation:</h4> 

<div id="ctrlholder" style="padding-bottom:10px">

</div>

<span class="ui-widget">
<label for="tags">Gene: </label>
<input id="tags" style="width: 150px;">
</span><button id="adder" type="button">Add</button>

<!--
###########################
# Submit and Menu Buttons #
###########################
-->
<br><br>
<button class="crunch" id="crunch" type="submit">fRNAkenstein, Crunch!</button>
<br> <br> <br>

</form>
<form action="menu.php">
    <input align="bottom" type="submit" value="Return to Menu">
</form>

echo "we have not yet made it to  graphing"; 

<!--
###################
# Response iFrame #
###################
-->

</td>
<td style="padding-left:24px">
<iframe id='frame' name='formresponse' src='placeholder_response.html' style="border: outset; background-color:#d0eace" width='500px' height='700px' frameborder='0'>
</iframe>
</td>
</tr>


</tr>
</table>

<div id="page-cover" style=" display: none;position: fixed;width: 100%;height: 100%;background-color: #000;z-index: 45;top: 0;left: 0;"></div>

</body>
</html>

