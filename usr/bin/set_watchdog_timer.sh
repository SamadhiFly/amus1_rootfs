#!/bin/bash

PROG=$(basename $0)
I2CSET="/usr/sbin/i2cset"
I2CDETECT="/usr/sbin/i2cdetect"
SET_CMD="${I2CSET} -f -y 0 "

print_usage()
{
	echo "Set or cancel MCM08C watchdog timeout (in minutes before reseting MCM08C CPU)"
	echo "command: ${PROG} {timeout}"
	echo "Example"
  echo "1) To set a 15 minutes timeout:"
	echo "	 ${PROG} 15"
	echo "2) To disable timeout:"
  echo "   ${PROG} 0"
	exit 0 
}

if [ -z "$1" ]; then
  print_usage
  exit 1
fi

if ! printf "%d" $1 ; then
  echo "Invalid timeout value!"
  print_usage
  exit 1
fi

if [ ! -x ${I2CSET} ]; then
	echo "${I2CSET} is not installed!"
	exit 1
fi

DEVICES=$(${I2CDETECT} -y -r 0 0x68 0x68 | grep UU)
if [ -z "$DEVICES" ]; then
	echo "Found no device in address 0x68"
	exit 1
fi

REG_ADDR=0x09
CHIP_ADDR=0x68

TIMEOUT=$(printf "%d" $1)
RESOLUTION_BYTE=0x20
CANCEL_BYTE=0xff

if [ ${TIMEOUT} -eq 0 ]; then
	echo "Canelling MCM08C reset timer..."
	OUT_BYTE=${CANCEL_BYTE}
else
	echo "Update MCM08C watchdog timer to reset Zynq CPU in $TIMEOUT minuts..."
	OUT_BYTE=$(( $(( $(( ${TIMEOUT} | ${RESOLUTION_BYTE} ))<<2 )) & 0xfc ))
fi

echo "Output_byte=$(printf "0x%x" ${OUT_BYTE})"

$SET_CMD ${CHIP_ADDR} ${REG_ADDR} ${OUT_BYTE}

exit 0

