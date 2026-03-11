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
import io
#=============================================================================
# Definitions
#=============================================================================
VERSION                                   = "1.0.2"
TASKID_FILE                               = "/home/bird/task_id.json"

HTTP_TIMEOUT_SECOND                       = 30
GET_TASKID_TASK_TIME                      = 5

#api list
TASK_API                                  = "/api/amu/task"
UPGRADE_STATUS_API                        = "/api/amu/upgradeStatus"

DEFAULT_JSON                              = '{ "task_id": 0 }'
#SAVE_FILE_PATH                            = '/tmp/'
SAVE_FILE_PATH                            = '/home/bird/data/upgrade/ota/box/packages/'
HISTORY_OTA_FILE                          = '/home/bird/data/upgrade/ota/box/ota_history.json'

#http server
SERVER_IP                                 = "192.168.0.3"
SERVER_PORT                               = 8001

ACTIVE_SCRIPT                             = "active.py"
PRE_ACTIVE_SCRIPT                         = "pre_active.py"
POST_ACTIVE_SCRIPT                        = "post_active.py"

TEMP_DIR                                  = "/tmp/ota"
IPADDR_FILE                               = "/tmp/udp_server_ip"

E_UNKONWN                            = 0
E_PRE_NO_INFO                        = 1
E_PRE_AMUS_NOT_SUPPORT               = 2
E_PRE_DSB_NOT_SUPPORT                = 3
E_PRE_ANTENNA_NOT_SUPPORT            = 4
E_PRE_HARDWARE_VERSION_NOT_SUPPORT   = 5
E_PRE_OTA_ENABLED_FALSE              = 6
E_DOWN_URL_FAIL                      = 7
E_DOWN_CHK_MD5_FAIL                  = 8
E_DOWN_NO_FILE                       = 9
E_INSTALL_UNZIP                      = 10
E_INSTALL_POST_ERROR                 = 11
E_INSTALL_CONTINUE                   = 12
E_INSTALL_ERROR                      = 13

CHK_UPGRADE_FAIL_REASON = [
  "unknown",
  "no info",
  "incompatible amus",
  "incompatible dsb",
  "incompatible antenna",
  "incompatible hardware",
  "ota unable ",
  "get url fail",
  "md5 chk fail",
  "no bop file",
  "unzip bop fail",
  "post_active.py fail",
  "install continue",
  "install active error"
]

#=============================================================================
# Static Variables
#=============================================================================

#=============================================================================
# Global Variables
#=============================================================================

History_Upgrade_List_Default='''{
  "upgrade_history": [
    {
      "local_task_id": 0,
      "romote_task_id": 0,
      "romote_md5sum": "",
      "pr_path": "",
      "pre_pr_version":"",
      "install_retry_time": 0,
      "install_retry_max": 10,
      "upgrade_time": "",
      "fail_reason":[]
    }
  ]
}'''

Current_Upgrade_Info_Default='''{
      "local_task_id": 0,
      "romote_task_id": 0,
      "romote_md5sum": "12345",
      "pr_path": "package_name",
      "pre_pr_version":"",
      "install_retry_time": 0,
      "install_retry_max": 100,
      "upgrade_time": "",
      "fail_reason":[]
    }'''

Romete_Json_Default='''{
    "task_id":0,
    "data":
    {
        "md5sum":"0",
        "path":""
    }
}'''

Romete_Json_Test='''{
    "task_id":3244,
    "data":
    {
        "md5sum":"e0c93617a981dcdaa681f19b29ffacec",
        "path":"url/path/bs-ota-2.6.1bas.bop"
    }
}'''


#=============================================================================
# record log to log file
#=============================================================================
def print_log(log):
    os.system("/usr/bin/write_log -p I \"CHK UPGRADE:%s\"" %log)

#=============================================================================
# Task id Json File class
# content: open file, get the task_id's value; and update the vaule to file
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
        ret = os.system(active)
        ret = ret>>8
        if 0 == ret:
            print_log("active file=%s is return 0" % active_file)
            return 0
        else:
            print_log("active file=%s is return %d " % (active_file,ret))
            return ret
    else:
        print_log("active file=%s is not found" % active_file)
        return 1

