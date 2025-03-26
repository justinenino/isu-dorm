<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIODATA</title>
    <style>
        /* Google Font */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        /* Body Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #ffccff, #ff99cc);
            color: #333;
            text-align: center;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Container Styling */
        .container {
            width: 60%;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease-in-out;
        }

        /* Hover Effect */
        .container:hover {
            transform: scale(1.03);
            box-shadow: 10px 10px 20px rgba(0, 0, 0, 0.3);
        }

        /* Profile Picture Styling */
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #ff66b2;
            object-fit: cover;
            transition: transform 0.3s ease-in-out;
        }

        .profile-pic:hover {
            transform: rotate(10deg) scale(1.1);
        }

        /* Biodata Styling */
        .biodata {
            text-align: left;
            margin-top: 20px;
            padding: 10px;
            background: #ff99cc;
            border-radius: 10px;
            color: white;
        }

        .biodata p {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            color: #333;
            display: flex;
            align-items: center;
        }

        .biodata p i {
            margin-right: 10px;
            color: #ff66b2;
        }

        /* Icon Styling */
        .icon {
            font-size: 18px;
        }
    </style>
    <!-- FontAwesome for Icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

    <div class="container">
        <h1 style="color: #ff66b2;">🌸 My Biodata 🌸</h1>
        <img src="https://scontent.fmnl17-3.fna.fbcdn.net/v/t39.30808-6/476163090_1865119480697353_4079146908187108373_n.jpg?_nc_cat=106&ccb=1-7&_nc_sid=a5f93a&_nc_eui2=AeFfa4hljQLs0ktsu-MgabZTTE-l1gww_xxMT6XWDDD_HBgZqYR6CpIikIqD3XezcNJCXRiknp5PBxh-TTu6ioL6&_nc_ohc=r-T96D7NOIYQ7kNvgGJuzxL&_nc_oc=Adiz4ErScuRiGCCRFGvS61FQT-yoRpHZXyXXzOGYz-lTbv1gxMvDXj9vmgl5V4Waa_o&_nc_zt=23&_nc_ht=scontent.fmnl17-3.fna&_nc_gid=ARZFQfP8in5opSGOp9guVEI&oh=00_AYBPY5aggIFF5uNdtDsFn_iDi-ueF66tk21M6Go8Z2E6RQ&oe=67AD20DB " alt="" class="profile-pic">
        
        <div class="biodata">
            <p><i class="fas fa-user icon"></i> <strong>Name:</strong> KRIZIA CASSANDRA S. LEANO</p>
            <p><i class="fas fa-birthday-cake icon"></i> <strong>Age:</strong> 21</p>
            <p><i class="fas fa-envelope icon"></i> <strong>Email:</strong> kriziacassandraleano@gmail.com</p>
            <p><i class="fas fa-phone icon"></i> <strong>Phone:</strong> 09750137754</p>
            <p><i class="fas fa-map-marker-alt icon"></i> <strong>Address:</strong> JONES, ISABELA</p>
            <p><i class="fas fa-heart icon"></i> <strong>Hobbies:</strong> TRAVELING</p>
        </div>
    </div>

</body>
</html>