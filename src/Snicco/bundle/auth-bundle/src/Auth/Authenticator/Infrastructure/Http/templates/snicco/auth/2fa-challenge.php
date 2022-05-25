<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv='X-UA-Compatible' content='ie=edge'>
        <title>Two Factor Challenge</title>
    </head>
    <body>
        <form method="POST">
	        <h2>Two-Factor Authentication needed</h2>
		        <?= $input_fields ?>
	        <button type="submit">Submit</button>
        </form>
    </body>
</html>
