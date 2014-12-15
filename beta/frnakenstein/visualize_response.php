
<script src="//code.jquery.com/jquery-1.10.2.js"></script>

<script>
$(document).ready(function(){

	$('#loading-image', window.parent.document).hide();
	$('#page-cover', window.parent.document).hide();
});

</script>

<?php

$toPutInFigures = $_POST['genes'];
$figureType = strip_tags (htmlspecialchars( escapeshellcmd($_POST['figureType'])));
$geneOrIsoform = strip_tags (htmlspecialchars( escapeshellcmd($_POST['geneOrIsoform'])));
$barOrLine = strip_tags (htmlspecialchars( escapeshellcmd($_POST['barOrLine'])));
$errorBars = strip_tags (htmlspecialchars( escapeshellcmd($_POST['errorBars'])));
$analysispath = strip_tags (htmlspecialchars( escapeshellcmd($_POST['analysispath'])));
$analysis = strip_tags (htmlspecialchars( escapeshellcmd($_POST['analysis'])));

foreach($toPutInFigures as &$gene)
{
	$gene = strip_tags (htmlspecialchars( escapeshellcmd($gene)));
}

$plotcommand = "";

if ($geneOrIsoform == "gene")
{ 
	#echo "Gene Level<br>";
	$plotcommand = "".$barOrLine."(gene, showErrorbars='$errorBars') \n";
}
else if ($geneOrIsoform == "isoform")
{
	#echo "Isoform Level<br>";
	$plotcommand = "".$barOrLine."(isoforms(gene), showErrorbars='$errorBars') \n";
}


# load cummerbund and initialize graph command
$graphCommand = "library(cummeRbund)\n";

# Set Working Directory
$graphCommand .= "setwd(\"$analysispath/cuffdiff_output\")\ncuff <- readCufflinks()\n";

# Set Image Path
$imagepath = "$analysispath/images";

if($figureType == "all")
{
	$count = 0;
	$geneVector = "";	

	$graphCommand .= "png(\"$imagepath/all.png\")\n";

	#echo "Figure Type: All<br>";

	foreach($toPutInFigures as &$gene)
	{
		$count += 1;
		
		if($count < count($toPutInFigures))
		{		
			$geneVector = $geneVector . "\"$gene\",";
		}
		else
		{
			$geneVector = $geneVector . "\"$gene\"";
		}
	}


	$graphCommand .= "gene <- getGenes(cuff,c($geneVector));\n".$plotcommand;  
	
        $graphCommand .= "dev.off()\n";

}
else if($figureType == "separate")
{
	$count = 0;	
		
	#echo "Figure Type: Separate<br>";
	foreach($toPutInFigures as &$gene)
	{
         
		$graphCommand .= "png(\"$imagepath/$gene.png\")\n";

		$graphCommand .= "gene <- getGenes(cuff,\"$gene\");\n".$plotcommand; 	
		
		$graphCommand .= "dev.off()\n";
	}
}

$rfile = "$analysispath/images.R";

# Analysis Image path
$analysisimagepath = "/var/www/html/analysis_images/$analysis";

file_put_contents($rfile, $graphCommand, LOCK_EX);
$sysout = exec("rm $imagepath/*; mkdir -p $imagepath; R --vanilla < $rfile 2> /dev/null; rm -r $analysisimagepath 2> /dev/null; mkdir $analysisimagepath 2> /dev/null; cp $imagepath/* $analysisimagepath", $output);


#get a list of all the images in the images directory

$images = scandir("$analysisimagepath");

foreach($images as $image)
{
	if ($image !== "." and $image !== "..")
	{
		$pattern = "/(.*).png/";
		preg_match($pattern, $image, $matches);
		$title = $matches[1];
		echo "<h2><center>".$title."</center></h2>";
		echo "<img src=\"/analysis_images/$analysis/$image\" alt=\"fRNAkenstein\" width=\"480\" > </td> <br> <br>";
		echo "<a href=\"/analysis_images/$analysis/$image\" download=\"$image\" title=\"Download\"><center>Download this image</center></a><br><br><hr>";

	}
}
?>

