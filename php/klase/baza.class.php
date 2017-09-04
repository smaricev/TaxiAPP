<?php

class Baza {
    /*const server = "localhost";
    const korisnik = "WebDiP2016x075";
    const lozinka = "admin_EQCF";
    const baza = "WebDiP2016x075";*/

    public $captchasitekey;
    public $captchasecretkey;

    private $server;
    private $korisnik ;
    private $lozinka ;
    private $baza ;

    private $veza = null;
    private $greska = '';

    function __construct()
    {

        if($_SERVER['HTTP_HOST']==="localhost"){
            $this->server = "localhost";
            $this->korisnik = "root";
            $this->lozinka = "";
            $this->baza = "WebDiP2016x075";
            $this->captchasecretkey = "6Lcy3B8UAAAAAJxvTerw3Y0W9-nS6_IPsgoncDNj";
            $this->captchasitekey = "6Lcy3B8UAAAAAEdfyZDl41QP8djfVu8T1l65r8LG";
        }

        if($_SERVER['HTTP_HOST']==="moops.ddns.net"){
            $this->server = "moops.ddns.net";
            $this->korisnik = "moops";
            $this->lozinka = "ge54ck32o1";
            $this->baza = "WebDiP2016x075";
            $this->captchasecretkey = "6LeGJCMUAAAAAL6rV2eCOOWSbc9bJGxL0PHVIucr";
            $this->captchasitekey = "6LeGJCMUAAAAAAn11fv7DNS3Ch0aXsv0p9aUPDsW";
        }
        if($_SERVER['HTTP_HOST']==="barka.foi.hr"){
            $this->server = "localhost";
            $this->korisnik = "WebDiP2016x075";
            $this->lozinka = "admin_EQCF";
            $this->baza = "WebDiP2016x075";
            $this->captchasecretkey = "6Lcy3B8UAAAAAJxvTerw3Y0W9-nS6_IPsgoncDNj";
            $this->captchasitekey = "6Lcy3B8UAAAAAEdfyZDl41QP8djfVu8T1l65r8LG";
        }
    }

    function Connect() {
        $this->veza = new mysqli($this->server, $this->korisnik, $this->lozinka, $this->baza);
        if ($this->veza->connect_errno) {
            echo "Neuspješno spajanje na bazu: " . $this->veza->connect_errno . ", " .
            $this->veza->connect_error;
            $this->greska = $this->veza->connect_error;
        }
        $this->veza->set_charset("utf8");
        if ($this->veza->connect_errno) {
            echo "Neuspješno postavljanje znakova za bazu: " . $this->veza->connect_errno . ", " .
            $this->veza->connect_error;
            $this->greska = $this->veza->connect_error;
        }
        return $this->veza;
    }

    function Disconnect() {
        $this->veza->close();
    }

    function Query($upit) {
        $rezultat = $this->veza->query($upit);
        if ($this->veza->connect_errno) {
            echo "Greška kod upita: {$upit} - " . $this->veza->connect_errno . ", " .
            $this->veza->connect_error;
            $this->greska = $this->veza->connect_error;
        }
        if (!$rezultat) {
            $rezultat = null;
        }
        return $rezultat;
    }


    
    function PogreskaDB() {
        if ($this->greska != '') {
            return true;
        } else {
            return false;
        }
    }

}

function ReturnMail($korime)
{
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT korime,email FROM korisnik");
    $upit->execute();
    $upit->bind_result($bkorime, $bemail);
    while ($upit->fetch()) {
        if ($bkorime === $korime) {
            $baza->Disconnect();
            return $bemail;
        }
    }
    $baza->Disconnect();
    return false;
}

function SendMail($subject, $tekst, $korisnik)
{
    $mail_to = $korisnik;
    $mail_subject = $subject;
    $mail_body = $tekst;

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: WebDiP_2017@foi.hr' . "\r\n";

    return (mail($mail_to, $mail_subject, $mail_body, $headers))?true:false;

}

function getIdPodrucja(baza $baza, $podrucje)
{
    $rezultat = $baza->Query("SELECT * FROM podrucje");
    $idpodrucja = "";

    while ($row = mysqli_fetch_array($rezultat, MYSQLI_ASSOC)) {
        // echo $row['naziv']." - ".$_POST['podrucje']."\n";
        if ($row['naziv'] == $podrucje) {
            $idpodrucja = $row['id'];
            // echo "uspjeh";
        }
    }
    return $idpodrucja;
}

