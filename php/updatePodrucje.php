<?php
require 'klase/baza.class.php';
$json_input = file_get_contents('php://input');

if ($json_input) {
$_POST = json_decode($json_input, true);
}
$dnevnik = new Dnevnik();
$ime=$prezime="";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
if(isset($_POST['npodrucje']) && isset($_POST['spodrucje'])){
    $npodrucje = $_POST['npodrucje'];
    $spodrucje =$_POST['spodrucje'];

    /*echo "Update podrucje set naziv = ?, drzava = ? where naziv = ? ";
    echo $npodrucje['podrucje']." ".$npodrucje['drzava']." ".$spodrucje['podrucje']." ".$spodrucje['drzava'];*/

$baza = new Baza();
$veza = $baza->Connect();
$upit = $veza->prepare("Update podrucje set naziv = ?, drzava = ? where naziv = ? and drzava = ? ");
$upit->bind_param('ssss',$npodrucje['podrucje'],$npodrucje['drzava'],$spodrucje['podrucje'],$spodrucje['drzava']);
$upit->execute();
$dnevnik->unosudnevnik($_POST['zahtjevsalje'],"5","Promjenjeno podrucje {$spodrucje['podrucje']}");

echo ($upit->affected_rows>0)?'1':'0';
$baza->Disconnect();
}
else{
echo "2";
}

}
