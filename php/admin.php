<?php
require 'klase/baza.class.php';
$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$dnevnik = new Dnevnik();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if(isset($_POST['statistikaispis'])){
        echo  $dnevnik->dajstatistiku();
    }
    if(isset($_POST['obrisistat'])){
        echo $dnevnik->obrisijedstat($_POST['id']);
    }








}
