<?php 

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
$admin = $ini['login']['admin'];
$def_path = $ini['login']['default'];
$subdirectories = $ini['filepaths']['subdirectories'];

session_start();

if(empty($_SESSION['user_name']) && !($_SESSION['user_is_logged_in']))
{
  header('Location: '.$def_path);
}


############################################
# Some error checking redundancy on inputs #
############################################

if(empty($_POST['fqfilename'])){
	exit("<h4>Error 1: No Fastq file selected</h4>");
}
if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['procs']))))){
	exit("<h4>Error 2: Number of proccessors error</h4>");
}
if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['afilename']))))){
	exit("<h4>Error 3: No annotation file selected</h4>");
}
if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['fafilename']))))){
	exit("<h4>Error 4: No Fasta file selected</h4>");
}
if(empty(strip_tags (htmlspecialchars( escapeshellcmd($_POST['annotationtype']))))){
	exit("<h4>Error 14: No annotation type selected</h4>");
}

##################################
# Grab values from HTML elements #
##################################

$fqarray = $_POST['fqfilename'];
$procs = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['procs']))));
$anno = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['afilename']))));
$fa = strip_tags (htmlspecialchars( escapeshellcmd(htmlentities($_POST['fafilename']))));
$annotype = strip_tags (htmlspecialchars( escapeshellcmd($_POST['annotationtype'])));

########################
# Printing information #
########################

echo "<body >";
echo "<div id='result_div'>";
echo "<h4>Crunching library with data:</h4>";

echo "<p>";
echo "Library file(s) selected: <br>";
foreach ($fqarray as $fqfile){
	echo strip_tags (htmlspecialchars( escapeshellcmd($fqfile)))."<br>";
}
echo "</p>";

echo "<p >";
echo "# Procs: $procs";
echo "</p>";

echo "<p >";
echo "Annotation file: $anno";
echo "</p>";

echo "<p >";
echo "Annotation type: $annotype";
echo "</p>";

echo "<p >";
echo "Fasta file: $fa";
echo "</p>";

echo "<p>";
echo "<b>NOTE:</b><br>Running the pipeline will take a long time; <br> An email will be sent when your run completes.";
echo "</p>";

echo "</div>";

##########################
# Begin front end script #
##########################

# Initialize output command string
$outputcommands = "";

# Generate a unique ID based on the time and echo it
$mytimeid = date('his.m-d-Y');
echo "<b>Your run ID is: </b> $mytimeid<br><br>";

# Create log path and initialize it
$logfile = "$subdirectories/logs/$mytimeid.mapcount.log";
$logoutput = "User: ".$_SESSION['user_name']."\n";
$logoutput .= "Bash commands...\n";

# Temp output path
$temppath = "$subdirectories/temp_output";

