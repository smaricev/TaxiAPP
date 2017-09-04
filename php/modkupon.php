<?php
require 'klase/baza.class.php';



$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$baza = new Baza();
$veza =$baza->Connect();
$vrijeme = new virtualnoVrijeme();
$dnevnik= new Dnevnik();

if(isset($_POST['modkuponizapodrucje'])){

    $upit = $veza->prepare("Select id,cijena,do,od,moderator,kupon_id,podrucje from mod_kupon where podrucje =?");
    $upit->bind_param('s',$_POST['podrucje']);
    $upit->execute();
    $upit->bind_result($id,$cijena,$do,$od,$moderator,$kupon_id,$podrucje);
    $rezultati=[];
    while($upit->fetch()){
        $rezultati[]=array("id"=>$id,"cijena"=>$cijena,"do"=>$do,'od'=>$od,'moderator'=>$moderator,'kupon_id'=>$kupon_id,'podrucje'=>$podrucje);
    }
    $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"9","Zahtjev za dobavljanje moderatorskih kupona za podrucje {$podrucje}");

    echo(json_encode($rezultati))?json_encode($rezultati):false;

}
if(isset($_POST['unesiprodani'])){
    $sifrakupona = sha1(uniqid(mt_rand() . microtime() . $_POST['mod_kupon_id'], true));
    $vrijemestvaranja = $vrijeme->Vrijeme();
    $upit = $veza->prepare("Insert into prodani_kupon (kupac, sifra, datvrij, mod_kupon_id) VALUES (?,?,?,?)");
    $upit->bind_param('ssss',$_POST['kupac'],$sifrakupona,$vrijemestvaranja,$_POST['mod_kupon_id']);
    $upit->execute();
    $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"9","Zahtjev za unosom prodanog kupona");
    echo($upit->affected_rows>0)?"1":"0";


}

if(isset($_POST['prodanikuponi'])){

    $upit = $veza->prepare("Select kupac,sifra,datvrij,mod_kupon_id from prodani_kupon where kupac=? ORDER BY datvrij DESC ");
    $upit->bind_param('s',$_POST['kupac']);
    $upit->execute();
    $upit->bind_result($kupac,$sifra,$datvrij,$mod_kupon_id);
    $rezultati=[];
    while($upit->fetch()){
        $rezultati[]=array('kupac'=>$kupac,'sifra'=>$sifra,'datvri'=>$datvrij,'mod_kupon_id'=>$mod_kupon_id);
    }
    $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"9","Zahtjev za dohvaćanje prodanih kupona");
    echo(json_encode($rezultati))?json_encode($rezultati):false;

}
if(isset($_POST['provjerisifru'])){

    $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"9","Korisnik salje upit dali postoji sifra {$_POST['sifra']}");
    $upit = $veza->prepare("Select sifra from prodani_kupon where sifra=?");
    $upit->bind_param('s',$_POST['sifra']);
    $upit->execute();
    $upit->bind_result($sifra);
    $brredaka = 0;
    while($upit->fetch()){
        $brredaka++;
    }
    echo($brredaka>0)?"1":"0";



}

