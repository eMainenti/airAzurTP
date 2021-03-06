<?php

//connexion a la bdd
include "connectPDO.php";

//fonction de recuperation de la liste des vols
function getLesVols() {

    //creation d'un objet PDO
    $connexion = connect();
    
    $i = 0;

        //requete sql pour recuperer les vols disponible pour la reservation
        $sql = "Select idVols, A1.ville as aeroportDepart, A2.ville as aeroportArrivee, dateDepart, dateArrivee, prix, place
                    from vols JOIN aeroport as A1 ON vols.aeroportDepart=A1.idAeroport JOIN aeroport as A2 ON vols.aeroportArrivee=A2.idAeroport
                    where place>0";

        //execution de la requete
        $resultatVol = $connexion->query($sql);
    
    try {
        //creation d'un tableau
        /*$lesVols = array();
        //index
        $i = 0;

        //requete sql pour recuperer les vols disponible pour la reservation
        $sql = "elect idVols, A1.ville as aeroportDepart, A2.ville as aeroportArrivee, dateDepart, dateArrivee, prix, place
                    from vols JOIN aeroport as A1 ON vols.aeroportDepart=A1.idAeroport JOIN aeroport as A2 ON vols.aeroportArrivee=A2.idAeroport
                    where place>0";
*/
        //execution de la requete
        $resultatVol = $connexion->query($sql);

        //parcours des resultats et stockage dans tableau        
        while ($unVol = $resultatVol->fetch(PDO::FETCH_ASSOC))
        {
            $lesVols[$i] = $unVol;
            $i++;
        }
        
        return $lesVols;

    }
    catch (PDOException $e)
    {
        echo "<br />Erreur dans la requête " . $e->getMessage()."<br /><br /><br />";
    }
}

//fonction de recuperation d'un vol
function getLeVol($idVol){
    
    //creation d'un objet PDO
    $connexion = connect();

    try
    {
       $sql = "select idVols, A1.ville as aeroportDepart, A2.ville as aeroportArrivee, dateDepart, dateArrivee, prix, place
                    from vols JOIN aeroport as A1 ON vols.aeroportDepart=A1.idAeroport JOIN aeroport as A2 ON vols.aeroportArrivee=A2.idAeroport
                    where idVols = '$idVol'";
       
       $resultat = $connexion->query($sql);
       
       return $resultat->fetch(PDO::FETCH_ASSOC);
       
    } 
    catch (PDOException $e)
    {
        echo "Erreur dans la requête";
    }
}


//fonction de reservation dans la bdd
function ajoutReservation() {
    //connexion a la bdd
    $connexion = connect();
    
    try {
        //requete securise
        $sql = "insert into reservation(nomClient,prenomClient,adresseClient,codePostalClient,villeClient,telClient,nbPlaceReservee,prixTotal,idVols)"
                . "values(:nom,:prenom,:adresse,:CP,:ville,:numTel,:placePrise,:prixTotal,:idVol)";
        
        //ajouter les réservations dans la BDD
        $preparation = $connexion->prepare($sql);
        $preparation->execute($_SESSION["reservation"]);
        decrementerVol();
        
    } catch (PDOException $e) {
        return "Erreur dans la requête " . $e->getMessage();
    }
}

//fonction pour calculer le prix total des places
function prixTotal($nbrPlace) {
    return $nbrPlace * $_SESSION["vol"]["prix"];
}

//fonction pour mettre des champs en variable de session
//parametre : tableau
function setVariableSession($champsVol) {
    foreach ($champsVol as $indice => $vol) {
        //ajoute variable session
        $_SESSION[$indice] = $vol;
    }
}

