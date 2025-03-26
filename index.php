<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSIT 3-1 WMAD | Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<?php
$names = [
  ["firstName" => "KHENT AARON", "lastName" => "ABERGAS"],
  ["firstName" => "VINCE ERROL", "lastName" => "ABRERA"],
  ["firstName" => "JAVIE KAYE", "lastName" => "AGUSTIN"],
  ["firstName" => "JAYLORD", "lastName" => "ALINDAYU"],
  ["firstName" => "AVEGAIL", "lastName" => "ALINGOG"],
  ["firstName" => "MARY-ANNE", "lastName" => "ALVAREZ"],
  ["firstName" => "LORRAINE", "lastName" => "ARQUILLA"],
  ["firstName" => "CARL JESSE", "lastName" => "AUSTRIA"],
  ["firstName" => "REIGNROMAR CHRYZEL", "lastName" => "BALICO"],
  ["firstName" => "ANTONINO RODULFO JR", "lastName" => "BALINADO"],
  ["firstName" => "GERALDINE", "lastName" => "BASE"],
  ["firstName" => "JOHN MARK", "lastName" => "BORJA"],
  ["firstName" => "ADAM QUINCY", "lastName" => "COLOBONG"],
  ["firstName" => "ERWIN JAKE", "lastName" => "DAGPIN"],
  ["firstName" => "MAUREEN JOIE", "lastName" => "DANZALAN"],
  ["firstName" => "ALYSSA JANE", "lastName" => "DAYAG"],
    ["firstName" => "CZARINA JANE", "lastName" => "DE GUZMAN"],
  ["firstName" => "JAIRUS BERNIE", "lastName" => "DELA CRUZ"],
  ["firstName" => "ELI MIGUEL", "lastName" => "DEPRA"],
  ["firstName" => "CLAIRE ANNE", "lastName" => "DOMINGO"],
  ["firstName" => "RAILEY JADE", "lastName" => "DULAY"],
  ["firstName" => "CHRYZAL QUEEN", "lastName" => "ELLA"],
  ["firstName" => "CHRIS LLOYD", "lastName" => "FALLARIA"],
  ["firstName" => "JOHN CLINT", "lastName" => "GABRIEL"],
  ["firstName" => "KRIZIA CASSANDRA", "lastName" => "LEANO"],
  ["firstName" => "RAHMAN LEI", "lastName" => "MACAPASIR"],
  ["firstName" => "FATIMIH", "lastName" => "MADDELA"],
  ["firstName" => "JEF", "lastName" => "MAMARIL"],
  ["firstName" => "KURT LAWRENCE", "lastName" => "MANANDIG"],
  ["firstName" => "JUSTINE NIO", "lastName" => "MANUEL"],
  ["firstName" => "MARK FRANCIS", "lastName" => "MIL"],
  ["firstName" => "CLYDEL SHANE", "lastName" => "NAVAS"],
  ["firstName" => "HANIEL JEZRAYE", "lastName" => "NOLASCO"],
  ["firstName" => "JERICK", "lastName" => "PARALLAG"],
  ["firstName" => "PRINCESSMAE", "lastName" => "PINERA"],
  ["firstName" => "DANIELLE KURT XAVIER", "lastName" => "PINTO"],
  ["firstName" => "JOHN REY THOMAS", "lastName" => "PUERTAS"],
  ["firstName" => "MARTH JUSTINE", "lastName" => "RAMIREZ"],
  ["firstName" => "HUMPHREY MILES", "lastName" => "RAMOS"],
  ["firstName" => "JAYRON BRYAN", "lastName" => "REANO"],
  ["firstName" => "KURT LIAM", "lastName" => "SADANG"],
  ["firstName" => "JOBERT", "lastName" => "SAET"],
  ["firstName" => "TRISTAN JAMES", "lastName" => "SALARZON"],
  ["firstName" => "MARC JEFFERSON", "lastName" => "SANTOS"],
  ["firstName" => "YUL IVAN", "lastName" => "SUGUI"],
  ["firstName" => "DEAN ANDREI", "lastName" => "TAVAS"],
  ["firstName" => "EUGENE", "lastName" => "TOBIAS"],
  ["firstName" => "ZYRILLE", "lastName" => "VILLANUEVA"],
  ["firstName" => "MELVIN", "lastName" => "WALATH"],
  ["firstName" => "JOHN RAY", "lastName" => "YU"]
];

?>


<div class="container mt-5">
    <h2>BSIT 3-1 WMAD | Class List</h2>
    <ul class="list-group">
        <?php
         echo "<ul class='list-group'>";
         foreach ($names as $name) {
            $href = strtolower(str_replace(' ', '-', $name["lastName"], )) . ".php";
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo "<a href='{$href}'>{$name['firstName']} {$name['lastName']}</a>";
            echo "<i class='fas fa-chevron-right chevron-icon'></i>";
            echo "</li>";
         }
         echo "</ul>";
        ?>
    </ul>
</div>
</body>
</html>
