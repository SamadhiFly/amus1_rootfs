#!/bin/bash -e
######################################################################
# Copyright BIRD/Thiss Technologies
# Date December 20, 2019
#
# PMT main script to watch AMU processes and restart them
# if necessary.
#
# Shall not invoke directly, use startup script /etc/init.d/bs-amu-pmt instead
#
# Parameters: none
######################################################################

SCRIPTNAME=$(basename $0)
DAEMON=${SCRIPTNAME}

SLEEP_INTERVAL=5 # sleep 5 seconds

MAX_LOG_FILE_SIZE=200000 # /tmp/ or /var/log/ file size maximum allowed is 200k per log file

FIRST_TIME_STAMP=0 #record first fault ON'time
FAULT_COUNT=0
MAX_FAILT_COUNT=20
FAULT_TIME_OUT=600
ANT_OK_CNT=0

# ensure log directories present
mkdir -p /home/bird/data/logs/backup
chown -R www-data:root /home/bird/data/logs

# ensure SSF directories present
mkdir -p /home/bird/data/ssf
chown -R www-data:root /home/bird/data/ssf

# ensure GCOH directories present
mkdir -p /home/bird/data/gcoh
chown -R www-data:root /home/bird/data/gcoh

# wrapper to log to both stdout and file
# argument: log message
log()
{
  /usr/bin/write_log -p N $@
}

# EXIT handler, for now just write a log message
on_exit()
{
  log "[$$] $DAEMON is terminating on signal"
  exit 0
}

# print directory size in bytes
# argument: directory to check
dir_size()
{
  DIR_NAME="$1"

  if [ -d "$DIR_NAME" ]; then
    du -D -b -c $DIR_NAME | tail -1 | awk '{printf "%d",$1}'
  else
    echo 0
  fi
}

# print file size in bytes
# argument: path to a file
file_size()
{
  FILE_NAME="$1"
  if [ -f "$FILE_NAME" ]; then
    stat -c %s $FILE_NAME
  else
    echo 0
  fi
}

# check if a file is text (0) or binary (1)
# argument: path to a file
is_text()
{
  FILE_NAME="$1"
  RET_VAL=$(perl -e 'if (-T $ARGV[0]) {print "0" } else {print "1"}' $FILE_NAME)
  return $RET_VAL
}

# exit if is not run as 'root'
check_privilege()
{
  if [ "$EUID" != "0" ]; then
    echo "Must run using root privilege (e.g. sudo)"
    log "Must run using root privilege (e.g. sudo)"
    exit 1
  fi
}

# exit if is already running
check_single_instance()
{
  RUNNING_PIDS=$(pgrep -f $DAEMON)
  if [ "$RUNNING_PIDS" != "$$" ] ; then
    log "Already started, daemon=$DAEMON, running=$RUNNING_PIDS, myself=$$!"
    exit 1
  fi
}

# check if syslog-ng is running, if not, start it
monitor_syslog()
{
  DAEMON_RUNNING=$(pgrep syslog)
  RET_VAL=0
  if [ -z "$DAEMON_RUNNING" ]; then
    if [ -x /etc/init.d/syslog ]; then
      log "Found syslog-ng is not running, starting syslog-ng"
      /etc/init.d/syslog start > /dev/null 2>&1
      log "syslog started"
      RET_VAL=1
    else
      log "Cannot start syslog"
    fi
  fi
  return $RET_VAL
}

