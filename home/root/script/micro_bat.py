#!/usr/bin/env python
# -*- coding: utf-8 -*-
# Date: Apr. 29th , 2020
# Author: Mickey Zhu

#=============================================================================
# Import packages
#=============================================================================

import os
import socket
import time
from datetime import datetime
import json

#=============================================================================
# Const Definitions
#=============================================================================

CURRENT_STATUS_DB_FILE      = "/tmp/current_status.sqlite"
CONFIG_DB_FILE              = "/tmp/config.sqlite"
SLEEP_TIME                  = 1.5
AMU_IP                      = '10.10.10.100' # Using 'localhost' if runing on AMU board
ACU_CLI_PORT                = 30310

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
  "Updating Software",
  "Searching (compass)"
]

ACU_SUBSTATE_TABLE = [
  "Initial",
  "Set elevation",
  "Set azimuth (compass)",
  "Set azimuth (gyro)",
  "Set skew",
  "Moving elevation",
  "Moving azimuth (compass)",
  "Moving azimuth (gyro)",
  "Moving skew",
  "Measuring",
  "Calculating next movement",
  "Sweeping azimuth (compass)",
  "Sweeping azimuth (gyro)",
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
  "EL move to start",
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
# Class Definitions
#=============================================================================

#=============================================================================
# Global Variables
#=============================================================================

HI_Config = None

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

#=============================================================================

# Get status from current_status.sqlite
# in : table - table name; module - ACU or other
# out: dict parsed from json string
def get_status_from_db(table="status", module="ACU"):

    json_sql_str    = "select json_status from %s where module = '%s' limit 1" % (table, module)
    utc_ms_sql_str  = "select utc_time_ms from %s where module = '%s' limit 1" % (table, module)

    # Get the json string
    json_str = os.popen('sqlite3 %s "%s"' % (CURRENT_STATUS_DB_FILE, json_sql_str)).read()
    #print(json_str)
    status = json.loads(json_str)

    # Get the utc time in ms
    utc_ms_str = os.popen('sqlite3 %s "%s"' % (CURRENT_STATUS_DB_FILE, utc_ms_sql_str)).read()
    #print(utc_ms_str)
    local_time = time.localtime(int(utc_ms_str)/1000)

    return status, local_time

#=============================================================================

# Get config from config.sqlite
# in : table - table name; module - ACU/HI or others
# out: dict parsed from json string
def get_config_from_db(module="ACU"):

    json_sql_str    = "select json_config from %s where active = '1' and group_name = '%s' limit 1" % ("config", module)
    utc_ms_sql_str  = "select utc_time_ms from %s where active = '1' and group_name = '%s' limit 1" % ("config", module)

    # Get the json string
    json_str = os.popen('sqlite3 %s "%s"' % (CONFIG_DB_FILE, json_sql_str)).read()
    #print(json_str)
    config = json.loads(json_str)

#    # Get the utc time in ms
#    utc_ms_str = os.popen('sqlite3 %s "%s"' % (CURRENT_STATUS_DB_FILE, utc_ms_sql_str)).read()
#    #print(utc_ms_str)
#    local_time = time.localtime(int(utc_ms_str)/1000)

    return config

#=============================================================================

# Display ACU current status
# in : s - socket handle;
# out: None
def display_acu_status(s):

    global HI_Config

    # status, local_time = get_status_from_db("status", "ACU")
    status = get_status_from_cli(s, 'rsc_acu_all\n')
    local_time = time.localtime(time.time())

    os.system('reset')
    print("==============================================")
    print("%s    %s" % (time.strftime("%Y-%m-%d %H:%M:%S", local_time), "Update every %d seconds" % (SLEEP_TIME+0.5)))
    print("##############################################")

    print("\n--HI Configs----------------------------------")
    amu_model       = HI_Config["amu_model"]
    antenna_model   = HI_Config["antenna_model"]
    antenna_serial  = HI_Config["antenna_serial"]
    box_sensor_pcb  = HI_Config["box_sensor_pcb"]
    buc_model       = HI_Config["buc_model"]
    print("%s-%s-%s-%s-%s" % (antenna_serial, antenna_model, amu_model, box_sensor_pcb, buc_model))

    print("\n--ACU Status----------------------------------")
    m, s = divmod(status["acu_uptime_s"], 60)
    h, m = divmod(m, 60)
    print("Up-%d:%02d:%02d  GPS-%s  OpenAMIP-%s" % (h, m, s, 'Lock' if status["gps_locked"] == True else 'Unlock', 'Link' if status["openamip_state_id"] == 1 else 'Unlink'))
    print("ACU State    - %s" % ACU_STATE_TABLE[(status["current_state"])] )
    print("ACU SubState - %s" % ACU_SUBSTATE_TABLE[(status["current_substate"])] )
    print("ANT State    - %s" % ANT_STATE_TABLE[(status["ant_current_state"])] )
    print("ANT SubState - %s" % ANT_SUBSTATE_TABLE[(status["ant_current_substate"])] )

    print("\n--Demod Staus---------------------------------")
    print("CON: %.2fdB  CPN/AGC: %.3fV" % (status["demod_con_db_avg"], status["cpn_db_avg"]))

    print("\n--ANT Status----------------------------------")
    print("\tCurrent\t\tGyro_offset")
    print("ELN\t%.2f" % status["el_nominal_degs"])
    print("AZN\t%.2f" % status["az_nominal_degs"])
    print("AZC\t%.2f" % status["az_compass_degs"])
    print("AZR\t%.2f\t\t%.2f" % (status["az_current_position_degs"], status["az_gyro_centre_offset_dps"]))
    print("EL\t%.2f\t\t%.2f" % (status["el_current_position_degs"], status["el_gyro_centre_offset_dps"]))
    print("RL\t%.2f\t\t%.2f" % (status["rl_current_position_degs"], status["rl_gyro_centre_offset_dps"]))
    print("SK\t%.2f" % status["sk_current_position_degs"])

    print("\n--MF Status-----------------------------------")
    print("ID:%2d  RF_Frequence:%d  Symbolrate:%.0f" % (status["mf_active_carrier_id"], status["rx_rf_freq_khz"], status["rx_symbol_rate_ksps"]))
    print("Polar:%s  Longitude:%3.1f  Local_Freq:%d" % (status["rx_polarisation"], status["sat_longitude_degs"], status["rx_lo_freq_khz"]))

#=============================================================================
# Main code start
#=============================================================================

if __name__=='__main__':

    # Get the HI config from database
    HI_Config = get_config_from_db("HI")

    #=============================================================================

    # Create socket:
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    # Socket connect:
    sock.connect((AMU_IP, ACU_CLI_PORT))

    while True:
        try:
            display_acu_status(sock)
            time.sleep(SLEEP_TIME)
        except KeyboardInterrupt:
            sock.close()
            print("BYE-BYE")
            # sys.exit(0)

    sock.close()

