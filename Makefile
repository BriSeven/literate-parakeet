

sync: mp.css index.php
	scp index.php linode:www/tools/logger.php
	scp mp.css linode:www/tools/mp.css

start: 
	php -S 0.0.0.0:8080