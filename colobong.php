<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colobong's Bio-Data</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: url('https://wallpaperaccess.com/full/628286.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
        .container {
            max-width: 900px;
            margin-bottom: 20px;
            background: rgba(10, 185, 191, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            transition: transform 0.3s ease-in-out;
            color: white;
        }
        .container:hover {
            transform: scale(1.02);
        }
        .header {
            background: linear-gradient(135deg,rgb(46, 202, 213),rgb(81, 176, 210));
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            transition: transform 0.3s;
        }
        .profile-img:hover {
            transform: scale(1.1);
        }
        .content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }
        .box {
            flex: 1;
            min-width: 280px;
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
            color: black;
        }
        .box:hover {
            transform: translateY(-5px);
        }
        .tech-stack {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tech-item {
            background: #6C63FF;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .tech-item:hover {
            background: #4A47A3;
        }
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            .profile-img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container header">
        <img src="https://scontent.fcrk2-4.fna.fbcdn.net/v/t39.30808-6/476826966_3998079227097397_747598898598508951_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeGwzaMlT3CRht9Wf-QSANN3IcgsJf-cm_IhyCwl_5yb8hhr0S5k0tjWhXc6eAHuOqXi4S8_Fzq9GL3OdZmuRApT&_nc_ohc=N8Vjc0pG_b0Q7kNvgEGddK3&_nc_oc=Adge6PVsz4dJHRjOyEGBHkboEZAjP8zm6ugQwwpuy0Xt2YyQ7cGfD4DllOpd3n_03zI&_nc_zt=23&_nc_ht=scontent.fcrk2-4.fna&_nc_gid=A07rk8SjB3zzzhXUdxHtdp2&oh=00_AYCLkYcG_RnSj1XZtoybxTTigjktPStMfh_m-iVGIRPXxw&oe=67AD0ABA" class="profile-img">
        <h1>Adam Quincy D. Colobong</h1>
        <h2>I'm a BSIT Student</h2>
        <p>adamcolobong@gmail.com | 09020250501 | San Mateo, Isabela</p>
        <p>"Wake up to reality. Nothing ever goes as planned in this accursed world. The longer you live, <br>
            the more you realize that the only things that truly exist in this reality are merely pain, suffering and futility."<br> -Uchiha Madara</p>
    </div>
    
    <div class="container content">
        <div class="box">
            <h2>Personal Information</h2>
            <p>Age: 21</p>
            <p>Birthday: December 29, 2003</p>
            <p>Birthplace: Cauayan City</p>
            <p>Address: Gaddanan San Mateo, Isabela</p>
            <p>Religion: Methodist</p>
            <p>Nationality: Filipino</p>
        </div>

        <div class="box">
            <h2>Hobbies</h2>   
            <p>Gaming</p>
            <p>Watching Anime</p>
            <p>Coding (a little bit)</p>
        </div>

        <div class="box">
            <h2>Educational Attainment</h2>
            <p>Kindergarten: Diamond Christian School</p>
            <p>Elementary: Gaddanan Elementary School</p>
            <p>High School: Salinungan National High School <br> Salinungan Stand-Alone Senior High School</p>
            <p>College: Isabela State University Main Campus (current)</p>
        </div>

        <div class="box">
            <h2>Tech Stack (Newbie)</h2>
            <div class="tech-stack">
                <span class="tech-item">Vue.js</span>
                <span class="tech-item">Laravel</span>
                <span class="tech-item">PHP</span>
            </div>
        </div>
    </div>
</body>
</html>
