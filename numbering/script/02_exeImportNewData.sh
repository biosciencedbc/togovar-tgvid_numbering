#!/bin/sh
set -eu

#スクリプトのあるディレクトリに移動
cd `dirname $0`

filepath=$1
datadir="/data/Togovar/update_rdf"

# 既にデータディレクトリがある場合、バックアップへ移動
if [ -d $datadir ]; then
  ls -1dr $datadir.bk* | xargs rm -r
  mv -fT $datadir $datadir.bk
fi

mkdir $datadir
chmod 777 $datadir


#指定したファイルをRDF化
sdate=`date '+%Y/%m/%d %T'`
echo $sdate": Start import $datadir data" #>> ./togovar_run.log
sdate=`date '+%Y/%m/%d %T'`
echo $sdate": Start convert $datadir VCF data to Turtle file" #>> ./togovar_run.log

if [ -f $filepath ]; then
  echo "$filepath"
  php 03_makeTtlFilesFromNewData.php $filepath
fi

if [ -d $filepath ]; then
  ls $filepath/*.tsv | while read line
  do
    echo "$line" 
    php 03_makeTtlFilesFromNewData.php $line
  done
fi

fdate=`date '+%Y/%m/%d %T'`
echo $fdate": Finish convert $datadir VCF data to Turtle file" #>> ./togovar_run.log

echo $fdate": Start import $datadir Turtle file to RDF database" #>> ./togovar_run.log

#作成されたRDFデータをvirtuosoにロード
/usr/local/virtuoso-opensource/bin/isql 1111 dba dba << EOF
log_enable(2,1);
ld_dir_all ('$datadir', '*.ttl', 'http://togovar.org/test');
rdf_loader_run();
EOF

fdate=`date '+%Y/%m/%d %T'`
echo $fdate": Finish import $datadir Turtle file to RDF database" #>> ./togovar_run.log

fdate=`date '+%Y/%m/%d %T'`
echo $fdate": Finish import $datadir data" #>> ./togovar_run.log
