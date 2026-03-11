#!/usr/bin/env python
# coding=utf-8
# Date: June. 14, 2022
# Author: shen

import os
import sys
import socket
import time
import json
import threading
import re

#import httplib
#=============================================================================
# Global Variables
#=============================================================================
ACU_CURRENT_STATUS_TIME = 0
UPLOAD_STATUS_TASK_TIME = 2
IP_ADDRESS             = ""
PORT_NUMBER           = 8080
ANTENNA_STATUS_DICT = {
                        'longitude':0,
                        'latitude':0,
                        'timestamp':0,
                      }

#=============================================================================
# print log
# Use pmt tools to write log file
#=============================================================================
def print_log(log):
    os.system("/usr/bin/write_log -p I \" WDSERVER : %s\"" %log)
    
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
#connect 8.8.8.8 get extract ip
# in : ;
# out: "10.235.13.222"
#=============================================================================
def get_extract_ip():
    
    st = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:       
        st.connect(("8.8.8.8", 80))
        IP = st.getsockname()[0]
    except Exception:
        IP = ''
    finally:
        st.close()
    
    print_log ("connect 8.8.8.8 get sockname()[0] is " + str(IP) )	
    return IP

#=============================================================================
# get ip address by ifname 
# in : ifname "wan@eth1"
# out: ip  "10.235.13.222"
#=============================================================================
#def get_local_ip(ifname):  
# 
#    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)  
#    #0x8915 : SIOCGIFADDR
#    inet = fcntl.ioctl(s.fileno(), 0x8915, struct.pack('256s', ifname[:15]))  
#    ret = socket.inet_ntoa(inet[20:24])  
#	
#    return ret  

#=============================================================================
# check ip is valid 
# in : "10.235.13.222"
# return : true or false
#=============================================================================    
def checkIP(strIP):
    try:
        socket.inet_aton(strIP)
        return True
    except socket.error:
        return False
        
#=============================================================================
# get ip address by ifname 
# in : ifname "wan@eth1"
# out: ip  "10.235.13.222"
#=============================================================================
def get_wan_ip():  

    wan_ip_address=""

    wan_if = os.popen("ifconfig wan@eth1")
    wan_if_str = wan_if.readlines()

    for line in wan_if_str:
        if("inet addr:" in line):
            inet_ip_items = line.split()
            wan_ip_address=inet_ip_items[1][5:]

    print ("get_wan_ip %s" % (wan_ip_address))
    print_log ("ifconfig wan@ethi ipaddress is " + str(wan_ip_address) )	
    return wan_ip_address 

#=============================================================================
# get current status
# in : s - socket handle;
# out: 0:successful from cli cmd; other is failed
#=============================================================================
def get_current_status():
    global ANTENNA_STATUS_DICT

    #get data from database
    global ACU_CURRENT_STATUS_TIME
    status = get_status_from_db()
    if len(status) > 0:
        if (int(status["utc_us"]/1e6) - ACU_CURRENT_STATUS_TIME) < UPLOAD_STATUS_TASK_TIME:
            return 1
        ACU_CURRENT_STATUS_TIME            = int(status["utc_us"]/1e6)
        ANTENNA_STATUS_DICT['timestamp']   = int(status["utc_us"]/1e6)
        ANTENNA_STATUS_DICT['longitude']   = status["gps_longitude_degs"]
        ANTENNA_STATUS_DICT['latitude']    = status["gps_latitude_degs"]
        return 0
    else:
        return 1


