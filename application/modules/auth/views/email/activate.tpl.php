<html>
<body>
    <h1><?php echo sprintf(lang('email_activate_heading'), $identity);?></h1>
    <p>If you have any questions, please email us as support@strifeproleague.com, or use our contact form on the website.</p>
    <p>Please activate your account via: <?php echo sprintf(lang('email_activate_subheading'), anchor('auth/activate/'. $id .'/'. $activation, lang('email_activate_link')));?></p>
</body>
</html>