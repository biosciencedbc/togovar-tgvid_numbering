<?php

ini_set("memory_limit","500M");

#引数が3つ以上で実行(phpファイル　データソース　入力ファイル名)
if($argc>=2)
{
	$file_name = $argv[1];
}
else
{
	$msg = <<< msg
[usage]
php 03_makeTtlFilesFromNewData.php [inputFileName] 

msg;
	echo($msg);
	exit;
}

# $ttl = "@prefix rdf:   <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
$prefix  = "@prefix rdf:   <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
$prefix .= "@prefix dct:    <http://purl.org/dc/terms/> .\n";
$prefix .= "@prefix dc:    <http://purl.org/dc/terms/> .\n";
$prefix .= "@prefix dcat:  <http://www.w3.org/ns/dcat#> .\n";
$prefix .= "@prefix faldo:  <http://biohackathon.org/resource/faldo#> .\n";
$prefix .= "@prefix tgv: <http://togovar.com/term/1.1/> .\n\n";



#ファイル読み込み
require_once("sparqllib.php");

#エンドポイントに接続
$db = sparql_connect( "http://localhost:8890/sparql" );
if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

#最新のtgvIDを取得

$tgvid_recent = getRecentID() + 1;

#最新のトランザクションIDを取得
$transaction_id = getTransactionID() + 1;

/// 入力パラメータ ///
/*** パラメータ情報
-p : ファイル位置
-r : リファレンスゲノム情報
-d : 取り込み日
-w : 取り込み理由
-m : 管理情報
***/
$data_dir = "/data/Togovar";
$rdf_dir = $data_dir."/update_rdf";
$genome = "GRCh37";
$day = date("Y-m-d");
$reason = "default";
$manage = "default";
$delflg = 0;

$logfile = fopen("/data/togovar_import.log", "a");

#オプション解析
$option=getopt('p:r:d:w:m:');

if(array_key_exists('p',$option)){
	$file=$option['p'];
}

if(array_key_exists('r',$option)){
	$genome=$option['r'];
}

if(array_key_exists('d',$option)){
	$day=$option['d'];
}

if(array_key_exists('w',$option)){
	$reason=$option['w'];
}

if(array_key_exists('m',$option)){
	$manage=$option['m'];
}


/// ディレクトリ整備 ///
if (!file_exists($rdf_dir)) {
#	system("rm -rf $rdf_dir.bk");
#	system("mv $rdf_dir $rdf_dir.bk");
	system("mkdir $rdf_dir");
}
#system("mkdir $rdf_dir");
system("chmod 777 $rdf_dir");

$line_num = 1;
$headflg = 1;
$idnum = 1;
$file_count = 1;
$end_key = end(explode("/", $file_name));
$posar = array();
$input_file= fopen($file_name,"r");

#ファイルを一行ずつ読み込んでいく
while(!feof($input_file)) {

# echo "while-start:".memory_get_usage()."\n";
	$error = 0;
	$tsv = fgetcsv($input_file,99999,"\t");

	$outfname = $rdf_dir."/".$end_key.$file_count.".ttl";

	#フィールドが5以上でかつ最初の読み込みの場合(カラムの作成)
	if (count($tsv) >= 5 && $headflg == 1) {
		$headflg = 0;
		for ($i=0; $i < count($tsv); $i++) { 
			if ($tsv[$i] == "CHROM") {
				$posar["CHROM"] = $i;
			}
			else if ($tsv[$i] == "POS") {
				$posar["POS"] = $i;
			}
			else if ($tsv[$i] == "REF") {
				$posar["REF"] = $i;
			}
			else if ($tsv[$i] == "ALT") {
				$posar["ALT"] = $i;
			}
		}
	}
	#フィールドが5以上で最初の行でない場合()
	else if(count($tsv) >= 5 && $headflg == 0) {
		$chrom = $tsv[$posar["CHROM"]];
		$pos = $tsv[$posar["POS"]];
		$ref = $tsv[$posar["REF"]];
		$alt = $tsv[$posar["ALT"]];
	
		$tmpout = $rdf_dir."/samtools_tmp.out";
		$cmd = "/usr/local/bin/samtools faidx /root/numbering_tgvid/input/fasta/Homo_sapiens.${genome}.dna.toplevel.fa.gz $chrom:${pos}-${pos} > $tmpout";
		exec($cmd);

		$input_file2 = fopen($tmpout,"r");
		$lnumtmp = 1;
		while(!feof($input_file2)) {
			$tsv2 = fgetcsv($input_file2,99999,"\t");
			if ($lnumtmp == 2) {
				$ref_refgenome = trim($tsv2[0]);
				break;
			}
			$lnumtmp++;
		}
		unset($lnumtmp);
		fclose($input_file2);

		if ($ref == $ref_refgenome) {
# echo "before_checkExists:".memory_get_usage()."\n";
			$return = checkExists($chrom, $pos, $ref, $alt);
# echo "after_checkExists :".memory_get_usage()."\n";
			// TogoVarに同じデータが存在した場合
			if ($return == 1) {
				fwrite($logfile, "[Notice]Transaction ID($transaction_id) line$line_num:The same variant already exists in TogoVar.\n");
				$error = 2;
			}
		}
		// Refferenceゲノムの情報と一致しなかった場合
		else {
			fwrite($logfile, "[Error]Transaction ID($transaction_id) line$line_num:Reference allele is different from ref_genome($genome)\n");
			$error = 1;
		}

		if ($error == 0 || $error == 1) {
			if ($idnum == 1) {
				file_put_contents($outfname, $prefix);
			}
			else {
				json2ttl($tgvid_recent, $chrom, $pos, $ref, $alt, $error, $outfname);
				$tgvid_recent++;
			}
		}
		
		$idnum++;
		if ($idnum == 100001) {  # 10万件ごとに1ファイル
			$idnum = 1;
			$file_count++;
			echo $outfname."_memory_usage:".memory_get_usage()."\n";
		}
	}

	$line_num++;

# echo "while-end  :".memory_get_usage()."\n";
}


