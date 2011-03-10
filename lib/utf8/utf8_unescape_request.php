<?php

/**
 * 1) ������������ ���������� ������� $_GET, $_POST, $_COOKIE, $_REQUEST
 *    ��������� �������� � �������, �������������� ����� ������� javascript escape() ~ "%uXXXX"
 *    C���������� PHP 5.2.x ����� ������ �� �����.
 * 2) ���� � HTTP_COOKIE ���� ��������� � ���������� ������, �� ������ ��������� ��������,
 *    � �� ������ (��� �������������� QUERY_STRING).
 * 3) ������ ������ $_POST ��� ������������� Content-Type, ��������, "Content-Type: application/octet-stream".
 *    ����������� PHP 5.2.x ������ ������ ������ ��� "Content-Type: application/x-www-form-urlencoded"
 *    � "Content-Type: multipart/form-data".
 *
 * @return   void
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.3.0
 */
function utf8_unescape_request()  #������ �������� ������� - fixRequestUnicode()
{
    $fixed = false;
    /*
    ATTENTION!
    HTTP_RAW_POST_DATA is only accessible when Content-Type of POST request is NOT default "application/x-www-form-urlencoded"!
    */
    $HTTP_RAW_POST_DATA = strcasecmp(@$_SERVER['REQUEST_METHOD'], 'POST') == 0 ? (isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : @file_get_contents('php://input')) : null;
    if (ini_get('always_populate_raw_post_data')) $GLOBALS['HTTP_RAW_POST_DATA'] = $HTTP_RAW_POST_DATA;
    foreach (array('_GET'    => @$_SERVER['QUERY_STRING'],
                   '_POST'   => $HTTP_RAW_POST_DATA,
                   '_COOKIE' => @$_SERVER['HTTP_COOKIE']) as $k => $v)
    {
        if (! is_string($v)) continue;
        if ($k === '_COOKIE')
        {
            /*
            ���������
            PHP �� ��������� (?) ������������ ��������� HTTP_COOKIE, ���� ��� ����������� ��������� � ���������� ������, �� ������� ����������.
            ������ HTTP-���������: "Cookie: sid=chpgs2fiak-330mzqza; sid=cmz5tnp5zz-xlbbgqp"
            � ���� ������ �� ���� ������ ��������, � �� ���������.
            ���� ���� � QUERY_STRING ���� ����� ��������, ������ ������ ��������� ��������, ��� ���������.
            � HTTP_COOKIE ��� ��������� � ���������� ������ ����� ���������, ���� ��������� ������� ��������� HTTP-���������:
            "Set-Cookie: sid=chpgs2fiak-330mzqza; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=domain.com"
            "Set-Cookie: sid=cmz6uqorzv-1bn35110; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=.domain.com"
            ��. ��� ��: RFC 2965 - HTTP State Management Mechanism <http://tools.ietf.org/html/rfc2965>
            */
            $v = preg_replace('/; *+/sS', '&', $v);
            unset($_COOKIE); #����� ������� HTTP_COOKIE ����
        }
        if (strpos($v, '%u') !== false)
        {
            if (! function_exists('utf8_unescape')) include_once 'utf8_unescape.php';
            parse_str(utf8_unescape($v, $is_rawurlencode = true), $GLOBALS[$k]);
            $fixed = true;
            continue;
        }
        if (@$GLOBALS[$k]) continue;
        parse_str($v, $GLOBALS[$k]);
        $fixed = true;
    }#foreach
    if ($fixed)
    {
        $_REQUEST =
            (isset($_COOKIE) ? $_COOKIE : array()) +
            (isset($_POST) ? $_POST : array()) +
            (isset($_GET) ? $_GET : array());
    }
}

?>