# For every library selected:
foreach($fqarray as $fqoriginal) {


	# Initialize moveandgunzip command
	$movecommand = "";

	# Split for double stranded
	$fqdoublestranded = explode("&", $fqoriginal);
	# don't like this but it needs to be done for clarity below
	$fq = $fqdoublestranded[0];

	# To make sure HTSeq command is stranded/unstranded as well
	$htseqstranded = "no";

	# Check if fastq file is zipped
	if (preg_match("/\.gz/", $fq)!=1 ){
		exit("Error 5: Fastq file not gzipped (.gz)");
	} else {

		# If double stranded, path is equal to both paths delimited by a space
		if (count($fqdoublestranded)==2) {
			$fqpath = "$subdirectories/fastq_to_be_crunched/$fqdoublestranded[0]";
			$fqpath2strand = "$subdirectories/fastq_to_be_crunched/$fqdoublestranded[1]";

			if(preg_match("/\.gz/", $fqpath)==1 and preg_match("/\.gz/", $fqpath2strand)==1){
				system("mv $fqpath $temppath/$fqdoublestranded[0]");
				system("mv $fqpath2strand $temppath/$fqdoublestranded[1]");

				$movecommand .= "gunzip $temppath/\*.gz\n";
				$fq = preg_replace("/.gz/","",$fq);
				$fq2 = preg_replace("/.gz/","",$fqdoublestranded[1]);
				$fqpath = "$temppath/$fq";
				$fqpath2strand = "$temppath/$fq2";
				$fqpath = $fqpath." ".$fqpath2strand;
			}

			# this is stranded for htseq also
			$htseqstranded = "yes";


		}
		# Otherwise, it's just equal to the one fastq filepath
		else{
			$fqpath = "$subdirectories/fastq_to_be_crunched/$fq";

			if(preg_match("/\.gz/", $fqpath)==1)
			{
				system("mv $fqpath $temppath/");
				$movecommand .= "gunzip $temppath/$fq\n";
				$fq = preg_replace("/.gz/","",$fq);
				$fqpath = "$temppath/$fq";

			}
		}

		# Generate other file paths for annotation and fasta files
		$annopath = "$subdirectories/annotation_directory/$anno";
		$fapath = "$subdirectories/fasta_directory/$fa/$fa";

		# Parse library number
		$library = preg_replace("/(_[a-zA-Z0-9]*)+(\.[a-zA-Z0-9]*)+/","",$fq);

		# Generate location for output files
		$thoutputfile = "$temppath/library_$library/tophat_out";
		$cloutputfile = "$temppath/library_$library/cufflinks_out";
		$sampath = "$temppath/library_$library/sam_output";
		$htseqpath = "$temppath/library_$library/htseq_output";

		# Generate mkdir commands for new directories
		# -p option prevents errors with pre-existing folders
		$makedirs = "mkdir -p $temppath/library_$library\n";
		$makedirs .= "mkdir -p $cloutputfile\n";
		$makedirs .= "mkdir -p $thoutputfile\n";
		$makedirs .= "mkdir -p $sampath\n";
		$makedirs .= "mkdir -p $htseqpath";

		# Generate commands for TH and CL
		$thcommand = "tophat -p $procs -o $thoutputfile $fapath $fqpath";
		$clcommand = "cufflinks -p $procs -g $annopath -o $cloutputfile $thoutputfile/accepted_hits.bam";

		# Generate HTSeq commands
		$htseqcommand = "samtools view -h -o $sampath/$library.sam $thoutputfile/accepted_hits.bam\n";
		if ($annotype == "ncbi")
		{
			$htseqcommand .= "htseq-count -t gene -i gene -s ".$htseqstranded." $sampath/$library.sam $annopath 2>> $logfile 1> $htseqpath/$library.counts";
		}
		else if ($annotype == "ensembl")
		{
			$htseqcommand .= "htseq-count -t gene -i Name -s ".$htseqstranded." $sampath/$library.sam $annopath 2>> $logfile 1> $htseqpath/$library.counts"; 
		}

		# Move temp files to output directory only after the library has been crunched
		$mvcommand = "mv -f $temppath/library_$library $subdirectories/mapcount_output/\n";

		# Append library commands to the output command string
		$singleoutputcommand = "$makedirs\n$thcommand >> $logfile 2>&1\n$clcommand >> $logfile 2>&1\n$htseqcommand\n";
		$outputcommands = $outputcommands.$movecommand.$singleoutputcommand.$mvcommand;

		# Build log output
		$logoutput = $logoutput."Fastq file: $fqoriginal\n"."Command generated: ".$singleoutputcommand;
	}
}


# Create bash file output directory
$bashfile = "$subdirectories/bash_scripts/run_$mytimeid.mapcount.sh";

# Add mv bashfile command to the end to prevent re-running
$mvbashcommand = "\nmv -f $bashfile $subdirectories/old_bash_scripts/";

# Append to log output (TH and CL will redirect stderr to log file)
$logoutput = $logoutput."MapCount output...\n";


# generate the mail commands
$mailtext = "Hello ".$_SESSION['user_name'].",\n";
$mailtext .= "Your Mapcount run information for run ID $mytimeid is...\n";
$mailtext .= "\nFastq files:\n";
foreach ($fqarray as $fqfile){
	$mailtext .= "$fqfile\n";
}
$mailtext .= "\nAnnotation File:\n$anno\n\nAnnotation Type:\n$annotype\n";
$mailtext .= "\nFasta File:\n$fa\n";
$mailtext .= "\nYour run will take some time to complete.\n";
$mailtext .= "You can view the status of your run using the fRNAkenstein status page.\n";
$mailtext .= "An email will be sent to you upon completion of this run.\n";
$mailtext .= "\n- fRNAkenstein Team";

$premailcommand = 'echo "'.$mailtext.'" | mail -s "$(echo -e "fRNAkenstein MapCount Run\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" '.$_SESSION['user_email']."\n";

$postmailcommand = 'if [ $? -eq 0 ]; then
	echo "Your MapCount run with ID: '.$mytimeid.' completed successfully! You can now run differential expression analysis on this data!" | mail -s "$(echo -e "fRNAkenstein MapCount Successful!\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" '.$_SESSION['user_email'].' 
else
	echo "Your MapCount run with ID: '.$mytimeid.' was unsuccessful! Please email an administrator with your run ID and subject line \"fRNAkenstein error\"" | mail -s "$(echo -e "fRNAkenstein MapCount Unsuccessful\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" '.$_SESSION['user_email'].'
fi';

$outputcommands = $premailcommand.$outputcommands.$postmailcommand;

#generate header
$outputcommands = "#!/bin/bash\n".$outputcommands.$mvbashcommand;

# Write files
file_put_contents($logfile, $logoutput);
file_put_contents($bashfile, $outputcommands, LOCK_EX);

?>

<!--
#########################
# fRNAkenstein Reloader #
#########################
-->

<script language="javascript">
function reloader()
{
	/* Reload window */
	parent.location.reload();
	/* Set iFrame to empty */
	window.location.assign("about:blank");

}
</script>

<input type="button" value="Run fRNAkenstein again!" onClick="return reloader(this);">


</body>
</html>
