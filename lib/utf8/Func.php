<?php
/**
 * ����� ��� �������� � ���������� �������, ������� �������� � �������� ����� � ���� PHP ������.
 *
 * ������ �������������:
 *   #���� ����� ����� �� ��������, ���� � .htaccess ���� ��� ��������
 *   Func::add_include_path(dirname(__FILE__) . '/func/');
 *   ...
 *   $s = Func::call('html_optimize', $s);  # PHP < 5.3.0
 *   $s = Func::html_optimize($s);          # PHP >= 5.3.0
 *   ...
 *   #��� ���� ������ ��� �������, ������� ���������� ��������� �� ������
 *   Func::load('utf8_str_limit', 'strip_tags_smart');
 *   $s = utf8_str_limit(strip_tags_smart($s), 100, null, $is_cutted);
 *
 * @license  http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.0.0
 */
class Func
{
    #��������� �������� ���������� ������, ����� ������� ����� ������ ������ ����������!
    private function __construct() {}

    /**
     * ��������� ���� � include_path (��� �������� �������, ���������� � PHP ������)
     *
     * ���� ����� ����� �� ��������, ���� � .htaccess �������� ��������� ���������:
     *   #���� ��� ���������� ��������� (����������� � Unix - ":", � Windows - ";")
     *   php_value include_path "./:/patch_to/func/"
     */
    public static function add_include_path(/*string*/ $path)
    {
        $pathes = explode(PATH_SEPARATOR, get_include_path());
        foreach (func_get_args() as $path)
        {
            if (! is_dir($path))
            {
                trigger_error('Include path "' . $path . '" does not exist!', E_USER_WARNING);
                continue;
            }
            $path = realpath($path);
            if (array_search($path, $pathes) === false) array_push($pathes, $path);
        }#foreach
        return set_include_path(implode(PATH_SEPARATOR, $pathes));
    }

    /**
     * ��������� (� ������ �������������) � ��������� �������.
     * ����� ��� Func::<function>(<param1>, <param2>, ...)
     * PHP >= 5.3.0
     */
    public static function __callStatic($func, $args)
    {
        if (! function_exists($func)) self::load($func);
        return call_user_func_array($func, $args);
    }

    /**
     * ��������� (� ������ �������������) � ��������� �������.
     * ����� ��� Func::call(<function>, <param1>, <param2>, ...)
     * ��� Func::call(<function>|<filename>, <param1>, <param2>, ...), ���� �������� ������� � ��� �����, � ������� ��� ��������, �����������
     * PHP < 5.3.0
     */
    public static function call()
    {
        $args = func_get_args();
        $func = array_shift($args);
        @list($func, $file) = explode('|', $func);
        if (! $file) $file = $func;
        if (! function_exists($func)) self::load($file);
        return call_user_func_array($func, $args);
    }

    /**
     * ��������� ���� ��� ��������� �������.
     * ��� ������ � ���������� ����������� ������� ������� ���������� ��������� �������� �������,
     * � ����� ������ �� ����� ��� ������.
     */
    public static function load()
    {
        foreach (func_get_args() as $func)
        {
            if (! function_exists($func))
            {
                require_once $func . '.php';
                #�� �.�. �������, ��� ������� ����������, �.�. ��� � call_user_func_array()
                #����� ���������� FALSE, � ����� �� ������� ��������� ��������� � E_USER_WARNING
                if (! function_exists($func)) trigger_error('Function "' . $func . '" does not exist!', E_USER_ERROR);
            }
        }#foreach
        return true;
    }
}
?>