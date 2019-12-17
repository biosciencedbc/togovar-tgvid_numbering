<?php


#引数が2つ以上であれば実行(実行phpファイルと入力ファイル名)、なければメッセージの出力
if($argc>=2)
{
	$filename = $argv[1];
}
else
{
	$message = <<< msg
[usage]
php 01_makeTtlFilesFromTogoVar.php [inputFileName]

msg;
	echo($message);
	exit;
}

// DB connection
#ファイルの読み込み,再読み込みの場合は実行しない(sparqllibの関数が使える)
require_once("sparqllib.php");

#エンドポイントに接続、出来なかった場合はエラー出力
$db = sparql_connect( "http://localhost:8890/sparql" );
if( !$db ) { exit; }

$data_dir = "/data/Togovar";
$rdf_dir = $data_dir."/rdf";

$prefix = $ttl = "@prefix rdf:   <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
$prefix .= "@prefix tgv: <http://togovar.com/term/1.1/> .\n\n";

/// 入力パラメータ ///
/*** パラメータ情報
-p : ファイル位置
-r : リファレンスゲノム情報
-d : 取り込み日
-w : 取り込み理由
-m : 管理情報
***/


$genome = "GRCh37";
$day = date("Y-m-d");
$reason = "default";
$manage = "default";
$error = 0;

// Get recent tracsaction ID
$transaction_id = getTransactionID() + 1;

#オプション解析
$options = getopt('p:r:d:w:m:');

if(array_key_exists('p',$options)){
	$file_position=$options['p'];
}

if(array_key_exists('r',$options)){
	$genome=$options['r'];
}

if(array_key_exists('d',$options)){
	$day=$options['d'];
}

if(array_key_exists('w',$options)){
	$reason=$options['w'];
}

if(array_key_exists('m',$options)){
	$manage=$options['m'];
}


/// ディレクトリ整備 ///
if (!file_exists($rdf_dir)) {
	system("mkdir $rdf_dir");
}
system("chmod 777 $rdf_dir");

#ファイルを読み込み
$idnum = 1;
$posar = array();
$file_count = 1;
$explode_name = explode("/", $filename);
$end_key = end($explode_name);
$input_file = fopen($filename,"r");
while(!feof($input_file)) {
	$row = fgetcsv($input_file,99999,"\t");

	$output_file_name = $rdf_dir."/".$end_key.$file_count.".ttl";
	
	#フィールドが5以上の場合
	if (count($row) >= 5) {
		if ($idnum == 1) {
			file_put_contents($output_file_name, $prefix);
			if ($file_count == 1){
				for ($i=0; $i < count($row); $i++) {
					if ($row[$i] == "chr") {
						$posar["CHROM"] = $i;
					}else if ($row[$i] == "position_grch37") {
                	        	        $posar["POS"] = $i;
                	        	}else if ($row[$i] == "ref") {
                	        	        $posar["REF"] = $i;
                	        	}else if ($row[$i] == "alt") {
                	        	        $posar["ALT"] = $i;
                	        	}
				}
			}
			else{
				$chrom = $row[$posar["CHROM"]];
				$pos = $row[$posar["POS"]];
				$ref = $row[$posar["REF"]];
				$alt = $row[$posar["ALT"]];

				json2ttl($row, $chrom, $pos, $ref, $alt, $output_file_name);
			}
		}
		else {
			$chrom = $row[$posar["CHROM"]];
			$pos = $row[$posar["POS"]];
			$ref = $row[$posar["REF"]];
			$alt = $row[$posar["ALT"]];

			json2ttl($row, $chrom, $pos, $ref, $alt, $output_file_name);
		}

		$idnum++;
		if ($idnum == 100001) {  # 10万件ごとに1ファイル
			$idnum = 1;
			$file_count++;
		}
	}
	else {
		continue;
	}
}


/// Functions ///
function getTransactionID() {
	global $db;

	$sparql = "select ?transactionid where { 
		?s  <http://togovar.com/term/1.1/transactionid> ?transactionid 
		. } 
		ORDER BY DESC(?transactionid)  
		LIMIT 1";

	$result = sparql_query($sparql);

	if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

	$resar = $result->rows;

	if (count($resar) == 0) {
		return 0;
	}
	else {
		$cnt = intval($resar[0]['transactionid']['value']);
		return $cnt;
	}
}


/// Functions ///
function json2ttl($row, $chrom, $pos, $ref, $alt, $out) {
	global $file_position, $genome, $day, $reason, $manage, $transaction_id, $error;
	$tvid = $row[0];
	// Make number id for recent_id
	$tvid_num = intval(str_replace("tgv", "", $tvid));

	$delflg = 0;

	$main_uri = "https://togovar.biosciencedbc.jp/variant/".$tvid;

	$ttl = "<" . $main_uri . ">\n";

	// TogoVar ID
	$ttl .= "  tgv:tgvid \"" . $tvid . "\";\n";

	// TogoVar ID(Number)
	$ttl .= "  tgv:tgvnumber " .$tvid_num.";\n";

	// uri
	$ttl .= "  tgv:accessURL \"" . $main_uri . "\";\n";

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
	$ttl .= "    tgv:delflg " . $delflg . ";\n";
	$ttl .= "  ]; \n";


	// import（空白ノード）
	$ttl .= "  tgv:import [ \n";
	// Transaction ID
	$ttl .= "    tgv:transactionid " . $transaction_id . ";\n";
	// エラー情報
	$ttl .= "    tgv:errorflg " . $error . ";\n";
	// 既存データのファイル位置
	$ttl .= "    tgv:fpos \"" . $file_position . "\";\n";
	// 取り込み日
	$ttl .= "    tgv:issued \"" . $day . "\"^^xsd:date;\n";
	// 取り込み理由
	$ttl .= "    tgv:reason \"" . $reason . "\";\n";
	// 元データ等の管理情報
	$ttl .= "    tgv:origin \"" . $manage . "\";\n";
	// データ最終更新日
	$ttl .= "    tgv:modified \"" . $day . "\"^^xsd:date;\n";
	$ttl .= "  ] \n";

	$ttl .= ".\n\n";
	file_put_contents($out, $ttl, FILE_APPEND);

}
?>


