<?
	ob_start('ob_gzhandler');
	
	require_once 'core.php';

    if (! ($person = getSessionPerson())) {

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="RU">
<head><title>Доступ к закрытой части сайта | <?=title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" type="text/css" href="<?=www?>core/css/main.css"/>
<script type="text/javascript">var www = '<?=www?>';</script>
<script type="text/javascript" src="<?=www?>core/js/base.js" charset="windows-1251">//<!--"--></script>
<script type="text/javascript" src="<?=www?>core/js/auth.js" charset="windows-1251">//<!--"--></script>
<style type="text/css">
body { margin: 0 0 0 1em; }
h2 { margin: 0 0 0.5em 0; }

.form { width: 46%; margin: 1em 0 1em 0; padding: 1%; }
.form input { font-size: 100%; margin: 0.5em 0 0 0; padding: 0.15em; }
.form label { display: block; margin: 0 0 0 0; }
.form label input { display: block; font: 100%/100% sans-serif; margin: 0 0 0.5em 0; padding: 0; width: 60%;}
.form label textarea { display: block; width: 99%; }
.form .error, .form .notice { font-weight: bold; }

.odd { background: #FFC; border: 1px dotted #CCC; }
.floatleft { float: left; }
.floatright { float: right; margin-right: 2%;}
.form label input.inline { width: auto; margin: 0 0 0.2em 0.15em; }
.menu { margin-left: -1em; }
</style>
</head>
<body>
<div class="menu"><div class="person"><a href="<?=www?>">Домой</a></div></div>
<h1>Закрытая часть сайта &raquo; <a href="<?=www?>"><?=title?></a></h1>

<div class="form floatright odd">
<h2>Регистрация</h2>
<p>Если Вы еще не регистрировались на нашем сайте, можно сделать это прямо сейчас.</p>
<p><strong>Все поля обязательны.</strong></p>
<form method="get" action="" onsubmit="javascript:return register()">
<label for="reglogin">Логин (последовательность из латинских букв и цифр): <input type="text" id="reglogin"/></label>
<label for="regpassword">Пароль: <input type="text" id="regpassword"/></label>
<label for="name">Ваше имя: <input type="text" id="name"/></label>
<label for="email">Ваш e-mail (для связи, на сайте не публикуется): <input type="text" id="email"/></label>
<label for="info">Информация о Вас (будет опубликована на сайте): <textarea id="info" rows="7" cols="60"></textarea> 
<? /*(Можно использовать HTML-тэги <b>&lt;a&gt;</b> и <b>&lt;b&gt;</b>)*/?></label>
<div id="regnotice"></div>
<input type="submit" value="Зарегистрироваться"/>
</form>
</div>

<div class="form floatleft">
<h2>Вход</h2>
<p>Для доступа к этой странице Вам необходимо ввести логин и пароль.</p>
<form method="get" action="" onsubmit="javascript:return auth()">
<label for="login">Логин: <input type="text" id="login"/></label>
<label for="password">Пароль: <input type="password" id="password"/></label>
<label for="remember" class="inline">Запомнить меня надолго <input class="inline" type="checkbox" id="remember"/> :)</label>
<div id="authnotice"></div>
<input type="submit" value="Войти"/>
</form>
</div>

<div class="form">
<h2>Вспомнить пароль</h2>
<p>Если Вы забыли пароль или логин, укажите e-mail, который Вы использовали при регистрации
на этом сайте. Мы вышлем Вам новый пароль письмом на указанный адрес.
<form method="get" action="" onsubmit="javascript:return forget()">
<label for="forgetemail">Ваш e-mail: <input type="text" id="forgetemail"/></label>
<div id="forgetnotice"></div>
<input type="submit" value="Выслать"/>
</form>
</div>

<p class="copyright">WikiCrowd by <a href="http://davidovsv.narod.ru/">Stas Davydov</a> and <a href="http://outcorp-ru.blogspot.com/">Outcorp</a> &#0169; 2008-2009.</p>
</body>
</html>
<?
	} else {
		header('Location: '.www.'?ts='.time());
		exit;
	}
	ob_end_flush();
?>