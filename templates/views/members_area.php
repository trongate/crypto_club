<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
	<link rel="stylesheet" href="<?= BASE_URL ?>css/members_area.css">
	<title>Members' Area (private)</title>
</head>
<body>
	<div class="container">
		<p style="text-align: right;">
			<?php
			echo anchor('members/logout', 'Logout', array('class' => 'button'));
		    ?>
		</p>
		<?= Template::display($data) ?>
	</div>
</body>
</html>