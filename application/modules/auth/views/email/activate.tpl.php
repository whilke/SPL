<html>
<body>
    <h1><?php echo sprintf(lang('email_activate_heading'), $identity);?>, Welcome to the Strife Pro League.</h1>
    <p>Please activate your account via: <?php echo sprintf(lang('email_activate_subheading'), anchor('auth/activate/'. $id .'/'. $activation, lang('email_activate_link')));?></p>
</body>
</html>