#!/bin/bash -e

if [[ $1 == 'release' ]];then
    make ARCH=armhf HW_IDENTIFIER=amus1 release
elif [[ $1 == 'clean' ]];then
    make ARCH=armhf HW_IDENTIFIER=amus1 clean
elif [[ $1 == 'package' ]];then
    make ARCH=armhf HW_IDENTIFIER=amus1 package
elif [[ $1 == 'debug' ]];then
  arm-xilinx-linux-gnueabi-gdb ./tt-cu-bmc
elif [[ $1 == 'scp' ]];then
  scp tt-cu-bmc root@amu:/home/root
#elif [[ $1 == 'test' ]];then
else
    make ARCH=armhf HW_IDENTIFIER=amus1 release
    
    set +e
    echo "Hard kill BMC at AMU..."
    ssh root@amu pkill -kill bmc
    set -e
    
    echo "Redeploy BMC to AMU..."
    scp bs-amu-bmc root@amu:/home/root
    ssh root@amu /home/root/bs-amu-bmc
#else
#    echo "usage: $0 release|clean|package"
fi

