#!/bin/bash

if [ $# -eq 0 ] ; then
    echo "Usage: smol.sh +12125551212 \"blah blah message goes here\""
    exit
fi

from=$1
msg=$2
host="sms.smalldata.coop"

echo "Sending '$msg' from $from..."
curl -XPOST "https://$host/sms.php" \
     --data-urlencode "From=$from" \
     --data-urlencode "Body=$msg"

echo "Sent!"
