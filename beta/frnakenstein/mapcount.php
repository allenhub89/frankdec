
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

$ini = parse_ini_file("../config.ini.php", true);
$def_path = $ini['login']['default'];
$subdirectories = $ini['filepaths']['subdirectories'];

session_start();

if(empty($_SESSION['user_name']) && !($_SESSION['user_logged_in']))
{
  header('Location: '.$def_path);
}

?>

<head>
<title>
fRNAkenstein - MapCount Cruncher
</title>
<link rel="STYLESHEET" type="text/css" href="css_dir/style.css">
<link rel="icon" type="image/ico" href="images/favicon.ico"/>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>

</head>

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
<font size="3"><center>Choose library numbers for mapping and assembly with FPKM and raw count quantification.</center></font>
</div>
<body>
<div class="help" id="help2" style="" title="Number of Processors">
<font size="3"><center>Choose number of proccessors for parallelization of tophat and cufflinks. (Recommended: 24)</center></font>
</div>
<body>
<div class="help" id="help3" style="" title="Fasta">
<font size="3"><center>Choose your organism's fasta file to which tophat will align raw reads.</center></font>
</div>
<body>
<div class="help" id="help4" style="" title="Annotation">
<font size="3"><center>Choose the accompanying .gtf or .gff annotation file for your organism's fasta.</center></font>
</div>
<body>
<div class="help" id="help5" style="" title="Annotation Type">
<font size="3"><center>Choose the matching format for the selected annotation file. <br><b>Warning:</b> Make sure this matches your annotation file.</center></font>
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
fRNAkenstein - MapCount Cruncher
</h3>
</legend>
<form id='submitform' onsubmit="return valthisform(this);" action='mapcount_response.php' method='post' target='formresponse'>


<input type='hidden' name='submitted' id='submitted' value='1'/>

<!--
################################
# Beginning of alignment table #
################################
-->

<table>

<!--
######################
# Checkbox Validator #
######################
-->

<script language="javascript">
function valthisform()
{
	var checkboxs=document.getElementsByName("fqfilename[]");
	var okay=false;
	for(var i=0,l=checkboxs.length;i<l;i++)
	{
		if(checkboxs[i].checked)
		{
	    		okay=true;
		}
	}
	if(okay){
		document.getElementById('crunch').className = "disabled";
		document.getElementById('crunch').disabled = 1
		alert("Running MapCount on Data!");
	}
	else alert("Please select a library!");
	return okay;
}

</script>

<!--
#############################
# Row for form and response #
#############################
-->

<tr style="padding:0px; margin:0px;">
<td valign="top" style="padding-top:12px;padding-left:8px;width:300px">

<!--
################################################
# Create Checkboxes for fastq files (lib nums) #
################################################
-->

<div class='container'>

<?php

$fqfiles = scandir("$subdirectories/fastq_to_be_crunched");

# Sorts files by "natural human sorting" such that:
# 1.ext                       1.ext
# 10.ext     ==becomes==>     2.ext
# 2.ext                       10.ext 
if(!empty($fqfiles))
{
  natsort($fqfiles);
}

