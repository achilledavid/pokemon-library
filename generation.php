<?php
ini_set("display_errors", 1);

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$generationId = isset($_GET['id']) ? $_GET['id'] : null;

$sql = "SELECT * FROM pokemons WHERE apiGeneration = $generationId";
$result = $conn->query($sql);
$pokemons = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pokédex</title>
    <link rel="stylesheet" href="./css/reset.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include './components/header.php'; ?>
    <main class="container">
        <h2>Génération <?php echo $generationId; ?> :</h2>
        <div class="pokemon-list">
            <?php
            foreach ($pokemons as $pokemon) {
                $pokemonData = (object) $pokemon;
                include './components/pokemon-card.php';
            }
            ?>
        </div>
</body>

</html>