<?php
require_once '../config.php';
require_once '../auth_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header('Location: ../account.php');
    exit();
}

$order_id = (int)$_GET['order_id'];

// Récupérer les détails de la commande et de la facture
$query = "
    SELECT 
        c.*,
        f.id as facture_id,
        f.nom_fichier,
        GROUP_CONCAT(CONCAT(ca.quantite, 'x ', a.nom) SEPARATOR ', ') as articles
    FROM commandes c
    LEFT JOIN factures f ON c.id = f.commande_id
    JOIN commande_articles ca ON c.id = ca.commande_id
    JOIN articles a ON ca.article_id = a.id
    WHERE c.id = ? AND c.user_id = ?
    GROUP BY c.id, f.id, f.nom_fichier
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../account.php');
    exit();
}

$commande = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Commande validée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .order-details {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            margin-right: 10px;
        }

        .btn:hover {
            background-color: #27ae60;
        }

        .btn-back {
            background-color: #34495e;
        }

        .btn-back:hover {
            background-color: #2c3e50;
        }

        .download-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <h1>Commande validée avec succès !</h1>

    <div class="order-details">
        <h2>Détails de la commande #<?php echo $commande['id']; ?></h2>
        <p><strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($commande['date_transaction'])); ?></p>
        <p><strong>Montant total :</strong> <?php echo number_format($commande['montant_total'], 2); ?> €</p>
        <p><strong>Articles :</strong> <?php echo htmlspecialchars($commande['articles']); ?></p>
        <p><strong>Adresse de livraison :</strong><br>
            <?php echo htmlspecialchars($commande['adresse']); ?><br>
            <?php echo htmlspecialchars($commande['code_postal'] . ' ' . $commande['ville']); ?>
        </p>
    </div>

    <div class="download-section">
        <?php if ($commande['facture_id']): ?>
            <a href="../toggle_order.php?id=<?php echo $commande['facture_id']; ?>" class="btn">
                Télécharger la facture
            </a>
        <?php endif; ?>

        <a href="../account.php" class="btn btn-back">Retour à mon compte</a>
    </div>
</div>
</body>
</html>