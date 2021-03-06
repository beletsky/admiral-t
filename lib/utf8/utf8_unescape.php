<?php

/**
 * ������� ���������� ������ � ������� %uXXXX � ������ ������� UTF-8.
 * ������� �������� ��� ������������� ���������� iconv!
 *
 * ������� ������������ ��� ������������� ������ ���� "%u0442%u0435%u0441%u0442",
 * �������������� ���������� �������� javascript://encode().
 * ������������� ������������ ������� javascript://encodeURIComponent().
 *
 * ���������
 * ���������� ������ %uXXXX ��������� ������������ ������ ������ �� ��������� UCS-2, �.�. �� U+0 �� U+FFFF
 *
 * @param    string   $s
 * @param    bool     $is_rawurlencode
 * @return   string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.1.0
 */
function utf8_unescape($s, $is_rawurlencode = false)
{
    if (! is_string($s) || strpos($s, '%u') === false) return $s; #use strpos() for speed improving
    if (! function_exists('utf8_chr')) include_once 'utf8_chr.php';
    return preg_replace_callback('/%u([\da-fA-F]{4})/sS', $is_rawurlencode ? '__utf8_unescape_rawurlencode' : '__utf8_unescape', $s);
}

function __utf8_unescape(array $m)
{
    $codepoint = hexdec($m[1]);
    return utf8_chr($codepoint);
}

function __utf8_unescape_rawurlencode(array $m)
{
    $codepoint = hexdec($m[1]);
    return rawurlencode(utf8_chr($codepoint));
}

?>