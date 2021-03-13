<?php 
require_once('./config.php');
require_once('./src/Planning/FormEvent.php');

// Redirection vers le login si l'usager n'est pas connecté.
if(!isOnline()) {
    header('Location: ./login');
}
// Redirection vers l'index si l'usager n'est ni un enseignant, ni un administrateur.
if($_SESSION['rang'] < 2) {
    header('Location: ./');
}

$last_search = isset($_GET['search']) ? $_GET['search'] : ' ';

// Ajout d'un cours.
if(isset($_POST['add_event'])) {

    // On récupère les données reçu en js.
    parse_str($_POST['post'], $data);

    // On crée et vérifie si il n'y a aucune erreur dans le formulaire.
    $form = new Planning\FormEvent($bdd, $data);
    $errors = $form->checkAddEvent();

    // Si il n'y a aucune erreurs, on ajout le cours.
    if(empty($errors)) {
        $form->insertEvent();
    }
    echo json_encode($errors);
    exit;
}

// Suppression d'un cours.
if(isset($_GET['removeEventID'])){
    $form = new Planning\FormEvent($bdd, $_GET);
    $form->deleteEvent($_GET['removeEventID']);
    header('Location: ./gestion');
}
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerer mes cours : Université d'Artois</title>
    <link type="image/x-icon" rel="shortcut icon" href="./assets/img/favicon.ico"/>
    <meta property="og:title" content="Gerer mes cours : Université d'Artois">
    <meta property="og:type" content="website">
    <meta name="author" content="Carpentier Quentin & Krogulec Paul-Joseph">
    <!-- CSS -->
    <link type="text/css" rel="stylesheet" href="./assets/css/icons.min.css">
    <link type="text/css" rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link type="text/css" rel="stylesheet" href="./assets/css/style.css">
