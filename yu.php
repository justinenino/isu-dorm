<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bio Data</title>
	<style>
		body {
			font-family: 'Poppins', sans-serif;
			margin: 0;
			padding: 0;
			background-color: #f9f9f9;
			color: #333;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
			min-height: 100vh;
		}

		.header {
			background: #2c3e50;
			color: white;
			text-align: center;
			padding: 20px;
			font-size: 24px;
			font-weight: bold;
			width: 90%;
			max-width: 900px;
			border-radius: 10px;
			margin-bottom: 20px;
		}

		.container {
			width: 90%;
			max-width: 900px;
			display: flex;
			flex-direction: row;
			gap: 20px;
			background: white;
			padding: 20px;
			border-radius: 10px;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
		}

		.sidebar {
			width: 250px;
			background: #ecf0f1;
			padding: 20px;
			border-radius: 10px;
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		.profile-img {
			width: 120px;
			height: 120px;
			border-radius: 50%;
			border: 4px solid #2c3e50;
			margin-bottom: 10px;
		}

		.info-section {
			flex: 1;
			display: flex;
			flex-direction: column;
			gap: 15px;
		}

		.info-card {
			background: white;
			padding: 15px;
			border-radius: 10px;
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
			border-left: 5px solid #2c3e50;
		}

		.footer {
			text-align: center;
			background: #2c3e50;
			color: white;
			padding: 15px;
			font-size: 14px;
			border-radius: 10px;
			width: 90%;
			max-width: 900px;
			margin-top: 20px;
		}
	</style>
</head>

<body>
	<div class="header">My Bio Data</div>

	<div class="container">
		<div class="sidebar">
			<img class="profile-img" src="https://scontent.fmnl8-6.fna.fbcdn.net/v/t39.30808-6/350349609_2194367594094440_6826836780756769668_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeFyWmzUyJVbAn_BS42DHCqhPe-r00ldWAU976vTSV1YBXLWyPRSY0wWpZw63ZDrnsmO2ljXsFhKXz3vZN4qx7xx&_nc_ohc=FbuO2vre8XIQ7kNvgHrhPDE&_nc_oc=AdigHmJ6CQari2dPfJutY62JQ33-GqFLe7haJx7b7Bt5waHkd-uWrK-YClku8mVkHeQ&_nc_zt=23&_nc_ht=scontent.fmnl8-6.fna&_nc_gid=Ar8f8rUtxJ-JIDNIZNIaT8n&oh=00_AYDcU3BuxyGuMFIJTEUnFxiSkZYeRdm123yXTVoI4ShM3A&oe=67AE5C8C" alt="Profile Picture">
			<h2>John Ray B. Yu</h2>
			<p><strong>IT Student</strong></p>
			<p><strong>BSIT 3-1 WMAD</strong></p>
		</div>

		<div class="info-section">
			<div class="info-card">
				<h3>Personal Information</h3>
				<p><strong>Age:</strong> 21 years old</p>
				<p><strong>Birthday:</strong> January 23, 2003</p>
				<p><strong>Address:</strong> San Mateo, Isabela</p>
				<p><strong>Religion:</strong> Christian</p>
				<p><strong>Height:</strong> 5'9</p>
				<p><strong>Weight:</strong> 58 kg</p>
				<p><strong>Blood Type:</strong> A</p>
			</div>

			<div class="info-card">
				<h3>Educational Attainment</h3>
				<p><strong>Elementary:</strong> San Mateo East Central School</p>
				<p><strong>High School:</strong> San Andres Vocational and Industrial High School</p>
				<p><strong>College:</strong> Example University</p>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="info-card" style="width: 100%;">
			<h3>Contact Information</h3>
			<p><strong>Email:</strong> yujohnray96@gmail.com</p>
			<p><strong>Facebook:</strong> <a href="https://www.facebook.com/johnray.yu.35">John Ray Yu</a></p>
		</div>
	</div>

	<div class="footer">
		<p><strong>Happy to Serve you</strong></p>
	</div>
</body>

</html>