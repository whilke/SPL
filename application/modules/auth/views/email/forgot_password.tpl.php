<html>
<body>
    <h1>Hello <?php echo sprintf(lang('email_forgot_password_heading'), $identity);?></h1>
    <p>Someone has requested a new password for your account on www.strifeproleague.com</p>
    <p>Please follow this link to complete that request : <?php echo sprintf(lang('email_forgot_password_subheading'), anchor('auth/reset_password/'. $forgotten_password_code, lang('email_forgot_password_link')));?></p>
</body>
</html>