#=============================================================================
# get pr version from current status sqlite
#=============================================================================
def get_current_pr_version():

    sqlite_cmd = '''sqlite3 /tmp/current_status.sqlite "select json_extract((select json_status from status where module = 'PMT' limit 1), '$.sw_version')"'''
    res = os.popen(sqlite_cmd)
    temp = res.readlines()
    str_list = temp[0].splitlines(False)
    pr_version = str_list[0]
    
    return pr_version   
#=============================================================================
# get pr version from ota extract file
#=============================================================================
def get_pr_form_ota_package(temp_dir,bop_file):

    pr_file_path = temp_dir + "/pkgs/"
     
    file_name_list =  os.listdir(pr_file_path)
    
    pr_file_name= os.path.basename(bop_file)
    pr_version=""
    
    if len(file_name_list)>0:
        pr_file_name =  file_name_list[0]
        #get 2.6.1 from bs-amus5_2.6.1.bas
        start_pos=pr_file_name.find("_")
        pr_version=pr_file_name[start_pos+1:(len(pr_file_name)-len(".bas"))]
        print_log("get pr version:%s form %s "%(pr_version,pr_file_name) )
    else:
        #get 2.6.1 from bs-ota-2.6.1bas.bop
        start_pos=pr_file_name.find("-", start=len("bs-ota"), end=len(pr_file_name))
        pr_version=pr_file_name[start_pos+1:(len(pr_file_name)-len("bas.bop"))]
        print_log("get pr version:%s form %s "%(pr_version,pr_file_name) )
     
    return pr_version 
        
#=============================================================================
# function: doing upgrade software
#=============================================================================
def do_upgrade(pre_pr_version,task_id, ufile):
    global ACTIVE_SCRIPT
    global PRE_ACTIVE_SCRIPT
    global POST_ACTIVE_SCRIPT
    global TEMP_DIR
    
    os.system("mkdir -p " + TEMP_DIR)
    os.system("rm " + TEMP_DIR + "/* -rf")
    sh_cmd = "/usr/bin/unzip -P $(cat /home/bird/.zkey) -d " + TEMP_DIR + " " +  ufile
    
    if 0 != os.system(sh_cmd):
        print "call unzip ng"
        post_upgrade_status(task_id, ufile, "unzip failed")
        return E_INSTALL_UNZIP
    
    current_pr=pre_pr_version
    remote_pr=get_pr_form_ota_package(TEMP_DIR,ufile)
    
    #current pr version is the target version already,donot need install again,return success
    if current_pr==remote_pr and len(current_pr)>0 :
        #call post active, 
        print "call post active.py"
        if 0 == call_active_file(TEMP_DIR, POST_ACTIVE_SCRIPT):
             print "call post active.py success"
             post_upgrade_status(task_id, ufile, "success")
             return 0
        else:
             print "call post active.py faile"
             post_upgrade_status(task_id, ufile, "do post active failed")
             return E_INSTALL_POST_ERROR    	       
    
    #current pr version is not the target version,do install 
    print_log("upgrade start ..... ")
    post_upgrade_status(task_id, file_name, "upgrade start")
    
    pre_active_ret = call_active_file(TEMP_DIR, PRE_ACTIVE_SCRIPT)
    #call pre active script file 
    if 0 == pre_active_ret:
        #call active file to upgrade
        if 0 == call_active_file(TEMP_DIR, ACTIVE_SCRIPT):
            #success to call active.py,do nothing, next time enter do_upgrade return success
            print_log("call active file success ,install and upgrade success,exit,do post success to remote next time" )
            return E_INSTALL_CONTINUE
        else:
            post_upgrade_status(task_id, ufile, "failed")
            return E_INSTALL_ERROR
    else:
        if pre_active_ret>=0 and pre_active_ret <=6:
            post_upgrade_status(task_id, ufile, "call pre-active fail , do nothing ,reason:"+CHK_UPGRADE_FAIL_REASON[pre_active_ret])
        else:
            post_upgrade_status(task_id, ufile, "call pre-active fail , do nothing")
        return pre_active_ret

