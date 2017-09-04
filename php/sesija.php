<?php
require 'klase/baza.class.php';


$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$dnevnik = new Dnevnik();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if(isset( $_POST['idsesije'])){
        $idsesije = $_POST['idsesije'];
        session_id($idsesije);
        session_start();
        if($idsesije === "nereganikorisnik"){
            echo 'false';
            exit();
        }
        if(isset($_SESSION['idsesije'])){
            $vrati = array('korime'=>$_SESSION['korime'],'tipkorisnika'=>$_SESSION['tipkorisnika']);
            echo json_encode($vrati);
        }
        else{echo 'false';}
    }
    else echo 'false';

}