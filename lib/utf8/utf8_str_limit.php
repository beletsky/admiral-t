<?php

/**
 * �������� ����� � ��������� UTF-8 �� �������� �����,
 * ������ ��������� ����� ������������ �������, � �� ���������� �� ��������.
 * Html �������� ��������� ��������������.
 *
 * @param    string   $s                ����� � ��������� UTF-8
 * @param    int      $maxlength        ����������� ����� ������
 * @param    string   $continue         ����������� ������, ������� ����� ��������� ����� ������, ���� �� ���������
 * @param    string   &$is_cutted       ����� ��� �������?
 * @param    int      $tail_min_length  ���� ����� "������", ����������� ����� ������� ������, ������ $tail_min_length,
 *                                      �� ����� ������������ ��� ���������
 * @return   string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  3.4.1
 */
function utf8_str_limit($s, $maxlength = null, $continue = "\xe2\x80\xa6", &$is_cutted = null, $tail_min_length = 20) #"\xe2\x80\xa6" = "&hellip;"
{
    $is_cutted = false;
    if ($continue === null) $continue = "\xe2\x80\xa6";
    if (! $maxlength) $maxlength = 256;

    #����������� ��������:
    #{{{
    if (strlen($s) <= $maxlength) return $s;
    $s2 = str_replace("\r\n", '?', $s);
    $s2 = preg_replace('/&(?> [a-zA-Z][a-zA-Z\d]+
                            | \#(?> \d{1,4}
                                  | x[\da-fA-F]{2,4}
                                )
                          );  # html �������� (&lt; &gt; &amp; &quot;)
                        /sxS', '?', $s2);
    #utf8_decode() converts characters that are not in ISO-8859-1 to '?', which, for the purpose of counting, is quite alright.
    if (strlen($s2) <= $maxlength || strlen(utf8_decode($s2)) <= $maxlength) return $s;
    #}}}

    preg_match_all('/(?> \r\n   # �������� �����
                       | &(?> [a-zA-Z][a-zA-Z\d]+
                            | \#(?> \d{1,4}
                                  | x[\da-fA-F]{2,4}
                                )
                          );  # html �������� (&lt; &gt; &amp; &quot;)
                       | [\x09\x0A\x0D\x20-\x7E]           # ASCII
                       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
                       |  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
                       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
                       |  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
                       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
                       | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
                       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
                     )
                    /sxS', $s, $m);
    #d($m);
    if (count($m[0]) <= $maxlength) return $s;

    $left = implode('', array_slice($m[0], 0, $maxlength));
    #�� ��������� ASCII ��������� �����, �����, ����������� ������ ������� [a-zA-Z\d\(\{\[] � ��������� ��. �������
    #������ �������� � ����� ������ ������ ";", �.�. �� ������������ � ��������� &xxx;
    $left2 = rtrim($left, "\x00..\x28\x2A..\x2F\x3A\x3C..\x3E\x40\x5B\x5C\x5E..\x60\x7B\x7C\x7E\x7F");
    if (strlen($left) !== strlen($left2)) $return = $left2 . $continue;
    else
    {
        #��������� ������� � ����������� �����
        $right = implode('', array_slice($m[0], $maxlength));
        preg_match('/^(?> [a-zA-Z\d\)\]\}\-\.:]+  #���������� ����� ��� �����, ����������� ������ �������, ����� ��� ��������� ����, ����, �����, IP-������, URL ���� www.ya.ru:80!
                        | \xe2\x80[\x9d\x99]|\xc2\xbb|\xe2\x80\x9c  #����������� �������
                        | \xc3[\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]|\xc4[\x9f\xb1\x9e\xb0]|\xc5[\x9f\x9e]  #��������
                        | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]   #������� �����
                        | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]  #���������
                      )+
                    /sxS', $right, $m);
        #d($m);
        $right = isset($m[0]) ? rtrim($m[0], '.-') : '';
        $return = $left . $right;
        if (strlen($return) !== strlen($s)) $return .= $continue;
    }
    $tail = substr($s, strlen($return));
    if (strlen(utf8_decode($tail)) < $tail_min_length) return $s;

    $is_cutted = true;
    return $return;

    /*
    #:DEPRECATED:
    #���������� ����������� ��������� ������ ������ �� ������� ������� $maxlength > 50!!!
    if (preg_match('/^#�������� ��� ������� � ������������ ����� ������
                      (?: \r\n   # �������� �����
                       | &(? > [a-zA-Z][a-zA-Z\d]+
                            | \#(? > \d{1,4}
                                  | x[\da-fA-F]{2,4}
                                )
                          );  # html �������� (&lt; &gt; &amp; &quot;)
                       | [\x09\x0A\x0D\x20-\x7E]           # ASCII
                       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
                       |  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
                       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
                       |  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
                       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
                       | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
                       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
                      ){1,' . intval($maxlength) . '}
                     #��������� ��������� �������, ����� ������ ������� ����� ����������
                     (? > [a-zA-Z\d\)\]\}\-]                #���������� ����� ��� �����, ����������� ������ �������, ����� ��� ��������� ����!
                         | \xe2\x80[\x9d\x99]|\xc2\xbb|\xe2\x80\x9c  #����������� �������
                         | \xc3[\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]|\xc4[\x9f\xb1\x9e\xb0]|\xc5[\x9f\x9e]  #��������
                         | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]   #������� �����
                         | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]  #���������
                     ){0,40}
                    /sxS', $s, $m))
    {
        #������ �������� � ����� ������ ������ ";", �.�. �� ������������ � ��������� &xxx;
        return rtrim($m[0], " \r\n\t`=\\/~!@#*<>'(_+|\";:.,?!*") . $continue;
        #return $m[0] . $continue;
    }
    #����� �� � ��������� UTF-8!
    trigger_error('Unknown charset !', E_USER_WARNING);
    return false;
    */
}

?>