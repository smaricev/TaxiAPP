<?php
require 'klase/baza.class.php';
$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$baza = new Baza();
$veza = $baza->Connect();
$dnevnik = new Dnevnik();
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if(isset($_POST['zakljucaj'])){
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin zahtjeva zakljucavanje racuna korisnika {$_POST['korime']}");
        $upit = $veza->prepare("Update korisnik set zakljucan = 1 where korime = ?");
        $upit->bind_param("s",$_POST['korime']);
        $upit->execute();
        echo($upit->affected_rows)?"1":"0";
        $baza->Disconnect();
        exit();
    }
    if(isset($_POST['otkljucaj'])){
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin zahtjeva otkjucavanje racuna korisnika {$_POST['korime']}");
        $upit = $veza->prepare("Update korisnik set zakljucan = 0 where korime = ?");
        $upit->bind_param("s",$_POST['korime']);
        $upit->execute();
        echo($upit->affected_rows)?"1":"0";
        $baza->Disconnect();
        exit();
    }
    if(isset($_POST['obrisi'])){
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin zahtjeva brisanje racuna korisnika {$_POST['korime']}");
        $upit = $veza->prepare("Delete from korisnik where korime = ?");
        $upit->bind_param("s",$_POST['korime']);
        $upit->execute();
        echo($upit->affected_rows)?"1":"0";
        $baza->Disconnect();
        exit();
    }
    else echo "kriva naredba";

}
