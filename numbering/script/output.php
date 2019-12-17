<?php

ini_set("memory_limit","800M");

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
-o : オフセット位置(1万件ずつ処理を行う際のカウント)
-f : 出力ファイル名
-r : リファレンスゲノム情報
***/
$today = date("YmdHis");

$outdir = "/root/numbering_tgvid/output";
$outfile = "$outdir/${today}_export.tsv";

$day = "default";
$transactionid = "all";
$offset_cnt = "1";
$genome = "GRCh37";

#オプション解析
$option=getopt('d:w:o:f:r:');

if(array_key_exists('d',$option)){
        $day=$option['d'];
}

if(array_key_exists('w',$option)){
        $transactionid=$option['w'];
}

if(array_key_exists('o',$option)){
	$offset_cnt=$option['o'];
}

if(array_key_exists('f',$option)){
        $outfile=$option['f'];
}

if(array_key_exists('r',$option)){
        $genome=$option['r'];
}

print_r("offset_count:".$offset_cnt."\n");

#outfileに指定したファイルにヘッダを出力
$of = fopen($outfile.$offset_cnt, 'w');
fwrite($of, "#CHROM\tPOS\tID\tREF\tALT\n");


for ($i=0;$i<10;$i++){

#クエリ作成
$sparql = "SELECT DISTINCT ?tgvid ?chrom ?start ?ref ?alt  WHERE {
                ?tgvid <http://togovar.com/term/1.1/position> ?pos ;
                        <http://togovar.com/term/1.1/ref> ?ref ;
                        <http://togovar.com/term/1.1/alt> ?alt ;
                        <http://togovar.com/term/1.1/tgvnumber> ?tgvnum ;
                        <http://togovar.com/term/1.1/import> ?o .
                ?pos  <http://togovar.com/term/1.1/refgenome> \"$genome\" ;
                        <http://togovar.com/term/1.1/chromosome> ?chrom ;
                        <http://togovar.com/term/1.1/start> ?start . \n";

if ($transactionid != "all") {
        $sparql .= "?o <http://togovar.com/term/1.1/transactionid> $transactionid . \n";
}

if ($day != "default") {
        $sparql .= " ?o <http://togovar.com/term/1.1/issued> ?day  .
                        FILTER( ?day > xsd:date(\"$day\")) ";
}

$sparql .= " FILTER ( 0 < ?tgvnum ) } limit 10000 offset $offset_cnt";

#クエリ実行
$result = sparql_query($sparql);

# 10_exportAllの長時間実行時エラーの対応
# if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
$error_cnt = 0;

if( !$result ) {
	print sparql_errno() . ": " . sparql_error(). "\n";
	print_r("restart virtuoso \n");

	shell_exec("sh /start_virtuoso.sh"); 

	while( !$result && $error_cnt < 10 ){
		sleep (60);
                $error_cnt++;
		print_r("retry sparql : ".$error_cnt." \n");
		$result = sparql_query($sparql);
	}
}
if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
$resar = $result->rows;
unset($result);
print_r("roop_offset:".$offset_cnt."\n");
$offset_cnt = $offset_cnt + 10000;

# #outfileに指定したファイルにヘッダを出力
# $of = fopen($outfile.$offset_cnt, 'w');
# fwrite($of, "#CHROM\tPOS\tID\tREF\tALT\n");

$restgvid = "";
$reschrome = "";
$respos = "";
$resref = "";
$resalt = "";


foreach ($resar as $row) {
        $tgvtmp = explode("/", $row['tgvid']['value']);
        $restgvid = $tgvtmp[count($tgvtmp)-1];
        $reschrome = $row['chrom']['value'];
        $respos = $row['start']['value'];
        $resref = $row['ref']['value'];
        $resalt = $row['alt']['value'];

        fwrite($of,$reschrome."\t".$respos."\t".$restgvid."\t".$resref."\t".$resalt."\n");
}

print_r("peak memory usage:". memory_get_peak_usage()."\n");

}
?>
