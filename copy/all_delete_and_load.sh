#! /bin/bash
#set -Ceu


# shutdown virtuoso
/usr/bin/isql-vt 1111 dba dba << EOF
shutdown();
EOF


# delete DB files
ls /usr/local/virtuoso-opensource/var/lib/virtuoso/db/* | grep -v virtuoso.ini | xargs rm


# restart virtuoso
/usr/local/virtuoso-opensource/bin/virtuoso-t -w --config /root/virtuoso/virtuoso71pubchem/var/lib/virtuoso/db/virtuoso.ini


# load RDF data
/usr/bin/isql-vt 1111 dba dba << EOF
log_enable(2,1);
ld_dir_all ('/root/numbering_tgvid/input/load', '*.ttl*', 'http://togovar.org/test');
#ld_dir_all ('/data/Togovar/rdf', '*_2*.ttl*', 'http://togovar.org/test');
rdf_loader_run();
exit;
EOF 

