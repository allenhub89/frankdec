<?php
$subdirectories = "/var/www/subdirectories_for_interface"; 
$output = "/var/www/subdirectories_for_interface/mirDeep2_output";

echo "well...this worked!!! \n";

if(empty(strip_tags(htmlspecialchars(escapeshellcmd($_POST['Phred']))))){
	exit("<h4>Error 2: Phred selection error</h4>");
}
if(empty(strip_tags(htmlspecialchars(escapeshellcmd($_POST['analysisName']))))){
	exit("<h4>Error 3: No analysisName</h4>");
}
if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['fafilename']))))){
	exit("<h4>Error 4: No Fasta file selected</h4>");
}

if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['fqfilename']))))){
	exit("<h4>Error 4: please choose a library to be crunched </h4>");
}

if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['mirFilename']))))){
	exit("<h4>Error 4: Please choose one of the appropriate mirBase Files </h4>");
}

if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['minLength']))))){
	exit("<h4>Error 4: please choose an appropriate min Length of the reads </h4>");
}


##################################
# Grab values from HTML elements #
##################################
$Phred = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['Phred']))));
$analysisName = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['analysisName']))));
$fa = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['fafilename']))));
$mirFile = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['mirFilename']))));
$minLength = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['minLength']))));
$fq = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['fqfilename']))));


################################
#Make other relevant variables #
################################
$fapath = "$subdirectories/fasta_directory/$fa/$fa";
$mirPath = "$subdirectories/mirFiles";
#$mirOutput = "$mirFile/minotaur_output/$analysisName";


#for now, we are going to use the sample output from tom's pipeline
$mirOutput = "$mirFile/minotaur_output/result_15_07_2014_t_15_43_40.html";

$mapperPath = "$mirPath/mirDeep2/mapper.pl";
$mirDeep2Path = "$mirPath/miRDeep2.pl";
$mappedPath = $analysisName . "fa";
$unmappedPath = $analysisName . "arf";
$identifiedOut = $analysisNme . "matureFixed";
$mytimeid = date('his.m-d-Y');


##############################
#Make the System Commands
##############################
$systemCommand = "perl $mapperPath $fa -c -j -k -l $minLength -m -p $fa -s $analysisName.fa -t $analysisName.arf -v&& \n";
$systemCommand .= "perl $mirDeep2Path $mappedPath $fapath $identifiedOut -t $mirPath $analysisName 2>$analysisName.log\n";
$premailtext = "Your MImapR analysis ".$analysisname." with run ID: ".$mytimeid." has been started!\n";
$premailtext .= "The estimated completion time for this run assuming no server load or queue is about 2 hours.\n";
$premailtext .= "You can view the status of your run using the MInotauR status page and an email will be\n";
$premailtext .= "sent upon the completion of your run.\n\n-The fRNAkenstein + MInotaur Team";
$premailcommand = 'echo "'.$premailtext.'" | mail -s "$(echo -e "MImampR Run\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" '.'user_email']."\n";

$postmailcommand = 'if [ $? -eq 0 ]; then
	echo "Your DiffExpress run with ID: '.$mytimeid.' completed successfully! You can view and download your data on the results page." | mail -s "$(echo -e "fRNAkenstein DiffExpress Successful\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" '.$_SESSION['user_email'].'
else
	echo "Your DiffExpress run with ID: '.$mytimeid.' was unsuccessful! Please email an administrator with your run ID and subject line \"fRNAkenstein error\"" | mail -s "$(echo -e "fRNAkenstein DiffExpress Unsuccessful\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" '.$_SESSION['user_email'].'
fi';
$commands = $premailcommand.$commands.$postmailcommand;


echo "well, this works!!!! \n";

echo $output . "is the output \n";

echo "\n $analysisName is the analysisName !!!! \n";




# Write files
file_put_contents($systemCommand, $output, LOCK_EX);

?>
