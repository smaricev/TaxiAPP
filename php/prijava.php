<?php
require 'klase/baza.class.php';

session_start();




if (isset($_SERVER['HTTPS'])) {
    if ($_SERVER['HTTPS'] == "on") {
        $secure_connection = true;
    }
}

$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$vrijeme = new virtualnoVrijeme();
$dnevnik = new Dnevnik();

if ($_SERVER["REQUEST_METHOD"] == "POST" && $secure_connection === true) {

    $povratneInformacije = array();
    $povratneInformacije['greske'] = array();
    if (!isset($_SESSION['unlogged']['pokusaj'])) {
        $_SESSION['unlogged']['pokusaj'] = 1;
        $_SESSION['unlogged']['korime'] = "";
    }

    if (KorExist($_POST['korisnicko_ime'],$povratneInformacije)) {
        if ($_SESSION['unlogged']['pokusaj'] > 2 && $_SESSION['unlogged']['korime'] === $_POST['korisnicko_ime']) {
            $baza = new Baza();
            $veza = $baza->Connect();
            $upit = $veza->prepare("UPDATE korisnik SET zakljucan = 1 WHERE korime =?");
            $upit->bind_param('s', $_POST['korisnicko_ime']);
            $upit->execute();
            $baza->Disconnect();
            $povratneInformacije['greske'][]= "racun zakljucan";
            echo json_encode($povratneInformacije);
            exit();
        }
        if (LozExist($_POST['korisnicko_ime'], $_POST['lozinka'],$povratneInformacije) && $_SESSION['unlogged']['pokusaj'] <= 3) {
            $subject = "Aktivacijski kod";
            if ($_POST['jedankorak'] === "false") {
                if (($_POST['Aktivacijski']) === "") {
                    $aktivacijskiKod = sha1(uniqid(mt_rand() . microtime() . $_POST["korisnicko_ime"], true));
                    $aktivacijskiKod.='P';
                    $sadrzajporuke = "Vas aktivacijski kod je {$aktivacijskiKod} ";
                    SendMailspec($subject, $aktivacijskiKod, ReturnMailspec($_POST['korisnicko_ime']));
                    $baza = new Baza();
                    $con = $baza->Connect();
                    $stvoren = $vrijeme->Vrijeme();
                    $upit = $con->prepare("Insert into aktivacijski_kod (aktivacijski_kod,korisnicko_ime,ts,tip) VALUES (?,?,?,1)");
                    $upit->bind_param("sss",$aktivacijskiKod,$_POST['korisnicko_ime'],$stvoren);
                    $upit->execute();
                    if($upit->affected_rows>0){echo "2step";}
                    $baza->Disconnect();
                    exit();
                } else {
                    $provera = false;
                    $baza = new Baza();
                    $con = $baza->Connect();
                    $upit = "Select* from aktivacijski_kod";
                    $upit = $con->prepare("Select korisnicko_ime,aktivacijski_kod,ts from aktivacijski_kod where tip = 1");
                    $upit->execute();
                    $upit->bind_result($korime1,$aktivacijski1,$logdate);
                    //echo $_POST ['Aktivacijski'];
                    //echo $_POST['korisnicko_ime'];
                    $tip = "";
                    while ($upit->fetch()) {
                        if ($korime1 === $_POST['korisnicko_ime'] && $aktivacijski1 === $_POST['Aktivacijski']) {
                            $provera = true;
                            $vrijemeregd =date_create_from_format('Y-m-d H:i:s',$logdate);
                            $trenutnovrijeme = date_create_from_format('Y-m-d H:i:s',$vrijeme->Vrijeme());
                            if(($vrijemeregd->add(new DateInterval("PT5M")))<$trenutnovrijeme){
                                $provera = false;
                                $povratneInformacije['greske'][]= "istekao login kod";
                            }
                        }
                    }
                    if ($provera) {
                        $upit = $con->prepare("Update aktivacijski_kod set iskoristen=1 where tip = 1 and aktivacijski_kod = ?");
                        $upit->bind_param('s',$_POST['Aktivacijski']);
                        $upit->execute();

                        session_destroy();
                        $idsesije = DajSessionID($_POST['korisnicko_ime']);
                        session_id($idsesije);
                        session_start();
                        $_SESSION['ulogiran'] = "true";
                        $_SESSION['korime'] = $_POST['korisnicko_ime'];
                        $_SESSION['idsesije'] = $idsesije;
                        $_SESSION['tipkorisnika'] = dajTipKorisnika($_POST['korisnicko_ime']);
                        $dnevnik->unosudnevnik($_POST['korisnicko_ime'],"2","Uspjesna prijava korisnika {$_POST['korisnicko_ime']} - dva koraka");
                        $povratneInformacije['logindata'] = array('tipkorisnika' => $_SESSION['tipkorisnika'], 'ulogiran' => 'true', 'korime' => $_POST['korisnicko_ime'], 'idsesije' => $idsesije);
                    };
                    $baza->Disconnect();
                }
            };
            if (($_POST['jedankorak'] === "true")) {
                unesiBodove("20","2",$_POST['korisnicko_ime'],"Uspjesno 1 step logiranje");
                $tip = dajTipKorisnika($_POST['korisnicko_ime']);
                session_destroy();
                $idsesije = DajSessionID($_POST['korisnicko_ime']);
                session_id($idsesije);
                session_start();
                $_SESSION['ulogiran'] = "true";
                $_SESSION['korime'] = $_POST['korisnicko_ime'];
                $_SESSION['idsesije'] = $idsesije;
                $_SESSION['tipkorisnika'] = $tip;
                $dnevnik->unosudnevnik($_POST['korisnicko_ime'],"2","Uspjesna prijava korisnika {$_POST['korisnicko_ime']} - jedan korak");
                $povratneInformacije['logindata'] = array('tipkorisnika' => $tip, 'ulogiran' => 'true', 'korime' => $_POST['korisnicko_ime'], 'idsesije' => $idsesije);
            }


        } else {
            if (!isset($_SESSION['unlogged']['korime'])) {
                $_SESSION['unlogged']['korime'] = $_POST['korisnicko_ime'];
                $_SESSION['unlogged']['pokusaj'] = 1;
                echo json_encode($povratneInformacije);
                exit();
            }

            if ($_POST['korisnicko_ime'] !== $_SESSION['unlogged']['korime']) {
                $_SESSION['unlogged']['korime'] = $_POST['korisnicko_ime'];
                $_SESSION['unlogged']['pokusaj'] = 1;
                echo json_encode($povratneInformacije);
                exit();
            }
            if ($_POST['korisnicko_ime'] === $_SESSION['unlogged']['korime']) {
                $_SESSION['unlogged']['pokusaj']++;
                echo json_encode($povratneInformacije);
                exit();
            }
        }
    }

    if (count($povratneInformacije) > 0) echo json_encode($povratneInformacije);
}


function ReturnMailspec($korime)
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

function SendMailspec($subject, $tekst, $korisnik)
{
    if(!$subject)return;
    $mail_to = $korisnik;
    $mail_subject = $subject;
    $mail_body = $tekst;

    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: WebDiP_2017@foi.hr' . "\r\n";
    if (!mail($mail_to, $mail_subject, $mail_body, $headers)) $povratneInformacije[] = array('greska maila' => true);
}



?>
