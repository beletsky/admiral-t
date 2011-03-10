<?php

/**
 * "���������" ��������� ���� ��� ����������� ��������� ������.
 * ���� ��� ��������� ���� ��� ����� ���� � html ���� � ��������� �� ��������� ������.
 * ����� ������ ���� � ��������� UTF-8.
 * �������������� ����������, �������, ���������, �������� �����.
 *
 * @param  string     $s               �����, � ������� ������
 * @param  array      $words           ������ ��������� ����
 * @param  bool       $is_match_case   ������ � ������ �� ��������?
 * @param  string     $tpl             ������ ��� ������
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  3.0.11
 */
function html_words_highlight($s, array $words = null, $is_match_case = false, $tpl = '<span class="highlight">%s</span>')
{
    #����������� ��� ������ ��������
    if (! strlen($s) || ! $words) return $s;

    #����������� "��  134" = "�� 134"
    #{{{
    if (! function_exists('utf8_convert_case')) include_once 'utf8_convert_case.php';  #����������� �������� include_once
    $s2 = utf8_convert_case($s, CASE_LOWER);
    foreach ($words as $k => $word)
    {
        $word = utf8_convert_case(trim($word, "\x20\r\n\t*"), CASE_LOWER);
        if ($word == '' || strpos($s2, $word) === false) unset($words[$k]);
    }
    if (! $words) return $s;
    #}}}

    #d($words);
    #����������� ���������� ���. ��������� ��� "�������������" ���� � ������� ��� ��������� �������
    static $func_cache = array();
    $cache_id = md5(serialize(array($words, $is_match_case)));
    if (! array_key_exists($cache_id, $func_cache))
    {
        #����� � ��������� UTF-8 ��� ������ ������:
        static $re_utf8_letter = '#���������� �������:
                                  [a-zA-Z]
                                  #������� ������� (A-�):
                                  | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]
                                  #+ ��������� ����� �� ���������:
                                  | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]
                                  #+ �������� ����� �� �������� (��������� ��������):
                                  | \xc3[\x84\xa4\x87\xa7\x91\xb1\x96\xb6\x9c\xbc]|\xc4[\x9e\x9f\xb0\xb1]|\xc5[\x9e\x9f]
                                  ';
        #���������� ��������� ��� ��������� �����
        #��������� ������������ ������� � ����� HTML � ������������ ��� UTF-8 ���������!
        static $re_attrs_fast_safe =  '(?> (?>[\x20\r\n\t]+|\xc2\xa0)+  #���������� ������� (�.�. �����������)
                                           (?>
                                             #���������� ��������
                                                                            [^>"\']+
                                             | (?<=[\=\x20\r\n\t]|\xc2\xa0) "[^"]*"
                                             | (?<=[\=\x20\r\n\t]|\xc2\xa0) \'[^\']*\'
                                             #�������� ��������
                                             |                              [^>]+
                                           )*
                                       )?';

        $re_words = array();
        foreach ($words as $word)
        {
            if ($is_mask = (substr($word, -1) === '*')) $word = rtrim($word, '*');

            $is_digit = ctype_digit($word);

            #���. ��������� ��� ������ ����� � ������ �������� ��� ����:
            $re_word = preg_quote($word, '/');

            #���. ��������� ��� ������ ����� ���������� �� ��������:
            if (! $is_match_case && ! $is_digit)
            {
                #��� ��������� ����
                if (preg_match('/^[a-zA-Z]+$/', $word)) $re_word = '(?i:' . $re_word . ')';
                #��� ������� � ��. ����
                else
                {
                    if (! function_exists('utf8_ucfirst')) include_once 'utf8_ucfirst.php';  #����������� �������� include_once
                    $re_word_cases = array(
                        'lowercase' => utf8_convert_case($re_word, CASE_LOWER),  #word
                        'ucfirst'   => utf8_ucfirst($re_word),                   #Word
                        'uppercase' => utf8_convert_case($re_word, CASE_UPPER),  #WORD
                    );
                    $re_word = '(?>' . implode('|', $re_word_cases) . ')';
                }
            }

            #d($re_word);
            if ($is_digit) $append = $is_mask ? '(?>\d*)' : '(?!\d)';
            else $append = $is_mask ? '(?>' . $re_utf8_letter . ')*' : '(?! ' . $re_utf8_letter . ')';
            $re_words[$is_digit ? 'digits' : 'words'][] = $re_word . $append;
        }#foreach
        #d($re_words);

        if (! empty($re_words['words']))
        {
            #����� ��������� �����:
            $re_words['words'] = '(?<!' . $re_utf8_letter . ')  #�������� �����
                                  (' . implode("\r\n|\r\n", $re_words['words']) . ')   #=$m[3]
                                  ';
        }
        if (! empty($re_words['digits']))
        {
            #����� ��������� �����:
            $re_words['digits'] = '(?<!\d)  #�������� �����
                                   (' . implode("\r\n|\r\n", $re_words['digits']) . ')   #=$m[4]
                                   ';
        }
        #d($re_words);

        $func_cache[$cache_id] = '/#���������� PHP, Perl, ASP ���:
                                   <([\?\%]) .*? \\1>

                                   #����� CDATA:
                                   | <\!\[CDATA\[ .*? \]\]>

                                   #MS Word ���� ���� "<![if! vml]>...<![endif]>",
                                   #�������� ���������� ���� ��� IE ���� "<!--[if lt IE 7]>...<![endif]-->":
                                   | <\! (?>--)?
                                         \[
                                         (?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
                                         \]
                                         (?>--)?
                                     >

                                   #�����������:
                                   | <\!-- .*? -->

                                   #������ ���� ������ � ����������:
                                   | <((?i:noindex|script|style|comment|button|map|iframe|frameset|object|applet))' . $re_attrs_fast_safe . '>.*?<\/(?i:\\2)>  #=$m[2]

                                   #������ � �������� ����:
                                   | <[\/\!]?[a-zA-Z][a-zA-Z\d]*+' . $re_attrs_fast_safe . '\/?>

                                   #html ��������:
                                   | &(?> [a-zA-Z][a-zA-Z\d]++
                                        | \#(?> \d{1,4}
                                              | x[\da-fA-F]{2,4}
                                            )
                                      );
                                   | ' . implode("\r\n|\r\n", $re_words) . '  #3 or 4
                                  /sxS';
        #d($func_cache[$cache_id]);
    }
    $GLOBALS['HTML_WORDS_HIGHLIGHT_TPL'] = $tpl;
    $s = preg_replace_callback($func_cache[$cache_id], '_html_words_highlight_callback', $s);
    unset($GLOBALS['HTML_WORDS_HIGHLIGHT_TPL']);
    return $s;
}

function _html_words_highlight_callback(array $m)
{
    foreach (array(3, 4) as $i)
    {
        if (array_key_exists($i, $m) && strlen($m[$i]) > 0)
        {
            //d($m);
            return sprintf($GLOBALS['HTML_WORDS_HIGHLIGHT_TPL'], $m[$i]);
        }
    }#foreach

    #���������� ����
    return $m[0];
}

?>
