<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Biodata</title>
   
</head>
<body>
    
    <div class="container">
    <img src="picture.jpg" alt="" id="picture">
        <div class="profile-card">
            <div class="header">
                <h1>Antonino Balinado</h1>
                <p>IT Student</p>
            </div>

            <div class="section">
                <h2>Personal Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Age</div>
                        <div>20 years old</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Birthday</div>
                        <div>June 11, 2004</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Address</div>
                        <div>Bagnos Alicia Isabela</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Education</h2>
                <div class="info-item">
                    <div class="label">College</div>
                    <div>Isabela State University</div>
                    <div>Bachelor of Science in Information Technology</div>
                    <div>2020 - Present</div>
                </div>
            </div>

            <div class="section">
                <h2>Skills</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Programming</div>
                        <div>PHP, HTML, CSS, JavaScript</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Tools</div>
                        <div>VS Code, Git, GitHub</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Contact Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Email</div>
                        <div>antonino.r.balinadojr@isu.edu.ph</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Phone</div>
                        <div>secret</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f0f2f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        

        h1 {
            color: #1a73e8;
            margin-bottom: 10px;
        }

        .section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #1a73e8;
            background: #f8f9fa;
        }

        .section h2 {
            color: #1a73e8;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            color: #666;
        }
        #picture{
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto;
            display: block;
            margin: 50px auto;
            border:3px solid #FFFCFCFF;
        }
    </style>

</html>s