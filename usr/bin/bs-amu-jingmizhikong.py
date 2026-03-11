#!/usr/bin/env python
# coding=utf-8
# Date: Dec. 7, 2021
# Author: zhang bingan

#=============================================================================
# Import packages
#=============================================================================

import os
import sys
import socket
import time
from datetime import datetime
import json
#import cgi, cgitb
import thread
import time
import httplib
import urllib2

#=============================================================================
# Definitions
#=============================================================================
VERSION                 = "1.0.1"
HTTP_TIMEOUT_SECOND     = 30
UPLOAD_STATUS_TASK_TIME = 60
GET_IP_TASK_TIME        = 10


#api list
ANTENNA_STATUS_API      = "/api/amu/antennaStatus"

#http server
SERVER_IP               = "10.235.255.34"
SERVER_PORT             = 7001

#=============================================================================
# Static Variables
#=============================================================================
ACU_STATE_TABLE = [
  "Disabled",
  "No communications",
  "Initialising",
  "Manual",
  "Waiting for necessary conditions",
  "Check if rx already OK",
  "Setting initial skew",
  "Setting initial elevation",
  "Setting initial azimuth",
  "Searching (fast)",
  "Searching (slow)",
  "Cable unwrapping",
  "Sidelobe check",
  "Tracking",
  "ReAcquiring Stage 1",
  "ReAcquiring Stage 2",
  "ReAcquiring Stage 3",
  "Fault",
  "Switching Satellite",
  "Updating Software"
]

ACU_SUBSTATE_TABLE = [
  "Initial",
  "Set elevation",
  "Set azimuth (compass)",
  "Set azimuth (encoder)",
  "Set skew",
  "Moving elevation",
  "Moving azimuth (compass)",
  "Moving azimuth (encoder)",
  "Moving skew",
  "Measuring",
  "Calculating next movement",
  "Sweeping azimuth (compass)",
  "Sweeping azimuth (encoder)",
  "Initialise fast search sweep",
  "Manual mode antenna position check",
  "Waiting",
  "Init: Wait for main running",
  "Init: Get antenna information",
  "Init: Wait for GPS lock",
  "Init: Configure antenna",
  "Init: Wait for antenna operating"
]

ANT_STATE_TABLE = [
  "Wait for config",
  "Test",
  "Initialising",
  "Self Test",
  "Calibrating",
  "Operating",
  "Unwrapping",
  "Idle",
  "Error",
  "Gyro Recal"
]

ANT_SUBSTATE_TABLE = [
  "Initial",
  "Initialising gyros",
  "SB move to limit",
  "SB move to start",
  "SK move to limit",
  "SK move to start",
  "EL move to 30",
  "RL move to 0",
  "AZ move to limit",
  "EL/RL gyro recal",
  "SB move to 90",
  "AZ gyro recal",
  "EL move to start",
  "RL move to start",
  "AZ move to start",
  "AZ compass calibration"
]

#=============================================================================
# Global Variables
#=============================================================================
ANTENNA_STATUS_DICT = {
                        'format_version':1, 
                        'utc':0, 
                        'antenna_serial':0, 
                        'gps_locked':False, 
                        'gps_longitude_degs':0, 
                        'gps_latitude_degs':0, 
                        'gps_speed_knots':0, 
                        'gps_heading_degs':0,
                        'fault_list':[],
                        'mute_state':True,
                        'mf_active_carrier_id':0,
                        'rx_polarisation':'',
                        'rx_rf_freq_khz':0,
                        'rx_lo_freq_khz':0,
                        'sat_longitude_degs':0,
                        'rx_symbol_rate_ksps':0,
                        'openamip_state_id':0,
                        'demod_con_db_avg':0,
                        'tracking_stable':False,
                        'temperature_degc':1.0,
                        'pr_version':'0.0.0',
                        'acu_state':'',
                        'acu_substate':'',
                        'ant_state':'',
                        'ant_substate':'',
                        'az_position_degs':1.0,
                        'el_position_degs':1.0,
                        'rl_position_degs':1.0,
                        'sk_position_degs':1.0,
                        'openamip_ip':'',
                        'openamip_mask':''
                      }
