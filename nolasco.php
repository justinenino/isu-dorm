<?php
$name = "Haniel Jezraye V. Nolasco";
$course = "Bachelor of Science in Information Technology";
$age = "22";
$born = "October 7, 2002";
$profile_picture = "https://scontent.fmnl8-1.fna.fbcdn.net/v/t39.30808-6/456379836_1668404567035499_4696424826547192620_n.jpg?_nc_cat=106&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeG1H_t35bzpEhpwbTQ99DO7NFfC_HEzEZU0V8L8cTMRlbx6L7Ksu8kBcIxswd2p9dMSbX1czmmhthCVXph9Z5z3&_nc_ohc=PwwX5pKyk84Q7kNvgGWYcm5&_nc_oc=Adh2GunO3iUobRpAKB-pF-5fqgLuIBYH2LdhiIN6U37rPF8Ml939N0rdnvNxnn9ldNE&_nc_zt=23&_nc_ht=scontent.fmnl8-1.fna&_nc_gid=AryFUmxDEVCV4tXysNAIL3c&oh=00_AYAik2gHV7kWwS50lxZkRj1DpKy0ftWU6o-6wTaizghGww&oe=67AB74D7"; // Change this to the actual image file path
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata - <?php echo $name; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: url('https://www.bulakenyo.ph/wp-content/uploads/2022/12/Death-Featured-Image.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #ffffff; /* White text color */
            font-family: Arial, sans-serif;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.3); /* More transparency */
            color: #000000; /* Black text inside card */
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.5); /* Light border for clarity */
        }
        .card img {
            border: 5px solid #ffffff;
            display: block;
            margin: 0 auto;
        }
        .card h2 {
            color: #000000; /* Black for better readability */
        }
        .quote {
            margin-top: 20px;
            font-style: italic;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container mt-5 d-flex justify-content-center">
        <div class="card p-4 shadow-lg text-center" style="width: 350px;">
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="rounded-circle" width="150" height="150"><br>
            <p><strong>Name:</strong> <?php echo $name; ?></p>
            <p><strong>Course:</strong> <?php echo $course; ?></p>
            <p><strong>Age:</strong> <?php echo $age; ?></p>
            <p><strong>Born:</strong> <?php echo $born; ?></p>

            
            <div class="quote mt-3">
                <p>"The greater the mass, the greater the force of attraction" - my homie Newton</p>
                <p>"🤫🧏‍♂️" - unknown</p>
            </div>
        </div>
    </div>
</body>
</html>
