<?php 
######################################
# fRNAkenstein                       #
#   by Allen Hubbard & Wayne Treible #
#                                    #
# Version 0.10 Updated 6/17/2014     #
######################################

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

$subdirectories = "/var/www/subdirectories_for_interface";

session_start();

if(empty($_SESSION['user_name']))
{
 # header('Location: index.php');
}

?>

<head>
<title>
MInotauR
</title>
<link rel="STYLESHEET" type="text/css" href="css_dir/style.css">
<link rel="icon" type="image/ico" href="images/favicon.ico"/>	
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
</head>
<body>
<center>

<!--
#########################
# Help Dialog Box Stuff #
#########################
-->

<script language="JavaScript">
$( document ).ready(
function() {

	$(document).mousemove(function (e) {
		$( ".help" ).dialog("option", "position", {
        		my: "left+30 top+30-$(document).scrollTop()",
        		at: "left top",
        		of: e
      		});

	});
        $('.help').each(function(k,v){ // Go through all Divs with .box class
        	var help = $(this).dialog({ autoOpen: false });
		$(this).parent().find('.ui-dialog-titlebar-close').hide();

		$( "#help"+k ).mouseover(function() { // k = key from the each loop
                	help.dialog( "open" );
                }).mouseout(function() {
                    	help.dialog( "close" );
                });
	});
});

</script>
<body>
<div class="help" id="help" style="" title="Library Numbers">
<font size="3"><center>Choose a microRNA fastq library to be processed.</center></font>
</div>
<body>
<div class="help" id="help2" style="" title="Fasta">
<font size="3"><center>Choose a fasta to align microRNA reads against</center></font>
</div>
<body>
<div class="help" id="help3" style="" title="Phred Score">
<font size="3"><center>Choose a minimum Phred Score for each read to be processed.</center></font>
</div>
<body>
<div class="help" id="help4" style="" title="Read Length">
<font size="3"><center>Please choose a minimum read length form the microRNA to be processed.</center></font>
</div>
<body>
<div class="help" id="help5" style="" title="Adapter Sequence">
<font size="3"><center>Please enter the adapter sequence on each of your reads</center></font>
</div>
<body>
<div class="help" id="help6" style="" title="mirBaseFile">
<font size="3"><center>Please choose a mirbase file for this analysis</center></font>
</div>
<body>
<div class="help" id="help7" style="" title="Analysis Name">
<font size="3"><center>Please name this analysis</center></font>
</div>
<center>
<!--
############################
# Beginning of submit form #
############################
-->
<div>
<fieldset class="fieldset-auto-width">
<legend>
<h3>
MInotauR - Stage1
</h3>
</legend>
<form id='submitform' onsubmit="return valthisform(this);" action='/minotaur/stage1_response.php' method='post' target='formresponse'>
<input type='hidden' name='submitted' id='submitted' value='1'/>

<!--
################################
# Beginning of alignment table #
################################
-->

<table>
<!--
#############################
# Row for form and response #
#############################
-->

<tr style="padding:0px; margin:0px;">
<td valign="top" style="padding-top:12px;padding-left:8px;width:300px">

<!--
################################################
# Create DDmenu for fastq files (lib nums) #
################################################
-->
<div class='container'>

<?php

$fqfiles = scandir("$subdirectories/mirFiles/fastq_to_be_crunched");

echo "<span><br><b>Choose Library Number: </b></span><span class=\"helper\" id=\"help0\" style=\"color:blue;\"><b>?</b></span><br><br>";
if(count($fqfiles)<3){ #because of . and .. directories existing
	echo "<b>Note:</b> No fastq files available! (email wtreible@udel.edu)";
} else {
	echo "<select name=\"fqfilename\">";
	foreach ($fqfiles as $fqfile)
	{
	  if (($fqfile != ".") and ($fqfile != ".."))
	  { 
	    	$libpattern = "/^s*(\d*).*/";
		preg_match($libpattern, $fqfile, $matches);
		$librarynum = $matches[1];
		echo "<option value=\"$fqfile\">$librarynum</option>";
	  }
	} 
	echo "</select>";
}
?>
<br>

<!--
################################
# Create DDBox for fasta files #
################################
-->
<?php
$fafiles = scandir("$subdirectories/fasta_directory"); 

