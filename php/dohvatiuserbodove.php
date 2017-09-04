<?php
require 'klase/baza.class.php';
$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$dnevnik = new Dnevnik();
$baza = new Baza();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if(isset($_POST['dohvbod'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $upit = $veza->prepare("SELECT bodovi,akcija,id,datvrij,korisnik,opis FROM Bodovi WHERE korisnik = ?");
        $upit->bind_param('s', $_POST['korime']);
        $upit->execute();
        $upit->store_result();
        $upit->bind_result($bodovi, $akcija, $id, $datvrij, $korisnik, $opis);
        $rezultati=[];
        $ukbodovi =0;
        while($upit->fetch()){
            $rezultati[]= ['bodovi'=>$bodovi,'akcija'=>$akcija,'id'=>$id,'datvrij'=>$datvrij,'korisnik'=>$korisnik,'opis'=>$opis];
            $ukbodovi+=$bodovi;
        }
        $baza->Disconnect();
        $obj = (object) [
            'rezultati' => $rezultati,
            'ukbodovi' => $ukbodovi
        ];
        echo(count($rezultati)>0)?json_encode($obj):false;


    }





}