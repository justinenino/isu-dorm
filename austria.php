<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bio Data</title>

</head>
<body>

    <div class="container">
        <div class="profile">
            <img src="https://scontent.fmnl7-1.fna.fbcdn.net/v/t39.30808-6/462928516_27511807288433531_3282370155892833619_n.jpg?_nc_cat=103&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeFPTWXn0LAd1Jm6rBuOgkhgeMDNNSah-KJ4wM01JqH4og6JGFtuZpAMwygvP8SBBkqHyia1UFG6WWUmpSD6s-ID&_nc_ohc=SP5Nlj3WKi4Q7kNvgHm3ruF&_nc_zt=23&_nc_ht=scontent.fmnl7-1.fna&_nc_gid=AtlSaaow0Ye0LE95_N9jM_6&oh=00_AYD8aZz4MHS2PczBoxJplGbUps_ZDpteNTaGCOAE_hoQrw&oe=67A92C5F" alt="Profile Picture">
        </div>
        <h1>My Bio Data</h1>
        <div class="info">
            <p><strong>Full Name:</strong>Carl Jesse F. Austria</p>
            <p><strong>Date of Birth:</strong> June 2, 2004</p>
            <p><strong>Gender:</strong> Male</p>
            <p><strong>Address:</strong> Purok 5,Sinamar Norte,San Mateo,Isabela</p>
            <p><strong>Email:</strong> carljessea@gmail.com</p>
            <p><strong>Phone Number:</strong> 09308062961</p>
            <p><strong>Education:</strong> Bachelor Of Science In Information Technology</p>
            <p><strong>Skills:</strong> Playing Guitar,Music Making,Photoshopping and Coding</p>
            <p><strong>Hidden Talent:</strong> Singing</p>
        </div>
        <a href="index.php" class="back-button">⬅ Back to Home</a>
    </div>

</body>

<style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 50%;
            background: #005670; 
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.3);
            color: #f0f0f0;
            text-align: center;
        }
        .profile img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #00e5ff;
        }
        h1 {
            margin-top: 10px;
            font-size: 28px;
            color:rgb(255, 255, 255);
        }
        .info {
            line-height: 1.8;
            text-align: left;
            margin-top: 15px;
            background: #111; 
            padding: 15px;
            border-radius: 8px;
        }
        .info p {
            margin: 8px 0;
            font-size: 16px;
        }
        .info strong {
            color: #00e5ff;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #003f5c; 
            color: #f0f0f0;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.3s;
        }
        .back-button:hover {
            background-color: #00e5ff;
            color: #003f5c;
        }
    </style>
</html>
