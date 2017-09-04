<?php
/**
 * Created by PhpStorm.
 * User: stjep
 * Date: 04/06/2017
 * Time: 06:50
 */
require 'klase/baza.class.php';

$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}
$dnevnik = new Dnevnik();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $korime ="";
    $email="";
    if(isset($_POST['korime']))$korime = $_POST['korime'];
    else{
        echo '0';
        exit();}
    $dnevnik->unosudnevnik($korime,"8","Korisnik {$korime} zahtjeva lozinku");
    $baza = new Baza();
    $baza->Connect();
    $rezultat = $baza->Query("select * from korisnik");
    $data = array();
    $i = 0;
    while ($row = mysqli_fetch_assoc($rezultat)) {
        if ($row['korime'] === $korime) {
            $email = $row['email'];
        }
    }

    if($email!==""){
        $pass =dajpass();
        $kriptpass = KriptiranaLozinka($pass);
        $veza = $baza->Connect();
        $upit = $veza->prepare("Update korisnik set lozinka = ? ,kriptirana_lozinka = ? where korime = ? ");
        $upit->bind_param('sss',$pass,$kriptpass,$korime);
        $upit->execute();
        SendMail1("Zaboravljena lozinka",$pass,$email);
        $dnevnik->unosudnevnik($_POST['korime'],"5","Zaboravljena lozinka poslana korisniku na mail");
        $baza->Disconnect();
        echo "uspjeh";
    }
}


    function SendMail1($subject, $tekst, $korisnik)
    {
        $mail_to = $korisnik;
        $mail_subject = $subject;
        $mail_body = $tekst;

        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: WebDiP_2017@foi.hr' . "\r\n";

        if (!mail($mail_to, $mail_subject, $mail_body, $headers))$povratneInformacije[]=array('greska maila' =>true);
    }

    function dajpass() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }



