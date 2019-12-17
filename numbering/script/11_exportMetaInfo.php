<?php

// DB connection
require_once("sparqllib.php");
$db = sparql_connect( "http://localhost:8890/sparql" );
if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }


/// 入力パラメータ ///
/*** パラメータ情報
-t : 出力タイプ（rdf or txt）
-r : リファレンスゲノム情報
-d : 取り込み日
-w : 取り込み理由(Transacion ID)
-m : 管理情報
***/
$today = date("YmdHis");

$outdir = "/data/rdf/export";
$outfile = "$outdir/${today}_metaexport.txt";

$type = "txt";
$day = "default";
$transactionid = "all";


$logfile = "/data/togovar_import.log";
$folog = fopen($logfile, "a");

#オプション解析
$option=getopt('d:w:');

if(array_key_exists('d',$option)){
	$day=$option['d'];
}

if(array_key_exists('w',$option)){
	$transactionid=$option['w'];
}

#クエリの作成
$sparql = "select  ?tgvid ?tid ?day ?rsn ?fpos where { 
		?tgvid <http://togovar.com/term/1.1/import> ?o .
        ?o <http://togovar.com/term/1.1/transactionid> ?tid ;
            <http://togovar.com/term/1.1/issued> ?day ;
            <http://togovar.com/term/1.1/reason> ?rsn ;
            <http://togovar.com/term/1.1/fpos> ?fpos . \n";

if ($transactionid != "all") {
	$sparql .= " ?o <http://togovar.com/term/1.1/transactionid> $transactionid . \n";
}

if ($day != "default") {
	$sparql .= " ?o <http://togovar.com/term/1.1/issued> ?day  . 
			FILTER( ?day > xsd:date(\"$day\")) ";
}

$sparql .= "} LIMIT 10";

#クエリの実行
$result = sparql_query($sparql);

if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
$resar = $result->rows;

#outfileに指定したファイルに「 TogoVarID　TransactionID　取り込み日　取り込み理由　ファイルの場所 」をリストにして出力
$of = fopen($outfile, 'w');
fwrite($of, "TogoVar ID\tTransaction ID\tImport day\tImport reason\tFile location\n");

$restgvid = "";
$restransid = "";
$resday = "";
$resreason = "";
$resfpos = "";

foreach ($resar as $row) {
	$tgvtmp = explode("/", $row['tgvid']['value']);
	$restgvid = $tgvtmp[count($tgvtmp)-1];
	$restransid = $row['tid']['value'];
	$resday = $row['day']['value'];
	$resreason = $row['rsn']['value'];
	$resfpos = $row['fpos']['value'];

	fwrite($of, $restgvid."\t".$restransid."\t".$resday."\t".$resreason."\t".$resfpos."\n");
}

?>