/// Functions ///
function getTransactionID() {
	global $db;
# echo "getTransactionID-start:".memory_get_usage()."\n";
	$sparql = "select ?transactionid where { 
		?s  <http://togovar.com/term/1.1/transactionid> ?transactionid 
		. } 
		ORDER BY DESC(?transactionid)  
		LIMIT 1";

	$result = sparql_query($sparql);

	if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

	$resar = $result->rows;

	if (count($resar) == 0) {
# echo "getTransactionID-end  :".memory_get_usage()."\n";
		return 0;
	}
	else {
		$cnt = intval($resar[0]['transactionid']['value']);
# echo "getTransactionID-end  :".memory_get_usage()."\n";
		return $cnt;
	}
}


/// Functions ///
#最新のtgvidを取得して返す
function getRecentID() {
#	global $db;
# echo "getRecentID-start:".memory_get_usage()."\n";
	sparql_ns( "tgv","http://togovar.com/term/1.1/" );

	$sparql = "select ?tgvnumber where { 
		?s  <http://togovar.com/term/1.1/tgvnumber> ?tgvnumber 
		. } 
		ORDER BY DESC(?tgvnumber)  
		LIMIT 1";

	$result = sparql_query($sparql);

	if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

	$resar = $result->rows;
	$cnt = intval($resar[0]['tgvnumber']['value']);

# echo "getRecentID-end  :".memory_get_usage()."\n";

	return $cnt;
}

#同じアリル情報があるかどうかの確認
function checkExists($chrom, $pos, $ref, $alt, $try = false) {

# echo "checkExist-start:".memory_get_usage()."\n";

	global $db;
	if( !$db ){echo "db connect\n"; $db = sparql_connect( "http://localhost:8890/sparql" ); }

	sparql_ns( "tgv","http://togovar.com/term/1.1/" );

	$sparql = "select (count(?s) as ?COUNT) where {
		?s <http://togovar.com/term/1.1/position> ?o ;
            <http://togovar.com/term/1.1/ref> \"$ref\" ;
            <http://togovar.com/term/1.1/alt> \"$alt\" .
        ?o <http://togovar.com/term/1.1/chromosome> \"$chrom\" ;
            <http://togovar.com/term/1.1/start> $pos ;
            <http://togovar.com/term/1.1/end> $pos .
		}";

	$result = sparql_query($sparql);

	if( !$result ) { 
		print "checkExists \n" . $sparql . "\n" . $result . "\n" . sparql_errno() . ": " . sparql_error(). "\n"; 
		if( $try ){exit;}
		sleep ( 5 );
		return checkExists($chrom, $pos, $ref, $alt, true); 
	}

	$resar = $result->rows;

	unset($sparql);
	unset($result);

# echo "checkExist-end  :".memory_get_usage()."\n";

#	$cnt = intval($resar[0]['COUNT']['value']);
	return intval($resar[0]['COUNT']['value']);
#	return $cnt;
}


function json2ttl($tgvid_recent, $chrom, $pos, $ref, $alt, $error, $out) {
	global $data_source, $rdf_dir, $file, $genome, $day, $reason, $manage, $transaction_id, $delflg;
	$tvid = "tgv".$tgvid_recent;
	$tvid_num = intval($tgvid_recent);

	$mainuri = "https://togovar.biosciencedbc.jp/variant/".$tvid;

	$ttl = "<" . $mainuri . ">\n";

	// TogoVar ID
	$ttl .= "  tgv:tgvid \"" . $tvid . "\";\n";

	// TogoVar ID(Number)
	$ttl .= "  tgv:tgvnumber " .$tvid_num.";\n";

	// uri
	$ttl .= "  dct:accessURL \"" . $mainuri . "\";\n";

	// リファレンスアリル
	$ttl .= "  tgv:ref \"" . $ref . "\";\n";

	// オルタナティブアリル
	$ttl .= "  tgv:alt \"" . $alt . "\";\n";


	// position（空白ノード）
	$ttl .= "  tgv:position [ \n";
	// リファレンスゲノム
	$ttl .= "    tgv:refgenome \"" . $genome . "\";\n";
	// 染色体番号
	$ttl .= "    tgv:chromosome \"" . $chrom . "\";\n";
	// 位置情報
	$ttl .= "    tgv:start " . $pos . ";\n";
	$ttl .= "    tgv:end " . $pos . ";\n";
	// 削除フラグ
	$ttl .= "    tgv:delflg \"" . $delflg . "\";\n";
	$ttl .= "  ]; \n";


	// import（空白ノード）
	$ttl .= "  tgv:import [ \n";
	// Transaction ID
	$ttl .= "    tgv:transactionid " . $transaction_id . ";\n";
	// エラー情報
	$ttl .= "    tgv:errorflg " . $error . ";\n";
	// 既存データのファイル位置
	$ttl .= "    tgv:fpos \"" . $file . "\";\n";
	// 取り込み日
	$ttl .= "    tgv:issued \"" . $day . "\"^^xsd:date;\n";
	// 取り込み理由
	$ttl .= "    tgv:reason \"" . $reason . "\";\n";
	// 元データ等の管理情報
	$ttl .= "    tgv:origin \"" . $manage . "\";\n";
	// データ最終更新日
	$ttl .= "    tgv:modified \"" . $day ."\"^^xsd:date;\n";
	$ttl .= "  ] \n";

	$ttl .= ".\n\n";
	file_put_contents($out, $ttl, FILE_APPEND);
}
?>