//fonction de recuperation de la liste des reservations
function getReservations() {

    //creation d'un objet PDO
    $connexion = connect();

    try {

        //requete sql pour recuperer les reservations
        $sql = "select idReservation, idVols, nomClient, prenomClient, adresseClient, codePostalClient,villeClient,telClient,prixTotal,nbPlaceReservee "
                . " from reservation"
                . " where idReservation in (".$_COOKIE['reservation'].")";
        //creation d'un tableau
        $lesRes = array();
        //index
        $i = 0;

        //execution de la requete
        $resultatRes = $connexion->query($sql);
        //return $resultat = $connexion->query($sql);
        //parcours des resultats et stockage dans tableau
        while ($res = $resultatRes->fetch(PDO::FETCH_OBJ)) {
            $unRes = array(
                "idReservation" => $res->idReservation,
                "idVols" => $res->idVols,
                "nomClient" => $res->nomClient,
                "prenomClient" => $res->prenomClient,
                "adresseClient" => $res->adresseClient,
                "codePostalClient" => $res->codePostalClient,
                "villeClient" => $res->villeClient,
                "telClient" => $res->telClient,
                "prixTotal" => $res->prixTotal,
                "nbPlaceReservee" => $res->nbPlaceReservee
            );
            //ecriture d'un vol dans le tableau a renvoyer
            $lesRes[$i] = $unRes;
            $i++;
        }
        return $lesRes;
    } catch (PDOException $e) {
        return "Erreur dans la requête " . $e->getMessage();
    }
}

//fonction pour récuperer les infos d'une seule reservation
function getLaReservation($id) {
    //création pdo
    $connexion = connect();
  
    
    try {
        //requete sql pour récuperer la réservation
        $sql = "select idReservation, idVols, nomClient, prenomClient, adresseClient, codePostalClient,villeClient,telClient,prixTotal,nbPlaceReservee "
                . "from reservation "
                . "where idReservation=" .$id;

        //execution de la requête 
        
        $resultat = $connexion->query($sql);
        $res=$resultat->fetch(PDO::FETCH_OBJ);

        //recuperation des résultats
        $reservation = array(
            "idReservation" => $res->idReservation,
            "idVols" => $res->idVols,
            "nomClient" => $res->nomClient,
            "prenomClient" => $res->prenomClient,
            "adresseClient" => $res->adresseClient,
            "codePostalClient" => $res->codePostalClient,
            "villeClient" => $res->villeClient,
            "telClient" => $res->telClient,
            "prixTotal" => $res->prixTotal,
            "nbPlaceReservee" => $res->nbPlaceReservee
        );
        return $reservation;
    } catch (Exception $ex) {
        return "Erreur dans la requête " . $ex->getMessage();
    }
}

//fonction pour decremeter le nombre de place disponible dans un vol apres reservation
function decrementerVol() {

    //creation d'un objet PDO
    $connexion = connect();

    try {
        //requete sql pour decrementer le nombre de place disponible
        $sql = "update vols "
                . "set place = place - " . $_SESSION["reservation"]['placePrise']
                . " where idVols = '" . $_SESSION["reservation"]['idVol'] . "'";


        //execution de la requete
        $connexion->query($sql);
    } catch (PDOException $e) {
        echo "Erreur dans la requête sql : " . $e->getMessage();
    }
}

//fonction pour initialiser une session panier
function initSession($nom) {
    if (!isset($_SESSION[$nom])) {
        $_SESSION[$nom] = array();
    }
}

//fonction qui ajoute un tableau dans un panier
function ajouterAuPanier($nomSession, $tableauAjouter) {
    $_SESSION[$nomSession] = $tableauAjouter;
}

//Stockage des vols reserve par l'agence
function ajouterAuCoockie() {
    $idReservation = getMaxIdReservation();
    
    if(!isset($_COOKIE['reservation'])) {
        setcookie('reservation',$idReservation);
    }
    else {
        $valeurAStocker = $_COOKIE['reservation'] . ",".$idReservation;
        setcookie('reservation',$valeurAStocker);
    }
}

//retourne la plus grande cle primaire de reservation
function getMaxIdReservation() {
    $connexion = connect();
    
    $req="SELECT MAX(idReservation) as idMax FROM reservation";
    $res = $connexion->query($req);
    
    $res=$res->fetch();
    
    return $res['idMax'];
}

//suppression d'une reservation
function deleteVol($idRes) {
    $connexion = connect();
    
    $req="delete from reservation where idReservation=".$idRes;

    $connexion->exec($req);
}