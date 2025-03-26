<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 70%;
        margin: 30px auto;
        background: #ffffff;
        padding: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        transition: all 0.3s ease;
        text-align: center;
    }

    .container:hover {
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    h1 {
        color: #333333;
        font-size: 2.2em;
        border-bottom: 2px solid #4CAF50;
        display: inline-block;
        padding-bottom: 10px;
    }

    .profile-picture {
        display: block;
        margin: 20px auto;
        border-radius: 50%;
        width: 120px;
        height: 120px;
        border: 3px solid #4CAF50;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .section {
        margin-bottom: 30px;
        text-align: left;
    }

    .section h2 {
        color: #4CAF50;
        border-bottom: 2px solid #4CAF50;
        font-size: 1.5em;
        padding-bottom: 10px;
        text-align: center;
    }

    .info {
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .info label {
        font-weight: bold;
        color: #555555;
        flex: 1;
        text-align: left;
    }

    .info span {
        color: #777777;
        flex: 2;
        text-align: right;
    }

    .info:last-child {
        border-bottom: none;
    }

    .skills {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .skills span {
        background-color: #4CAF50;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 20px;
        margin: 5px;
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .hobbies {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
    }

    .hobbies span {
        background-color: #ff9800;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 20px;
        margin: 5px;
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Biodata</h1>
        <img src="https://scontent.fcrk4-1.fna.fbcdn.net/v/t39.30808-6/475308727_3933125550349524_8869366470150492385_n.jpg?stp=dst-jpg_s206x206_tt6&_nc_cat=108&ccb=1-7&_nc_sid=fe5ecc&_nc_eui2=AeH0VFxQGnkKtd-I-aphq0XFvtXlp3LqfL--1eWncup8v7Ar1WMEJxeXMmo4a4zhUdk5JZOq-EKwA4Zw14Y3-nXX&_nc_ohc=AcCXrIiwc-UQ7kNvgFdBfsB&_nc_oc=Adj6iMtKFq0MaMIgpDlK805MOZoy8PKZccAXwfGNK-1wKnJxklEEMFnJw1LPdffA7vQ&_nc_zt=23&_nc_ht=scontent.fcrk4-1.fna&_nc_gid=AG0aisvFRqXM_RW_ad2S-qq&oh=00_AYCvkM0eOF-p1JjhO2e9umOI1--cmggmv2iX7Q_4jzupmw&oe=67AA608B"
            alt="Profile Picture" class="profile-picture">
        <div class="section">
            <h2>Personal Information</h2>
            <div class="info"><label>Full Name:</label> <span>Humphrey Miles G. Ramos</span></div>
            <div class="info"><label>Date of Birth:</label> <span>January 2, 2004</span></div>
            <div class="info"><label>Gender:</label> <span>Male</span></div>
            <div class="info"><label>Nationality:</label> <span>Filipino</span></div>
            <div class="info"><label>Address:</label> <span>Zamora, Alicia, Isabela</span></div>
            <div class="info"><label>Phone:</label> <span>0916 313 2917</span></div>
            <div class="info"><label>Email:</label> <span>humphreymiles.g.ramos@isu.edu.ph</span></div>
        </div>
        <div class="section">
            <h2>Educational Background</h2>
            <div class="info"><label>School Attended:</label> <span>Isabela State University </span></div>
            <div class="info"><label>Course:</label> <span>Bachelor of Science in Information Technology</span></div>
            <div class="info"><label>Grade Level:</label> <span>3rd Year</span></div>
        </div>
        <div class="section">
            <h2>Skills</h2>
            <div class="skills">
                <span>HTML</span>
                <span>CSS</span>
                <span>JavaScript</span>
                <span>PHP</span>
                <span>Magluto</span>
                <span>Maglaba</span>
                <span>Maglinis</span>
                <span>Maalaga</span>
                <span>Mapagmahal</span>
            </div>
        </div>
        <div class="section">
            <h2>Hobbies</h2>
            <div class="hobbies">
                <span>Reading</span>
                <span>Traveling</span>
                <span>Gaming</span>
                <span>Watching Anime</span>
                <span>Ang maalala siya</span>
            </div>
        </div>
    </div>
</body>

</html>