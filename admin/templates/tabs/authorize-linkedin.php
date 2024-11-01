<form action="options.php" style="margin-top:5%;" method="post">
	<?php 
		settings_fields('wplinkpressPlugin');
		do_settings_sections('wplinkpressPlugin');
		submit_button();
	?>
</form>
<?php