#If it have a fault error all the time for 10 minutes, then touch a temp file to disable daemons run.
detect_fault()
{
  MODULE_NAME=$1
  fault_time=$(sqlite3 /tmp/current_status.sqlite "select max(update_time_us) from (select update_time_us,item from fault where fault_on = 1 and (item LIKE '%motor%' or item LIKE '%sensor%'))")
  if [ -z "$fault_time" ];then
    FAULT_COUNT=0
    FIRST_TIME_STAMP=0
    echo "reset fault count...0"
    return
  fi

  fault_s=$(expr $fault_time / 1000000)
  log "fault time: $fault_s $MODULE_NAME Count:$FAULT_COUNT"

  if [ $FIRST_TIME_STAMP == 0 ]; then
    FIRST_TIME_STAMP=$fault_s
    FAULT_COUNT=0
  else
    FAULT_COUNT=$(($FAULT_COUNT+1))
    sub_val=$fault_s-$FIRST_TIME_STAMP
    if [ $(echo "$sub_val < $FAULT_TIME_OUT"|bc) -eq 1 ]; then
      if [ ${FAULT_COUNT} -ge 10 ]; then
        echo "fault count=$FAULT_COUNT  max count=$MAX_FAILT_COUNT"
        if [ ! -f /tmp/${MODULE_NAME}_disabled ]; then
          touch /tmp/${MODULE_NAME}_disabled
          bs-amu-logevent -i 304 -s 1 "{\"utc_s\":\"$fault_s\",\"info\":\"touch ${MODULE_NAME}_disabled tmp file\"}"
        fi
        FIRST_TIME_STAMP=0
        FAULT_COUNT=0
      fi
    fi
  fi
}

# If the cumulative number of fatal errors exceeds max counts, no restart is performed
detect_count()
{
  MODULE_NAME=$1
  FAULT_COUNT=$(($FAULT_COUNT+1))
  if [ ${FAULT_COUNT} -ge $MAX_FAILT_COUNT ]; then
    FAULT_COUNT=0
    if [ ! -f /tmp/${MODULE_NAME}_disabled ]; then
      touch /tmp/${MODULE_NAME}_disabled
      time_stamp=$(date -u +%s)
      bs-amu-logevent -i 304 -s 1 "{\"utc_s\":\"$time_stamp\",\"info\":\"touch ${MODULE_NAME}_disabled tmp file\"}"
    fi
  fi
}

# AMUS SW to be monitored
ALL_MODULES="ant acu bmc clean-cs logsta poststatus udpserver jingmizhikong wdserver"

