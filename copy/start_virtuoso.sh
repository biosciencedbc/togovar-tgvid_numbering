#! /bin/bash
set -Ceu

# starting virtuoso in the background
#/root/virtuoso/virtuoso71pubchem/bin/virtuoso-t +wait --config /root/virtuoso/virtuoso71pubchem/var/lib/virtuoso/db/virtuoso.ini 
/usr/bin/virtuoso-t +wait --config /virtuoso.ini 

echo "started virtuoso."

 exit 0
