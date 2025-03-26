<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MANANDIG BIO-DATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: rgb(39, 35, 35);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('https://img.freepik.com/free-photo/wet-monstera-deliciosa-plant-leaves-garden_53876-139814.jpg');
            background-size: cover;
            background-position: center;
        }
        .container {
            max-width: 90%;
            margin-left: 110px;
        }
        .profile-container {
            position: relative;
            display: inline-block;
        }
        .profile-image {
            width: 78%;
            border-radius: 30px;
            box-shadow: 0 0 100px rgba(50, 50, 50, 0.8);
            animation: fireGlow 1.5s infinite alternate;
        }
        @keyframes fireGlow {
            0% {
                box-shadow: 0 0 60px rgba(70, 69, 69, 0.7), 0 0 60px rgba(28, 28, 28, 0.5);
                filter: hue-rotate(0deg);
            }
            50% {
                box-shadow: 0 0 80px rgba(86, 86, 86, 0.8), 0 0 80px rgba(79, 79, 79, 0.6);
                filter: hue-rotate(10deg);
            }
            100% {
                box-shadow: 0 0 100px rgba(127, 127, 127, 0.9), 0 0 100px rgba(94, 94, 94, 0.7);
                filter: hue-rotate(-10deg);
            }
        }
        .glassy-box {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            color: black;
            font-weight: bold;
        }
        h1 {
            color: white;
            font-size: 44px;
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            text-align: center;
        }
        p {
            font-size: 19px;
            font-family: 'Times New Roman', Times, serif;
            color: white;
        }
        .flip-card {
            position: relative;
            height: 328px;
            cursor: pointer;
        }
        .flip-card-inner {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .flip-card-front, .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 15px;
            padding: 15px;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        .flip-card-front {
            background: rgba(255, 255, 255, 0.2);
            opacity: 1;
            transform: translateY(0);
        }
        .flip-card-back {
            background: rgba(255, 255, 255, 0.2);
            opacity: 0;
            transform: translateY(20px);
        }
        .flip-card.flipped .flip-card-front {
            opacity: 0;
            transform: translateY(-20px);
        }
        .flip-card.flipped .flip-card-back {
            opacity: 1;
            transform: translateY(0);
        }
        #typed-text {
            display: inline-block;
            color: white;
            font-size: 44px;
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            text-align: center;
        }
        .cursor {
            animation: blink 0.7s infinite;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="profile-container">
                    <img src="https://scontent.fmnl8-4.fna.fbcdn.net/v/t39.30808-6/472115104_3998058267189455_1347776851556229618_n.jpg?_nc_cat=102&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeG6GmTR7pfP65G-8oZAYwQppqWwPZL_fuempbA9kv9-56SGypCWqhPBx1-xVyxk_53MzsYFfI2ait9gKhR9qv_J&_nc_ohc=6aQpc5vn4XYQ7kNvgExbgNa&_nc_zt=23&_nc_ht=scontent.fmnl8-4.fna&_nc_gid=AlLSWxxwzNHmxeil_hjsOWF&oh=00_AYAel8T9hUhX3rKPDI93CbhISipqF7NX04aXsC1mzznavQ&oe=67A93EC3" alt="Profile Image" class="profile-image">
                </div>
            </div>

            <div class="col-md-7">
                <div class="glassy-box mb-3">
                    <h1 id="typed-text"></h1>
                </div>
                <hr class="rounded">
                <div class="flip-card" id="infoCard" onclick="flipCard()">
                    <div class="flip-card-inner">
                        <div class="flip-card-front">
                            <p>Click here to see other info's...</p>
                        </div>
                        <div class="flip-card-back">
                            <div class="row">
                                <div class="col-md-3 text-start">  
                                    <p>COURSE: </p>
                                    <p>DATE OF BIRTH: </p>
                                    <p>BIRTH PLACE: </p>
                                    <p>ADDRESS: </p>
                                    <p>GENDER: </p>
                                    <p>RELIGION: </p>
                                    <p>NATIONALITY: </p>
                                </div>
                                <div class="col-md-9">
                                    <p>BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY</p>
                                    <p>AUGUST 08, 2004</p>
                                    <p>CAUAYAN CITY</p>
                                    <p>SILAUAN SUR ECHAGUE ISABELA</p>
                                    <p>MALE</p>
                                    <p>ROMAN CATHOLIC</p>
                                    <p>FILIPINO</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function flipCard() {
            document.getElementById('infoCard').classList.toggle('flipped');
        }

        document.addEventListener("DOMContentLoaded", function () {
            const text = "KURT LAWRENCE B. MANANDIG";
            let index = 0;
            const speed = 150;
            const target = document.getElementById("typed-text");

            function typeWriter() {
                if (index < text.length) {
                    target.innerHTML += text.charAt(index);
                    index++;
                    setTimeout(typeWriter, speed);
                } else {
                    target.innerHTML += '<span class="cursor">|</span>';    
                }
            }
            typeWriter();
        });
    </script>
</body>
</html>