# check if a module is running, if not, start it
# argument: name of the module
monitor_module()
{
  MODULE_NAME=$1
  RET_VAL=0

  if [ "$MODULE_NAME" = "clean-cs" ]; then
    if ! pgrep "bs-amu-$MODULE_NAME" ; then
      nohup bs-amu-clean-cstatus -m 2  > /dev/null 2>&1 &
      RET_VAL=1
    fi
  elif [ "$MODULE_NAME" = "logsta" ]; then
    if ! pgrep "bs-amu-$MODULE_NAME" ; then
      nohup bs-amu-logstatus > /dev/null 2>&1 &
      RET_VAL=1
    fi
  elif [ "$MODULE_NAME" = "poststatus" ]; then
    postatus_cnt=$(ps aux | grep poststatus | wc -l)
    if [ "$postatus_cnt" == "1" ] ; then
      nohup bs-amu-poststatus.py > /dev/null 2>&1 &
      RET_VAL=1
    fi
  elif [ "$MODULE_NAME" = "jingmizhikong" ]; then
    jingmizhikong_cnt=$(ps aux | grep jingmizhikong | wc -l)
    if [ "$jingmizhikong_cnt" == "1" ] ; then
      nohup bs-amu-jingmizhikong.py > /dev/null 2>&1 &
      RET_VAL=1
    fi
  elif [ "$MODULE_NAME" = "udpserver" ]; then
    udpserver_cnt=$(ps aux | grep udpserver | wc -l)
    if [ "$udpserver_cnt" == "1" ] ; then
      nohup bs-amu-udpserver.py > /dev/null 2>&1 &
      RET_VAL=1
    fi
  elif [ "$MODULE_NAME" = "wdserver" ]; then
    wdserver_cnt=$(ps aux | grep wdserver | wc -l)
    if [ "$wdserver_cnt" == "1" ] ; then
      nohup python /usr/bin/bs-amu-wdserver.py > /dev/null 2>&1 &
      RET_VAL=1
    fi
  elif [ "$MODULE_NAME" = "ant" ]; then
    if [ -x /etc/init.d/bs-amu-${MODULE_NAME} ] && [ ! -f /var/run/bs_daemons_stop ]; then
      disable_auto_startup bs-amu-$MODULE_NAME
      DAEMON_RUNNING=$(pgrep bs-amu-$MODULE_NAME)
      if [ -z "$DAEMON_RUNNING" ]; then
        ANT_OK_CNT=0
        detect_count $MODULE_NAME
        detect_file="$MODULE_NAME"_disabled
        if [ ! -f "/tmp/$detect_file" ]; then
            #log "Found $MODULE_NAME is not running, starting ${MODULE_NAME}"
            /etc/init.d/bs-amu-${MODULE_NAME} start > /dev/null 2>&1
            #log "${MODULE_NAME} started"
            RET_VAL=1
            log_restart_event $MODULE_NAME
        fi
      else
        # ok cnt ++
        ANT_OK_CNT=$(($ANT_OK_CNT+1))
        if [ ${ANT_OK_CNT} -ge 20 ]; then
          ANT_OK_CNT=0
          FIRST_TIME_STAMP=0
          FAULT_COUNT=0
          echo "reset ant ok cnt..."
        fi
      fi
    fi
  else
    if [ -x /etc/init.d/bs-amu-${MODULE_NAME} ] && [ ! -f /var/run/bs_daemons_stop ]; then
      disable_auto_startup bs-amu-$MODULE_NAME
      DAEMON_RUNNING=$(pgrep bs-amu-$MODULE_NAME)
      if [ -z "$DAEMON_RUNNING" ]; then
        #log "Found $MODULE_NAME is not running, starting ${MODULE_NAME}"
        /etc/init.d/bs-amu-${MODULE_NAME} start > /dev/null 2>&1
        #log "${MODULE_NAME} started"
        RET_VAL=1
        log_restart_event $MODULE_NAME
      fi
    fi
  fi

  return $RET_VAL
}

# log exit reason of a module
# argument: name of the module
log_restart_event()
{
  MODNAME=$1
  EVENT_FILE=/tmp/${MODNAME}_exit_reason.txt

  if [ -f "$EVENT_FILE" ]; then
    e_info=$(cat $EVENT_FILE)

    if [ "$e_info" = "no_event" ]; then
      rm -r $EVENT_FILE
      return
    fi

    e_time=$(date -r $EVENT_FILE +"%s")
  else
    e_info="unknown exit reason"
    e_time=""
  fi
  rm -f $EVENT_FILE
  bs-amu-logevent -i 304 -s 1 "{\"utc_s\":\"$e_time\",\"module\":\"$MODNAME\",\"info\":\"$e_info\"}"
}

# truncate log files in /tmp if their sizes exceed max allowed
monitor_tmp()
{
  large_files=$(find /tmp/ -name "*.log" -type f -size +${MAX_LOG_FILE_SIZE}c -print)

  for f in $large_files
  do
    log "Truncating $f"
    cat /dev/null > $f
  done

  sleep 1
  sync
}

# truncate text files in /var/log if their sizes exceed max allowed
# remove binary files in /var/log if their sizes exceed max allowed
monitor_varlog()
{
  large_files=$(find /var/log/ -type f -size +${MAX_LOG_FILE_SIZE}c -print)

  for f in $large_files
  do
    if ! is_text "$f" ; then
      log "Removing $f"
      rm -f "$f"
    else
      log "Truncating $f"
      cat /dev/null > "$f"
    fi
  done

  sleep 1
  sync
}

# wrapper function
monitor_log()
{
  monitor_tmp
  monitor_varlog
}

