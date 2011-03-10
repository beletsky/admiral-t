<?php
/*
 * Session Management for PHP3
 *
 * (C) Copyright 1999-2000 NetUSE GmbH
 *                    Kristian Koehntopp
 *
 * $Id: template.inc,v 1.5 2000/07/12 18:22:35 kk Exp $
 *
 * Modified 2002 by Ilya Blagorodov (ilya@blagorodov.ru)
 *
 */ 

class Template {
  var $classname = "Template";

  /* if set, echo assignments */
  var $debug     = false;

  /* $file[handle] = "filename"; */
  var $file  = array();
  
  /* template string. You can set it manually */
  var $template = "";

  /* relative filenames are relative to this pathname */
  var $root   = "";

  /* $varkeys[key] = "key"; $varvals[key] = "value"; */
  var $varkeys = array();
  var $varvals = array();

  /* "remove"  => remove undefined variables
   * "comment" => replace undefined variables with comments
   * "keep"    => keep undefined variables
   */
  var $unknowns = "remove";
  
  /* "yes" => halt, "report" => report error, continue, "no" => ignore error quietly */
  var $halt_on_error  = "yes";
  
  /* last error message is retained here */
  var $last_error     = "";
  
  /**
   * This variable define using finish function,
   * for different actions with undefined template variables;
   *
   * @var boolean
   */
  var $use_finish = false;

  /**
   * Cutting commentaries flag.
   *
   * @var boolean
   */
  var $use_cut_comments = true;
  
  /**
   * To compress or not to compress :)
   *
   * @var boolean
   */
  var $use_compress = false;
  
  /***************************************************************************/
  /* public: Constructor.
   * root:     template directory.
   * unknowns: how to handle unknown variables.
   *
   * params: array  //  Массив дополнительных параметров
   * Параметры: 
   *  'use_finish'        //  Этот параметр, в случае установки в true
   *                      //  будет вырезать 
   *                      //  все неустановленные в коде переменные из шаблона.
   *                      //  (или действовать по другому, в зависимости
   *                      //  от переданной в шаблон переменной $unknowns, по умолчанию, она
   *                      //  у нас эквивалентна remove )
   *                      //  ВНИМАНИЕ! Он может вырезать например такую js конструкцию
   *                      //  {return}
   *                                     
   *  'use_cut_comments'  //  Этот параметр отвечает за вырезку блоков с комментариями.
   *                      //  блок с комментариями выглядит вот так:
   *                      //    <!-- BEGIN COMMENT -->
   *                      //      текст комментария
   *                      //    <!-- END COMMENT -->
   *
   *  'use_compress'      //  Удаление лишних пробелов, табуляций и переводов строки для 
   *                      //  сжатия страницы.
   *
   */
  function Template($root = ".", $unknowns = "remove", $params = array()) {
    $this->set_root($root);
    $this->set_unknowns($unknowns);
    if (!empty($params)) {
        foreach ($params as $param_key => $param_value) {
            if (!isset($this->$param_key)) {
                continue;
            } else {
                $this->$param_key = $param_value;
            }
        }
    }
    $this->set_var( '__EXT', HTML_EXTENSION );
    $this->set_var( '__ROOT', SITE_ROOT_PATH );
    $this->set_var( '__DELIM', PATH_DELIMITER );
    $this->set_var( '__PARAMS', kept_url_params() );
  }

  /* public: setroot(pathname $root)
   * root:   new template directory.
   */  
  function set_root($root) {
    if (!is_dir($root)) {
      $this->halt("set_root: $root is not a directory.");
      return false;
    }
    
    $this->root = $root;
    return true;
  }

  /* public: set_unknowns(enum $unknowns)
   * unknowns: "remove", "comment", "keep"
   *
   */
  function set_unknowns($unknowns = "keep") {
    $this->unknowns = $unknowns;
  }

  /* public: set_file(array $filelist)
   * filelist: array of handle, filename pairs.
   *
   * public: set_file(string $handle, string $filename)
   * handle: handle for a filename,
   * filename: name of template file
   */
  function set_file($handle, $filename = "") {
    if (!is_array($handle)) {
      if ($filename == "") {
        $this->halt("set_file: For handle $handle filename is empty.");
        return false;
      }
      $this->file[$handle] = $this->filename($filename);
    } else {
      foreach ($handle as $h => $f) {
        $this->file[$h] = $this->filename($f);
      }
    }
  }