echo "<span><b>Choose library number(s): </b></span><span class=\"helper\" id=\"help0\" style=\"color:blue;\"><b>?</b></span><br><br>";
if(count($fqfiles)<3){ #because of . and .. directories existing
	echo "<b>Note:</b> No libraries ready to crunch!<br>";
} else {
	# else, list the files
	foreach($fqfiles as $fqfile)
	{
		# *** TO FIX ***
		# Modifying arrays while 'foreach' iterating is broken in php
		# -> It buffers the array at the foreach call and iterates over 
		# -> potentially old or modified data (bad, php!)
		# This double checks that the element is in the new fqfiles array 
		# to fix this minor annoying problem...
		# Edit: I was an idiot (didn't know about as &$var), but I guess this still works
		if (($key = array_search($fqfile, $fqfiles)) !== false) 
		{
			$doublestranded = 0;
			if ($fqfile !== "." and $fqfile !== "..")
			{ 
				$librarynum = "";
				$libpattern = "/^s*(\d*).*/";
				preg_match($libpattern, $fqfile, $matches);
				$librarynum = $matches[1];

				foreach ($fqfiles as $fqfile2)
				{
					if ($fqfile != "." and $fqfile != ".." and $fqfile2 != "." and $fqfile2 != "..")
					{ 
						$librarynum2 = "";
						$libpattern = "/^s*(\d*).*/";
						preg_match($libpattern, $fqfile2, $matches2);
						$librarynum2 = $matches2[1];

						if(($librarynum2 == $librarynum) and ($fqfile !== $fqfile2))
						{
							# Remove double stranded results from list
							if (($key = array_search($fqfile, $fqfiles)) !== false) 
							{
								$key2 = array_search($fqfile2, $fqfiles);
								unset($fqfiles[$key]);
								unset($fqfiles[$key2]);
							}	      	
							$doublestranded = 1;

							echo "<input type=\"checkbox\" name=\"fqfilename[]\" value=\"$fqfile&$fqfile2\">$librarynum (double stranded)<br>";

						} 
					}
				}

				if ($doublestranded == 0)
				{
					echo "<input type=\"checkbox\" name=\"fqfilename[]\" value=\"$fqfile\">$librarynum<br>";
				}
			}
		}
	}
} 

echo "</select>";

?>

<!--
######################################
# Proc Selector Slider (JS onchange) #
######################################
-->
<br>
<span><b>Number of processors: </b></span><span class="helper" id="help1" style="color:blue;"><b>?</b></span><br><br>
<script>
function showVal(newVal){ 
    document.getElementById("slideVal").innerHTML = newVal;
}
</script> 

<div style="float:left;">Run on&nbsp;</div>
<div id="slideVal" style="float:left;">24</div>
<div style="float:left;">&nbsp;processor(s)</div><br>

<div style="height:30px;width:250px;float:left;">
1<input name="procs" type="range" min="1" max="31" step="1" value="24" oninput="showVal(this.value)"> 31</div>
<br>

<!--
################################
# Create DDBox for fasta files #
################################
-->

<?php
$fafiles = scandir("$subdirectories/fasta_directory"); 

echo "<br><span><b>Choose a fasta: </b></span><span class=\"helper\" id=\"help2\" style=\"color:blue;\"><b>?</b></span><br><br>";
if(count($fafiles)<3){ #because of . and .. directories existing
	echo "<b>Note:</b> No fasta files available! (email wtreible@udel.edu)<br>";
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

<!--
#####################################
# Create DDBox for annotation files #
#####################################
-->

<?php

$afiles = scandir("$subdirectories/annotation_directory");

echo "<br><br><span><b>Choose an Annotation File: </b></span><span class=\"helper\" id=\"help3\" style=\"color:blue;\"><b>?</b></span><br><br>";

if(count($afiles)<3){ #because of . and .. directories existing
	echo "<b>Note:</b> No annotation files available! (email wtreible@udel.edu)<br>";
} else {
	echo "<select name=\"afilename\">";
	foreach ($afiles as $afile) 
	{
	  if(($afile != ".") and ($afile != ".."))
	  { 
	    echo "<option value=\"$afile\">$afile</option>";
	  }
	} 
	echo "</select>";
}

?>

<!--
#######################
# Annotation Selector #
#######################
-->

<br><br>
<span><b>Annotation Type: </b></span><span class="helper" id="help4" style="color:blue;"><b>?</b></span><br>

<div class="frnakRadio">
<div class="checkname">NCBI</div>
<input type="radio" id="frnakRadioInput" name="annotationtype" value="ncbi" checked>
<label for="frnakRadioInput"></label></div>

<div class="frnakRadio">
<div class="checkname">Ensembl</div>
<input type="radio" id="frnakRadioInput2" name="annotationtype" value="ensembl" >
<label for="frnakRadioInput2"></label></div>

<br>
<!--
###########################
# Submit and Menu Buttons #
###########################
-->

<div class='container'>
<button id='crunch' class='crunch' type="submit">fRNAkenstein, Crunch!</button>

<br> <br> <br>
</form>
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
<iframe name='formresponse' src='placeholder_response.html' style="border: outset; background-color:#d0eace" width='500px' height='<?php echo count($fqfiles)*16+500;?>' frameborder='0'>
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

