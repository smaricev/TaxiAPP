<?php

require "klase/baza.class.php";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $dnevnik=new Dnevnik();
    $vrijeme = new virtualnoVrijeme();

    $json_input = file_get_contents('php://input');

    if ($json_input) {
        $_POST = json_decode($json_input, true);
    }
    if(isset($_POST['dohvatitip'])){
        $baza = new Baza();
        $veza = $baza->Connect();
        $upit = $veza->prepare("select id,naziv from tip_korisnika");
        $upit->execute();
        $upit->bind_result($id,$naziv);
        $data = array();
        while($upit->fetch()){
            $data[]=array("id"=>$id,"naziv"=>$naziv);
        }
        echo json_encode($data);
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"5","Zahtjev za tipom korisnika");
        $baza->Disconnect();

    }
    if(isset($_POST['promjenitip'])){
        $baza = new Baza();
        $veza = $baza->Connect();
        $upit = $veza->prepare("update korisnik set tip_korisnika_id = ? WHERE korime = ?");
        $upit->bind_param('ss',$_POST['tip'],$_POST['korime']);
        $upit->execute();
        echo($upit->affected_rows>0)?"true":"false";
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"5","Zahtjev za promjenu tip korisnika");
        $baza->Disconnect();
        exit();

    }


    if(isset($_POST['dodajbodove'])){
        echo unesiBodove($_POST['bodovi'],$_POST['akcija'],$_POST['korisnik'],$_POST['opis']);
        exit();
    }

}

elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("select korime,email,tip_korisnika_id from korisnik");
    $upit->execute();
    $upit->bind_result($korime,$email,$tipkorisnika);
    $data = array();
    while($upit->fetch()){
        $data[]=array("korime"=>$korime,"email"=>$email,"tip_korisnika_id"=>$tipkorisnika);
    }
    echo json_encode($data);
    $baza->Disconnect();

}