# remove all RCs to the module so the module cannot be started by the kernel automatically
# hence PMT can fully control its startup and stop
# argument: the name of the module
disable_auto_startup()
{
  if [ -z "$1" ]; then
    return
  fi

  RC=$(find /etc/ -name "*$1*" -type l)
  if [ ! -z "$RC" ]; then
    log "Removing RCs to module $1"
    rm -f $RC
  fi
}

# Delete default gateway if it's not go via WAN interface
monitor_default_gw()
{
  # assume WAN interface address is always automatic

  def_gw=$( ip route show | grep default | cat )

  # no default gateway yet
  if [ -z "$def_gw" ]; then
    return
  fi

  wan_if=$( ifconfig -a | grep wan | awk '{print $1}' )
  # WAN interface is not defined, this shall not happen
  if [ -z "$wan_if" ]; then
     return
  fi
  wan_address=$( ifconfig "$wan_if" | grep "inet addr" | cat )

  # WAN interface is not up
  if [ -z "$wan_address" ]; then
    return
  fi

  # default gateway shall go via WAN interface
  def_via_wan=$( echo "$def_gw" | grep "$wan_if" | cat )
  # if not, delete default gateway
  if [ -z "$def_via_wan" ]; then
    ip route delete default
  fi
}

# set the kernel scheduling time
set_rt_period()
{
  if [ -f /proc/sys/kernel/sched_rr_timeslice_ms ]; then
    echo " Set sched_rr_timeslice_ms to 2ms"
    echo 2 > /proc/sys/kernel/sched_rr_timeslice_ms
  fi

  if [ -f /proc/sys/kernel/sched_rt_runtime_us ]; then
    echo " Set sched_rt_runtime_us to 2.4ms"
    echo 2400 > /proc/sys/kernel/sched_rt_runtime_us
  fi

  if [ -f /proc/sys/kernel/sched_rt_period_us ]; then
    echo " Set sched_rt_period_us to 3ms"
    echo 3000 > /proc/sys/kernel/sched_rt_period_us
  fi
}

######################################################################
# Main program start here
######################################################################

# Verify user has sufficient privilege
check_privilege

# Verify only a single user is running
check_single_instance

# Log exit message
trap 'on_exit' EXIT

# Main loop for maintenance
log "$DAEMON started"

# Stop current status clean-up
if pgrep bs-amu-clean-cs ; then
  pkill -9 bs-amu-clean-cs
fi

# stop PMT status reporting
if pgrep bs-amu-logsta ; then
  pkill -9 bs-amu-logsta
fi

# Set the kernel scheduling time
set_rt_period

#nohup bs-amu-logstatus 2>&1 > /dev/null &

# make sure both NV and RT net config are up to date
/usr/share/amu/pmt/update_nconfig

# initialize RT config/current status/faults
init_config -r

# initialize network interfaces
net_config=$(sqlite3 /tmp/config.sqlite "select json_config from config where active = 1 and group_type = 'network'")

if [ ! -z "$net_config" ]; then
  net_init $net_config
else
  log "Found no valid networking config"
fi

# Start FRP if FRP config file is exist
if [ -f /home/root/frps.ini ]; then
  if ! pgrep "frp" ; then
    log "Start frp server"
    nohup /usr/bin/frpc -c /home/root/frps.ini >> /dev/null 2>&1 &
  fi
fi

# start PMT main loop
while [ 1 ]
do
  log "$DAEMON woke up"

  monitor_syslog

  # AMUS modules
  for m in $ALL_MODULES
  do
    if ! monitor_module $m; then
      # module has been restarted
      if [ "$m" = "ant" ]; then
        # for ANT restart, sleep for 3s
        sleep 3
      fi
    fi
  done

  monitor_log

  monitor_default_gw

  sleep $SLEEP_INTERVAL # sleep 5s
done

# Jobs are done, shall not reach here
exit 0