function getIDUlica($ulicaIme, $ulicaPodrucje)
{
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT id FROM ulica WHERE naziv = ? AND podrucje =?");
    $upit->bind_param('ss', $ulicaIme, $ulicaPodrucje);
    $upit->execute();
    $upit->bind_result($rezultatid);
    $upit->fetch();
    return $rezultatid;

}

function podrucjaUdrzavi($drzava)
{
    $podrucja = [];
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT naziv FROM podrucje WHERE drzava = ?");
    $upit->bind_param('s', $drzava);
    $upit->execute();
    $upit->bind_result($podrucje);
    while ($upit->fetch()) {
        $podrucja[] = $podrucje;
    };
    echo (array_count_values($podrucja) > 0) ? '1' : '0';
    return $podrucja;
}

function brisiVezumodPodrucje($podrucje, mysqli $veza)
{
    $upit = $veza->prepare("DELETE FROM moderator_pripada_podrucju WHERE podrucje = ?");
    $upit->bind_param('s', $podrucje);
    $upit->execute();
    echo ($upit->affected_rows > 0) ? '1' : '0';

}

function uliceupodrucju(mysqli $veza, $idpodrucja)
{
    $upit = $veza->prepare("SELECT naziv,id,podrucje FROM ulica WHERE podrucje = ?");
    $upit->bind_param('s', $idpodrucja);
    $upit->execute();
    $upit->bind_result($ulicanaziv, $ulicaid, $ulicapodrucje);
    $ulice = [];
    while ($upit->fetch()) {
        $ulice[] = ['naziv' => $ulicanaziv, 'id' => $ulicaid, 'podrucje' => $ulicapodrucje];
    }
    return $ulice;
}


function provjeri_podatke($imepodatka)
{
    $message = 'Error uploading file';

    if (!isset($_FILES[$imepodatka])) {
        return "{$imepodatka} ne postoji u Files";
    }
    switch ($_FILES[$imepodatka]['error']) {
        case UPLOAD_ERR_OK:
            $message = false;;
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $message .= ' - file too large (limit of ' . return_bytes(ini_get('upload_max_filesize')) . ' bytes).';
            break;
        case UPLOAD_ERR_PARTIAL:
            $message .= ' - file upload was not completed.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $message .= ' - zero-length file uploaded.';
            break;
        default:
            $message .= ' - internal error #' . $_FILES['newfile']['error'];
            break;
    }
    return $message;

}

function Delete($path)
{
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file) {
            Delete(realpath($path) . '/' . $file);
        }

        return rmdir($path);
    } else if (is_file($path) === true) {
        return unlink($path);
    }

    return false;
}

function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}


function getIdKupona(mysqli $veza, $kuponime)
{
    $upit = $veza->prepare("SELECT id FROM admin_kupon WHERE naziv = ?");
    $upit->bind_param('s', $kuponime);
    $upit->execute();
    $upit->bind_result($id);
    $upit->fetch();
    return $id;
}


function getModIdKupona(mysqli $veza, $id)
{
    $upit = $veza->prepare("SELECT id FROM mod_kupon WHERE kupon_id = ?");
    $upit->bind_param('s', $id);
    $upit->execute();
    $upit->bind_result($id1);
    $izlaz=[];
    while ($upit->fetch()) {
        $izlaz[] = ['mod_kuponid' => $id1];
    };
    return $izlaz;
}

function LozExist($korime, $lozinka,&$povratneInformacije)
{
    $kriptloz = KriptiranaLozinka($lozinka);
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT korime,kriptirana_lozinka FROM korisnik");
    $upit->execute();
    $upit->bind_result($bkorime, $bklozinka);
    while ($upit->fetch()) {
        if ($bkorime === $korime && $bklozinka === $kriptloz) {
            $baza->Disconnect();
            return true;
        }
    }
    $baza->Disconnect();
    $povratneInformacije['greske'][]= "lozinka ne odgovara";
    return false;
}

function KorExist($korime,&$povratneInformacije)
{
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT korime,zakljucan,aktiviran FROM korisnik");
    $upit->execute();
    $upit->bind_result($bkorime, $bzakljucan, $baktiviran);
    while ($upit->fetch()) {
        if ($bkorime === $korime) {
            if ($bzakljucan == 1){ $povratneInformacije['greske'][]= "zakljucan";return false;}
            if ($baktiviran == 0) {$povratneInformacije['greske'][]= "nijeaktiviran";return false;}
            if ($bzakljucan == 0 and $baktiviran === 1) {
                $baza->Disconnect();
                return true;
            }
        }
    }
    $baza->Disconnect();
    $povratneInformacije['greske'][]= "Korisnik ne postoji";
    return false;
}