#=============================================================================
# function: remove download file
#=============================================================================
def remove_download_file(file_path):

   #for i in os.listdir(file_path) :#  os.listdir(file_path)#返回一个列表，里面是当前目录下面的所有东西的相对路径
   #   file_data = file_path + "\\" + i  #当前文件夹的下面的所有东西的绝对路径
   #   if os.path.isfile(file_data) == True: #os.path.isfile判断是否为文件,如果是文件,就删除.如果是文件夹.递归给del_file.
   #       try:
   #           os.remove(file_data)
   #       except BaseException as e:
   #           print_log(e)
   #   else:
   #       clear_download_dir(file_data)
      
      
    #try:
    #    os.remove(file_path)
    #except BaseException as e:
    #   print_log(e)
    #   print e
       
    os.system("rm " + file_path + "* -rf")
    
#=============================================================================
# function: check file exist and md5 ok
#=============================================================================
def check_file_md5(json_md5val,file_path):

    #check if file exist
    if os.access(file_path, os.F_OK):
        print_log ("Given file %s is exist." % (file_path) )
    else:
        print_log ("Given file %s is not exist." % (file_path) )
        return E_DOWN_NO_FILE
    
    #check file is valid ota package
    md5sum_cmd = 'md5sum ' + file_path
    res = os.popen(md5sum_cmd)
    temp = res.readlines()
    print("md5sum val:%s", temp)
    md5sum_val = temp[0].split(' ')[0]
    if md5sum_val == json_md5val:
        print_log("check md5sum OK, %s is a valid file" % (file_path) )
        return 0
    else:
        print_log("check md5sum faile: %s is not a valid file" % (file_path) )
        return E_DOWN_CHK_MD5_FAIL
    
#=============================================================================
# function: download file by the path
#=============================================================================
def download_file(task_id, json_md5val, url):
    global taskId

    file_name = url.split('/')[-1]  
    save_file = SAVE_FILE_PATH + file_name
    
    if 0==check_file_md5(json_md5val,save_file):
        print_log("ota package is alread exist,no need download again")
        return 0
    
    print_log("download_file start download file from %s " % url )
    remove_download_file(SAVE_FILE_PATH)
        
    try:
        f = urllib2.urlopen(url)
    except Exception, e:
        print e
        post_upgrade_status(task_id, file_name, "urlopen error, download failed")
        return E_DOWN_URL_FAIL
    data = f.read() 
    with open(save_file, "wb") as code:   
        code.write(data)
    f.close
    
    return check_file_md5(json_md5val,save_file)
      
      
#    #md5sum file
#    md5sum_cmd = 'md5sum ' + save_file
#    res = os.popen(md5sum_cmd)
#    temp = res.readlines()
#    print("md5sum val:%s", temp)
#    md5sum_val = temp[0].split(' ')[0]
#    if md5sum_val == json_md5val:
#        print_log("check md5sum OK, todo install package")
#        post_upgrade_status(task_id, file_name, "upgrade start")
#        #call shell script
#        if 0 == do_upgrade(task_id, save_file):
#            #update task id to file
#            taskId.refresh_id(task_id)
#    else:
#        print_log("check md5sum: package md5sum=%s json md5sum=%s" % (md5sum_val, json_md5val))
#        post_upgrade_status(task_id, file_name, "Check md5sum failed")
#        return -1
#        
#    return 0

#=============================================================================
# function:parse json; download file
# example josn:
#{
#    "task_id":1234567890,
#    "data":
#    {
#        "md5sum":"对升级包的md5值",
#        "path":"url/path/package_name"
#    }
#}
#=============================================================================
def parse_json(stringData):
      
    print 'in Function=parse_json'

    remote_json_data = json.loads(Romete_Json_Default)
    
    try:
        remote_json_data = json.loads(stringData)
        task_id = remote_json_data['task_id']
    except Exception,e:
        post_upgrade_status(0, "", "Bad json")
        print_log("url data :%s"%(stringData));
        print e
        
    print_log("recv task id=%d" % task_id)
    
    return remote_json_data
#    if task_id == 0:    #do nothing
#        return
#    if task_id > taskId.get_id():
#        json_md5val = json_data['data']['md5sum']
#        package_path = json_data['data']['path']
#        print_log("parse json data, get md5sum=%s path=%s" %(json_md5val, package_path))
#        download_file(task_id, json_md5val, package_path)


