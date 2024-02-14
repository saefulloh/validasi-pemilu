<?php
/*
perintah :
php validasi.php 33 

33 adalah kode wilayah untuk jateng
*/

$kode_wilayah = isset($argv[1])? $argv[1] : "01";

function getWilayah($kode){
    $curl = curl_init();

    $path = implode("/",$kode);
    echo $path."\n";
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://sirekap-obj-data.kpu.go.id/wilayah/pemilu/ppwp/'.$path.'.json',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $res = json_decode($response,true);

    return $res;
}
//getWilayah(['33','3304','330412','3304122015']);

function generateWilayah($kode_provinsi){
    $list_tps = [];
    $kabupaten = getWilayah([$kode_provinsi]);
    $myfile = fopen("tps-".$kode_provinsi.".csv", "w") or die("Unable to open file!");
    $txt = "Kabupaten;Kecamatan;Desa;TPS;Kode\n";
    fwrite($myfile, $txt);
    foreach ($kabupaten as $kab) {
        $kecamatan = getWilayah([$kode_provinsi,$kab['kode']]);
        foreach ($kecamatan as $kec) {
            $desa = getWilayah([$kode_provinsi,$kab['kode'],$kec['kode']]);
            foreach ($desa as $des) {
                $tps = getWilayah([$kode_provinsi,$kab['kode'],$kec['kode'],$des['kode']]);
                foreach ($tps as $value) {
                    $txt = $kab['nama'].";".$kec['nama'].";".$des['nama'].";".$value['nama'].";".$value['kode']."\n";
                    fwrite($myfile, $txt);
                }
            }
        }
    }
    fclose($myfile);
}

function getResult($kode){
    $curl = curl_init();

    $path = implode("/",$kode);
    echo $path."\n";
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://sirekap-obj-data.kpu.go.id/pemilu/hhcw/ppwp/'.$path.'.json',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $res = json_decode($response,true);

    return $res;
}
//getResult(['32','3201','320101','3201011008','3201011008022']);

function validasi($kode_provinsi){
    $filename="tps-".$kode_provinsi.".csv";
    echo $filename;
    $file = fopen($filename,"r");
    $list_invalid=[];
    while(! feof($file)) {
        $line = fgets($file);
        $line = trim($line);
        $rec = explode(";",$line);
        $codes = [
                substr($rec[4],0,2),
                substr($rec[4],0,4),
                substr($rec[4],0,6),
                substr($rec[4],0,10),
                $rec[4]
        ];
        $res = getResult($codes);
        //var_dump($res);
        if(!empty($res['chart']) && !empty($total_suara_sah = $res['administrasi'])){
            $total_suara = $res['chart']['100025']+$res['chart']['100026']+$res['chart']['100027'];
            $total_suara_sah = $res['administrasi']['suara_sah'];
            if($total_suara>$total_suara_sah){
                $invalid_data=[$rec[4],$res['chart']['100025'],$res['chart']['100026'],$res['chart']['100027'],$res['administrasi']['suara_sah'],"invalid"];
                array_push($list_invalid,$invalid_data);
            }else{
                //echo "valid";
            }
        }else{
            //echo "Belum terinput";
        }
    }
    fclose($file);

    $myfile = fopen("invalid-".$kode_provinsi.".csv", "w") or die("Unable to open file!");
    $txt = "Kode;Amin;Pragib;Gama;Total;Status\n";
    fwrite($myfile, $txt);
    foreach ($list_invalid as $key => $value) {
        $txt = implode(";",$value);
        fwrite($myfile, $txt."\n");
    }
}
validasi($kode_wilayah);
