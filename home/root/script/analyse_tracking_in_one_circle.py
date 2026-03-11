#!/usr/bin/env python 
# -*- coding: utf-8 -*- 
# Date: Jul. 30th , 2020
# Author: Mickey Zhu

#=============================================================================
# Import packages
#=============================================================================

import os
import sys
import socket
import time
from datetime import datetime
import json

#=============================================================================
# Definitions
#=============================================================================

# DB_FILE         = "/tmp/current_status.sqlite"
# SLEEP_TIME      = 2.5
# AMU_IP          = '192.168.0.2' # Using 'localhost' if runing on AMU board
# ACU_CLI_PORT    = 30310

LINE_STATE_INITIAL              = 0
LINE_ACU_STARTING               = 1
LINE_ENTER_CHECK_RX             = 2
LINE_ENTER_FAST_SEARCH          = 3
LINE_FAST_SEARCH_START          = 4
LINE_MOVE_OFFSET                = 5 # 用不到吧
LINE_ENTER_TRACKING             = 6
LINE_TRACKING_STABLE            = 7

LINE_STATE_NUMS                 = 8

MAX_AZ_MOVE_OFFSET_100_COUNTS   = 3 # If we find times of 'az move offset 100' more than this count, we consider it as long time to search
 
#=============================================================================
# Static Variables
#=============================================================================

#ACU_STATE_TABLE =

#=============================================================================
# Global Variables
#=============================================================================

#=============================================================================
# Function Definitions
#=============================================================================

# Get the json string from CLI, parse to dict
# in : s - socket handle; cmd - CLI command
# out: dict parsed from json string
def get_status_from_cli(s, cmd):

    s.send(cmd.encode('utf-8'))

    time.sleep(0.5)
    # receive 20 KB:
    d = s.recv(19999)
    data = d.decode('utf-8').replace('250 OK.','')
    status = json.loads(data)

    # print(status)
    return status

# Get status from current_status.sqlite
# in : table - table name; module - ACU or other
# out: dict parsed from json string
def get_status_from_db(table="status", module="ACU"):

    json_sql_str    = "select json_status from %s where module = '%s' limit 1" % (table, module)
    utc_ms_sql_str  = "select utc_time_ms from %s where module = '%s' limit 1" % (table, module)

    # Get the json string
    json_str = os.popen('sqlite3 %s "%s"' % (DB_FILE, json_sql_str)).read()
    #print(json_str)
    status = json.loads(json_str)

    # Get the utc time in ms
    utc_ms_str = os.popen('sqlite3 %s "%s"' % (DB_FILE, utc_ms_sql_str)).read()
    #print(utc_ms_str)
    local_time = time.localtime(int(utc_ms_str)/1000)

    return status, local_time

# 得到 substring 在 string 中, 第 times 出现的位置
# in : 
# out: index of the substring
def find_sub_str(string, substring, times):
    current = 0
    for i in range(1,times+1):
        current = string.find(substring,current) + len(substring)
        if current == 0 :  return -1
 
    return current - len(substring)
    
# 得到当前行的时间字符串
# in : 当前行 - line_str
# out: 时间字符串 - time_str
def get_line_time_str(line_str):
    tmp_index = find_sub_str(line, ",", 3)
    time_str = line[(tmp_index+1):(tmp_index+9)]
 
    return time_str
 
#=============================================================================
# Main code start
#=============================================================================