  /* public: set_template(string $handle, string $tpl)
   * handle: handle for a filename,
   * tpl: string with template
   */
  function set_template($handle, $tpl = "") {
    $this->set_file($handle, 'dummy');
    $this->template = $tpl;
  }

  /* public: set_block(string $parent, string $handle, string $name = "")
   * extract the template $handle from $parent, 
   * place variable {$name} instead.
   */
  function set_block($parent, $handle, $name = "") {
    if (!$this->loadfile($parent)) {
      $this->halt("subst: unable to load $parent.");
      return false;
    }
    if ($name == "")
      $name = $handle;

    $str = $this->get_var($parent);
    //$reg = "/<!--\s+BEGIN $handle\s+-->(.*)\n\s*<!--\s+END $handle\s+-->/sm";
    $reg = "/<!--\s*BEGIN $handle\s*-->(.*)<!--\s*END $handle\s*-->/sm";
    if (preg_match_all($reg, $str, $m)){
      $str = preg_replace($reg, '{' . $name . '}', $str);
      $this->set_var($handle, $m[1][0]);
      $this->set_var($parent, $str);
      $this->set_var($name, '');
      return true;
    } else {
      return false;
    }
  }
  
  /* public: set_var(array $values)
   * values: array of variable name, value pairs.
   *
   * public: set_var(string $varname, string $value)
   * varname: name of a variable that is to be defined
   * value:   value of that variable
   */
  function set_var($varname, $value = "") {
    if (!is_array($varname)) {
      if (!empty($varname)) {
        if ($this->debug) print "scalar: set *$varname* to *$value*<br>\n";
        $this->varkeys[$varname] = '{' . $varname . '}';
        $this->varvals[$varname] = $value; 
      }
    } else {
      foreach ($varname as $k => $v) 
        if (!empty($k)) {
          if ($this->debug) print "array: set *$k* to *$v*<br>\n";
          $this->varkeys[$k] = '{' . $k . '}';
          $this->varvals[$k] = $v; 
        }
    }
  }

  /* public: subst(string $handle)
   * handle: handle of template where variables are to be substituted.
   */
  function subst($handle) {
    if (!$this->loadfile($handle)) {
      $this->halt("subst: unable to load $handle.");
      return false;
    }
    return str_replace($this->varkeys, $this->varvals, $this->get_var($handle));
  }
  
  /* public: psubst(string $handle)
   * handle: handle of template where variables are to be substituted.
   */
  function psubst($handle) {
    print $this->subst($handle);
    
    return false;
  }

  /* public: parse(string $target, string $handle, boolean append)
   * public: parse(string $target, array  $handle, boolean append)
   * target: handle of variable to generate
   * handle: handle of template to substitute
   * append: append to target handle
   */
  function parse($target, $handle, $append = false) {
    if (!is_array($handle)) {
      $str = $this->subst($handle);
      if ($append) {
        $this->set_var($target, $this->get_var($target) . $str);
      } else {
        $this->set_var($target, $str);
      }
    } else {
      foreach ($handle as $h) {
        $str = $this->subst($h);
        $this->set_var($target, $str);
      }
    }
    if ($this->use_cut_comments) {
      $str = $this->cut_comments($str);
    }
    if ($this->use_finish) {
      $str = $this->finish($str);
    }
    if ($this->use_compress) {
      $str = $this->compress($str);
    } 

    return $str;
  }
  
  function pparse($target, $handle, $append = false) {
    $output = $this->parse($target, $handle, $append);
    print $output;
    return false;
  }
  
  /* public: get_vars()
   */
  function get_vars() {
    foreach ($this->varkeys as $k => $v) 
      $result[$k] = $this->varvals[$k];
    return $result;
  }
  
