<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bio Data</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #b76e79, #e5c3c6);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease;
        }
        .container:hover {
            transform: scale(1.03);
        }
        .profile-img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #b76e79;
            display: block;
            margin: 0 auto 20px;
        }
        h1 {
            color: #b76e79;
            font-size: 28px;
            margin-bottom: 8px;
        }
        .bio-info {
            text-align: left;
            padding: 20px;
            background: #f5e1e3;
            border-radius: 10px;
            margin-top: 20px;
        }
        .bio-info p {
            margin: 10px 0;
            font-size: 17px;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            color: white;
            background-color: #b76e79;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 20px;
            transition: 0.3s;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #a05c68;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Replace the src with your actual image link -->
        <img src="https://scontent.fcrk2-2.fna.fbcdn.net/v/t39.30808-6/433842550_2079176569126115_8085826875952630750_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=a5f93a&_nc_ohc=DJxkQFtreIwQ7kNvgHKRRf8&_nc_oc=Adi6DflfNHgHnvZ_S9FM8I_mCxhs1tx79OwbYPpEuaWF5ElXskxrjmsUFadtQ8f1b_w&_nc_zt=23&_nc_ht=scontent.fcrk2-2.fna&_nc_gid=ADLkHqeIfqc8tq98YbBRVc1&oh=00_AYCaXCo7XWVwBZ4l5nnC9Bb7aRsU_6mfyshN70WexrU9sg&oe=67AD5AC1" alt="Profile Picture" class="profile-img">

        <h1>Javie Kaye T. Agustin</h1>
        <p><strong>Age:</strong> 20</p>
        <p><strong>Email:</strong> javiekaye.t.agustin@isu.edu.ph</p>

        <div class="bio-info">
            <p><strong>Address:</strong> Usol Jones, Isabela</p>
            <p><strong>Phone:</strong> 09055093228</p>
        </div>

        <a href="https://www.facebook.com/share/15ovqmnSmC/?mibextid=wwXIfr" class="btn" target="_blank">
            Visit My Facebook Profile
        </a>
    </div>
</body>
</html>