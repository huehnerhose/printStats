#!/bin/bash
scp helpdesk@pareto.soz.tu-berlin.de:/var/log/cups/page_log.1.gz /tmp/
gzip -dfq /tmp/page_log.1.gz
chmod +r /tmp/page_log.1
curl https://granovetter.soz.tu-berlin.de/printStats/addToDb.php?format=cups
