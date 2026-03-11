#!/bin/bash

kill_nc()
{
  sleep 1
  nc_pid=$(pidof nc) 2>&1 > /dev/null
  kill -9 $nc_pid & 2>&1 > /dev/null
}


get_state()
{
  acu_pid=$(pidof bs-amu-acu)
  if [ $acu_pid = "" ]; then
    exit
  fi

  echo rsc_acu_all | nc localhost 30310 > /tmp/value & 2>&1

  kill_nc

  state=$(cat /tmp/value | grep "\"current_state" | awk -F":" '{print $2}' |  awk -F"," '{print $1}' )

  echo "current state=$state"
}


get_current_az()
{
  acu_pid=$(pidof bs-amu-acu)
  if [ $acu_pid = "" ]; then
    exit
  fi

  echo rsc_acu_all | nc localhost 30310 > /tmp/value & 2>&1

  kill_nc

  current_az=$(cat /tmp/value | grep "\"az_current_position_degs" | awk -F":" '{print $2}' |  awk -F"," '{print $1}' )

  echo "az current position degs=$current_az"
}


mp_mode()
{
  echo "pointing_mode 2" | nc localhost 30310 & 2>&1
  kill_nc
  echo "mp 50 50 0 50" | nc localhost 30310 & 2>&1
  kill_nc
}


disconnect_bat()
{
  echo 'bat_disconnect' | nc localhost 30310 & 2>&1
  kill_nc
}


kill_acu()
{
  acu_pid=$(pidof bs-amu-acu) 2>&1 > /dev/null
  kill -9 $acu_pid & 2>&1 > /dev/null
  sleep 1
}

#==========================================================================
# Main start

get_state

#we detect current state, if current state = 13, then into mp mode
if [ $state = 13 ]; then

  get_current_az

  echo "now we in mp mode...."
  mp_mode
  
  # Read AZ current angle all the time until < 70
  while [[ $(echo "$current_az > 70"|bc) -eq 1 ]]
  do
  get_current_az
  sleep 2
  done
  
  disconnect_bat
  sleep 2
  
  kill_acu
  reboot
fi

