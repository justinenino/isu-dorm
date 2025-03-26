<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata</title>
    <style>
        body {
            font-family: "Comic Sans MS", sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .biodata-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            width: 500px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid black;
            margin-right: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: black;
            margin-top: 15px;
            border-bottom: 2px solid black;
            padding-bottom: 5px;
        }

        .info p {
            margin: 5px 0;
            font-size: 16px;
        }

        
        .education {
            list-style: none;
            padding: 0;
        }

        .education li {
            margin: 5px 0;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="biodata-container">

        <div class="profile-section">
            <div class="profile">
                <img src="https://scontent.fcrk2-2.fna.fbcdn.net/v/t39.30808-6/456407806_1245314279971179_4849921018132381801_n.jpg?_nc_cat=105&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeG5RxxG-pVJwl0nq2vl1R46H7IPfD3ErlYfsg98PcSuVvWvKGPpL3dE9L7TLone8FSzQmvuLYX_wZPAXzVY2TQZ&_nc_ohc=p8FA4ztZ9mkQ7kNvgE8oQGz&_nc_oc=AdiHbqecejzawZkR_VhVyBrTaoxVLgGxwPM9DwEiGJuPtiCeR2ehbwxoT9qglQNIT-Q&_nc_zt=23&_nc_ht=scontent.fcrk2-2.fna&_nc_gid=AQtbmElDy7meE6IS1HszuDJ&oh=00_AYCMjUbyuVHOW4OVpIebRaDFGDwhBRuJrPeYD1fN-A15gQ&oe=67AD0FA2" alt="Profile Picture">
            </div>
            <div class="info">
                <h2>Claire Anne M. Domingo</h2>
                <p><strong>Email:</strong> claireannedomingo22@gmail.com</p>
                <p><strong>Phone:</strong> 09057534489</p>
                <p><strong>Address:</strong> Callao, Alicia, Isabela</p>
                <p><strong>Date of Birth:</strong> June 22, 2001</p>
                <p><strong>Age:</strong> 23</p>
                <p><strong>Gender:</strong> Female</p>
                <p><strong>Nationality:</strong> Filipino</p>
            </div>
        </div>

        <div class="education-section">
            <h2 class="section-title">Educational Background</h2>
            <ul class="education">
                <li><strong>College:</strong> Isabela State University Echague Campus</li>
                <li><strong>High School:</strong> Alicia National High School</li>
                <li><strong>Elementary:</strong> Duminit Elementary School</li>
            </ul>
        </div>



    </div>
</body>
</html>
