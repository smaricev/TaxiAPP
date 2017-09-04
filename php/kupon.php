<?php
require 'klase/baza.class.php';
$baza = new Baza();
$veza = $baza->Connect();
$dnevnik = new Dnevnik();


pobrisiNepostojeceKupone();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($_SERVER["CONTENT_TYPE"] === "application/x-www-form-urlencoded") {
        $json_input = file_get_contents('php://input');
        if ($json_input) {
            $_POST = json_decode($json_input, true);
        }
    }


    if (intval($_SERVER['CONTENT_LENGTH']) > 0 && count($_POST) === 0) {
        throw new Exception('PHP discarded POST data because of request exceeding post_max_size.');
    }

    if (isset($_POST['stvorimodkupon'])) {
        echo "usao";
        $kuponid = getIdKupona($veza, $_POST['kupon']);
        $upit = $veza->prepare("INSERT INTO mod_kupon (cijena, od, do, moderator, kupon_id, podrucje) VALUES (?,?,?,?,?,?)");
        $upit->bind_param('ssssss', $_POST['cijena'], $_POST['od'], $_POST['do'], $_POST['korime'], $kuponid, $_POST['podrucje']);
        $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"11","Moderator/Admin stvorio moderatorski kupon ");
        echo ($upit->execute()) ? "1" : "0";
    }


    if (isset($_POST['delete'])) {

        $upit = $veza->prepare("DELETE FROM admin_kupon WHERE naziv = ? ");
        $upit->bind_param('s', $_POST['name']);
        if ($upit->execute()) {
            $target_dir = __DIR__ . "/upload/adminkupon/" . $_POST['name'];
            Delete($target_dir);
            echo "1-deleted";
            pobrisiNepostojeceKupone();
            $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin pobrisao kupon {$_POST['name']}");

        }
        exit();
    }

    $slikaProv = provjeri_podatke("slika");
    $pdfProv = provjeri_podatke("pdf");
    $videoProv = provjeri_podatke("video");

    // echo $slikaProv ." ".$pdfProv." ".$videoProv;


    if (isset($_POST['kreiraj'])) {
        //echo "usao2";
        //echo $_POST['name']." ".$_POST['korime'];
        $upit = $veza->prepare("INSERT INTO admin_kupon (naziv,admin) VALUES (?,?)");
        $upit->bind_param('ss', $_POST['name'], $_POST['korime']);
        if ($upit->execute()) {
            echo '1';
            $name = $_POST['name'];
            $target_dir = __DIR__ . "/upload/adminkupon/" . $name;
            mkdir($target_dir, 0777);
            $dir2 = "/" . $name . "/";

            if (!$slikaProv) {
                //ako je uploadana slika

                if (!is_uploaded_file($_FILES['slika']['tmp_name'])) {
                    $message = 'Error uploading file - unknown error.';
                    exit();
                }
                $target_file_slika = $target_dir . "/" . basename($_FILES["slika"]["name"]);
                $filepath = $dir2 . basename($_FILES["slika"]["name"]);
                if (!move_uploaded_file($_FILES["slika"]["tmp_name"], $target_file_slika)) {
                    echo "0";
                    exit();
                }
                $upit = $veza->prepare("UPDATE admin_kupon SET slika=? WHERE naziv = ?");
                $upit->bind_param('ss', $filepath, $_POST['name']);
                echo ($upit->execute()) ? '1' : '0';
            } elseif (!$pdfProv) {
                //ako je uploadan pdf

                if (!is_uploaded_file($_FILES['pdf']['tmp_name'])) {
                    $message = 'Error uploading file - unknown error.';
                    exit();
                }
                $target_file_pdf = $target_dir . "/" . basename($_FILES["pdf"]["name"]);
                $filepath = $dir2 . basename($_FILES["pdf"]["name"]);
                if (!move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_file_pdf)) {
                    echo "0";
                    exit();
                }
                $upit = $veza->prepare("UPDATE admin_kupon SET pdf=? WHERE naziv = ?");
                $upit->bind_param('ss', $filepath, $_POST['name']);
                $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin kreirao kupon  {$_POST['name']} bez videa");
                echo ($upit->execute()) ? '1' : '0';

            } elseif (!$videoProv) {
                // ako je uploadan video

                if (!is_uploaded_file($_FILES['video']['tmp_name'])) {
                    $message = 'Error uploading file - unknown error.';
                    exit();
                }
                $target_file_video = $target_dir . "/" . basename($_FILES["video"]["name"]);
                $filepath = $dir2 . basename($_FILES["video"]["name"]);
                if (!move_uploaded_file($_FILES["video"]["tmp_name"], $target_file_video)) {
                    echo "0";
                    exit();
                }
                $upit = $veza->prepare("UPDATE admin_kupon SET video=? WHERE naziv = ?");
                $upit->bind_param('ss', $filepath, $_POST['name']);
                $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin kreirao kupon {$_POST['name']} sa videom");
                echo ($upit->execute()) ? '1' : '0';

            }


        } else echo "3";

    } elseif (isset($_POST['update'])) {

        $name = $_POST['name'];
        $target_dir = __DIR__ . "/upload/adminkupon/" . $name;
        $dir2 = "/" . $name . "/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777);
        if (!$slikaProv) {

            obrisiPostojeciFile($_POST['name'], "slika");

            if (!is_uploaded_file($_FILES['slika']['tmp_name'])) {
                $message = 'Error uploading file - unknown error.';
                exit();
            }
            $target_file_slika = $target_dir . "/" . basename($_FILES["slika"]["name"]);
            $filepath = $dir2 . basename($_FILES["slika"]["name"]);
            if (!move_uploaded_file($_FILES["slika"]["tmp_name"], $target_file_slika)) {
                echo "0";
                exit();
            }
            $upit = $veza->prepare("UPDATE admin_kupon SET slika=? WHERE naziv = ?");
            $upit->bind_param('ss', $filepath, $_POST['name']);
            $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin dodao/promjenio sliku na kuponu {$_POST['name']}");
            echo ($upit->execute()) ? '1' : '0';
        } elseif (!$pdfProv) {
            obrisiPostojeciFile($_POST['name'], "pdf");

            if (!is_uploaded_file($_FILES['pdf']['tmp_name'])) {
                $message = 'Error uploading file - unknown error.';
                exit();
            }
            $target_file_pdf = $target_dir . "/" . basename($_FILES["pdf"]["name"]);
            $filepath = $dir2 . basename($_FILES["pdf"]["name"]);
            if (!move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_file_pdf)) {
                echo "0";
                exit();
            }
            $upit = $veza->prepare("UPDATE admin_kupon SET pdf=? WHERE naziv = ?");
            $upit->bind_param('ss', $filepath, $_POST['name']);
            $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin dodao/promjenio pdf na kuponu {$_POST['name']}");
            echo ($upit->execute()) ? '1' : '0';

        } elseif (!$videoProv) {
            obrisiPostojeciFile($_POST['name'], "video");


            if (!is_uploaded_file($_FILES['video']['tmp_name'])) {
                $message = 'Error uploading file - unknown error.';
                exit();
            }
            $target_file_video = $target_dir . "/" . basename($_FILES["video"]["name"]);
            $filepath = $dir2 . basename($_FILES["video"]["name"]);
            if (!move_uploaded_file($_FILES["video"]["tmp_name"], $target_file_video)) {
                echo "0";
                exit();
            }
            $upit = $veza->prepare("UPDATE admin_kupon SET video=? WHERE naziv = ?");
            $upit->bind_param('ss', $filepath, $_POST['name']);
            $dnevnik->unosudnevnik($_POST['zahtjevsalje'],"10","Admin dodao/promjenio video na kuponu {$_POST['name']}");
            echo ($upit->execute()) ? '1' : '0';
        }
    }


} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {

    $upit = $veza->prepare("SELECT id,naziv,pdf,slika,video,admin FROM admin_kupon");
    $upit->execute();
    $upit->bind_result($id,$naziv, $pdf, $slika, $video, $admin);
    $izlaz = [];
    while ($upit->fetch()) {
        $izlaz[] = ['id'=>$id,'naziv' => $naziv, 'pdf' => $pdf, 'slika' => $slika, 'video' => $video, 'admin' => $admin];
    };
    echo json_encode($izlaz);
}


$baza->Disconnect();

/**
 * @param $imekupona (ime kupona) /string
 * @param $tipzabrisanje (slika/pdf/video) /string
 */