PR_VERSION              = ""
ACU_CURRENT_STATUS_TIME = 0
ANTENNA_SERIAL          = ''
OPENAMIP_IP             = ''
OPENAMIP_MASK           = ''


#=============================================================================
# print log
# Use pmt tools to write log file
#=============================================================================
def print_log(log):
    os.system("/usr/bin/write_log -p I \"%s\"" %log)

#=============================================================================
# get acu current status from database
#=============================================================================
def get_status_from_db():
    sql_cmd = '''sqlite3 /tmp/current_status.sqlite "select json_status from status where module = 'ACU' order by id desc limit 1"'''
    res = os.popen(sql_cmd)
    data = res.readlines()
    try:
        status = json.loads(data[0])
    except:
        status = ""
    return status
    
#=============================================================================
# get openamip ip address
#=============================================================================
def get_openamip_ip():
    global OPENAMIP_IP
    shell_cmd = '''ifconfig openamip@eth1 | grep "inet addr" | awk '{print $2}' | awk -F: '{print $2}' '''
    res = os.popen(shell_cmd)
    temp = res.readlines()
    list_tmp = [x.strip() for x in temp]
    OPENAMIP_IP = ','.join(str(i) for i in list_tmp)
    
#=============================================================================
# get openamip ip address
#=============================================================================
def get_openamip_mask():
    global OPENAMIP_MASK
    shell_cmd = '''ifconfig openamip@eth1 | grep "inet addr" | awk '{print $4}' | awk -F: '{print $2}' '''
    res = os.popen(shell_cmd)
    temp = res.readlines()
    list_tmp = [x.strip() for x in temp]
    OPENAMIP_MASK = ','.join(str(i) for i in list_tmp)


#=============================================================================
# get current status
# in : s - socket handle; 
# out: 0:successful from cli cmd; other is failed
#=============================================================================
def get_current_status():
    global ANTENNA_STATUS_DICT
    global PR_VERSION
    global ANTENNA_SERIAL
    global ACU_CURRENT_STATUS_TIME
    global OPENAMIP_IP
    global OPENAMIP_MASK
    #status = get_status_from_cli(s, 'rsc_acu_all\n')
    status = get_status_from_db()
    if len(status) > 0:
        if (int(status["utc_us"]/1e6) - ACU_CURRENT_STATUS_TIME) < UPLOAD_STATUS_TASK_TIME: 
            return 1
        ACU_CURRENT_STATUS_TIME = int(status["utc_us"]/1e6)
        ANTENNA_STATUS_DICT['utc'] = int(status["utc_us"]/1e6)
        ANTENNA_STATUS_DICT['gps_locked'] = status["gps_locked"]
        ANTENNA_STATUS_DICT['gps_longitude_degs'] = status["gps_longitude_degs"]
        ANTENNA_STATUS_DICT['gps_latitude_degs'] = status["gps_latitude_degs"]
        ANTENNA_STATUS_DICT['gps_speed_knots'] = status["gps_speed_knots"]
        ANTENNA_STATUS_DICT['gps_heading_degs'] = status["gps_heading_degs"]
        ANTENNA_STATUS_DICT['mf_active_carrier_id'] = status["mf_active_carrier_id"]
        ANTENNA_STATUS_DICT['rx_polarisation'] = status["rx_polarisation"]
        ANTENNA_STATUS_DICT['rx_rf_freq_khz'] = status["rx_rf_freq_khz"]
        ANTENNA_STATUS_DICT['rx_lo_freq_khz'] = status["rx_lo_freq_khz"]
        ANTENNA_STATUS_DICT['sat_longitude_degs'] = status["sat_longitude_degs"]
        ANTENNA_STATUS_DICT['rx_symbol_rate_ksps'] = status["rx_symbol_rate_ksps"]
        ANTENNA_STATUS_DICT['openamip_state_id'] = status["openamip_state_id"]
        ANTENNA_STATUS_DICT['demod_con_db_avg'] = status["demod_con_db_avg"]
        ANTENNA_STATUS_DICT['tracking_stable'] = status["tracking_stable"]
        ANTENNA_STATUS_DICT['temperature_degc'] = status["air_temperature_degc"]
        ANTENNA_STATUS_DICT['pr_version'] = PR_VERSION
        ANTENNA_STATUS_DICT['antenna_serial'] = ANTENNA_SERIAL 
        if status["mute_percent"] == 100.0: 
            ANTENNA_STATUS_DICT['mute_state'] = True
        else:
            ANTENNA_STATUS_DICT['mute_state'] = False
        ANTENNA_STATUS_DICT['acu_state'] = ACU_STATE_TABLE[(status["current_state"])] 
        ANTENNA_STATUS_DICT['acu_substate'] =  ACU_SUBSTATE_TABLE[(status["current_substate"])] 
        ANTENNA_STATUS_DICT['ant_state'] = ANT_STATE_TABLE[(status["ant_current_state"])] 
        ANTENNA_STATUS_DICT['ant_substate'] = ANT_SUBSTATE_TABLE[(status["ant_current_substate"])] 
        ANTENNA_STATUS_DICT['az_position_degs'] = status["az_current_position_degs"]
        ANTENNA_STATUS_DICT['el_position_degs'] = status["el_current_position_degs"]
        ANTENNA_STATUS_DICT['rl_position_degs'] = status["rl_current_position_degs"]
        ANTENNA_STATUS_DICT['sk_position_degs'] = status["sk_current_position_degs"]
        ANTENNA_STATUS_DICT['openamip_ip'] = OPENAMIP_IP
        ANTENNA_STATUS_DICT['openamip_mask'] = OPENAMIP_MASK


        print ANTENNA_STATUS_DICT
        return 0
    else:
        return 1

