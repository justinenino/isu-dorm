<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bio Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 700px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            margin: auto;
            text-align: center;
            margin-top: 50px;
        }
        img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
        }
        .table {
            margin-top: 15px;
        }
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .label {
            font-weight: bold;
            color: #007bff;
            width: 40%;
        }
        .hobbies {
            margin-top: 20px;
            text-align: left;
        }
        .hobbies ul {
            list-style: none;
            padding: 0;
        }
        .hobbies ul li {
            background: #007bff;
            color: white;
            padding: 8px;
            border-radius: 5px;
            margin: 5px 0;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-3">My Bio Data</h2>
        <img src="https://scontent.fmnl3-2.fna.fbcdn.net/v/t39.30808-6/457108488_1881203995714259_7685706730043315013_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeF0vVjLbgX-XmGwUgAx-wrT-hE_mIuvd776ET-Yi693vmPiMsMZx58KCkGcUI9_aSy-Dt-EidDHBMe464-OTzAw&_nc_ohc=B3GRP7gCFw8Q7kNvgERQ11v&_nc_oc=AdiI6iX25qjAKDMG5wRuPMmy-yiuP9dDwqmQtNVIMzO4ULhHwWjMOgPCT74CMkWQTpI&_nc_zt=23&_nc_ht=scontent.fmnl3-2.fna&_nc_gid=AYHukotc0Tag03Eq8qiVCQL&oh=00_AYB2g-JjHigpz2QSX0RJWkRYGfN1Z7GDBBbu2_zr_Fb6RQ&oe=67AD097F" alt="Your Picture" class="mb-3">
        <table class="table">
            <tr>
                <td class="label">Full Name:</td>
                <td>John Mark A. Borja</td>
            </tr>
            <tr>
                <td class="label">Date of Birth:</td>
                <td>September 16, 2004</td>
            </tr>
            <tr>
                <td class="label">Gender:</td>
                <td>Male</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td>Purok 6 Fugu, Echague, Isabela</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td>johnmark.a.borja@isu.edu.ph</td>
            </tr>
            <tr>
                <td class="label">Phone:</td>
                <td>09920341318</td>
            </tr>
            <tr>
                <td class="label">Nationality:</td>
                <td>Filipino</td>
            </tr>
            <tr>
                <td class="label">Marital Status:</td>
                <td>Single</td>
            </tr>
            <tr>
                <td class="label">Occupation:</td>
                <td>Student</td>
            </tr>
            <tr>
                <td class="label">Education:</td>
                <td>Bachelor of Science in Information Technology</td>
            </tr>
        </table>
        <div class="hobbies">
            <h4>Hobbies & Interests</h4>
            <ul>
                <li>Volleyball</li>
                <li>Gaming</li>
                <li>Manga</li>
                <li>Anime</li>
            </ul>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>