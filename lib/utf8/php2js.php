<?php
/**
 * Convert PHP scalar, array or hash to JS scalar/array/hash.
 *
 * � PHP/5.2.0 ��������� ������� json_encode(), �� ����� php2js() ����������������:
 *   1) �������� �� ������ � UTF-8, �� � � ������ ������� ������������� ����������� (��������: windows-1251, koi8-r)
 *   2) ����� �������������� �����, �������������� ��������� ���� ������ � �����. �������� ���� ������ (�����)
 *   3) ��������� ���������� ��� � javascript ��� ���������� � html ����� ������ <script></script>
 *
 * @param    mixed    $a
 * @param    char     $quote               ������ ������������ (`'` ��� `"`)
 * @param    bool     $is_convert_numeric  �����, �������������� ��������� ���� ������ � ������������ � �����. �������� ���� ������
 *                                         �����������, �����, ��������, XML ������ ���������� ����� ��� ������
 * @retun    string
 *
 * @link     http://www.json.org/
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Dmitry Koterov <dklab.ru>, Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.0.4
 */
function php2js($a, $quote = '"', $is_convert_numeric = false, $_is_key = false)
{
    if (is_null($a)) return strlen($quote) == 0 ? '#DATA_NULL' : 'null';
    if ($a === false) return 'false';
    if ($a === true)  return 'true';
    if (is_scalar($a))
    {
        if ( (! $_is_key && ! $is_convert_numeric && ! is_string($a))
             ||
             #�����, �������������� ��������� ���� ������ � ������������ � �����. �������� ���� ������
             #����������� ������� is_numeric() �� ��������, ��. ������������
             (! $_is_key && $is_convert_numeric && (is_int($a) || is_float($a) || ctype_digit($a) || preg_match('/^-?(?!=0)\d+(?>\.\d+)?(?>[eE]\d+)?$/sS', $a)))
           ) return str_replace(',', '.', $a); #always use "." for floats

        #http://www.ecma-international.org/publications/files/ECMA-ST/Ecma-262.pdf#SingleEscapeCharacter
        static $escape_table = array(
            "\x08" => '\b',   #backspace
            "\x09" => '\t',   #horizontal tab
            "\x0a" => '\n',   #line feed (new line)
            "\x0b" => '\v',   #vertical tab
            "\x0c" => '\f',   #form feed
            "\x0d" => '\r',   #carriage return
            "\x22" => '\"',   #double quote
            "\x27" => "\'",   #single quote
            "\x5c" => '\\\\', #backslash
            #addition specially for "</script":
            "\x2f" => '\\/',  #slash
        );
        $a = strtr($a, $escape_table);
        #$a = str_replace(array("\r", "\n"), array('\r', '\n'), addslashes($a));
        #���������� ������ ������� javascript ��������� ���������: ��� ���� ��������� "</script>" � �� ����������� �� ���������!
        #$a = preg_replace('/<\\/(script)/si', '<\\x2f$1', $a);
        return $quote . $a . $quote;
    }
    if (! is_array($a) || ! strlen($quote))
    {
        $a = '#DATA_' . gettype($a);  #"����������" ����������
        return $quote . $a . $quote;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
        if (key($a) !== $i)
        {
            $isList = false;
            break;
        }
    }
    $result = array();
    if ($isList)
    {
        foreach ($a as $v) $result[] = php2js($v, $quote, $is_convert_numeric);
        return '[' . join(',', $result) . ']';
    }
    else
    {
        #� IE-5.01 ���� ������: ������ ������� ����� ��� ����� ����, �.�. ������!
        foreach ($a as $k => $v)
        {
            $result[] = php2js($k, $quote ? $quote : '"', $is_convert_numeric, true) .
                        ':' .
                        php2js($v, $quote, $is_convert_numeric);
        }
        return '{' . join(',', $result) . '}';
    }
}
?>