#=============================================================================
# get pr version from current status sqlite
#=============================================================================
def get_pr_version():
    global PR_VERSION
    sqlite_cmd = '''sqlite3 /tmp/current_status.sqlite "select json_extract((select json_status from status where module = 'PMT' limit 1), '$.sw_version')"'''
    res = os.popen(sqlite_cmd)
    temp = res.readlines()
    str_list = temp[0].splitlines(False)
    if len(str_list[0]) > 0:
        PR_VERSION = str_list[0]
        print("sw version:%s" % PR_VERSION)


#=============================================================================
# get fault status from current status database
#=============================================================================
def get_fault():
    global ANTENNA_STATUS_DICT
    sql_cmd = '''sqlite3 /tmp/current_status.sqlite "select item from fault where fault_on=1"'''
    res = os.popen(sql_cmd)
    temp = res.readlines()
    #remove \n from list
    lists = [x.strip() for x in temp]
    ANTENNA_STATUS_DICT['fault_list'] = lists

#=============================================================================
# post antenna status
#=============================================================================
def post_status():
    global ANTENNA_STATUS_DICT
    headers = {"Content-Type":"application/json"}
    try:
        conn = httplib.HTTPConnection(SERVER_IP, SERVER_PORT, timeout=HTTP_TIMEOUT_SECOND)
    except Exception, e:
        print e
        print_log("post status1:" + str(e))
        return

    try:
        conn.request("POST", ANTENNA_STATUS_API, json.dumps(ANTENNA_STATUS_DICT), headers)
        res = conn.getresponse()
        print res.msg
        print_log("post jingmizhikong project status successfull")
        conn.close()
    except Exception, e:
        print e
        print_log("post jingmizhikong project status failed:" + str(e))
        conn.close()
        return


