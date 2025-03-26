<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <img src="https://cdn.donmai.us/original/1c/5a/__girls_frontline_and_2_more_drawn_by_erich__1c5acce10b7104675175e31734929ff1.jpg" 
        alt="Background Image" class="background-image">
    
    <div class="container">
        <section class="hero">
            <img src="https://scontent.fmnl33-3.fna.fbcdn.net/v/t1.15752-9/473091392_1583772898938864_8817752505929616452_n.jpg?_nc_cat=109&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeF_uYcK4Bo__Yy75Kcffr4tenDtXiPeyyR6cO1eI97LJHOqqqCHQymNZR3Sfj7jwgwgs3v4rN6lhrUstNu5JxDy&_nc_ohc=_JwG0HpdkaIQ7kNvgGsPD36&_nc_oc=AdgAa_2N3gM-hUBSjDtV14dGU0bRh097qD8v9kpQaoO-EoiYIFCHA6F1oAiE_u2jA1E&_nc_zt=23&_nc_ht=scontent.fmnl33-3.fna&oh=03_Q7cD1gEPhtG5UFS-Snv7jH3FfdM1ODp6-lGlhi0jGDvtQl8iSQ&oe=67CD3EF2" 
                alt="Main Image" class="main-image">
            <div class="hero-text">
                <h1>Eli Miguel Depra</h1>
                <p> <b> <i> "The Laziest."</i></b></p>
                <div class="links">
                    <a href="https://www.youtube.com/@depra_from_isumain"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/YouTube_full-color_icon_%282017%29.svg/1024px-YouTube_full-color_icon_%282017%29.svg.png" alt=""></a>
                    <a href="https://steamcommunity.com/id/AventusAretino/"><img src="https://upload.wikimedia.org/wikipedia/commons/8/83/Steam_icon_logo.svg" alt="https://steamcommunity.com/id/AventusAretino/"></a>
                    <a href="https://open.spotify.com/user/i4zwmijoqf5cjjfkw07iycc8e"><img src="https://storage.googleapis.com/pr-newsroom-wp/1/2023/05/Spotify_Primary_Logo_RGB_Green.png" alt=""></a>
                    <a href="https://discord.com/users/526737255319207938"><img src="https://cdn.prod.website-files.com/6257adef93867e50d84d30e2/636e0a6a49cf127bf92de1e2_icon_clyde_blurple_RGB.png" alt="Discord"></a>
                </div>
            </div>
        </section>
        
        <section class="data">
            <h2>DATA</h2>
            <div class="data-content">
                <div class="data-item"><strong>Birthdate:</strong> November 21st, 2003</div>
                <div class="data-item"><strong>Address:</strong> San Fabian, Echague, Isabela</div>
                <div class="data-item"><strong>Email:</strong> elimiguel.a.depra@gmail.com</div>
                <div class="data-item"><strong>Phone Number:</strong> 09999209714</div>
                <div class="data-item"><strong>Hidden Talent:</strong> I'd like to keep it hidden</div>
                <div class="data-item"><strong>Would you study?:</strong> Nah, I'd chat GPT.</div>
            </div>
        </section>
    </div>
</body>
</html>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        position: relative;
        overflow: hidden;
    }

    .background-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: 20% center;
        z-index: -2;
    }

    body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(8px);
        z-index: -1;
    }

    .container {
        width: 85%;
        background: rgba(0, 0, 0, 0.85);
        padding: 40px;
        border-radius: 15px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        position: relative;
        z-index: 1;
    }

    .hero {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
        gap: 30px;
    }

    .hero img {
        width: 200px;
        height: 250px;
        object-fit: cover;
    }

    .hero-text {
        text-align: left;
        max-width: 40%;
    }

    .data {
        margin-top: 20px;
        text-align: left;
        width: 100%;
    }

    .data-content {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .links {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
        margin-top: 45px;
    }

    .links img {
        width: 45px;
        height: auto;
        cursor: pointer;
    }
</style>