echo "<span><br><b>Choose a fasta file: </b><span class=\"helper\" id=\"help1\" style=\"color:blue;\"><b>?</b></span><br><br>";
if(count($fafiles)<3){ #because of . and .. directories existing
	echo "<b>Note:</b> No fasta mirBase available! (email wtreible@udel.edu)";
} else {
	echo "<select name=\"fafilename\">";
	foreach ($fafiles as $fafile)
	{
	  if (($fafile != ".") and ($fafile != ".."))
	  { 
	    echo "<option value=\"$fafile\">$fafile</option>";
	  }
	} 
	echo "</select>";
}
?>
<br><br>
<!--
######################################
# Phred Selector Slider (JS onchange) #
######################################
-->
<script>
function showVal(newVal){ 
    document.getElementById("slideVal").innerHTML = newVal;
}
</script> 

<div style="float:left;"><b>Phred Score:</b>&nbsp;</div>
<div id="slideVal" style="float:left;">33</div>
<div style="float:left;">&nbsp;</div><br><br>

<div style="height:30px;width:250px;float:left;">
1<input name="Phred" type="range" min="1" max="40" step="1" value="33" oninput="showVal(this.value)"> 40</div>
<br><br>

<!--
####################################################
# Min Length Of Read Selector Slider (JS onchange) #
####################################################
-->
<?php
#echo "<br>Choose a minimum length </b></span><span class=\"helper\" id=\"help3\" style=\"color:blue;\"><b>?</b></span><br>";
?>
<script>
function showVal2(newVal){ 
    document.getElementById("slideVal2").innerHTML = newVal;
}
</script> 

<div style="float:left;"><b>Minimum Length:</b>&nbsp;</div>
<div id="slideVal2" style="float:left;">18</div>
<div style="float:left;">&nbsp;</div><br><br>
<div style="height:30px;width:250px;float:left;">
1<input name="minLength" type="range" min="1" max="50" step="1" value="18" oninput="showVal2(this.value)"> 50</div>
<br>

<!--
####################
# Adapter Sequence #
####################
-->
<?php
echo "<span><br> <b>Adapter sequence: </b></span><span class=\"helper\" id=\"help4\" style=\"color:blue;\"><b>?</b></span><br> <br><input type=\"text\" style=\"width:200px;\"><br>";
?>
<!--
############################################
# Create DDBox for mirBase file by Species #
############################################
-->
<?php
$mirFiles = scandir("$subdirectories/mirFiles"); 
echo "<span><br><b>Species Name: </b></span><span class=\"helper\" id=\"help5\" style=\"color:blue;\"><b>?</b></span><br><br>";

if(count($mirFiles)<3){ #because of . and .. directories existing
	echo "<b>Note:</b> No fasta files available! (email wtreible@udel.edu)";
} else {
	echo "<select name=\"mirFilename\">";
	foreach ($mirFiles as $mirFile)
	{
	  if (($mirFile != ".") and ($mirFile != ".."))
	  { 
	    echo "<option value=\"$mirFile\">$mirFile</option>";
	  }
	} 
	echo "</select>";
}
?>
<br>
<!--
#####################
# name the analysis #
#####################
-->
<?php
echo "<span><br><b>Analysis name: </b></h4></b></span><span class=\"helper\" id=\"help6\" style=\"color:blue;\"><b>?</b></span><br><br><input type=\"text\" id=\"analysisName\" name=\"analysisName\"> <br><br>";
?>



<!--
###########################
# Submit and Menu Buttons #
###########################
-->
<div class='container'>
<button class="crunch" type="submit">fRNAkenstein, Crunch!</button>
</form>
<br><br><br><br>
<form action="menu.php">
    <input align="bottom" type="submit" value="Return to Menu">
</form>
</div>
</td>

<!--
#######################
# iFrame for Response #
#######################
-->
<td valign="top" style="padding-left:0px;align:left">
<br>
<iframe name='formresponse' src='placeholder_response.html' style="border: outset; background-color:#A69066" width='500px' height='800px' frameborder='0'>
</iframe>

<!--
#######################
# Footer and clean-up #
#######################
-->
</td>
</tr>
</table>
</link></form>
<p align="right"><font size="1">- Created by Allen Hubbard and Wayne Treible at the University of Delaware - </font></p>
</fieldset>
</body>