#=============================================================================
# thread:report_antenna_status
#=============================================================================
def report_antenna_status(threadName, delay):
    global ANTENNA_STATUS_DICT
    global ANTENNA_SERIAL
    global PR_VERSION
    global OPENAMIP_IP
    global OPENAMIP_MASK
    print "report antenna status thread is running\n"

    while 1:
        get_fault()
        if len(ANTENNA_SERIAL) == 0:
            get_antenna_serial()
        if len(PR_VERSION) == 0:
            get_pr_version()
        if len(OPENAMIP_IP) == 0:
            get_openamip_ip()
        if len(OPENAMIP_MASK) == 0:
            get_openamip_mask()

        if 0 == get_current_status():
            #send json
            post_status()
        else:
            print_log("poststatus:get current status return 1")

            
        time.sleep(delay)

#=============================================================================
# get antenna serial
#=============================================================================
def get_antenna_serial():
    global ANTENNA_SERIAL
    sqlite_cmd = '''sqlite3 /tmp/config.sqlite "select json_extract((select json_config from config where group_name = 'HI' and active > 0 limit 1), '$.antenna_serial')"'''
    res = os.popen(sqlite_cmd)
    temp = res.readlines()
    str_list = temp[0].splitlines(False)
    ANTENNA_SERIAL = str_list[0]

    # antenna serial is empty, use amu serial
    if len(ANTENNA_SERIAL) == 0:
        sqlite_cmd = '''sqlite3 /tmp/config.sqlite "select json_extract((select json_config from config where group_name = 'HI' and active > 0 limit 1), '$.amu_serial')"'''
        res = os.popen(sqlite_cmd)
        temp = res.readlines()
        str_list = temp[0].splitlines(False)
        AMU_SERIAL = str_list[0]
        if len(AMU_SERIAL) == 0:
            print_log("antenna/amu serial is empty")
            ANTENNA_SERIAL = ""
            #os._exit()
        else:
            ANTENNA_SERIAL = AMU_SERIAL
            print_log("use amu serial=%s" % ANTENNA_SERIAL)
    else:
        print_log("antenna serial=%s" % ANTENNA_SERIAL)


#=============================================================================
# check_single_instance
#=============================================================================
def check_single_instance():
    PID_FILE_PATH = '''/tmp/post_jingmizhikong.pid'''
    find_flag = 0
    print("current pid:%s" %os.getpid())

    if os.path.exists(PID_FILE_PATH):
        pid_file = open(PID_FILE_PATH, "r+")
        pid_string = pid_file.read()

        print("In file:%s" % pid_string)
        print type(pid_string)
        if pid_string == os.getpid():
            sys.exit(-1)
        else:
            res = os.popen("pidof python")
            temp = res.readlines()
            string_temp = temp[0].strip('\n')
            pid_list = string_temp.split(' ')
            print pid_list

            for index in range(len(pid_list)):
                flag = pid_list[index].find(str(pid_string))
                #if pid_list[index].find(str(pid_string)) >= 0:
                if str(pid_string) == str(pid_list[index]):
                    find_flag = 1
                    print("%s Found! Pid index=%d string=%s" % (pid_string, index, pid_list[index]))
                    break
                else:
                    print("%s Not Found! Pid index=%d string=%s" % (pid_string, index, pid_list[index]))
                    print type(pid_list[index])
                    print type(pid_string)
                    print flag

            
            if str(pid_string) in pid_list:
                print_log("Already started PID:%s" %str(pid_string))
                sys.exit(-1)
            else:
                print ("Pid=%s in %s not found" % (str(pid_string), pid_list))
                pid_file.truncate(0)
                pid_file.write(str(os.getpid()))
    else:
        pid_file = open(PID_FILE_PATH, "w+")
        pid_file.write(str(os.getpid()))

    pid_file.flush()
    pid_file.close()

#=============================================================================
# Main code start
#=============================================================================
if __name__=='__main__':
    check_single_instance()
    #Get antenna serail on running first time, if empty, then exit script 
    get_antenna_serial()

    #get sw version from current status database
    get_pr_version()

    # create thread
    try:
        #60s interval report antenna status
        thread.start_new_thread(report_antenna_status, ("report_antenna_status", UPLOAD_STATUS_TASK_TIME, ))
    except:
        print "Error: unable to start thread"
    while 1:
        time.sleep(5)
