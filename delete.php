<?php
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "pokemon";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $pokemonId = isset($_GET['id']) ? $_GET['id'] : null;

    $aze = "DELETE FROM pokemons WHERE id = $pokemonId";
    $conn->query($aze);
    header("Location: ./index.php");
?>