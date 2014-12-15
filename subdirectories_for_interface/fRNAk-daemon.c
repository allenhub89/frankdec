#include <sys/types.h>
#include <sys/stat.h>
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <errno.h>
#include <unistd.h>
#include <syslog.h>
#include <string.h>
#include <regex.h>   
#include <dirent.h>
#include <assert.h>

int 
compare( const void* a, const void* b);

int 
main(void) {
	
	const char* monitorpath = "/var/www/subdirectories_for_interface/bash_scripts/";
	const int sleep_running = 15; /* 15 seconds when queued */
	const int sleep_idle = 300; /* 5 mins when no queue */

	int sleep_time = sleep_running; /* time to sleep */
	

	/* Process and session IDs */
	pid_t pid, sid;

	/* Fork off the parent process */
	pid = fork();
	if (pid < 0) {
		exit(EXIT_FAILURE);
	}

	/* Exits the parent process leaving
	only fRNAkdaemon running */
	if (pid > 0) {
		exit(EXIT_SUCCESS);
	}

	/* Change the file mode mask 
	to allow us to write/read 
	logs and files */
	umask(0);
	
	/* Open Daemon log here */        
	
	/* Create a new session ID */
	sid = setsid();
	/* If failed, exit */
	if (sid < 0) {
		exit(EXIT_FAILURE);
	}



	/* Change the current working directory 
	 If failed, exit */
	if ((chdir(monitorpath)) < 0) {
		exit(EXIT_FAILURE);
	}

	/* Close out the standard file descriptors 
	to plug some security holes */
	close(STDIN_FILENO);
	close(STDOUT_FILENO);
	close(STDERR_FILENO);

	/* Daemon main loop */        		
	while (1) {
		//printf("\n===NEW ITERATION===\n");
		char *files[100];
		char *ids[100];	

		/* Build directory list */
		int size = 0;
		DIR *dir;
		struct dirent *ent;
		if ((dir = opendir (monitorpath)) != NULL) 
		{
			/* print all the files and directories within directory */
			int file;
			for(file=0; file<100; file++)
			{
				if ((ent = readdir (dir)) != NULL) 
				{
					if((strcmp(ent->d_name, ".") != 0 && strcmp(ent->d_name, "..")) !=0 )
					{
						if( ((char*)ent->d_name)[1] != '_' )
						{
							files[size] = (char*)malloc(sizeof(ent->d_name));
							strcpy(files[size], ent->d_name);
							size++;
						}
					}
				}
			}
			closedir (dir);		
		} else {
			/* could not open directory */
			perror ("");
			return EXIT_FAILURE;
		}
		int i;	
		for(i=0; i < size; i++){

			regex_t regex;
			int reti,retl;
			int n_matches = 10;
			regmatch_t m[n_matches];
			char * exp = "run_(.*)\\.(.*)\\.(.*)\\.sh";
			
			/* copy the string so we can
			   mess with it further down */
			int stringlen = strlen(files[i]);
			char sourceCopy[stringlen];
			strcpy(sourceCopy, files[i]);
			
			/* Compile regular expression */
			retl = regcomp(&regex, exp, REG_EXTENDED|REG_NEWLINE);
			/* Regex matchs gest stored in array m */
			reti = regexec(&regex, sourceCopy, n_matches, m, 0);

			assert(retl >= 0);
			assert(reti >= 0);

			printf("len %d, string %s\n", stringlen, sourceCopy);
			/* length of the first match */
			int idlen = m[1].rm_eo - m[1].rm_so;
			/* safe n-length string copy */
			char id[idlen];
			*id = '\0'; strncat(id, sourceCopy + m[1].rm_so, idlen);

			int idlen2 = m[2].rm_eo - m[2].rm_so;
			char id2[idlen2];
			*id2 = '\0'; strncat(id2, sourceCopy + m[2].rm_so, idlen2);

			printf("ID1: (%s)\n", id);
			printf("ID2: (%s)\n", id2);
			/* remove hyphens from id2 (date) */
			char *s,*d;
			for ( s=d=id2; (*d=(*s)); (d+=(*s++!='-')) );

			/* concatenate the two ids
			 into datetime for sorting */
			strcat(id2, id);

			/* Free regex and files array */
			regfree(&regex);
			free(files[i]);
			
			ids[i] = (char*)malloc(sizeof(id2));
			strcpy(ids[i], id2); 
			

		
		}

		if(size > 0)
		{
			/* sort file ids */
			qsort(ids, size, sizeof(char*), compare);
		
			/* Grab individual strings for building file path */
			char file_time_id[7];
			memcpy( file_time_id, &ids[0][8], 6 );
			file_time_id[6] = '\0';
			
			char file_day_id[3];
			memcpy( file_day_id, &ids[0][0], 2 );
			file_day_id[2] = '\0';

			char file_month_id[3];
			memcpy( file_month_id, &ids[0][2], 2 );
			file_month_id[2] = '\0';

			char file_year_id[5];
			memcpy( file_year_id, &ids[0][4], 4 );
			file_year_id[4] = '\0';
			
			/* final build of file path */
			char *file_path;
			size_t sz;
			sz = snprintf(NULL, 0, "%srun_%s.%s-%s-%s.*.sh", 
						monitorpath, file_time_id, file_day_id, file_month_id, file_year_id);
			assert(sz > 0);
			file_path = (char *)malloc(sz + 1);
			assert(file_path != NULL);			
			snprintf(file_path, sz+1, "%srun_%s.%s-%s-%s.*.sh", 
						monitorpath, file_time_id, file_day_id, file_month_id, file_year_id);

			/* bash command */
			char *exec_cmd;
			sz = snprintf(NULL, 0, "bash %s", file_path);
			assert(sz > 0);
			exec_cmd = (char *)malloc(sz + 1);
			assert(exec_cmd != NULL);			
			snprintf(exec_cmd, sz+1, "bash %s",file_path);	
			
			/* rm command */
			char *rm_cmd;
			sz = snprintf(NULL, 0, "rm -f %s", file_path);
			assert(sz > 0);
			rm_cmd = (char *)malloc(sz + 1);
			assert(rm_cmd != NULL);			
			snprintf(rm_cmd, sz+1, "rm -f %s",file_path);	
			
			/* execute commands */
			system(exec_cmd);
			system(rm_cmd);

			/* if the queue will be empty, set to idle time */
			if(size == 1)
			{
				sleep_time = sleep_idle;
			} 
			/* if the queue is still full, wait 30 sec */
			else { 
				sleep_time = sleep_running;
			}
			int j;
			/* free memory alloc'd for ids */
			for(j=0; j<size; ++j)
			{
				free(ids[j]);
			}
		} 
		else {
			/* if no queue, idly poll */
			sleep_time = sleep_idle;
		} 
 		
		sleep(sleep_time); /* wait X seconds */
	}
}


/* compare function for qsort */
int 
compare(const void* a, const void* b)
{	
	const char **ia = (const char **)a;
	const char **ib = (const char **)b;
	return strcmp(*ia, *ib);
}
