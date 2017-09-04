<?php
/**
 * Created by PhpStorm.
 * User: stjep
 * Date: 28.5.2017.
 * Time: 13:59
 */
include "../klase/baza.class.php";
$baza = new Baza();
$veza = $baza->Connect();
$upit = $veza->prepare("select korime,kriptirana_lozinka,lozinka,ime,prezime,email,bodovi,tip_korisnika_id,aktiviran,zakljucan from korisnik");
$upit->execute();
$upit->bind_result($korime,$klozina,$lozinka,$ime,$prezime,$email,$bodovi,$tip_korisnika_id,$aktiviran,$zakljucan);
$data = array();
$i = 0;
while($upit->fetch()){
    $data[]=array("korime"=>$korime,"kriptirana_lozinka"=>$klozina,"ime"=>$ime,"prezime"=>$prezime,"email"=>$email,"lozinka"=>$lozinka,
        "bodovi"=>$bodovi,"tip_korisnika_id"=>$tip_korisnika_id,"aktiviran"=>$aktiviran,"zakljucan"=>$zakljucan);
}
echo json_encode($data);
$baza->Disconnect();