#=============================================================================
# service_client(new_socket)
#=============================================================================
def service_client(new_socket):
    global ANTENNA_STATUS_DICT
    """return datafor this client. """
    # 1. Receive the request message sent by the browser
    # 1. Receive requests from the browser, which are HTTP requests
    # GET/HTTP / 1.1
    file_index = " "



    request = new_socket.recv(4096).decode("utf-8")
    if request:
        #print("")
        #print("==="*20)
        #print(request)
        request_lines = request.splitlines()
        try:
            ret = re.search(r"[^/]+(/[getgis]*)",request_lines[0])
            if ret:
                file_index = ret.group(1)
                #print("=" * 20)
                #print(file_index)
                #print("\r\n")
        except:
            print("running failed!")
            print_log ("search getgis error " )
        ANTENNA_STATUS_DICT['longitude'] = round(ANTENNA_STATUS_DICT['longitude'],2)
        ANTENNA_STATUS_DICT['latitude'] = round(ANTENNA_STATUS_DICT['latitude'],2)
            
        if file_index == "/getgis":
            # 2. Return HTTP data to the browser
            # 2.1. Prepare the data to send to the browser ----header
            response    = "HTTP/1.1 200 OK\r\n"
            response += "\r\n"
            # Prepare the data to be returned to the client -- body
            json_string = json.dumps(ANTENNA_STATUS_DICT)
            response += json_string
        else:
            response = "HTTP/1.1 404 NOT FOUND\r\n"  #response header is 404
            response += "\r\n"
            response += "<h1>--------NOT FOUND--------<h1>"

        try:# send the response to client
            new_socket.send(response.encode("utf-8"))
        except:
            print("Response failed!")
            print_log ("send Response failed! " )

        else:
            print(response)
            print_log("Response success!")
        # Close the socket
    new_socket.close()
    print("socket closed")



#=============================================================================
# check_single_instance
#=============================================================================
def check_single_instance():
    PID_FILE_PATH = '''/tmp/wangda_server.pid'''
    find_flag = 0
    print("current pid:%s" %os.getpid())

    if os.path.exists(PID_FILE_PATH):
        pid_file = open(PID_FILE_PATH, "r+")
        pid_string = pid_file.read()
        pid_string = pid_string.strip()
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
                print_log ("Already started PID exit -1 " )
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


def start_server(threadName, delay):
    """" To complete the overall control. """
    try:
       #1.creat socket
       tcp_server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
       tcp_server_socket.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1)
       #2.bind
       tcp_server_socket.bind((IP_ADDRESS,PORT_NUMBER))
       #3.listen the socket
       tcp_server_socket.listen(6)
       #tcp_server_socket.setblocking(False)
       print_log ("create ais server success "+str(IP_ADDRESS) + ":"+str(PORT_NUMBER) )
    except Exception, e:
       print_log("create ais server error,exit" + str(e))  
       sys.exit(-1)
    
    while True:
         #if 0==get_current_status():
         # 4.Waiting for a new client link
         
         try:
             new_socket, client_addr = tcp_server_socket.accept()
         except Exception, e:
             print_log("ais access error,exit" + str(e))  
             sys.exit(-1)
             
         if 0!=get_current_status():
             print_log("db get failed,use last gps")
         # 5.Serve this client
         p = threading.Thread(target=service_client, args=(new_socket,))
         p.start()
         #  service_client(new_socket)
         print("pass good!!!")
         #new_socket.close()
         
    # 5.Serve this client        
    tcp_server_socket.close()
    print_log ("get ais server stop tcp_server_socket"  )
 

if __name__ == "__main__":
    check_single_instance()
#   IP_ADDRESS = get_wan_ip()
#   if (False == checkIP(IP_ADDRESS)):
#       IP_ADDRESS = get_extract_ip()
    
#   if (False == checkIP(IP_ADDRESS)):
        # sleep wait for ip ready
#       time.sleep(1)
#       print_log ("can not get right ipaddress exit " + str(IP_ADDRESS) )	
#       sys.exit(-1)
                
    print_log ("ais server Port is " + str(PORT_NUMBER) )
    start_server("wangda_Server", UPLOAD_STATUS_TASK_TIME, )
    print_log ("ais server stop " )
#    try:
#         #5s interval report server status
#        thread.start_new_thread(start_server, ("wangda_Server", UPLOAD_STATUS_TASK_TIME, ))
#    except:
#        print_log("Error: unable to Antenna server start thread\r\n")
#        sys.exit(-1)
    
#    while True:
#        time.sleep(10)
#        pass
   
   





