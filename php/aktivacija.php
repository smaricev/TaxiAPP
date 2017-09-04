<?php

require 'klase/baza.class.php';

$baza = new Baza();
$veza = $baza->Connect();
$vrijeme = new virtualnoVrijeme();



$aktivacijskiKod = $_GET['aktivacijskiKod'];
$upit = $veza->prepare("SELECT korisnicko_ime,ts FROM aktivacijski_kod WHERE aktivacijski_kod = ? AND iskoristen = 0 AND tip = 0");
$upit->bind_param('s',$aktivacijskiKod);
$upit->execute();
$upit->bind_result($korime,$vrijemereg);
$brredaka= 0;
while($upit->fetch()){
    $brredaka++;
};

if ($brredaka == 0) {
    echo 'Aktivacijski kod iskoristen ili ne postoji';
    exit();
}


echo  $brredaka;

if ($brredaka>0) {
    $korisnickoIme = $korime;
    $vrijemeregd =date_create_from_format('Y-m-d H:i:s',$vrijemereg);
    $trenutnovrijeme = date_create_from_format('Y-m-d H:i:s',$vrijeme->Vrijeme());
    if(($vrijemeregd->add(new DateInterval("PT5H")))<$trenutnovrijeme){
        echo 'aktivacijski link istekao';
        exit();
    }
    echo $korisnickoIme;
    $upit1 = $veza->prepare("UPDATE korisnik set aktiviran=1 WHERE korime=? AND aktiviran = 0");
    $upit1->bind_param('s',$korisnickoIme);
    $upit1->execute();
    $brred=0;


    if ($upit->affected_rows==-1) {
        $upit2 = $veza->prepare("UPDATE aktivacijski_kod SET iskoristen=1 WHERE aktivacijski_kod=? and tip = 0");
        $upit2->bind_param('s',$aktivacijskiKod);
        $upit2->execute();


        if ($upit->affected_rows==-1) {
            header('Location: http://barka.foi.hr/WebDiP/2016_projekti/WebDiP2016x075/app');
        }
    } else {
        echo 'Korisnik nije aktiviran';
        exit();
    }

}

echo 'Pogreska na serveru';