if __name__=='__main__':

    # 初始化变量
    acu_starting_times_count            = 0                     # ACU重启的次数
    fast_search_start_az_degree         = 0                     # AZ 快速找星的起始角度
    az_move_offset_100_count            = 0                     # 单次找星，AZ移动100度的次数
    not_tracking_in_first_time_count    = 0                     # 没有一圈锁定的次数
    enter_tracking_times_count          = 0                     # 此文件中，进入锁星状态的次数
    tracking_stable_times_count         = 0                     # 此文件中，Tracking stable的次数
    line_state                          = LINE_STATE_INITIAL    # log文件当前行的的状态

    # 分析输入的文件名, 打开文件
    if len(sys.argv) != 2:
        print("Usage: python analyse_tracking_first_time.py [LOG FIEL NAME]")
        print("Analyse the acu log file and judge wether tracking in one circle")
        sys.exit()
    
    # Open file
    file_name = sys.argv[1]
    fd = open(file_name, mode='r')
    
    # 逐行分析log文件，找到开始寻星和锁定的地方，分析是否是一次锁定，写入csv文件
    # cat acu.log | grep -E 'move offset AZ|ELCP|Tracking stable'
    line = fd.readline()            # 调用文件的 readline()方法   
    while line:   
        # print line,                 # 在 Python 2中，后面跟 ',' 将忽略换行符   
        # print(line, end = '')       # 在 Python 3中使用 
        
        # 统计重启的次数
        if line.find("acu starting") != -1:

            # 没有锁定直接被重启
            if line_state != LINE_ENTER_TRACKING:
                time_str = get_line_time_str(line)
                print("ACU re-starting without Tracking stable last time，time-%s" % time_str)
                
            # 设置当前行的状态
            line_state = LINE_ACU_STARTING

            # 重启次数+1
            acu_starting_times_count = acu_starting_times_count + 1
        
        # 查找Check RX状态，这是个一定会有的状态，在此处尝试设置统计
        # if line.find("Check if rx already OK") != -1:

            # # 设置当前行的状态
            # line_state = LINE_ENTER_CHECK_RX
        
        # 查找开始寻星的一句
        if line.find("ACU entered searching state") != -1:

            # 设置当前行的状态
            line_state = LINE_ENTER_FAST_SEARCH
            
            # 复位统计az move 100的次数
            az_move_offset_100_count = 0
        
        # 查找开始寻星后，Init azimuth sweep的第一次初始化,此处有初始AZ角
        if line.find("Init azimuth sweep") != -1 and line_state == LINE_ENTER_FAST_SEARCH:
            
            # 保存开始fast search的时间
            fast_search_start_time_str = get_line_time_str(line)
            
            # 保存fast search开始时的AZ角度
            tmp1_index = find_sub_str(line, "AZGCP=", 1)
            tmp2_index = find_sub_str(line, "ELCP=", 1)
            fast_search_start_az_degree = float(line[(tmp1_index+6):tmp2_index])
            
            # 设置当前行的状态
            line_state = LINE_FAST_SEARCH_START
            
        # 统计az move 100的次数
        if line.find("offset AZ (100.00)") != -1 or line.find("offset AZ (-100.00)") != -1:
        
            if line_state == LINE_FAST_SEARCH_START:
            
                az_move_offset_100_count = az_move_offset_100_count + 1
            
        #查找进入锁星状态
        if line.find("ACU entered tracking state") != -1 and line_state != LINE_ENTER_TRACKING:
        
            # 保存进入锁星状态的时间
            enter_tracking_time_str = get_line_time_str(line)
            
            # 判断是否是第一圈锁定卫星
            if az_move_offset_100_count > MAX_AZ_MOVE_OFFSET_100_COUNTS:
                print("Didn't lock in one circle, start searching-%s, found signal-%s, az move offset(100) %d 次, AZ init angle %f°" % (fast_search_start_time_str, enter_tracking_time_str,az_move_offset_100_count, fast_search_start_az_degree)) 
                not_tracking_in_first_time_count = not_tracking_in_first_time_count + 1
                
            # 锁星次数+1
            enter_tracking_times_count = enter_tracking_times_count + 1
            
            # 设置当前行的状态
            line_state = LINE_ENTER_TRACKING
            
            # 复位统计az move 100的次数
            az_move_offset_100_count = 0
            
        # 查找锁星稳定状态
        # if line.find("Tracking stable") != -1 and line_state != LINE_TRACKING_STABLE:
        
            # # 保存开始Tracking stable的时间
            # tracking_time_str = get_line_time_str(line)
            # # print(tracking_time_str)
            
            # # 判断是否是第一圈锁定卫星
            # if az_move_offset_100_count > MAX_AZ_MOVE_OFFSET_100_COUNTS:
                # print("Didn't lock in one circle, start searching-%s, found signal-%s, az move offset(100) %d 次, AZ init angle %f°" % (fast_search_start_time_str, tracking_time_str,az_move_offset_100_count, fast_search_start_az_degree)) 
                # not_tracking_in_first_time_count = not_tracking_in_first_time_count + 1
                
            # # 锁星次数+1
            # tracking_stable_times_count = tracking_stable_times_count + 1
            
            # # 设置当前行的状态
            # line_state = LINE_TRACKING_STABLE
            
            # # 复位统计az move 100的次数
            # az_move_offset_100_count = 0
        
        # 读取下一行
        line = fd.readline() 
    
    # 关闭文件
    fd.close()
    
    print("---------------------------------------------------------------------------------------------------")
    print("此文件中，ACU 重启 %d 次，进入锁星状态共 %d 次，锁星时间长 %d 次" % (acu_starting_times_count, enter_tracking_times_count, not_tracking_in_first_time_count))
    print("In this log file，ACU starting %d times，Tracking %d times，long time in searching %d times" % (acu_starting_times_count, enter_tracking_times_count, not_tracking_in_first_time_count))