function DajSessionID($input)
{
    return sha1("694200{$input}694200");
}
function KriptiranaLozinka($lozinka){
    return sha1("314882{$lozinka}371115");
}

function dajTipKorisnika($korime){
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT tip_korisnika_id FROM korisnik where korime = ?");
    $upit->bind_param('s',$korime);
    $upit->execute();
    $upit->bind_result($btipkor);
    $upit->fetch();
    $baza->Disconnect();
    return $btipkor;
}

function captchacheck($secret,$secretresponse){
    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $secretresponse);
    $responseData = json_decode($verifyResponse);
    if(!$responseData->success){
        $greske['captcha'] = "neuspjesna verifikacija";
    }

    //forma['g-recaptcha-response'] = vcRecaptchaService.getResponse();


}



function Checkunalowedchar($subject){
    $illegal = "(){}'!#“/\\";
    if(strpbrk($subject,$illegal))return true;
    return false;
};
function PasswordCorrect($subject){
    $regex = '/(?=.*?\d)(?=(.*?[A-Z]){2})(?=(.*?[a-z]){2}).{5,15}/';
    if(preg_match($regex,$subject))return true;
    else return false;
}
function EmailCorrect($email){
    $regex = '/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';
    if(preg_match($regex,$email))return true;
    else return false;
}

function DbExist($korime,$mail){
    $baza = new Baza();
    $con = $baza->Connect();
    $upit = $con->prepare("Select korime,email from korisnik");
    $upit->bind_result($korim1,$email1);
    while($upit->fetch()){
        if($korim1 === $korime or $email1 === $mail)return true;
    }
    $baza->Disconnect();
    return false;
}
function pobrisiNepostojeceKupone(){
    $baza = new baza();
    $veza = $baza->connect();
    $upit = $veza->prepare("select naziv from admin_kupon");
    $upit->execute();
    $upit->bind_result($naziv);
    $imenakupona = array();
    while($upit->fetch()){
        $imenakupona[] = $naziv;
    }
    $target_dir = __dir__ . "/upload/adminkupon";

    $directories = glob($target_dir . '/*' , GLOB_ONLYDIR);

    foreach($directories as $dir){
        $dirname = substr($dir, strrpos($dir, '/') + 1);
        if(!in_array($dirname,$imenakupona))delete($target_dir."/".$dirname);
    }
}
function obrisiPostojeciFile($imekupona, $tipzabrisanje){
    $gendir = __DIR__ . "/upload/adminkupon";
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("Select {$tipzabrisanje} from admin_kupon WHERE naziv = ?");
    $upit->bind_param('s', $imekupona);
    $upit->execute();
    $upit->bind_result($putanja);
    $upit->fetch();
    if(is_file($gendir.$putanja)){
    unlink($gendir.$putanja);
    }

}

function modkupon1whereparam($param1,$param2)
{
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("SELECT id,cijena,do,od,moderator,kupon_id,podrucje FROM mod_kupon WHERE ?=?");
    $upit->bind_param('s', $param1, $param2);
    $upit->execute();
    $upit->bind_result($id, $cijena, $do, $od, $moderator, $kupon_id, $podrucje);
    $rezultati = [];
    while ($upit->fetch()) {
        $rezultati[] = array("id" => $id, "cijena" => $cijena, "do" => $do, 'od' => $od, 'moderator' => $moderator, 'kupon_id' => $kupon_id, 'podrucje' => $podrucje);
    }
}


/**
 * @param $bodovi
 * @param $akcija
 * @param $korisnik
 * @param $opis
 * @return string
 */
/*
 "dodajbodove":"true",
 "bodovi":"5",
 "akcija":"9",
 "korisnik":"moops",
 "opis":"Neki opis"
  *
  */
function unesiBodove($bodovi, $akcija, $korisnik, $opis)
{
    $vrijeme1 = new virtualnoVrijeme();
    $vrijeme = $vrijeme1->Vrijeme();
    $baza = new Baza();
    $veza = $baza->Connect();
    $upit = $veza->prepare("INSERT INTO Bodovi(bodovi, akcija, datvrij, korisnik, opis) VALUES (?,?,?,?,?) ");
    $upit->bind_param('sssss',$bodovi,$akcija,$vrijeme,$korisnik,$opis);
    $upit->execute();
    $upit->store_result();
    $baza->Disconnect();
    return ($upit->affected_rows>0)?"true":"false";
}

