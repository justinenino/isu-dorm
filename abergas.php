<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abergas, Khent Aaron</title>
    
</head>

<body>

    <div class="container">
        <div class="profile-container">
            <img src="https://scontent.fmnl8-2.fna.fbcdn.net/v/t39.30808-6/476158666_1495805828060647_1655587710689294324_n.jpg?stp=dst-jpg_p526x296_tt6&_nc_cat=111&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeGzbUZjroLDsue9VedUQT5YfESc6uLf27B8RJzq4t_bsHhMTfqvjiKtcn9gShLfYaF-AY5JtY22dSLvz2bJ9YDI&_nc_ohc=JfqsM0nDd1AQ7kNvgG41_8e&_nc_oc=AdjLGvi-QIXK9CdseX77ZTItsRUws0cDQzm2lJB1ZBIUAD2_nXrGJL1hQraUs4zWWcc&_nc_zt=23&_nc_ht=scontent.fmnl8-2.fna&_nc_gid=Al2tgfIQdD_yij_m7-28N05&oh=00_AYBhf1RPmzABaluaENvHa8Z1vvxNhFYVNIPKFFTqMvNQMQ&oe=67AC0CA2"
                alt="Profile Picture" class="profile-pic">
        </div>

        <div class="info">
            <p><strong>Name:</strong> Khent Aaron A. Abergas</p>
            <p><strong>Age:</strong> 20</p>
            <p><strong>Course:</strong> BS Information Technology</p>
            <p><strong>Department:</strong> College of Computing Studies, Information and Communication Technology</p>
            <p><strong>Email:</strong> abergaskhentaaron@gmail.com</p>
            <p><strong>Contact No:</strong> 09759083966</p>
        </div>

        <div class="hobbies">
            <h2>Hobbies</h2>
            <ul>
                <li><span class="emoji">🎮</span> Playing Mobile Games</li>
                <li><span class="emoji">📺</span> Watching Anime</li>
                <li><span class="emoji">📖</span> Reading Manga</li>
            </ul>
        </div>

        <div class="skills">
            <h2>Skills</h2>
            <ul>
                <li><span class="emoji">💻</span> PHP</li>
                <li><span class="emoji">🎨</span> CSS</li>
                <li><span class="emoji">🛠️</span> Laravel</li>
                <li><span class="emoji">🌐</span> HTML</li>
            </ul>
        </div>

        <a href="https://www.facebook.com/kheron.abergas.1/" class="btn" target="_blank">Contact Me</a>
    </div>

</body>

</html>

<style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('https://wallpaperaccess.com/full/6313.jpg') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 30px;
            border-radius: 15px;
            width: 60%;
            text-align: center;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            min-height: 500px;
        }

        .profile-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .profile-pic {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            animation: glow 2s infinite alternate ease-in-out;
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 10px rgba(255, 255, 255, 0.4);
            }
            100% {
                box-shadow: 0 0 20px rgba(255, 255, 255, 0.9);
            }
        }

        .info {
            text-align: left;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .info p {
            font-size: 18px;
            margin: 10px 0;
        }

        .info strong {
            color: #ffcc00;
        }

        .hobbies, .skills {
            margin-top: 20px;
            text-align: left;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .hobbies ul, .skills ul {
            list-style: none;
            padding: 0;
        }

        .hobbies li, .skills li {
            font-size: 18px;
            margin: 5px 0;
            color: #ffcc00;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .emoji {
            display: inline-block;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #ffcc00;
            color: black;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn:hover {
            background: white;
            color: black;
        }
    </style>