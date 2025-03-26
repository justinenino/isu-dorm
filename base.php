<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0; 
            color: #333; 
            margin: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center; 
            max-width: 600px;
            width: 90%; 
        }

        .biodata {
            display: grid;
            grid-template-columns: 1fr; 
            grid-gap: 20px;
        }

        .biodata img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px auto; 
        }

        .info {
            text-align: left;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .info p {
            margin-bottom: 8px;
        }

        strong {
            display: inline-block;
            min-width: 120px;
            text-align: right;
            margin-right: 10px;
        }

        .data-content {
            display: grid;
            grid-template-columns: 1fr;
            grid-gap: 10px;
        }

        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BIODATA</h1>
        <div class="biodata">
            <img src="https://scontent.fcrk2-3.fna.fbcdn.net/v/t39.30808-6/467529145_2213447029038642_4779948601274720483_n.jpg?_nc_cat=110&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeGMgUrjx5VBjgRQ4hUr7Tqep1yJ5O8cbKunXInk7xxsqx3i_SwFw7MAgizmWCdm6bEdk7OeGpswPQknlopJ_orM&_nc_ohc=RMK5I9jAGJ4Q7kNvgFwT_lw&_nc_oc=Adiet7gZJ0_TY3DAsEIHxA1bDIz6n1AdFWt_h3aBnbYZDDGVctcXrihJGB3tkB1UPww&_nc_zt=23&_nc_ht=scontent.fcrk2-3.fna&_nc_gid=APA1GqC7k0ugxwPO-3GFaJs&oh=00_AYAOrwh8peGQBKauCRARmiLTDYXtuZQ74X1BWbnR9iNOrA&oe=67AD19C2" alt="Profile Picture">  <!-- REPLACE with your image URL -->
            <section class="data">
                
                <div class="data-content">
                <h1>Geraldine P. Base</h1>
                    <div class="data-item"><strong>Sex:</strong> Female</div>
                    <div class="data-item"><strong>Birthdate:</strong> August 28, 2003</div>
                    <div class="data-item"><strong>Address:</strong> Mabini, Alicia, Isabela</div>

                    <div class="data-item"><strong>Email:</strong> geraldinebase44@gmail.com</div>
                    <div class="data-item"><strong>Phone Number:</strong> 09556485163</div>
                    <div class="data-item"><strong>Course:</strong> BS Information Technology</div>
                    
                </div>
            </section>
        </div>
    </div>
</body>
</html>
