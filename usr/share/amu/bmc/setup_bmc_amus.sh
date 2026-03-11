#!/bin/sh -e

#================================================================
# THISS Technologies
# P Stootman
# January 2019
#
# This script sets up the bmc process after installation
#
#================================================================

# Include the environment variables.
ENV_FILE=/usr/share/amu/bmc/bs-amu-environment
if [ ! -f ${ENV_FILE} ]; then
  echo "Could not find the file ${ENV_FILE} !"
  exit 1
fi
. ${ENV_FILE}

# Create fifo to use for watching events
MACHINE=$(uname -m)
if [ ! -p /usr/share/amu/bmc/bs-amu-bmc.events ]; then

  rm -f /usr/share/amu/bmc/bs-amu-bmc.event

  mkfifo --mode=777 /usr/share/amu/bmc/bs-amu-bmc.events

#  if [ "$MACHINE" != "armv7l" ];
#  then
#     # Update the syslog.conf and restart to user created fifo
#     /usr/share/amu/bmc/config_syslog.sh install

#     # Restart the syslog to take effect
#     /etc/init.d/sysklogd restart
#  fi
fi

if [ ! -f /home/bird/data/bmc_private.sqlite ]; then
  echo "Install empty BMC private database"
  cp /home/bird/data/bmc_private.sqlite.empty /home/bird/data/bmc_private.sqlite
  ls -la /home/bird/data/bmc_private.sqlite 
fi

# check sqlite init runs, and then install it for startup
BMC_SQLITE_SCRIPT=/usr/bin/bmc_sqlite_init
if [ ! -x ${BMC_SQLITE_SCRIPT} ]; then
  echo "Could not find the executable file ${BMC_SQLITE_SCRIPT}!"
  exit 1
fi

# Check the process runs, and then install it for startup
STARTUP_SCRIPT_SRC=/usr/share/amu/bmc/bs-amu-bmc.init
STARTUP_SCRIPT=/etc/init.d/bs-amu-bmc
if [ ! -x ${STARTUP_SCRIPT_SRC} ]; then
  echo "Could not find the executable file ${STARTUP_SCRIPT_SRC}!"
  exit 1
fi

# shall not start immediately after installation
# PMT will be responsible for start up BMC
#${STARTUP_SCRIPT_SRC} restart
echo

# Create temp filesystem startup scripts
echo "Updating bs-amu-bmc startup script... "
echo " * Start up script: $STARTUP_SCRIPT"
cp ${STARTUP_SCRIPT_SRC} ${STARTUP_SCRIPT}
chmod 755 ${STARTUP_SCRIPT}
chown root:root ${STARTUP_SCRIPT}

echo "Updating bmc-sqlite-init script... "
echo " * Start up script: $BMC_SQLITE_SCRIPT"
$BMC_SQLITE_SCRIPT

echo "Startup script for bmc installed... OK"
echo

echo
echo "BMC setup is done"
