#!/usr/bin/env python
# coding=utf-8
# Date: Nov, 27 2020
# Author: zhang bingan

#=============================================================================
# Import packages
#=============================================================================
import os
import sys


#=============================================================================
# Definitions
#=============================================================================
VERSION                 = "1.0.0"
ACTIVE_SCRIPT           = "active.py"
PRE_ACTIVE_SCRIPT       = "pre_active.py"
POST_ACTIVE_SCRIPT      = "post_active.py"

TEMP_DIR                = "/tmp/ota"

#=============================================================================
# record log to log file
#=============================================================================
def print_log(log):
    os.system("/usr/bin/write_log -p I \"%s\"" %log)

#=============================================================================
# record event to event database
#=============================================================================
def record_event(event):
    os.system("/usr/bin/bs-amu-logevent -i 301 -s 1 \"%s\"" %event)

#=============================================================================
# function: call active file, to upgrade
#=============================================================================
def call_active_file(temp_dir, active_file):
    active = temp_dir + "/" + active_file
    if os.path.isfile(active):
        os.system("chmod +x " + active)
        if 0 == os.system(active):
            return 0
        else:
            return 1
    else:
        print_log("activefile=%sIsNotFound" % active_file)
        return 1

#=============================================================================
# function: doing upgrade software
#=============================================================================
def do_upgrade(ufile):
    global ACTIVE_SCRIPT
    global PRE_ACTIVE_SCRIPT
    global POST_ACTIVE_SCRIPT
    global TEMP_DIR
    
    os.system("mkdir -p " + TEMP_DIR)
    os.system("rm " + TEMP_DIR + "/* -rf")
    sh_cmd = "/usr/bin/unzip -P $(cat /home/bird/.zkey) -d " + TEMP_DIR + " " +  ufile
    if 0 == os.system(sh_cmd):
        #call pre active script file 
        if 0 == call_active_file(TEMP_DIR, PRE_ACTIVE_SCRIPT):
            #call active file to upgrade
            if 0 == call_active_file(TEMP_DIR, ACTIVE_SCRIPT):
                #success to call post active file
                if 0 == call_active_file(TEMP_DIR, POST_ACTIVE_SCRIPT):
                    print_log("OTAupgrade:%s UpgradeSuccess" % (ufile)) 
                    record_event("OTAupgrade:%s UpgradeSuccess" % (ufile))
                else:
                    print_log("OTAUpgrade:%s Do Post Active Failed" % (ufile))
            else:
                print_log("OTAUpgrade:%s Failed" % (ufile))
        else:
            print_log("OTAUpgrade:%s Call Pre-active File, Do Nothing" % (ufile))

    else:
        print_log("OTA Upgrade:%s Unzip Failed" % (ufile))
        record_event("OTA Upgrade:%s Unzip Failed" % (ufile))

#=============================================================================
# Main code start
#=============================================================================
if __name__=='__main__':
    if len(sys.argv) != 2:
        print "Usage: install_bop.py filename.bop"
        os._exit(1)
    ufile = sys.argv[1]
    do_upgrade(ufile)