#=============================================================================
# thread:get_task_id
#=============================================================================
def get_task_id_thread(threadName, delay):
    print "get task id thread is running\n"
    while 1:
        try:
            conn = httplib.HTTPConnection(SERVER_IP, SERVER_PORT, timeout=HTTP_TIMEOUT_SECOND)
        except Exception, e:
            print e
            time.sleep(5)
            continue

        try:
            conn.request("GET", TASK_API)
            res = conn.getresponse()
            print res.msg
            if res.status == 200:
                jsondata = res.read()
                print jsondata
                parse_json(jsondata)
        except Exception, e:
            print e
            time.sleep(delay)
            continue
        time.sleep(delay)

#=============================================================================
# get_task_id
#=============================================================================
def get_task_id():
    
    romete_json = json.loads(Romete_Json_Default)
    #romete_json = parse_json(Romete_Json_Test)
    
    try:
        conn = httplib.HTTPConnection(SERVER_IP, SERVER_PORT, timeout=HTTP_TIMEOUT_SECOND)
    except Exception, e:
        print_log("get task id:"+str(e))
        return romete_json

    try:
        conn.request("GET", TASK_API)
        res = conn.getresponse()
        if res.status == 200:
            jsondata = res.read()
            print jsondata
            romete_json = parse_json(jsondata)
        else:
            print_log("get task id failed, http code=%d" % res.status)
    except Exception, e:
        print_log("get task id:"+str(e))

    return romete_json

#=============================================================================
# get box ip
#=============================================================================
def get_box_ip():
    global SERVER_IP
    
    if os.access(IPADDR_FILE, os.F_OK)==True:
        try:
            fo = open(IPADDR_FILE, "r")
            SERVER_IP = fo.read(1024)
            fo.close()
        except:
            print "open "+IPADDR_FILE+"faile"
    
    print_log("box ip:%s" % SERVER_IP)

        
#=============================================================================
# post upgrade status
#=============================================================================
def post_upgrade_status(task_id, package_name, status_string):
    status_dict = {"task_id":0, "package_name":"", "status":""}
    status_dict["task_id"] = task_id
    base_name = os.path.basename(package_name)
    status_dict["package_name"] = base_name
    status_dict["status"] = status_string

    headers = {"Content-Type":"application/json"}
    try:
        conn = httplib.HTTPConnection(SERVER_IP, SERVER_PORT, timeout=HTTP_TIMEOUT_SECOND)
    except Exception, e:
        print_log(str(e))
        return

    try:
        conn.request("POST", UPGRADE_STATUS_API, json.dumps(status_dict), headers)
        res = conn.getresponse()
        print res.msg
        if res.status != 200:
            print_log("post upgrade status failed, http code=%d" % res.status)
        conn.close()
    except Exception, e:
        print_log(str(e))
        conn.close()
        return

#=============================================================================
# get box ip
#=============================================================================
def get_creat_history_json(task_id):
    global HISTORY_OTA_FILE


    try:
        fo = io.open(HISTORY_OTA_FILE, "r", encoding='utf-8')
        ota_lists_json = json.load(fo)
    except Exception, e:
        print "get_creat_history_json bad file "+HISTORY_OTA_FILE +" " + str(e)
        ota_lists_json = json.loads(History_Upgrade_List_Default)

            
        
    history_json=json.loads(Current_Upgrade_Info_Default)
    
    #json_data = r.json()
    for upgrade_info in ota_lists_json["upgrade_history"]:
        print_log(" romote_task_id %s " % (upgrade_info["romote_task_id"]))
        if upgrade_info["romote_task_id"] == task_id:
            history_json = upgrade_info
            break;
           
    return history_json

#=============================================================================
# get box ip
#=============================================================================
def save_history_json_to_file(history_json):
    global HISTORY_OTA_FILE
 
    try:
        fo = io.open(HISTORY_OTA_FILE, "r", encoding='utf-8')
        ota_lists_json = json.load(fo)
    except Exception, e:
        print "get_creat_history_json bad file "+HISTORY_OTA_FILE +" " + str(e)
        ota_lists_json = json.loads(History_Upgrade_List_Default)
            
            
    print "history_json"
    print history_json
    
    #print "ota_lists_json"
    #print ota_lists_json
    
    find_json = 0
    
    ota_json_array=ota_lists_json["upgrade_history"]
    for i in range(0,len(ota_json_array)):
        if(ota_json_array[i]["romote_task_id"]==history_json["romote_task_id"]):
            ota_lists_json["upgrade_history"][i]=history_json;
            print ("find json %d"% i )
            find_json=1
            break;
            
    if 0==find_json:
        ota_lists_json["upgrade_history"].append(history_json);
        print ("append one json " )
       
    #print "ota_lists_json"
    #print ota_lists_json
    
    with open(HISTORY_OTA_FILE, "w") as fp:
      json.dump(ota_lists_json, fp, indent=4)
            
