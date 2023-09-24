#!/bin/bash
url='https://iot.jonathanwise.net/checkin/'
queryString='?version=1&'

# Get or Create Unique ID
if [ -f ~/.iotid ]; then
   uuid=$(cat ~/.iotid);
else
   uuid=$(cat /proc/sys/kernel/random/uuid)
   echo $uuid > ~/.iotid;
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
   lastUsers=$(last -1 -n 1 --nohostname)
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
ports=$(netstat -lntu)
#echo $url
curl --header 'Content-Type: text/plan' --data "$ports" "$url"
