<div class="wrap">
<h2>Myriad Integration Options</h2>
<form method="post" action="options.php"> 
<?php settings_fields( 'myriad_settings' );
do_settings_sections( 'myriad_settings' );
submit_button(); ?>
</form>
</div>