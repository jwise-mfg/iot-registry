#!/bin/bash
url='<SERVERPATH>/checkin/'
queryString='?version=2&'

## Install as Service
installservice() {
   if [ "$EUID" -ne 0 ]
      then echo "Please run as root"
      exit
   fi
   echo Installing as Service...
   lastUsers=$(last -1 -n 1 --nohostname)
   set -- $lastUsers
   currUser=$1
   currHome="/home/$currUser/"
   
   mkdir -p /opt/iot-registry
   SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
   DEST_PATH=/opt/iot-registry/
   UNIT_PATH=/etc/systemd/system/iot-registry.service
   if [ -f $UNIT_PATH ]; then
      echo Service already installed!
      exit
   fi
   \cp -r $SCRIPT_DIR/$0 $DEST_PATH"checkin.sh"
   if [ -f $currHome".iotid" ]; then
      mv $currHome".iotid" $DEST_PATH".iotid"
   else
      if [ ! -f $DEST_PATH".iotid" ]; then
         uuid=$(cat /proc/sys/kernel/random/uuid)
         echo $uuid > $DEST_PATH".iotid"
      fi
   fi
   if [ $(which systemctl) ]; then
      echo "[Unit]" > $UNIT_PATH
      echo "Description=IOT Registry Service" >> $UNIT_PATH
      echo "Wants = network-online.target" >> $UNIT_PATH
      echo "After = network.target network-online.target" >> $UNIT_PATH
      echo "" >> $UNIT_PATH
      echo "[Service]" >> $UNIT_PATH
      echo "Type=oneshot" >> $UNIT_PATH
      echo "ExecStart=$DEST_PATH""checkin.sh" >> $UNIT_PATH
      echo "" >> $UNIT_PATH
      echo "[Install]" >> $UNIT_PATH
      echo "WantedBy=multi-user.target" >> $UNIT_PATH
      systemctl daemon-reload
      systemctl enable iot-registry.service
      systemctl start iot-registry.service
  else
    #TODO: Come up with a better way to control services on systemd-less environments
    echo Now add to cron a line like:
    echo "@reboot sleep 60 && $DESTPATH""checkin.sh"
  fi
  exit
}
if [ "$1" == "--install-service" ]; then
   installservice
fi

## Remove Service
removeservice() {
   if [ "$EUID" -ne 0 ]
      then echo "Please run as root"
      exit
   fi
   echo Removing Service...
   lastUsers=$(last -1 -n 1 --nohostname)
   set -- $lastUsers
   currUser=$1
   currHome="/home/$currUser/"
   SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
   DEST_PATH=/opt/iot-registry/
   UNIT_PATH=/etc/systemd/system/iot-registry.service
   if [ -f $DEST_PATH ]; then
      mv $DEST_PATH"checkin.sh" $currHome"checkin.sh"
      mv $DEST_PATH".iotid" $currHome".iotid"
      rm -rf $DEST_PATH
   fi
   if [ $(which systemctl) ]; then
      if [ -f $UNIT_PATH ]; then
         systemctl stop iot-registry.service
         systemctl disable iot-registry.service
         rm $UNIT_PATH
         systemctl daemon-reload
      else
         echo Service not found to remove!
         exit
      fi
  else
    #TODO: Come up with a better way to control services on systemd-less environments
    echo Now remove from cron the line like:
    echo "@reboot sleep 60 && $DESTPATH""checkin.sh"
  fi
  exit
}
if [ "$1" == "--remove-service" ]; then
   removeservice
fi

## Normal Operation

# Get or Create Unique ID
if [ -f ~/.iotid ]; then
   uuid=$(cat ~/.iotid)
fi
if [ -f /opt/iot-registry/.iotid ]; then
   uuid=$(cat /opt/iot-registry/.iotid)
fi
if [ ! "$uuid" ]; then
   uuid=$(cat /proc/sys/kernel/random/uuid)
   echo $uuid > ~/.iotid
fi
queryString=${queryString}iotid=$uuid
queryString=${queryString}\&

# Find Hostname
if [ $(which hostname) ]; then
    queryString=${queryString}hostname=$(hostname)
fi
queryString=${queryString}\&

# Find IPs
if [ $(which ip) ]; then
   ips=$(ip -4 -o addr show scope global | awk '{gsub(/\/.*/,"",$4); print $4}')
   queryString=${queryString}ips=
   while IFS= read -r line; do
      queryString=${queryString}${line}\,
   done <<< "$ips"
   queryString=${queryString%,*}
fi
queryString=${queryString}\&

# Find last logged in user
if [ $(which hostname) ]; then
   lastUsers=$(last -1 -n 1)
   set -- $lastUsers
   currUser=$1
   queryString=${queryString}username=${currUser}
fi
queryString=${queryString}\&

# Find Architecture
if [ $(which arch) ]; then
    queryString=${queryString}arch=$(arch)
fi
url=${url}${queryString}

# Find Open ports
if [ $(which netstat) ]; then
   ports=$(netstat -lntu)
fi

#echo $url
curl --header 'Content-Type: text/plain' --data "$ports" "$url"
exit