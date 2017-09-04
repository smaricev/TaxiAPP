<?php
require 'klase/baza.class.php';

$greske = [];
$baza = new Baza();
$vrijeme = new virtualnoVrijeme();
$dnevnik = new Dnevnik();

/**
 * @param $polje
 * @return bool|string
 */
$greska = function ($polje) use (&$greske) {
    if (array_key_exists($polje, $greske)) {
        return $greske[$polje];
    }

    return false;
};

class Unallowed{
    public $imepolja;
    public $vrijednost;
    function __construct($key1,$value1)
    {
       $this->imepolja = $key1;
       $this->vrijednost = $value1;
    }
}
$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$ime=$prezime="";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $D = array();
    foreach($_POST as $key => $value) {
        if(Checkunalowedchar($value)){
            $nevaljani = new Unallowed($key,$value);
            $D[] = $nevaljani;
        };
    }
    if(strlen($_POST['korisnicko_ime'])<5){
        $greske['korisnicko_ime']="krivo korisnicko ime";
    }

    if(!PasswordCorrect($_POST['lozinka'])){
        $greske['lozinka'] = "kriva lozinka";
    }
    foreach($D as $value){
        $greske[$value->imepolja] = "Nedozvoljeni znak ( ) { } ' ! # “ \ /";
    }
    if($_POST['lozinka'] !== $_POST['potvrda_lozinke']){
        $greske['potvrda_lozinke'] =  "lozinke nisu iste";
    }

    if(!EmailCorrect($_POST['email'])){
        $greske['email'] = "email je kriv";
    }
    if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        //your site secret key
        $secret = $baza->captchasecretkey;
        captchacheck($secret,$_POST['g-recaptcha-response']);

    }
    if(DbExist($_POST['korisnicko_ime'],$_POST['email'])){
        $greske['korisnicko_ime'] =  "postoji u bazi";
    }
    if(empty($greske)){
        $con = $baza->Connect();
        $korimes = $_POST['korisnicko_ime'];
        $lozinkas = $_POST['lozinka'];
        $klozinka = KriptiranaLozinka($lozinkas);
        $imes = $_POST['ime'];
        $prezimes = $_POST['prezime'];
        $emails = $_POST['email'];
        $upit = $con->prepare("insert into korisnik (korime, kriptirana_lozinka, ime, prezime, email, lozinka, tip_korisnika_id, aktiviran, zakljucan) values(?,?,?,?,?,?,3,0,0)");
        $upit->bind_param("ssssss",$korimes,$klozinka,$imes,$prezimes,$emails,$lozinkas);
        $upit->execute();

        if ($upit->affected_rows>0) {
            $dnevnik->unosudnevnik($korimes,"3","Uspjesna Registracija korisnika {$korimes} ");
            $poruka = 'Registracija uspjesna';
            $aktivacijskiKod = sha1(uniqid(mt_rand() . microtime() . $korimes, true));
            $aktivacijskiKod .= 'R';
            $stvoren = $vrijeme->Vrijeme();
            echo "stvoren ". $stvoren;
            $upit1 = $con->prepare("insert into aktivacijski_kod (aktivacijski_kod, korisnicko_ime,ts,tip) VALUES (?,?,?,0)");
            $upit1->bind_param("sss",$aktivacijskiKod,$korimes,$stvoren);
            $upit1->execute();
            $link = "http://barka.foi.hr/WebDiP/2016_projekti/WebDiP2016x075/php/aktivacija.php?aktivacijskiKod={$aktivacijskiKod}";
            $tekst = '<html><body><p>'.'Postovani '.$imes.' '.$prezimes.',<br> Zahvaljujemo se na registraciji kako bi aktivirali vas racun molimo vas da kliknete na logo :) <br> <a href="'.$link.'"><img src="http://barka.foi.hr/WebDiP/2016_projekti/WebDiP2016x075/app/slike/mem.png" width="90" height="125"/> </a></p></body></html>';
            SendMailspec("Aktivacijski kod",$tekst,$emails);

        } else {
            $poruka = 'Registracija nije uspjesna';
        }
    }
    echo json_encode($greske);
}


function SendMailspec($subject, $tekst, $korisnik)
{
    $mail_to = $korisnik;
    $mail_subject = $subject;
    $mail_body = $tekst;

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: WebDiP_2017@foi.hr' . "\r\n";


    if (mail($mail_to, $mail_subject, $mail_body, $headers)) {
        echo("Poslana poruka za: '$mail_to'!");
    } else {
        echo("Problem kod poruke za: '$mail_to'!");
    }

}

?>

