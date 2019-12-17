<?php

ini_set("memory_limit","500M");

// DB connection
require_once("sparqllib.php");
$db = sparql_connect( "http://localhost:8890/sparql" );
if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

#dbを出力
# print_r($db);

/// 入力パラメータ ///
/*** パラメータ情報
-d : 取り込み日
-w : 取り込み理由(Transacion ID)
-s : 開始位置(offset指定)
***/
$today = date("YmdHis");

$outdir = "/root/numbering_tgvid/output";
$outfile = "$outdir/${today}_export.tsv";

$day = "default";
$transactionid = "all";
$startnum = 0;

#オプション解析
$option=getopt('d:w:s:');

if(array_key_exists('d',$option)){
	$day=$option['d'];
}

if(array_key_exists('w',$option)){
	$transactionid=$option['w'];
}

if(array_key_exists('s',$option)){
        $startnum=(int)$option['s'];
	$startnum=floor($startnum/100000);
}

$count_id = 100000;

$cnt_sparql = "select count(?tgvnum) WHERE {select distinct ?tgvnum where{
                ?tgvid  <http://togovar.com/term/1.1/tgvnumber> ?tgvnum.
                FILTER ( 0 < ?tgvnum ) } }";

#クエリ実行
$cnt_result = sparql_query($cnt_sparql);

if( !$cnt_result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

$cnt_resar = $cnt_result->rows;
$count_id = $cnt_resar[0]['callret-0']['value'];

# 出力対象IDの数を表示
print_r($count_id."\n");

# テスト用に出力件数を制限
#$count_id = 6000000;

# 10万件ずつ出力
for($i=0+$startnum;$i*100000<$count_id;$i++) {

  $offset_num = $i*100000;

  print_r("offset_".$offset_num." : ");

  $string="php /root/numbering_tgvid/script/output.php -o ".$offset_num." -f ".$outfile;

  exec($string, $out);
  print_r($out);

  # 変数の中身をクリア
  unset($out);
}

// ファイルすべてを指定
$filelist = glob($outdir.'/*'); 

print_r("filelist:".$filelist."\n");

$filenum=1;
$data="";
$cnt=1;
//10ファイルをひとつのファイルに結合
#foreach($filelist as $file){

#	// 2つめ以降のファイルは先頭行(ヘッダー行)を削除
#	if($cnt!=1){
#		$modfile = file($file);
#		unset($modfile[0]);
#		file_put_contents($file, $modfile);
#	}	
#	$filedata = file_get_contents($file);
#	$data .=$filedata;
	
#	// 10ファイル目なら結合
#	if($cnt==10){
#		$mergefilename="test".$filenum.".txt";
#		file_put_contents($mergefilename,$data);
#		$filenum++;
#		$data="";
#		$cnt=0;
#		print_r($mergefilename."\n");
#	}else{
#		$cnt++;
#	}
#}


?>