function dohvatikorimeizsesije(){
    if(isset($_SESSION['idsesije'])){
        return $_SESSION['korime'];
    }
    else return  false;
}

class virtualnoVrijeme
{


    function postaviVrijeme()
    {
        $url = "http://barka.foi.hr/WebDiP/pomak_vremena/pomak.php?format=json";
        $json = file_get_contents($url);
        $obj = json_decode($json);
        $pomak = $obj->WebDiP->vrijeme->pomak->brojSati;
        $baza = new Baza();
        $veza = $baza->Connect();
        $date = (new DateTime())->format('Y-m-d H:i:s');
        $virtualno = (new DateTime())->add(new DateInterval("PT{$pomak}H"))->format('Y-m-d H:i:s');
        $query = $veza->prepare("INSERT INTO virtualno_vrijeme (id,pomak, stvarno_vrijeme,virtualno,aktivno) VALUES (DEFAULT ,?,?,?,0)");
        $query->bind_param('sss', $pomak, $date, $virtualno);
        $query->execute();
        echo ($query->affected_rows > 0) ? "1" : "0";

    }

    function dohvatiVrijeme()
    {
        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("SELECT pomak,stvarno_vrijeme,virtualno FROM virtualno_vrijeme WHERE aktivno = 1 ORDER BY id DESC LIMIT 0,1 ");
        $query->execute();
        $query->bind_result($pomak, $stvarno_vrijeme, $virtualno);
        $povr = [];
        while ($query->fetch()) {
            $povr[] = ['pomak' => $pomak, 'stvarno_vrijeme' => $stvarno_vrijeme, 'virtualno' => $virtualno];
        }
        echo json_encode($povr);
    }

    function Vrijeme()
    {
        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("SELECT pomak,stvarno_vrijeme,virtualno FROM virtualno_vrijeme WHERE aktivno = 1 ORDER BY id DESC LIMIT 1");
        $query->execute();
        $query->bind_result($pomak, $stvarno_vrijeme, $virtualno);
        $dohv = 0;
        while ($query->fetch()) {
            $dohv++;
        };
        $query->store_result();
        if ($dohv == 1) return $virtualno;
        else return (new DateTime())->format('Y-m-d H:i:s');
    }

    function VrijemeD()
    {

        return date_create_from_format('Y-m-d H:i:s', $this->Vrijeme());
    }

    function stop()
    {
        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("UPDATE virtualno_vrijeme SET aktivno = 0 ");
        $query->execute();
        return ($query->affected_rows > 0) ? "1" : "0";

    }

    function koristi()
    {
        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("UPDATE virtualno_vrijeme SET aktivno = 1 ORDER BY id DESC LIMIT 1 ");
        $query->execute();
        return ($query->affected_rows > 0) ? "1" : "0";
    }




}

Class Dnevnik{

    function unosudnevnik($korisnik,$tipakcije,$pojedinosti){
        $vrieme= new virtualnoVrijeme();
        $vunos = $vrieme->Vrijeme();

        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("Insert into dnevnik (datvrij, korisnik, tip_akcije, ostalo) VALUES (?,?,?,?)");
        $query->bind_param('ssss',$vunos,$korisnik,$tipakcije,$pojedinosti);
        $query->execute();
        $dohv = 0;
        while ($query->fetch()) {
            $dohv++;
        };
    }


    function dajstatistiku(){
        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("select d.id,datvrij, korisnik, tip_akcije, ostalo,naziv from dnevnik d JOIN tip_akcije t on d.tip_akcije = t.id ORDER BY datvrij DESC ");
        $query->execute();
        $query->store_result();
        $query->bind_result($id,$datvrij, $korisnik, $tip_akcije, $ostalo,$tip_naziv);
        $vrati = [];
        while($query->fetch()){
            $vrati[]=['id'=>$id,'datvrij'=>$datvrij,'korisnik'=>$korisnik,'tip_akcije'=> $tip_akcije, 'ostalo'=>$ostalo,'tip_naziv'=>$tip_naziv];
        }
        return json_encode($vrati);

    }

    function obrisijedstat($id){
        $baza = new Baza();
        $veza = $baza->Connect();
        $query = $veza->prepare("Delete from dnevnik where id = ? ");
        $query->bind_param('s',$id);
        $query->execute();
        $query->store_result();
        return($query->affected_rows>0)?"1":"0";
    }






}




