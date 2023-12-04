<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pokemon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT apiGeneration FROM pokemons GROUP BY apiGeneration";
?>

<header>
    <a href="index.php">Pokédex</a>
    <ul>
        <li><a href="index.php">Tout</a></li>
        <?php
        $result = $conn->query($sql);
        $generations = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($generations as $generation) {
            $generationData = (object) $generation;
        ?>
            <li><a href="generation.php?id=<?php echo $generationData->apiGeneration; ?>">Génération <?php echo $generationData->apiGeneration; ?></a></li>
        <?php
        }
        ?>
    </ul>
</header>