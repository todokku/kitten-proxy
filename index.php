<?php
$requestUri = $_SERVER['REQUEST_URI'];

if(isset($_POST['url']))
{
    require_once "proxy.php";
}
else if(strpos($requestUri, '/go/') === 0 && strlen($requestUri) > 4)
{
    $_GET['url'] = substr($requestUri, 4);
    require_once "proxy.php";
}
else 
{
?>
<!DOCTYPE html>
<html>
<head>
    <title>I'm a Kitten!</title>
    <link rel="stylesheet" href="/css/style.css" type="text/css" media="screen" />
</head>
<body>
<div id="container">
    <div id="logo"><img src="http://placekitten.com/g/200/300" border="0" /></div>
    <div><h2>Hi, I'm a kitten!</h2></div>
    <form method="post" action="/">
        <input type="text" name="url" placeholder="http://anywhere.you.want" /> <input type="submit" value="Go &raquo;" />
    </form>
</div>


</body>
</html>
<?php } ?>