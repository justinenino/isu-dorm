<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justine Manuel - Personal Biodata</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #121212;
        }

        .card-container {
            perspective: 1000px;
        }

        .card {
            width: 350px;
            height: 450px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s;
        }

        .card:hover {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 15px;
            backface-visibility: hidden;
            box-shadow: 0px 4px 10px rgba(255, 215, 0, 0.5);
        }

        .front {
            background: linear-gradient(135deg, #0f3057, #005f73);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }

        .front h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #FFD700;
        }

        .front img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #FFD700;
            margin-bottom: 15px;
        }

        .back {
            background: #003049;
            color: white;
            text-align: center;
            padding: 20px;
            transform: rotateY(180deg);
        }

        .back h3 {
            color: #FFB703;
            margin-bottom: 10px;
        }

        .back p {
            margin: 5px 0;
            font-size: 16px;
        }

        .motto {
            margin-top: 15px;
            font-style: italic;
            color: #FF006E;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card">
            
            <div class="card-face front">
                <img src="https://via.placeholder.com/120" alt="Profile Picture">
                <h2>Justine Manuel</h2>
                <p>Age: 20</p>
                <p>Location: Echague, Isabela, Philippines</p>
            </div>

            
            <div class="card-face back">
                <h3>Hobbies</h3>
                <p>🎮 Playing</p>
                <p>🍳 Cooking</p>
                <p🚴‍♂️ Biking</p>
                <p>🏍️ Riding</p>

                <h3 class="motto">"Nu madi ka sumrik, madi nak mit sumrik"</h3>
            </div>
        </div>
    </div>
</body>
</html>