  /* public: get_var(string varname)
   * varname: name of variable.
   *
   * public: get_var(array varname)
   * varname: array of variable names
   */
  function get_var($varname) {
    if (!is_array($varname)) {
      return isset($this->varvals[$varname]) ? $this->varvals[$varname] : '';
    } else {
      foreach ($varname as $k => $v) {
        $result[$k] = $this->varvals[$k];
      }
      
      return $result;
    }
  }
  
  /* public: get_undefined($handle)
   * handle: handle of a template.
   */
  function get_undefined($handle) {
    if (!$this->loadfile($handle)) {
      $this->halt("get_undefined: unable to load $handle.");
      return false;
    }
    
    preg_match_all("/\{([^}]+)\}/", $this->get_var($handle), $m);
    $m = $m[1];
    if (!is_array($m))
      return false;

    foreach ($m as $k => $v) {
      if (!isset($this->varkeys[$v]))
        $result[$v] = $v;
    }
    
    if (count($result))
      return $result;
    else
      return false;
  }

  /* public: finish(string $str)
   * str: string to finish.
   */
  function finish($str) {
    switch ($this->unknowns) {
      case "keep":
      break;
      
      case "remove":
        $str = preg_replace('/{[^ \t\r\n}]+}/', "", $str);
      break;

      case "comment":
        $str = preg_replace('/{([^ \t\r\n}]+)}/', "<!-- Template: Variable \\1 undefined -->", $str);
      break;
    }
    
    return $str;
  }
  
  /**
   * Сuting comments blocks from string
   *
   * @param string $str
   */
  function cut_comments ($str) {
    $reg = "/<!--\s*BEGIN COMMENT\s*-->(.*?)<!--\s*END COMMENT\s*-->/sm";
    return preg_replace($reg, '', $str);
  }
  
  /**
   * Function for compress html
   *
   * @param unknown_type $str
   */
  
  function compress($str) {
    return preg_replace("/[\s]+/", " ", $str);
  }
  
  /* public: p(string $varname)
   * varname: name of variable to print.
   */
  function p($varname) {
    print $this->finish($this->get_var($varname));
  }

  function get($varname) {
    return $this->finish($this->get_var($varname));
  }
  
  function has_block($handle) {
    if (isset($this->varkeys[$handle]) and !empty($this->varvals[$handle]))
      return true;

    if (isset($this->file[$handle]))
      return true;
      
    return false;
  }
    
  /***************************************************************************/
  /* private: filename($filename)
   * filename: name to be completed.
   */
  function filename($filename) {
    if (substr($filename, 0, 1) != "/") {
      $filename = $this->root . '/' . $filename;
    }
    
//    if (!file_exists($filename))
//      $this->halt("filename: file $filename does not exist.");

    return $filename;
  }
  
  /* private: varname($varname)
   * varname: name of a replacement variable to be protected.
   */
  function varname($varname) {
    return preg_quote('{' . $varname . '}');
  }

  /* private: loadfile(string $handle)
   * handle:  load file defined by handle, if it is not loaded yet.
   */
  function loadfile($handle) {
    if (isset($this->varkeys[$handle]) and !empty($this->varvals[$handle]))
      return true;

    if (!isset($this->file[$handle])) {
      print_r($this->file);
      $this->halt("loadfile: $handle is not a valid handle.");
      return false;
    }
    $filename = $this->file[$handle];

    //  implode("", @file($filename)) - измененно на @file_get_contents($filename)
    
    $str = $this->template == "" ? @file_get_contents($filename) : $this->template;
    if (empty($str)) {
      $this->halt("loadfile: While loading $handle, $filename does not exist or is empty.");
      return false;
    }

    $this->set_var($handle, $str);
    
    return true;
  }

  /***************************************************************************/
  /* public: halt(string $msg)
   * msg:    error message to show.
   */
  function halt($msg) {
    $this->last_error = $msg;
    
    if ($this->halt_on_error != "no")
      $this->haltmsg($msg);
    
    if ($this->halt_on_error == "yes")
      die("<b>Halted.</b>");
    
    return false;
  }
  
  /* public, override: haltmsg($msg)
   * msg: error message to show.
   */
  function haltmsg($msg) {
    printf("<b>Template Error:</b> %s<br>\n", $msg);
  }
}
?>
