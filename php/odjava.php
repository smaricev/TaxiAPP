<?php
require 'klase/baza.class.php';


$json_input = file_get_contents('php://input');

if ($json_input) {
$_POST = json_decode($json_input, true);
}
$dnevnik = new Dnevnik();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['idsesije'])){
        session_id($_POST['idsesije']);
        session_start();
        session_destroy();
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"3","sesija korisnika {$_POST['zahtjevsalje']} izbrisana");
        echo "SessionEND";

    }

    else{echo "error";}
}
else{
    echo "error";
}
