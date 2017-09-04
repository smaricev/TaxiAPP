<?php
require 'klase/baza.class.php';


$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$ime = $prezime = "";
$virtualnov = new virtualnoVrijeme();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['postavivirtualno'])){
        $virtualnov->postaviVrijeme();
        exit();
    }
    if(isset($_POST['dohvativirtualno'])){
        $virtualnov->dohvatiVrijeme();
        exit();
    }
    if(isset($_POST['koristivirtualno'])){
        echo $virtualnov->koristi();
        exit();
    }
    if(isset($_POST['stopvirtualno'])){
        echo $virtualnov->stop();
        exit();
    }



}