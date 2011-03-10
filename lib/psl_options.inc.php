<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_options.inc.php                                                        #
#   Настройки. Скаляры и списки. Получение значений, вывод, редактирование.    #
#                                                                              #
################################################################################
/*

  string GetOption(string optionCode)                 - Вернуть значение константы по ее коду
   array GetOptionList(string optionCode)             - Вернуть список по его коду

     int GetErrno()                                   - Последняя ошибка (код)
     int GetError()                                   - Последняя ошибка (сообщение)

*/

define ('E_PSL_OPTIONS_OK',       0);
define ('E_PSL_OPTIONS_CODE',     1);
define ('E_PSL_OPTIONS_NOTFOUND', 2);

class PslOptions {

    # Настройки
    var $mOptionsTbl      = 'dwOptions';
    var $mOptionsId       = 'ID_Option';
    var $mOptionsParent   = 'ID_Parent';
    var $mOptionsCode     = 'OptionCode';
    var $mOptionsItemCode = 'OptionSubCode';
    var $mOptionsName     = 'OptionName';
    var $mOptionsValue    = 'OptionValue';
    var $mOptionsOrder    = 'OptionOrder';
    
    # public:
    var $mDb;                          // Объект доступа к БД
    
    # private (don't change it manually!):
    var $_Errno = E_PSL_OPTIONS_OK;
    var $_Fetched = false;
    var $_Options = array();
    var $_OptionsIds = array();
    
    function GetOption($optionCode) {
        $this->_FlushError();
        $this->_FetchOptions();
        $r = $this->GetOptionList($optionCode);
        if (is_array($r) && isset($r[0])) $r = $r[0];
        return $this->GetErrno() ? false : $r;
    }
    
    function GetOptionList($optionCode) {
        $this->_FlushError();
        $this->_FetchOptions();    
        $r = 0;
        if (!string_is_login($optionCode))
            $this->_Errno = E_PSL_OPTIONS_CODE;
        elseif (!isset($this->_OptionsIds[$optionCode]))
            $this->_Errno = E_PSL_OPTIONS_NOTFOUND;
        else
            $r = $this->_Options[$this->_OptionsIds[$optionCode]];
        return $this->GetErrno() ? false : $r;
    }
    
    function GetErrno() {
        return $this->_Errno;
    }
    
    function GetError() {
        $e = array(E_PSL_OPTIONS_OK         => 'Выполнено без ошибок',
                   E_PSL_OPTIONS_CODE       => 'Код содержит недопустимые символы',
                  );
        return in_array($this->_Errno, array_keys($e)) ? $e[$this->_Errno] : 'Неизвестная ошибка';
    }
    
    # Загружает все настройки
    function _FetchOptions() {
        $this->_CheckDb();
        $this->_FlushError();
        if (!$this->_Fetched) {
            $q = 'select * from ' . $this->mOptionsTbl . ' order by ' . $this->mOptionsParent . ', ' . $this->mOptionsOrder;
            $this->mDb->Query($q);
            $i = 0;
            while ($this->mDb->NextRecord()) {
                $k = $this->mDb->F($this->mOptionsItemCode);
                // Если ID_Parent не установлен, добавим настройку, 
                // иначе это подчиненный, добавим в подчиненные
                if (!isset($this->mDb->mRecord[$this->mOptionsParent]) || !$this->mDb->F($this->mOptionsParent)) {
                    if ($k == '') $k = 0;
                    $this->_OptionsIds[$this->mDb->F($this->mOptionsCode)] = $this->mDb->F($this->mOptionsId);
                    $this->_Options[$this->mDb->F($this->mOptionsId)] = array($k => $this->mDb->F($this->mOptionsValue));
                } else {
                    if ($k == '') $k = $i++;
                    $this->_Options[$this->mDb->F($this->mOptionsParent)][$k] = $this->mDb->F($this->mOptionsValue);
                }
            }
            
            $this->_Fetched = true;
        }
    }
    
    # Проверка, подключена ли БД
    function _CheckDb() {
        if (!isset($this->mDb)) die ('PslOptions: Вы забыли подключить БД!');
    }
    
    # Сброс кода последней ошибки
    function _FlushError() {
        $this->_Errno = E_PSL_OPTIONS_OK;
    }
    
}

?>