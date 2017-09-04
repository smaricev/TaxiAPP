<?php
require 'klase/baza.class.php';
$json_input = file_get_contents('php://input');

if ($json_input) {
    $_POST = json_decode($json_input, true);
}

$ime = $prezime = "";
$dnevnik = new Dnevnik();


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['rezerviraj'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);
        $idulice = getIDUlica($_POST['polaziste'], $idpodrucja);
        $upit = $veza->prepare("INSERT INTO rezervacija (datvri, status, polaziste, odrediste, kupac) VALUES (?,0,?,?,?) ");
        $upit->bind_param('ssss', $_POST['datvri'], $idulice, $_POST['odrediste'], $_POST['kupac']);
        $upit->execute();
        echo ($upit->affected_rows > 0) ? "1" : "0";
        $baza->Disconnect();
        unesiBodove("30","12",$_POST['kupac'],"Uspjesno ostavio rezervaciju!");
        exit();
    }

    if (isset($_POST['popisulica'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        //echo $_POST['podrucje'];
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);
        //echo $idpodrucja;
        if ($idpodrucja) {
            $upit = $veza->prepare("SELECT naziv,id,podrucje FROM ulica WHERE podrucje = ?");
            $upit->bind_param('s', $idpodrucja);
            $upit->execute();
            $upit->bind_result($ulicanaziv, $ulicaid, $ulicapodrucje);
            $ulice = [];
            while ($upit->fetch()) {
                $ulice[] = ['naziv' => $ulicanaziv, 'id' => $ulicaid, 'podrucje' => $ulicapodrucje];
            }
            echo (count($ulice)) ?
                json_encode($ulice) : "0 ";
            $baza->Disconnect();
            exit();
        }
        echo "00";
        $baza->Disconnect();
        exit();
    }
    if (isset($_POST['rezvstatusprom'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $mail = ReturnMail($_POST['korime']);
        $id="";
        $status="";
        if (isset($_POST['zavrid'])) {
            $id = $_POST['zavrid'];
            $status = 3;
        }
        elseif(isset($_POST['odbijid'])){
            $id = $_POST['odbijid'];
            SendMail("Status rezervacije","Vasa Rezervacija je odbijena",$mail);
            $status = 2;
        }
        elseif(isset($_POST['potvrid'])){
            $id = $_POST['potvrid'];
            SendMail("Status rezervacije","Vasa Rezervacija je potvrdena",$mail);
            $status = 1;
        }
        elseif(isset($_POST['resetid'])){
        $id = $_POST['resetid'];
            $status = 0;
        }
        $upit = $veza->prepare("UPDATE rezervacija SET status = ?,vozac = ? WHERE id = ?");
        $upit->bind_param('sss',$status,$_POST['vozac'],$id);
        $upit->execute();
        echo ($upit->affected_rows > 0) ? "1" : "0";
        $baza->Disconnect();
        exit();
    }
    if(isset($_POST['dodajfeedback'])){
        $baza = new Baza();
        $veza = $baza->Connect();
        $upit = $veza->prepare("UPDATE rezervacija SET feedback = ? WHERE id = ?");
        $upit->bind_param('ss',$_POST['feedback'],$_POST['id']);
        $upit->execute();
        echo ($upit->affected_rows > 0) ? "1" : "0";
        $baza->Disconnect();


        $korime = dohvatikorimeizsesije();
        unesiBodove("50","12",$korime,"Korisnik ostavio Feedback");
        exit();
    }


    if (isset($_POST['dohvatirezervacije'])) {
        $baza = new Baza();
        $veza = $baza->Connect();

        $podrucjenaziv = $_POST['podrucje'];

        $idpodr = getIdPodrucja($baza, $podrucjenaziv);
        $ulice = uliceupodrucju($veza, $idpodr);
        $ulicestring = "";
        foreach ($ulice as $ulica) {
            $ulicestring .= $ulica['id'] . ",";
        }

        $upit = $veza->prepare("SELECT id,datvri,feedback,status,polaziste,odrediste,kupac,vozac FROM rezervacija WHERE polaziste IN (?)");
        $upit->bind_param('s', substr($ulicestring, 0, -1));
        $upit->execute();
        $upit->bind_result($id1, $datvri1, $feedback1, $status1, $polaziste1, $odrediste1, $kupac1, $vozac1);
        $rezervacije = [];
        while ($upit->fetch()) {
            $rezervacije[] = ['id' => $id1, 'datvri' => $datvri1, 'feedback' => $feedback1,
                'status' => $status1, 'polaziste' => $polaziste1, 'odrediste' => $odrediste1, 'kupac' => $kupac1, 'vozac' => $vozac1];
        }
        echo (count($rezervacije)) ?
            json_encode($rezervacije) : "0 ";
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"12","Zahtjev za dohvaćanje rezervacija");
        $baza->Disconnect();
        exit();
    }


    if (isset($_POST['brisiulicu'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);
        $upit = $veza->prepare("DELETE FROM ulica WHERE naziv =? AND podrucje= ?");
        $upit->bind_param('ss', $_POST['naziv'], $idpodrucja);
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Zahtjev za brisanje ulica");
        echo ($upit->execute()) ? "1" : "0";
        $baza->Disconnect();
        exit();
    }

    if (isset($_POST['promjeniulicu'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);
        //echo $idpodrucja.$_POST['naziv'].$_POST['snaziv'];
        $upit = $veza->prepare("UPDATE ulica SET naziv = ? WHERE naziv = ? AND podrucje = ? ");
        $upit->bind_param('sss', $_POST['naziv'], $_POST['snaziv'], $idpodrucja);
        $upit->execute();
        echo ($upit->affected_rows > 0) ? "1" : "0";
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Zahtjev za mjenjanje ulica");
        $baza->Disconnect();
        exit();
    }


    if (isset($_POST['stvoriulicu'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);
        if ($idpodrucja) {
            //echo $_POST['imeulice']. $idpodrucja;
            $upit = $veza->prepare("INSERT INTO ulica (naziv, podrucje) VALUES (?,?)");
            $upit->bind_param('ss', $_POST['imeulice'], $idpodrucja);
            echo ($upit->execute()) ? "1" : "0";
            exit();
        }
        echo "00";
        $baza->Disconnect();
        exit();
    }

    if (isset($_POST['modpripada'])) {
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Zahtjev za dohvacanje podrucja kojima moderator pripada");
        $baza = new Baza();
        $veza = $baza->Connect();
        $upit = $veza->prepare("SELECT podrucje FROM moderator_pripada_podrucju WHERE moderator = ?");
        $upit->bind_param('s', $_POST['korime']);
        $upit->execute();
        $upit->bind_result($podrucjeid);
        $podrucja = [];
        $podrucjamoderator = [];
        while ($upit->fetch()) {
            $podrucja[] = ['podrucjeid' => $podrucjeid];
        };
        if (count($podrucja) > 0) {
            $veza1 = $baza->Connect();
            $upit = $veza1->prepare("SELECT naziv FROM podrucje WHERE id = ?");
            foreach ($podrucja as $podrucje) {
                $upit->bind_param('s', $podrucje['podrucjeid']);
                $upit->execute();
                $upit->bind_result($podrucjanaziv1);
                $upit->fetch();
                $podrucjamoderator[] = ['naziv' => $podrucjanaziv1, 'id' => $podrucje['podrucjeid']];
            }
            echo json_encode($podrucjamoderator);
            $baza->Disconnect();
            exit();

        }
        $baza->Disconnect();
        exit();


    }

    if (isset($_POST['podrucje']) and isset($_POST['drzava']) and isset($_POST['moderator']) and !isset($_POST['brisipodrucje']) and !isset($_POST['brisidrzavu'])) {

        $baza = new Baza();
        $veza = $baza->Connect();
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);
        // echo $idpodrucja;

        if (!isset($idpodrucja)) {
            echo "2";
            exit();
        }

        $css = $_POST['moderator'] . $idpodrucja;

        //echo "(css:".$css." podrucje ".$idpodrucja." mod ".$_POST['moderator'].")";
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Zahtjev za dodijeljivanje moderatora podrucju");

        $upit = $veza->prepare("INSERT INTO moderator_pripada_podrucju (moderator,podrucje,css) VALUES (?,?,?)");
        $upit->bind_param('sss', $_POST['moderator'], $idpodrucja, $css);
        $upit->execute();
        echo ($upit->affected_rows > 0) ? '1' : '0';
        $baza->Disconnect();
        exit();
    } elseif (isset($_POST['brisipodrucje']) and isset($_POST['podrucje'])) {
        $baza = new Baza();
        $veza = $baza->Connect();
        $idpodrucja = getIdPodrucja($baza, $_POST['podrucje']);

        //echo "id podrucja ".$idpodrucja."             -";

        $upit = $veza->prepare("DELETE FROM moderator_pripada_podrucju WHERE podrucje = ?");
        $upit->bind_param('s', $idpodrucja);
        $upit->execute();
        echo ($upit->affected_rows > 0) ? '1' : '0';
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Zahtjev za brisanjem  podrucja");

        $upit1 = $veza->prepare("DELETE FROM podrucje WHERE id = ?");
        $upit1->bind_param('s', $idpodrucja);
        $upit1->execute();
        echo ($upit1->affected_rows > 0) ? '1' : '0';

        $baza->Disconnect();
        exit();

    } elseif (isset($_POST['brisidrzavu']) && $_POST['drzava']) {
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Zahtjev za brisanjem  drzave");
        $baza = new Baza();
        $veza = $baza->Connect();
        $drzava = $_POST['drzava'];
        $podrucja = podrucjaUdrzavi($drzava);
        foreach ($podrucja as $podrucje) {
            $id = getIdPodrucja($baza, $podrucje);
            brisiVezumodPodrucje($id, $veza);
        }
        $upit1 = $veza->prepare("DELETE FROM podrucje WHERE drzava = ?");
        $upit1->bind_param('s', $drzava);
        $upit1->execute();
        echo ($upit1->affected_rows > 0) ? '1' : '0';
        $baza->Disconnect();
        exit();
    } else {
        echo "2";
    }

}





