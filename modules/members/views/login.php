<h1>Member Login</h1>
<p>Please fill out the form below and then hit 'Submit'.</p>
<?php
echo validation_errors();
echo form_open('members/submit_login');
echo form_label('Username');
$attr['placeholder'] = 'Enter username here';
$attr['autocomplete'] = 'off';
echo form_input('username', '', $attr);

$attr['placeholder'] = 'Enter password here';
echo form_label('Password');
echo form_password('password', '', $attr);

echo '<p>Remember me ';
echo form_checkbox('remember', 1);
echo '</p>';

echo form_submit('submit', 'Submit');
echo anchor(BASE_URL, 'Cancel', array('class' => 'button alt'));

echo form_close();