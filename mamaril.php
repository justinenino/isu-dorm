<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jef A. Mamaril</title>

</head>
<body>
    <div class="profile-wrapper">
        <div class="profile-container">
            <div class="profile-sidebar">
                <img src="https://scontent.fmnl4-3.fna.fbcdn.net/v/t39.30808-6/426988175_1546075556186227_6547270323913288562_n.jpg?_nc_cat=110&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeEzz4Q0RWxfRuMx33veQEh6M0HIvzitqKEzQci_OK2ooYnYPqMcQ2NX7QR1vH-rSxntkENGw68kP385J2NpYM4R&_nc_ohc=AlBbue-EMqcQ7kNvgHGQM3Z&_nc_oc=Adgn91WGb66SvapRelfXMsNa4gbkb7DQNaiPXweiSFCLCEt8J90WIF-c4jzdPN2Bc4Dxi24wgelXe4_9rhljIg3Y&_nc_zt=23&_nc_ht=scontent.fmnl4-3.fna&_nc_gid=AJnuJJCLj0y-LgaxUOCAIc9&oh=00_AYAkJNWzREb3G0rllSJmivLnm5LIpqsRoAZ70e_tUie0nw&oe=67ACFCD6" alt="Profile Image" class="profile-image">
                <h1 style="margin-top: 20px; text-align: center;">Jef A. Mamaril</h1>
                <p style="margin-top: 10px; opacity: 0.8;">Programmer | Singer | Dancer</p>

            </div>
            <div class="profile-details">
                <div class="section">
                    <h3>About Me</h3>
                    <p>Passionate performer dedicated to entertaining through music and dance. Also committed to develop IT solutions for everyone.</p>
                </div>
                <div class="section">
                    <h3>Skills/Hobbies</h3>
                    <ul>
                        <li>Cooking</li>
                        <li>Dancing</li>
                        <li>Singing</li>
                        <li>Web Designing</li>
                    </ul>
                </div>
                <div class="section">
                    <h3>Experience</h3>
                    <p>Member of a choir and dance group from elemetary to highschool. Started to like programming on year 2017 (1st Language used is Pascal) and continued to be a developer up until this day.</p>
                </div>
                <div class="section">
                    <h3>Contact</h3>
                    <p>Email: jefmamaril@gmail.com</p>
                    <p>Phone: +639756488535</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #ebc979 0%, #bd9843 100%);
        --text-color: #2c3e50;
        --hover-color: #d4a159;
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Inter', sans-serif;
        background: #f4f6f9;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        perspective: 1500px;
    }
    .profile-wrapper {
        width: 1000px;
        transform-style: preserve-3d;
        animation: subtleFloat 5s ease-in-out infinite;
    }
    @keyframes subtleFloat {
        0%, 100% { transform: translateY(0) rotateX(5deg); }
        50% { transform: translateY(-20px) rotateX(-5deg); }
    }
    .profile-container {
        background: white;
        border-radius: 25px;
        box-shadow: 0 30px 60px rgba(0,0,0,0.12);
        display: grid;
        grid-template-columns: 1fr 2fr;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    .profile-sidebar {
        background: var(--primary-gradient);
        color: white;
        padding: 40px 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    .profile-image {
        width: 250px;
        height: 250px;
        border-radius: 50%;
        border: 8px solid rgba(255,255,255,0.2);
        object-fit: cover;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        transition: all 0.5s ease;
        position: relative;
        z-index: 2;
    }
    .profile-image:hover {
        transform: scale(1.1) rotate(5deg);
    }
    .profile-sidebar::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(-45deg);
        animation: shine 5s linear infinite;
    }
    @keyframes shine {
        0% { transform: rotate(-45deg) translateX(-100%); }
        100% { transform: rotate(-45deg) translateX(100%); }
    }
    .profile-details {
        background: white;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .section {
        margin-bottom: 30px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .section:nth-child(1) { transition-delay: 0.2s; }
    .section:nth-child(2) { transition-delay: 0.3s; }
    .section:nth-child(3) { transition-delay: 0.4s; }
    .section:nth-child(4) { transition-delay: 0.5s; }
    .profile-container:hover .section {
        opacity: 1;
        transform: translateY(0);
    }
    .section h3 {
        color: var(--hover-color);
        border-bottom: 3px solid var(--hover-color);
        padding-bottom: 10px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }
    .section:hover h3 {
        letter-spacing: 2px;
        color: #000000;
    }
    .contact-btn {
        display: inline-block;
        padding: 12px 25px;
        background: var(--primary-gradient);
        color: white;
        text-decoration: none;
        border-radius: 30px;
        transition: all 0.3s ease;
        margin-top: 20px;
    }
    .contact-btn:hover {
        transform: scale(1.1) translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
</style>