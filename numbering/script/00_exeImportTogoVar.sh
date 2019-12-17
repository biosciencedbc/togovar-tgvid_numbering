#!/bin/sh


#入力ファイルパスを引数としてtogovarのデータをttlに変換、virtuosoにロードする
filepath=$1
datadir="/data/Togovar/rdf"

sdate=`date '+%Y/%m/%d %T'`
echo $sdate": Start import TogoVar data" #>> ./togovar_run.log
echo $sdate": Start convert TogoVar VCF data to Turtle file" #>> ./togovar_run.log

if [ -f $filepath ]; then
  php 01_makeTtlFilesFromTogoVar.php $filepath
#  php 01_1_makeTtlFilesFromTogoVar_MTXY.php $filepath
fi

if [ -d $filepath ]; then
  ls $filepath/*.tsv | while read line 
  do
    echo "$line"
    php 01_makeTtlFilesFromTogoVar.php $line
#    php 01_1_makeTtlFilesFromTogoVar_MTXY.php $line
  done
fi

fdate=`date '+%Y/%m/%d %T'`
echo $fdate": Finish convert TogoVar VCF data to Turtle file" #>> ./togovar_run.log

sdate=`date '+%Y/%m/%d %T'`
echo $sdate": Start import TogoVar Turtle file to RDF database" #>> ./togovar_run.log

/usr/bin/isql-vt main-virtuoso:1111 dba << EOF
#/usr/local/virtuoso-opensource/bin/isql 1111 dba dba << EOF
log_enable(2,1);
ld_dir_all ('$datadir', '*.ttl', 'http://togovar.org/test');
rdf_loader_run();
EOF


fdate=`date '+%Y/%m/%d %T'`
echo $fdate": Finish import TogoVar Turtle file to RDF database" #>> ./togovar_run.log
echo $fdate": Finish import TogoVar data" #>> ./togovar_run.log
