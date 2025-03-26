<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata Page</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .section {
            margin-top: 20px;
            text-align: left;
        }
        .section h3 {
            background: #007bff;
            color: white;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
        }
        .info {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
            margin-top: 10px;
        }
        .info p {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #555;
        }
        .info p strong {
            width: 180px;
            display: inline-block;
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Biodata</h2>
    <img src="https://th.bing.com/th/id/OIP.WFEutOWYtepOJBTjwRW--QHaGV?rs=1&pid=ImgDetMain" alt="Profile Picture" class="profile-pic">

    <div class="section">
        <h3>Personal Info</h3>
        <div class="info">
            <p><strong>Name:</strong> Marth Justine Ramirez</p>
            <p><strong>Email:</strong> marthjustineramirez@gmail.com</p>
            <p><strong>Address:</strong> Antonino, Alicia, Isabela</p>
            <p><strong>Gender:</strong> Male</p>
            <p><strong>Date of Birth:</strong> December 12, 2003</p>
        </div>
    </div>

    <div class="section">
        <h3>Additional Info</h3>
        <div class="info">
            <p><strong>Hobby:</strong> Ang ma-disappoint si mama.</p>
            <p><strong>Strengths:</strong> Mga ngiti niya.</p>
            <p><strong>Weaknesses:</strong> Mga ngiti niya rin.</p>
        </div>
    </div>
</div>

</body>
</html>
