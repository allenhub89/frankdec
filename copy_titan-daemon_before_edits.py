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
			print str(os.listdir(users_path)) + "is the list of users"
			print users_path + "is user path!!"
			bash_files = []
			for user in [d for d in os.listdir(users_path) if os.path.isdir(os.path.join(users_path,d))]:				
				print "User: ",user
				print d + "\tis user!!"
				print os.path.join(users_path,d) + "is  the directory path joined"		
				# Sort bash_files (only files)
				user_path = users_path + "/" + user
				
				for f in os.listdir(user_path):
					if os.path.isfile(os.path.join(user_path,f)):
						if (f[1] != '_'):
							bash_files.append(f)
				
				#bash_files = [f for f in os.listdir(user_path) if os.path.isfile(os.path.join(user_path,f))] 
				
				# some more complex sorting magic...
				import collections
				dict_ = {}
				if(len(bash_files) > 0):
					for b in bash_files:
						print b + "is a bash file";
						file_date = b[11:13] + b[14:16]
						file_time = b[4:10]
					
					#if(b[0] == 'r' & b[1] == '_'):
						#file_date = b[9:11] + b[12:14]
						#file_time = b[4:10]
							
						
					
						dt = file_date + file_time
						if b[1] != '_':
							dict_[dt] = b
					#print bash_files + "is bash files!!"
						print b + "is b!!"
						print str(len(bash_files)) + "is the length of the bash script array and " + user + "is user" 	
					od = collections.OrderedDict(sorted(dict_.items()))
				
					sorted_bash_files = []
					print str(len(sorted_bash_files)) + "is the length of the list of sorted bash files" 
				
					for k, v in od.iteritems():
						sorted_bash_files.append(os.path.join(user_path,v))
						print v + "is a bash file in the sort list !!!"
					#message = "this is the lib\n"
                        		#sys.stderr.write(message % v)
                        		#sys.exit(1)
					bash_files = sorted_bash_files
				
					for element in sorted_bash_files:
						print element + "is the element"
				# Number of bash files in this user's folder
					num_files = len(bash_files)
				
				# Number of scripts this user is running
					running = len(list(exe_dict[user]))
					print "\tRunning ",running, " out of ", self.max_user_processes
				
					# If I'm running less process than my max allowed to run...
					print str(running) + "is the number of processes running for this user"
					print str(self.max_user_processes) + "is the number of total processes allowed"
				
					if (running < self.max_user_processes):
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
