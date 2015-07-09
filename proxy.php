<?php

if(isset($_GET['url']))
{
    $url = $_GET['url'];
}
else if(isset($_POST['url']))
{
    $url = $_POST['url'];
}

if(empty($url))
{
    die('Where you want to go?');
}

if(!contains($url, 'http'))
{
    $url = base64_decode($url);
}

$url = str_replace(array('+', ' '), '%20', $url);

$info = parse_url($url);
if(!isset($info['path']))
{
    $info['path'] = '/';
}

if(strtolower($info['host']) == strtolower($_SERVER['HTTP_HOST']))
{
    header('Location: /');
    die();
}

// define('PROXY_URL', 'io.php?url=');
define('PROXY_URL', '/go/');

$httpHeaders = [];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, "deflate,gzip");
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.130 Safari/537.36");
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies/" . str_replace('.', '_', $info['host']).'.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies/" . str_replace('.', '_', $info['host']).'.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    "Accept-Encoding: gzip, deflate",
    "Connection: Close"]);
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
    if(beginWith($header, 'Location:'))
    {
        $GLOBALS['httpHeaders']['location'] = trim(str_replace('Location:', '', $header));
    }
    else if(beginWith($header, 'Content-Type:'))
    {
        $GLOBALS['httpHeaders']['content-type'] = trim(str_replace('Content-Type:', '', $header));
    }
    else if(beginWith($header, 'HTTP/'))
    {
        if (contains($header, '200') == false)
        {
            define('HEADER_SET', 1);
            header($header);
        }
    }
    return strlen($header);
});

$content = curl_exec($ch);

curl_close($ch);

if (isset($httpHeaders['location']))
{
    header('Location: ' . url($httpHeaders['location']));
    die();
}

if(!isset($httpHeaders['content-type']))
{
    if(!defined('HEADER_SET'))
    {
        header('HTTP/1.1 500 Internal Server Error');    
    }
    die();
}

header('Content-Type:', $httpHeaders['content-type']);
// echo $httpHeaders['content-type'];

if(contains($httpHeaders['content-type'], 'text/html'))
{
    $content = parse_html_links($content);
}
else if(contains($httpHeaders['content-type'], 'text/css'))
{
    $content = parse_css($content);
}

echo $content;
// echo htmlspecialchars($content);

function beginWith($haystack, $needle)
{
    return (strpos($haystack, $needle) === 0);
}

function contains($haystack, $needle)
{
    return (strpos($haystack, $needle) !== false);
}

function url($url)
{
    $info = $GLOBALS['info'];
    // $domain = explode('.', $GLOBALS['info']['host']);

    if (beginWith($url, '#') || contains($url, PROXY_URL) || beginWith($url, 'javascript'))
    {
        return $url; // no changes
    }    

    if (beginWith($url, '//'))
    {
        $url = $info['scheme'] . ':' . $url;
    }

    // absolute path url
    if (beginWith($url, '/'))
    {
        return PROXY_URL . base64_encode($info['scheme'].'://'.$info['host'].$url);
    }

    if (beginWith($url, '..'))
    {
        // echo $url . '->' . relative_url($url) . "\n";
        return relative_url($url);
    }

    // full http url
    if (beginWith($url, 'http'))
    {
        // Comment this to save some resources
        return PROXY_URL . base64_encode($url);

        $i = parse_url($url);
        // $d = explode('.', $i['host']);

        if ($i['host'] === $info['host'])
        {   // same domain only
            return PROXY_URL . base64_encode($url);
        }
        else if(is_subdomain($GLOBALS['info']['host'], $i['host']))
        // else if(count($domain) > 2 && count($d) > 2 && "{$d[1]}.{$d[2]}" == "{$domain[1]}.{$domain[2]}")
        {   // same main domain
            return PROXY_URL . base64_encode($url);
        }
        else
        {
            return $url; // no changes
        }
    }

    // relative url
    return PROXY_URL . base64_encode($info['scheme'].'://'.$info['host'].dirname($info['path']).'/'.$url);
}

function is_subdomain($parent, $child)
{
    if(contains($child, $parent))
    {
        return true;
    }

    $parent_parts = explode('.', $parent);
    if(count($parent_parts) > 1)
    {
        $com = array_pop($parent_parts);
        $name = array_pop($parent_parts);

        if(contains($child, $name.'.'.$com))
        {
            return true;
        }
    }

    return false;
}

function relative_url($url)
{
    $info = $GLOBALS['info'];
    $path = $info['path'];

    if ($path{strlen($path)-1} != '/')
    {
        $path = dirname($path) . '/';
    }

    $path = explode('/',substr($path, 1, strlen($path)-2));
    $urls = explode('/', $url);

    $realpath = [];
    foreach($urls as $u)
    {
        if($u == '')
        {
            continue;
        }
        if($u == '..')
        {
            array_pop($path);
            continue;
        }
        $realpath[] = $u;
    }

    $absoluteUrl = $info['scheme'].'://'.$info['host'];

    if(count($path))
    {
        $absoluteUrl .= '/' . join('/', $path);
    }
    if(count($realpath))
    {
        $absoluteUrl .= '/' . join('/', $realpath);
    }

    return PROXY_URL . base64_encode($absoluteUrl);
}

function parse_css($content)
{
    $content = preg_replace_callback("#url\([\'\"]?([^\'\"\)]+)[\'\"]?\)#", function($m) {
        $href = $m[1];
        return str_replace($href, url($href), $m[0]);
    }, $content);    

    $content = preg_replace_callback("#import\s+[\'\"]?([^\'\"]+)[\'\"]?#", function($m) {
        $href = $m[1];
        return str_replace($href, url($href), $m[0]);
    }, $content);    

    return $content;
}

function parse_html_links($content)
{
    return preg_replace_callback("@(src|href)\s?=\s?[\'\"\(]([^\'\"\)]+)[\'\"\)]@", function($m) {
        $href = $m[2];
        return str_replace($href, url($href), $m[0]);
    }, $content);
}