#=============================================================================
# Main code start
#=============================================================================
if __name__=='__main__':
   
    get_box_ip()
    #instantiation class TaskId, get the task_id value
    localtaskId = TaskId()
    remote_json_data = get_task_id()
    
    remote_task_id = remote_json_data['task_id']
    #remote no ota task 
    if remote_task_id == 0:                  #do nothing
        print_log ("get remote task id  0,exit")
        sys.exit(0) 
    
    #alread upgrade
    if remote_task_id <= localtaskId.get_id():    #do nothing
        print_log ("remote id %s is equal or smaller than local %s " % (remote_task_id,localtaskId.get_id()))
        sys.exit(0)
    
    print_log ("remote id %s is larger than local %s,start upgrade " % (remote_task_id,localtaskId.get_id()))
    
    #star upgrade
    upgrade_info_json=get_creat_history_json(remote_task_id);
    #set task id to current upgrade info json
    upgrade_info_json["romote_task_id"]=remote_task_id
    upgrade_info_json["local_task_id"]=localtaskId.get_id()
        
    #mkdir -p '/home/bird/ota/packages'
    os.system("mkdir -p " + SAVE_FILE_PATH)
    
    #do start upgrade from box
    json_md5val = remote_json_data['data']['md5sum']
    package_path = remote_json_data['data']['path']
    file_name = package_path.split('/')[-1] 
        
    #set md5 ,file path to current upgrade info json
    upgrade_info_json["romote_md5sum"]=json_md5val
    upgrade_info_json["pr_path"]=package_path
    
    #start down file from url
    downfile_ret=download_file(remote_task_id, json_md5val, package_path)
    

    
    #download file fail,exit with faile
    if downfile_ret != 0:
        print_log("down file fail check md5sum: download package ret =%d json md5sum=%s" % (downfile_ret, json_md5val))
        post_upgrade_status(remote_task_id, file_name, "Check md5sum failed")
        upgrade_info_json["install_retry_time"]+=1
        if downfile_ret>=0 and downfile_ret<=13 :
            upgrade_info_json["fail_reason"].append(CHK_UPGRADE_FAIL_REASON[downfile_ret])
        else:
            upgrade_info_json["fail_reason"].append(CHK_UPGRADE_FAIL_REASON[E_UNKONWN])
        save_history_json_to_file(upgrade_info_json);
        sys.exit(-1)   	
      
    print_log("down load file and check md5sum OK, todo install package")
    
    save_file = SAVE_FILE_PATH + file_name
    pre_pr_version=get_current_pr_version()
        
    upgrade_ret = do_upgrade(pre_pr_version,remote_task_id, save_file)
    
    if 0 == upgrade_ret:
      print_log("upgrade pr package %s task_id %s from %s success " % (package_path,remote_task_id,pre_pr_version) )
      localtaskId.refresh_id(remote_task_id)
      upgrade_info_json["local_task_id"]=remote_task_id
      upgrade_info_json["upgrade_time"]=time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(time.time()))
      save_history_json_to_file(upgrade_info_json)
      remove_download_file(SAVE_FILE_PATH)
      #post_upgrade_status(task_id, ufile, "success")
      sys.exit(0)
    else:
      print_log("upgrade is not finish or not success " )
      #to retry time add one,save to json file
      upgrade_info_json["pre_pr_version"]=pre_pr_version
      upgrade_info_json["install_retry_time"]+=1
      if upgrade_ret>=0 and upgrade_ret<=13 :
          upgrade_info_json["fail_reason"].append(CHK_UPGRADE_FAIL_REASON[upgrade_ret])
      else:
          upgrade_info_json["fail_reason"].append(CHK_UPGRADE_FAIL_REASON[E_UNKONWN])
      save_history_json_to_file(upgrade_info_json);
      sys.exit(-1)
      


