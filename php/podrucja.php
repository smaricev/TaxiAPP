<?php

require "klase/baza.class.php";
$baza = new Baza();
$query = $baza->Connect();
$upit = $query->prepare("select id,drzava,naziv,postanski_broj,regija from podrucje");
$upit->execute();
$upit->bind_result($id,$drzava,$naziv,$postanski_broj,$regija);
$data = array();
while($upit->fetch()){
        $data[]=['id'=>$id,'drzava'=>$drzava,'naziv'=>$naziv,'postanskibroj'=>$postanski_broj,'regija'=>$regija];
}
echo json_encode($data);
$baza->Disconnect();


