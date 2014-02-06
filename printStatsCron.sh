#!/bin/bash
scp helpdesk@pareto.soz.tu-berlin.de:/var/log/cups/page_acc_log.1.gz /tmp/
gzip -dfq /tmp/page_acc_log.1.gz
chmod +r /tmp/page_acc_log.1
curl http://granovetter.soz.tu-berlin.de/printStats/addToDb.php
