#!/usr/bin/env python
# coding=utf-8
# Date: Nov, 4 2020
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
VERSION                 = "1.0.0"
TASKID_FILE             = "/home/bird/upgrade_task_id.json"

HTTP_TIMEOUT_SECOND     = 10

DEFAULT_JSON            = '{ "task_id": 0 }'
SAVE_FILE_PATH          = '/tmp/'

#http server
SERVER_IP               = "bmp.chinastar.net.cn"
SERVER_PORT             = 6606

ACTIVE_SCRIPT           = "active.py"
PRE_ACTIVE_SCRIPT       = "pre_active.py"
POST_ACTIVE_SCRIPT      = "post_active.py"

TEMP_DIR                = "/tmp/ota"
#=============================================================================
# Static Variables
#=============================================================================

#=============================================================================
# Global Variables
#=============================================================================
ANTENNA_SERIAL          = ''

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
# Task id Json File class,
# content: open file, get the utcus's value; and update the vaule to file
#=============================================================================
class TaskId:
    task_id = 0
    json_data = ""
    fo = ""
    def __write_default_json(self):
        self.fo = open(TASKID_FILE, "w")
        print("task_id using default value=0")
        self.task_id = 0
        self.fo.write(DEFAULT_JSON)
        self.json_data = json.loads(DEFAULT_JSON)

    def __init__(self):
        #open json file, get task_id
        try:
            self.fo = open(TASKID_FILE, "r+")
        except IOError:
            self.__write_default_json()
        else:
            file_content = self.fo.read()
            print("file_content:%s" % file_content)
            print_log("file_content:%s" % file_content)
            #file is bad json file
            try:
                self.json_data = json.loads(file_content)
            except:
                self.__write_default_json()
            else:
                self.task_id = self.json_data["task_id"]
                print("task_id:%d" % self.task_id)

    def refresh_id(self, id):
        self.task_id = id
        self.json_data["task_id"] = id
        print("json id:%d" % self.json_data["task_id"])
        #close old file ,delete old data, write new json data into file
        self.fo.close()
        self.fo = open(TASKID_FILE, "w")
        self.fo.write("%s" % json.dumps(self.json_data))
        self.fo.close()
        print_log("write taskid file, json id:%d" % id)

    def get_id(self):
        print ("get local json id=%d" % self.task_id)
        return self.task_id


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
def do_upgrade(task_id, ufile):
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
                print_log("OTAUpgrade:%sFailed" % (ufile))
        else:
            print_log("OTAUpgrade:%s Call Pre-active File, Do Nothing" % (ufile))

    else:
        print "call unzip ng"
        print_log("OTAUpgrade:%s Unzip Failed" % (ufile))
        record_event("OTAUpgrade:%s Unzip Failed" % (ufile))

#=============================================================================
# function: download file by the path
#=============================================================================
def download_file(task_id, json_md5val, url):
    global taskId

    file_name = url.split('/')[-1]  
    save_file = SAVE_FILE_PATH + file_name
    try:
        f = urllib2.urlopen(url)
    except Exception, e:
        print e
        print_log("OTAUpgrade:Urlopen Error utcUs=%d %s Download Failed" % (task_id, file_name))
        record_event("OTAUpgrade:Urlopen Error utcUs=%d %s Download Failed" % (task_id, file_name))
        return -1
    data = f.read() 
    with open(save_file, "wb") as code:   
        code.write(data)
    f.close
    #md5sum file
    md5sum_cmd = 'md5sum ' + save_file
    res = os.popen(md5sum_cmd)
    temp = res.readlines()
    print("md5sum val:%s", temp)
    md5sum_val = temp[0].split(' ')[0]
    if md5sum_val == json_md5val:
        print_log("OTAUpgrade:Check Md5sum OK Todo install package")
        record_event("OTAUpgrade:Check Md5sum OK Todo install package")
        #call shell script
        do_upgrade(task_id, save_file)
        #update task id to file
        taskId.refresh_id(task_id)
    else:
        print_log("OTAupgrade checkmd5sum package md5sum=%s json md5sum=%s" % (md5sum_val, json_md5val))
        record_event("OTAUpgrade Check md5sum failed")
        return -1
    return 0

#=============================================================================
# function:parse json; download file
# example josn:
#{
#  "code": 1, //0:失败  1：成功
#  "data": {
#    "antennaSerial": "AW000373N0001",
#    "downFileUrl": "http://192.168.33.112:6604/file/software/VS80A_1.0.5_sw_20200205175254.bas",
#    "md5sum":"123456789abcdef",
#    "jobId": "AW000373N0001SW20200205175525",
#    "utcUs": 1580896525000
#  },
#  "message": "操作成功！"
#}
#=============================================================================
def parse_json(stringData):
    print 'in Function=parse_json'
    try:
        json_data = json.loads(stringData)
    except Exception,e:
        print e
        return

    code = json_data['code']
    if code == 0:  #failed, return
        message = json_data['message']
        print_log("OtaUpgrade Message=%s" % message)
        return

    task_id = json_data['data']['utcUs']
    print_log("recv task id=%d" % task_id)
    
    if task_id > taskId.get_id():
        json_md5val = json_data['data']['md5sum']
        package_path = json_data['data']['downFileUrl']
        job_id = json_data['data']['jobId']
        record_event(job_id)
        print_log("parse Json data get md5sum=%s path=%s" %(json_md5val, package_path))
        download_file(task_id, json_md5val, package_path)

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
            print_log("antenna amu serial is empty")
            ANTENNA_SERIAL = ""
            #os._exit()
        else:
            ANTENNA_SERIAL = AMU_SERIAL
            print_log("use amu serial=%s" % ANTENNA_SERIAL)
    else:
        print_log("antenna_serial=%s" % ANTENNA_SERIAL)


#=============================================================================
# get_task_id
#=============================================================================
def get_task_id():
    global ANTENNA_SERIAL
    global SERVER_IP

    get_antenna_serial()
    api = "/amu/task?antennaSerial="+ANTENNA_SERIAL+"&device=amu"
    
    print("TASK api=%s" % api)
    print("server ip=%s" % SERVER_IP)
    print("server port=%d" % SERVER_PORT)
    try:
        conn = httplib.HTTPConnection(SERVER_IP, SERVER_PORT, timeout=HTTP_TIMEOUT_SECOND)
    except Exception, e:
        print_log("httplib:" + str(e))
        return

    try:
        conn.request("GET", api)
        res = conn.getresponse()
        if res.status == 200:
            jsondata = res.read()
            print jsondata
            parse_json(jsondata)
        else:
            print_log("get taskid failed http_code=%d" % res.status)
    except Exception, e:
        print_log("request:" + str(e))
        return

    
#=============================================================================
# Main code start
#=============================================================================
if __name__=='__main__':
    #instantiation class TaskId, get the task_id value
    taskId = TaskId()
    get_task_id()

