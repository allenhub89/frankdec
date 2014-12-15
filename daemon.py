#!/usr/bin/env python

import sys, os, time, atexit
from collections import defaultdict
import re, subprocess

from signal import SIGTERM 

class TitanDaemon:
	"""
	A generic daemon class.
	
	Usage: subclass the Daemon class and override the run() method
	"""
	def __init__(self, pidfile, bash_scripts, max_processes, max_user_processes, idle_wait, active_wait, stdin='/dev/null', stdout='/dev/null', stderr='/dev/null'):
		self.stdin = stdin
		self.stdout = stdout
		self.stderr = stderr
		self.pidfile = pidfile
		self.bash_scripts_dir = bash_scripts
		self.max_processes = int(max_processes)
		self.max_user_processes = int(max_user_processes)
		self.idle_wait = int(idle_wait)
		self.active_wait = int(active_wait)
	
	def daemonize(self):
		"""
		do the UNIX double-fork magic, see Stevens' "Advanced 
		Programming in the UNIX Environment" for details (ISBN 0201563177)
		http://www.erlenstar.demon.co.uk/unix/faq_2.html#SEC16
		"""
		try: 
			pid = os.fork() 
			if pid > 0:
				# exit first parent
				sys.exit(0) 
		except OSError, e: 
			sys.stderr.write("fork #1 failed: %d (%s)\n" % (e.errno, e.strerror))
			sys.exit(1)
	
		# decouple from parent environment
		os.chdir("/") 
		os.setsid() 
		os.umask(0) 
	
		# do second fork
		try: 
			pid = os.fork() 
			if pid > 0:
				# exit from second parent
				sys.exit(0) 
		except OSError, e: 
			sys.stderr.write("fork #2 failed: %d (%s)\n" % (e.errno, e.strerror))
			sys.exit(1) 
	
		# redirect standard file descriptors
		sys.stdout.flush()
		sys.stderr.flush()
		si = file(self.stdin, 'r')
		so = file(self.stdout, 'a+')
		se = file(self.stderr, 'a+', 0)
		os.dup2(si.fileno(), sys.stdin.fileno())
		os.dup2(so.fileno(), sys.stdout.fileno())
		os.dup2(se.fileno(), sys.stderr.fileno())
	
		# write pidfile
		atexit.register(self.delpid)
		pid = str(os.getpid())
		file(self.pidfile,'w+').write("%s\n" % pid)
	
	def delpid(self):
		os.remove(self.pidfile)

	def start(self):
		"""
		Start the daemon
		"""
		# Check for a pidfile to see if the daemon already runs
		try:
			pf = file(self.pidfile,'r')
			pid = int(pf.read().strip())
			pf.close()
		except IOError:
			pid = None
	
		if pid:
			message = "pidfile %s already exist. Daemon already running?\n"
			sys.stderr.write(message % self.pidfile)
			sys.exit(1)
		
		# Start the daemon
		self.daemonize()
		self.run()

	def stop(self):
		"""
		Stop the daemon
		"""
		# Get the pid from the pidfile
		try:
			pf = file(self.pidfile,'r')
			pid = int(pf.read().strip())
			pf.close()
		except IOError:
			pid = None
	
		if not pid:
			message = "pidfile %s does not exist. Daemon not running?\n"
			sys.stderr.write(message % self.pidfile)
			return # not an error in a restart

		# Try killing the daemon process	
		try:
			while 1:
				os.kill(pid, SIGTERM)
				time.sleep(0.1)
		except OSError, err:
			err = str(err)
			if err.find("No such process") > 0:
				if os.path.exists(self.pidfile):
					os.remove(self.pidfile)
			else:
				print str(err)
				sys.exit(1)

	def restart(self):
		"""
		Restart the daemon
		"""
		self.stop()
		self.start()

	def natural_sort(self, l): 
		convert = lambda text: int(text) if text.isdigit() else text.lower() 
		alphanum_key = lambda key: [ convert(c) for c in re.split('([0-9]+)', key) ] 
		return sorted(l, key = alphanum_key)
		
	def run(self):
		"""
		You should override this method when you subclass Daemon. It will be called after the process has been
		daemonized by start() or restart().
		"""
		
		# Exe Dict of format
		# User => Running File(s)
		exe_dict = defaultdict(list)

		while True:

			sleep_time = self.active_wait
			#foreach user in bash_scripts (only directories)
			users_path = self.bash_scripts_dir
			#print str(os.listdir(users_path)) + "is the list of users"
			#print users_path + "is user path!!"
			bash_files = []
			#iterate through the users
			for user in [d for d in os.listdir(users_path) if os.path.isdir(os.path.join(users_path,d))]:				
				#have a list of all of the users, see if there is a directory for each user
				print "User: ",user
				#print os.path.join(users_path,d) + "is  the directory path joined"		
				# Sort bash_files (only files)
				user_path = users_path + "/" + user
				#make the path to that user's directory
				
				for f in os.listdir(user_path):
					#once we have the path to that user's directory
					if os.path.isfile(os.path.join(user_path,f)):
						#go through all of the files; add the ones that are not R files to the list which will
						#represent scheduled jobs for that user.  The ones that are not R files will
						#not have a dash in their second position.  This is because the R files will be named
						#r_nameOfFile
						if (f[1] != '_' and f[0] != '.'):
							bash_files.append(f) #if not an R file, we append the file to the list
							#bash files
							#print str(bash_files) + "is bash files!!"
				
				# some more complex sorting magic...we are going to sort the bash files
				# by the time at which they were made 
				import collections
				dict_ = {}
				if(len(bash_files) > 0):
					for b in bash_files:
						#print b + "is a bash file";
						file_date = b[11:13] + b[14:16] #get the file_date
						file_time = b[4:10] #get the file_time
					
					#if(b[0] == 'r' & b[1] == '_'):
						#file_date = b[9:11] + b[12:14]
						#file_time = b[4:10]
						dt = file_date + file_time
						#again, double check that it is not an R script
						
						dict_[dt] = b
						
						#It looks like that what we do is make a dictionary (k,v)
						#where k is bash_script's date and time and then v is the bash script name							
				
						#print str(len(bash_files)) + "is the length of the bash script array and " + user + "is user" 	
					#now we do the sorting of the dictionary by the date and time
					od = collections.OrderedDict(sorted(dict_.items()))
				
					sorted_bash_files = []
					
					#now, we go through and we add the sorted job names to the absolute paths so that
					#they can be run
					for k, v in od.iteritems():
						sorted_bash_files.append(os.path.join(user_path,v))
						
					#message = "this is the lib\n"
                        		#sys.stderr.write(message % v)
                        		#sys.exit(1)
					bash_files = sorted_bash_files
					print sorted_bash_files 
					
					
					num_files = len(bash_files)
					# Number of scripts this user is running
					running = len(list(exe_dict[user]))
					print str(running) + " is the length of processes running for the user!"
					print self.max_user_processes
					#for i in exe_dict[user]:
						#print i + "is the key !!! \n"
						#print running + "is the length of processes running for the user !!"
					
					#if we can still run more processec			
					if (running < self.max_user_processes):
						#make sure that it is not already being run
						#for i in exe_dict[user]:
						sub = subprocess.Popen(["bash",bash_files[running]])
						exe_dict[user].append(sub)
						print "\tStarted: ", bash_files[running]
					
					elif (running == self.max_user_processes or running == num_files or num_files == 0):
						print "\tChecking for ended processes..."
						for proc in exe_dict[user]:
							if(proc.poll() != None):
								exe_dict[user].remove(proc)
								print "\t\tProcess ", proc, " has ended and has been removed!"
							else:
								print "\t\tProcess ", proc, " is still running!"
								pass					
					else:
						sleep_time = self.idle_wait

				
			time.sleep(sleep_time)