</head>
<body>
    <!-- HEADER -->
    <?php require_once('./views/header.php') ?>

    <!-- PAGE -->
    <div class="container">

        <h4>Gestion des cours</h4>

        <div class="row">
            <div class="col-md-7"> 
                <div class="box-content">
                    <?php 
                    $where = 'WHERE NomMatiere LIKE \'%'. $last_search.'%\'';
                    if($_SESSION['rang'] == 2)  $where = 'WHERE UsagerID ="'. $_SESSION['id'] . '" AND NomMatiere LIKE \'%'. $last_search.'%\'';
                    $sCours = $bdd->query('SELECT * FROM Cours INNER JOIN Matieres USING(MatiereID), TypeCours USING(TypeID), Usagers USING(UsagerID), Promotions USING(PromotionID), Salles USING(SalleID) '.$where.' ORDER BY DateDebut DESC, HeureDebut DESC');
                    while($aCours = $sCours->fetch()) { ?>
                        <div class="list-items d-flex flex-row align-items-center justify-content-between">
                            <div class="item-info">
                                <p><?= $aCours['NomType'] ?> <?= $aCours['NomMatiere'] ?></p>
                                <span>Par <?= htmlspecialchars($aCours['Prenom']) ?> <?= htmlspecialchars($aCours['Nom']) ?>, en <?= htmlspecialchars($aCours['NomSalle']) ?></span>
                            </div>
                            
                            <div class="item-info">
                                <p>du <?= date('d-m-Y', $aCours['DateDebut']) ?> au <?= date('d-m-Y', $aCours['DateFin']) ?></p>
                                <span>de  <?= $aCours['HeureDebut'] ?> à <?= $aCours['HeureFin'] ?></span>
                            </div>
                        
                            <a href="?courID=<?= $aCours['CourID'] ?>" class="btn btn-primary"><i class="mdi mdi-pencil-outline"></i></a>
                            <a href="?removeEventID=<?= $aCours['CourID'] ?>" class="btn btn-danger"><i class="mdi mdi-close"></i></a>
                                                    
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-md-5">    
                <div class="box-content">
                    <div class="content-title">Ajouter un cours</div>
                    <form method="POST" id="form_addEvent">
                        <div class="alert" style="display:none"></div>
                        <div class="row">
                            <div class="col-md-5 form-group">
                                <label for="promo">Promotion</label>
                                <select name="promo" class="form-control" id="promo">
                                    <?php                                    
                                    $option = '';
                                    if($_SESSION['rang'] == 2) {
                                        $option = 'INNER JOIN Appartient ON Promotions.PromotionID = Appartient.PromotionID AND UsagerID = "'.$_SESSION['id'].'"';
                                    }
                                    $sPromo = $bdd->query('SELECT * FROM Promotions '.$option.' ORDER BY PromotionID');
                                    while($aPromo = $sPromo->fetch()) {
                                        echo '<option value="'.$aPromo['PromotionID'].'"'. ((isset($_POST['promo']) && $_POST['promo'] == $aPromo['PromotionID']) ? ' selected' : '') .'>'.$aPromo['NomPromotion'].'</option>';
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-7 form-group">
                                <label for="matter">Matière</label>
                                <select name="matter" id="" class="form-control" id="matiere">
                                    <?php
                                    $option = '';
                                    if($_SESSION['rang'] == 2) {
                                        $option = 'INNER JOIN Enseigne ON Matieres.MatiereID = Enseigne.MatiereID AND UsagerID = "'.$_SESSION['id'].'"';   
                                    }
                                    $sMatieres = $bdd->query('SELECT * FROM Matieres '.$option. ' ORDER BY NomMatiere');
                                    while($aMatieres = $sMatieres->fetch()) {
                                        echo '<option value="'.$aMatieres['MatiereID'].'">'.htmlspecialchars($aMatieres['NomMatiere']).'</option>';
                                    } ?> 
                                </select>
                            </div>
                            <div class="col-md-6 form-group" id="firstdate">
                                <label for="firstdate">Date</label>
                                <input type="date" name="firstdate" class="form-control" value="<?= (isset($_POST['firstdate'])) ? $_POST['firstdate'] : '' ?>">
                                <small class="invalid-feedback"></small>
                            </div>
                            <div class="col-md-3 form-group" id="start">
                                <label for="start">Heure de début</label>
                                <input type="time" name="start" class="form-control" value="<?= (isset($_POST['start'])) ? $_POST['start'] : '' ?>">
                                <small class="invalid-feedback"></small>
                            </div>
                            <div class="col-md-3 form-group" id="end">
                                <label for="end">Heure de fin</label>
                                <input type="time" name="end" class="form-control" value="<?= (isset($_POST['end'])) ? $_POST['end'] : '' ?>">
                                <small class="invalid-feedback"></small>  
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="type">Type de cours</label>
                                <select name="type" class="form-control">
                                    <?php $query = $bdd->query('SELECT * FROM TypeCours');
                                        while ($row = $query->fetch()){
                                            echo '<option value="' . $row['TypeID'].'">' . htmlspecialchars($row['NomType']) . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>                            
                            <div class="col-md-6 form-group">
                                <label for="user">Enseignant</label>
                                <select name="user" class="form-control">
                                    <?php if($_SESSION['rang'] == 2) {
                                            $sql = 'AND UsagerID = "'.$_SESSION['id'].'"';
                                        } else {
                                            $sql = '';
                                        }
                                        $query = $bdd->query('SELECT * FROM Usagers WHERE RangID = 2 ' . $sql);
                                        while ($row = $query->fetch()){
                                            echo '<option value="' .$row['UsagerID'] .'">' . $row['Prenom'] . ' ' .  $row['Nom'] . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>                            
                            <div class="col-md-3 form-group">
                                <label for="room">Salle</label>
                                <select name="room" class="form-control">
                                    <?php $query = $bdd->query('SELECT * FROM Salles');
                                        while ($row = $query->fetch()){
                                            echo '<option value="' . $row['SalleID'].'">' . $row['NomSalle'] . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-12 form-group">
                                <label for="room">Durée du cours</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input class="form-check-input" type="radio" name="nbweek" value="1" checked>
                                        <label class="form-check-label" for="nbweek">Cours fixe</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input class="form-check-input" type="radio" name="nbweek" value="2">
                                        <label class="form-check-label" for="nbweek">2 semaines</label>
                                    </div>
                                    <div class="col-md-6 ">
                                        <input class="form-check-input" type="radio" name="nbweek" value="3">
                                        <label class="form-check-label" for="nbweek">3 semaines</label>
                                    </div>                                    
                                    <div class="col-md-6">
                                        <input class="form-check-input" type="radio" name="nbweek" value="4">
                                        <label class="form-check-label" for="nbweek">1 mois</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="submit" name="add_event" value="Programmer ce cours" class="btn btn-success">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <?php require_once('./views/footer.php') ?>
    
	<!-- JS -->
	<script type="text/javascript" src="./assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="./assets/js/functions.js"></script>
</body>
</html>
