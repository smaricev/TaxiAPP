<?php
require 'klase/baza.class.php';
$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$dnevnik = new Dnevnik();

$ime=$prezime="";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if(isset($_POST['drzava'])  and isset($_POST['naziv'])){
        $baza = new Baza();
        $veza = $baza->Connect();
        $upit = $veza->prepare("Insert into podrucje (drzava,naziv) VALUES (?,?)");
        $upit->bind_param('ss',$_POST['drzava'],$_POST['naziv']);
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"5","Stvoreno Podrucje");
        echo ($upit->execute())?'1':'0';
        $baza->Disconnect();
    }
    else{
        echo "2";
    }

}
