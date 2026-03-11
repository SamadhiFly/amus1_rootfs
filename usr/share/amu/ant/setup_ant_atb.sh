#!/bin/sh -e

#================================================================
# Starlight Technologies
# Monk He
# January 2019
#
# This script sets up the ant process after installation
#
#================================================================

# Include the environment variables.
ENV_FILE=/usr/share/cu/ant/tt-cu-environment
if [ ! -f ${ENV_FILE} ]; then
  echo "Could not find the file ${ENV_FILE} !"
  exit 1
fi
. ${ENV_FILE}

# Create fifo to use for watching events
MACHINE=$(uname -m)
if [ ! -p /usr/share/cu/ant/tt-cu-ant.events ]; then

  rm -f /usr/share/cu/ant/tt-cu-ant.event

  mkfifo --mode=777 /usr/share/cu/ant/tt-cu-ant.events

#  if [ "$MACHINE" != "armv7l" ];
#  then
#     # Update the syslog.conf and restart to user created fifo
#     /usr/share/cu/ant/config_syslog.sh install

#     # Restart the syslog to take effect
#     /etc/init.d/sysklogd restart
#  fi
fi

# Check the process runs, and then install it for startup
STARTUP_SCRIPT_SRC=/usr/share/cu/ant/tt-cu-ant.init
STARTUP_SCRIPT=/etc/init.d/tt-cu-ant
if [ ! -x ${STARTUP_SCRIPT_SRC} ]; then
  echo "Could not find the executable file ${STARTUP_SCRIPT_SRC}!"
  exit 1
fi

# shall not start immediately after installation
# MDM will be responsible for start up ANT
#${STARTUP_SCRIPT_SRC} restart
echo

# Create temp filesystem startup scripts
echo "Updating tt-cu-ant startup script... "
echo " * Start up script: $STARTUP_SCRIPT"
cp ${STARTUP_SCRIPT_SRC} ${STARTUP_SCRIPT}
chmod 755 ${STARTUP_SCRIPT}
chown root:root ${STARTUP_SCRIPT}

echo "Startup script for ant installed... OK"
echo

echo
echo "ANT setup is done"
