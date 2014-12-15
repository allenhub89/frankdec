#!/usr/bin/env python

import sys, time, ConfigParser
import subprocess
from daemon import TitanDaemon
			
if __name__ == "__main__":

	# Load the Config File
	config = ConfigParser.ConfigParser()
	config.read('config.ini.php')
	
	# Set Config Options
	bash_scripts = config.get("filepaths", "bash_scripts")
	subdirectories = config.get("filepaths", "subdirectories")
	max_processes = config.get("daemon", "max_processes")
	max_user_processes = config.get("daemon", "max_user_processes")
	idle_wait = config.get("daemon", "idle_wait")
	active_wait = config.get("daemon", "active_wait")	
	
	pid_file = subdirectories + "/titan-daemon.pid"
	
	# Initialize the Daemon Object
	daemon = TitanDaemon(pid_file, bash_scripts, max_processes, max_user_processes, idle_wait, active_wait)
		
	# Daemon Control
	if len(sys.argv) == 2:
		if 'start' == sys.argv[1]:
			print "titan-daemon started!"
			daemon.run()
			#daemon.start()
		elif 'stop' == sys.argv[1]:
			print "titan-daemon stopped!"
			daemon.stop()
		elif 'restart' == sys.argv[1]:
			print "titan-daemon restarted!"
			daemon.restart()
		else:
			print "Unknown command"
			sys.exit(2)
		sys.exit(0)
	else:
		print "usage: %s start|stop|restart" % sys.argv[0]
		sys.exit(2)
		
	
	
