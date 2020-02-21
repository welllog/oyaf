<?php
function dump($var, $echo=true, $label=null, $strict=true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else {
        return $output;
    }
}

function filterInput(array $params) {
    foreach ($params as $k => &$v) {
        if (is_array($v)) {
            $v = filterInput($v);
        } elseif (is_numeric($v)) {

        } else {
            $v = htmlspecialchars(trim($v), ENT_QUOTES);
        }
    }
    return $params;
}

function getIp() {
    if(isset($_SERVER["HTTP_CLIENT_IP"]) and strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")){
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]) and strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")){
        return $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
    if(isset($_SERVER["REMOTE_ADDR"])){
        return $_SERVER["REMOTE_ADDR"];
    }
    return "";
}

function prettyJson($enc) {
    return json_encode($enc, JSON_UNESCAPED_UNICODE);
}

function array2Map($array, $key, \Closure $handler = null)
{
    $map = [];
    foreach ($array as $row) {
        if (isset($row[$key])) {
            $index = $row[$key];
            $row = $handler ? $handler($row) : $row;
            $map[$index] = $row;
        }
    }
    return $map;
}