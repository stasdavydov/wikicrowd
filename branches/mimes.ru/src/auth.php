<?
	if (ob_get_level() == 0)
		ob_start('ob_gzhandler');
	
	require_once 'core.php';
	
	$person = getSessionPerson();
    if (isGuest($person)) {

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LOCALE?>">
<head><title><?=getMessage('RestrictedAccess')?> &#0187; <?=title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="shortcut icon" href="<?=www?>core/img/favicon.gif" />
<link rel="stylesheet" type="text/css" href="<?=www?>core/css/main.css"/>
<script type="text/javascript">var www = '<?=www?>';</script>
<script type="text/javascript" src="<?=www?>core/js/base.js">//<!--"--></script>
<script type="text/javascript" src="<?=www?>core/js/locale.js" charset="utf-8">//<!--"--></script>
<script type="text/javascript" src="<?=www?>core/js/auth.js">//<!--"--></script>
<style type="text/css">
body { margin: 0 0 0 1em; }
p.copyright { margin-left: -1.25em; }
h2 { margin: 0 0 0.5em 0; }

.form { width: 46%; margin: 1em 0 1em 0; padding: 1%; }
.form input { font-size: 100%; margin: 0.5em 0 0 0; padding: 0.15em; }
.form label { display: block; margin: 0 0 0 0; }
.form label input { display: block; font: 100%/100% sans-serif; margin: 0 0 0.5em 0; padding: 0; width: 60%;}
.form label textarea { display: block; width: 98%; }
.form .error, .form .notice { font-weight: bold; }

.odd { background: #FFC; border: 1px dotted #CCC; }
.floatleft { float: left; }
.floatright { float: right; margin-right: 1%;}
.form label input.inline { width: auto; margin: 0 0 0.2em 0.15em; }
.menu { margin-left: -1em; }

#regpassword { color: #DDD; }
</style>
</head>
<body>
<?
	menu('auth');

?><h1><?=getMessage('RestrictedArea')?> &raquo; <a href="<?=www?>"><?=title?></a></h1>
<?
	if (anyoneCanRegister) {
?>
<div class="form floatright odd">
<h2><?=getMessage('Registration')?></h2>
<p><?=getMessage('NotRegisteredYet')?></p>
<p><strong><?=getMessage('AllFieldsRequired')?></strong></p>
<form method="get" action="" onsubmit="javascript:return register()">
<label for="reglogin"><?=getMessage('LoginField')?>: <input type="text" id="reglogin"/></label>
<label for="regpassword"><?=getMessage('Password')?>: <input type="text" id="regpassword"/></label>
<label for="name"><?=getMessage('YourName')?>: <input type="text" id="name"/></label>
<label for="email"><?=getMessage('YourEmail')?>: <input type="text" id="email"/></label>
<label for="info"><?=getMessage('YourInformation')?>: <textarea id="info" rows="7" cols="60"></textarea></label>
<div id="regnotice"></div>
<input type="submit" value="<?=getMessage('Register')?>"/>
</form>
</div>
<?
	}
?>
<div class="form floatleft">
<h2><?=getMessage('Enter')?></h2>
<p><?=getMessage('EnterLoginAndPassword')?></p>
<form method="get" action="" onsubmit="javascript:return auth()">
<label for="login"><?=getMessage('YourLogin')?>: <input type="text" id="login"/></label>
<label for="password"><?=getMessage('Password')?>: <input type="password" id="password"/></label>
<label for="remember" class="inline"><?=getMessage('RememberMe')?> <input class="inline" type="checkbox" id="remember"/> :)</label>
<div id="authnotice"></div>
<input type="submit" value="<?=getMessage('Login')?>"/>
</form>
</div>

<div class="form">
<h2><?=getMessage('ForgotPassword')?></h2>
<p><?=getMessage('IfYourForgetPassword')?></p>
<form method="get" action="" onsubmit="javascript:return forget()">
<label for="forgetemail"><?=getMessage('YourEmailField')?>: <input type="text" id="forgetemail"/></label>
<div id="forgetnotice"></div>
<input type="submit" value="<?=getMessage('Send')?>"/>
</form>
</div>

<?
	copyright();

?></body>
</html>
<?
	} else {
		header('Location: '.www.'?ts='.time());
		exit;
	}
	if (ob_get_level() == 1)
		ob_end_flush();
?>