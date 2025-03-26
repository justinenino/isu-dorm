<?php
// Biodata Information
$biodata = [
    "name" => "Jairus Bernie A. Dela Cruz",
    "email" => "Miss.Ko.Na.Siya@gmail.com",
    "phone" => "+123 Saan Ako Nagkamali",
    "address" => "123 Miss Ko na SIya, City, Country",
    "dob" => "December 2, 2003",
    "gender" => "Male",
    "nationality" => "Filipino",
    "skills" => ["PHP", "HTML", "CSS", "JavaScript", "MySQL"],
    "hobbies" => ["Reading", "Coding", "Traveling"],
    "profile_pic" => "https://tse3.mm.bing.net/th?id=OIP.dtlHz4I4sENafs8TnZC3dwHaHa&pid=Api&P=0&h=180", // Change to your image file
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata - <?php echo $biodata['name']; ?></title>
    
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
        }
        .biodata-card {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-in-out;
            color: black;
        }
        .profile-pic {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #2575fc;
            transition: transform 0.3s;
        }
        .profile-pic:hover {
            transform: scale(1.1);
        }
        .icon {
            font-size: 22px;
            color: #2575fc;
        }
        .list-group-item {
            border: none;
            background: none;
            color: black;
        }
        .list-group-item:hover {
            background: #2575fc;
            color: white;
        }
        .card-footer {
            background: none;
            border-top: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="biodata-card text-center p-4">
        <img src="<?php echo $biodata['profile_pic']; ?>" alt="Profile Picture" class="profile-pic mb-3">
        <h2><?php echo $biodata['name']; ?></h2>
        <p class="text-muted"><i class="fa-solid fa-envelope icon"></i> <?php echo $biodata['email']; ?></p>
        <p class="text-muted"><i class="fa-solid fa-phone icon"></i> <?php echo $biodata['phone']; ?></p>

        <hr>

        <div class="row text-start">
            <div class="col-md-6">
                <p><i class="fa-solid fa-location-dot icon"></i> <strong>Address:</strong> <?php echo $biodata['address']; ?></p>
                <p><i class="fa-solid fa-calendar icon"></i> <strong>Date of Birth:</strong> <?php echo $biodata['dob']; ?></p>
            </div>
            <div class="col-md-6">
                <p><i class="fa-solid fa-venus-mars icon"></i> <strong>Gender:</strong> <?php echo $biodata['gender']; ?></p>
                <p><i class="fa-solid fa-flag icon"></i> <strong>Nationality:</strong> <?php echo $biodata['nationality']; ?></p>
            </div>
        </div>

        <hr>

        <h4><i class="fa-solid fa-code icon"></i> Skills</h4>
        <ul class="list-group">
            <?php foreach ($biodata['skills'] as $skill) : ?>
                <li class="list-group-item"><?php echo $skill; ?></li>
            <?php endforeach; ?>
        </ul>

        <hr>

        <h4><i class="fa-solid fa-heart icon"></i> Hobbies</h4>
        <p><?php echo implode(", ", $biodata['hobbies']); ?></p>

        <div class="card-footer">
            <a href="#" class="btn btn-primary mt-3"><i class="fa-solid fa-download"></i> Download Resume</a>
        </div>
    </div>
</div>

</body>
</html>