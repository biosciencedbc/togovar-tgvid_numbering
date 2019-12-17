#! /bin/bash
#set -Ceu


# load RDF data
 usr/bin/isql-vt 1111 dba dba << EOF
log_enable(2,1);
#ld_dir_all ('/root/numbering_tgvid/input/load', '*.ttl*', 'http://togovar.org/test');
ld_dir_all ('/data/Togovar/rdf', '*_1_*.ttl*', 'http://togovar.org/test');
rdf_loader_run();
exit;
EOF


