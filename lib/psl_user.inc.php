<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_user.inc.php                                                           #
#   Объект для работы с текущим пользователям. Умеет поддерживать состояние    #
#   текущего пользователя.                                                     #
#                                                                              #
################################################################################
/*

    bool Start(void)                                  - Инициализация текущего пользователя. Если true, то успешно
  
    bool SetLogin(string login)                       - Устанавливает текущий логин. Если true, то успешно
  string GetLogin()                                   - Возвращает текущий логин
  string GetGroup()                                   - Возвращает код группы (если включена поддержка групп)
     int GetGroupId()                                 - Возвращает id группы пользователя
    bool SetGroup(string group_code)                  - Установить код группы (если включена поддержка групп)
     int GetId()                                      - Получить id текущего пользователя
    bool IsAuthorized(void)                           - Залогинен ли пользователь?
  
   mixed Get(string paramName = '')                   - Получить значение параметра или хэш со всеми параметрами
    bool Set(mixed paramName, string paramValue = '') - Установить параметр или хэш со всеми параметрами. Если true, то успешно
  
    bool Login(string login, string pwd)              - Попытка логина. Если true, то успешно
    void Logout()                                     - Выполнить выход из системы

     int GetErrno()                                   - Последняя ошибка (код)
     int GetError()                                   - Последняя ошибка (сообщение)

*/

define ('_PHPSITELIB_USER_LN', '_phpsitelibuser_login' . $_PHPSITELIB_USER_POSTFIX);
define ('_PHPSITELIB_USER_AN', '_phpsitelibuser_auth' . $_PHPSITELIB_USER_POSTFIX);
define ('E_PSL_USER_OK',         0);
define ('E_PSL_USER_STRLOGIN',   1);
define ('E_PSL_USER_STRPWD',     2);
define ('E_PSL_USER_NOTFOUND',   3);
define ('E_PSL_USER_NOLOGIN',    4);
define ('E_PSL_USER_NOTLOGGED',  5);
define ('E_PSL_USER_NOGP',       6);

class PslUser {
 
    # Настройки
    var $mUsersTable   = 'dwUsers';      // Таблица с пользователями
    var $mIdField      = 'ID_User';    // Поле с ID пользователя
    var $mLoginField   = 'UserLogin';  // Поле с логином
    var $mPwdField     = 'UserPwd';    // Поле с паролем
    var $mDeletedField = 'UserDeleted';// Поле с флагом "удален", если пусто, то не используется
    var $mPwdCrypt     = false;        // Кодировать пароль с помощью функции PASSWORD
    var $mSecureDelay  = 3;            // Число секунд задержки при неверном логине. Если 0, то задержка не выполняется
    
    var $mGroupsTable  = 'dwGroups';     // Таблица групп пользователей, если пусто, то группы не используются
    var $mUGroupField  = 'ID_Group';   // Поле в табл. пользователей со ссылкой на группу
    var $mGIdField     = 'ID_Group';   // Поле ID в таблице групп (на кот. идет ссылка)
    var $mGCodeField   = 'GroupCode';  // Поле с кодом группы в таблице групп (хранится в свойствах)
    var $mGCodeAlias   = 'GroupCode';  // Название, по которому осущ. доступ к коду группы в свойствах
    var $mGAccessBackEnd  = 'GroupAccessBackEnd'; // Поле в таблице групп - флаг доступа к админской части
    var $mGAccessFrontEnd = 'GroupAccessFrontEnd'; // Поле в таблице групп - флаг доступа к сайту
    
    var $mAcceptGroups = array();      // Список разрешенных групп для авторизации
    
    var $mAutoAcceptGroups = false;    // Автоматическая выборка групп, разрешенных к авторизации (используется флаг GroupAccessBackEnd)
    var $mAccessBackEnd = false;       // Если используется автоматическая выборка групп, то эти флаги означают выбирать группы, в которых
    var $mAccessFrontEnd = false;      // установлены соответственно поля GroupAccessBackEnd и GroupAccessFrontEnd
    var $mCheckAccessBackEnd = true;   // Проверять наличие доступа к админу
    var $mCheckAccessFrontEnd = true;  // Проверять наличие доступа к сайту
    
    # public:
    var $mDb;                          // Объект доступа к БД
    
    # private (don't change it manually!):
    var $_Fetched = false;
    var $_Auth = false;
    var $_Login = '';    
    var $_Id = 0;
    var $_Props = array();
    var $_Errno = E_PSL_USER_OK;
    
    function Start() {
        if (isset($_SESSION[_PHPSITELIB_USER_LN]) && isset($_SESSION[_PHPSITELIB_USER_AN]) && $_SESSION[_PHPSITELIB_USER_AN])
            $this->_Auth = $this->SetLogin($_SESSION[_PHPSITELIB_USER_LN]);
        return $this->_Auth;
    }

    function SetLogin($login) {
        if ($login != $this->_Login) return $this->_FetchProps(false, $login);
    }
    
    function GetLogin() {
        return $this->_Login;
    }
    
    function SetGroup($code) {
        return $this->Set($this->mGCodeAlias, $code);
    }
    
    function GetGroup() {
        return $this->Get($this->mGCodeAlias);
    }
    
    function GetGroupId() {
        return $this->Get($this->mUGroupField);
    }
    
    function GetId() {
        return $this->_Id;
    }
    
    function IsAuthorized() {
        return $this->_Auth;
    }
    
    function GetErrno() {
        return $this->_Errno;
    }
    
    function GetError() {
        $e = array(E_PSL_USER_OK         => 'Выполнено без ошибок',
                   E_PSL_USER_STRLOGIN   => 'Логин содержит недопустимые символы',
                   E_PSL_USER_STRPWD     => 'Пароль содержит недопустимые символы', 
                   E_PSL_USER_NOTFOUND   => 'Запись не найдена', 
                   E_PSL_USER_NOLOGIN    => 'Логин не определен', 
                   E_PSL_USER_NOTLOGGED  => 'Не авторизован', 
                   E_PSL_USER_NOGP       => 'Не определены параметры для работы с группами', #!
                  );
        return in_array($this->_Errno, array_keys($e)) ? $e[$this->_Errno] : 'Неизвестная ошибка';
    }
    
    function Get($paramName = '') {
        if (!$this->_Fetched) 
            if (!$this->_FetchProps())
                return false;
        return $paramName != '' ? $this->_Props[$paramName] : $this->_Props;
    }
    
    function Set($paramName, $paramValue = '') {
        $this->_CheckDb();
        
        // Лочим все таблицы, которые могут использоваться
        $this->mDb->Lock($this->mGroupsTable != '' ? array($this->mUsersTable, $this->mGroupsTable) : $this->mUsersTable);
        
        // Проверка существования текущего пользователя
        if (!$this->_Exists($this->_Login)) return false;
        
        // Если входной параметр - не массив, то сделаем его массивом для универсальной обработки
        if (!is_array($paramName)) $paramName = array($paramName => $paramValue);
        
        // Сформируем строчку на апдейт и выполним ее
        $a = array();
        foreach ($paramName as $k => $v)
            if (string_is_login($k) && $k != $this->mGCodeAlias) {
                $v = addslashes($v);
                $a[] = $k == $this->mPwdField && $this->mPwdCrypt ? "$k = password('$v')" : "$k = '$v'";
            }
                    
        if (count($a)) {
            $q = 'update ' . $this->mUsersTable . 
                 ' set ' . implode(', ', $a) . 
                 ' where ' . $this->mLoginField . ' = "' . $this->_Login . '"';
            $this->mDb->Query($q);
        }
        
        // Если в параметрах была задана группа, то проапдейтим ее:
        // Получим ID группы и апдейтим пользователя
        if ($this->mGroupsTable != '' && in_array($this->mGCodeAlias, array_keys($paramName)) && string_is_login($paramName[$this->mGCodeAlias])) {
        
            $q = 'select ' . $this->mGIdField . 
                 ' from ' . $this->mGroupsTable . 
                 ' where ' . $this->mGCodeField . ' = "' . $paramName[$this->mGCodeAlias] . '"';
            $this->mDb->Query($q);
            
            if ($this->mDb->NextRecord()) {
            
                $q = 'update ' . $this->mUsersTable . 
                     ' set ' . $this->mUGroupField . ' = ' . $this->mDb->F(0) . 
                     ' where ' . $this->mLoginField . ' = "' . $this->_Login . '"';
                $this->mDb->Query($q);
                
            }
        }
        
        $this->mDb->Unlock();
        
        $this->_Fetched = false;
        return true;
    }
    
    # Проверка логина и пароля, если ок, то логиним, иначе отключаем
    function Login($login, $pwd) {
        eval('global $' . _PHPSITELIB_USER_LN .', $' . _PHPSITELIB_USER_AN . ';');
        $this->Logout();
        if ($this->_FetchProps(true, $login, $pwd)) {
            $this->_Auth  = true;
//            eval('$' . _PHPSITELIB_USER_LN . ' = "' . $this->_Login . '";');
//            eval('$' . _PHPSITELIB_USER_AN . ' = "' . $this->_Auth . '";');
//            session_register(_PHPSITELIB_USER_LN);
//            session_register(_PHPSITELIB_USER_AN);
            $_SESSION[_PHPSITELIB_USER_LN] = $this->_Login;
            $_SESSION[_PHPSITELIB_USER_AN] = $this->_Auth;
        } elseif ($this->mSecureDelay) {
            sleep($this->mSecureDelay);
        }
        return $this->_Errno == E_PSL_USER_OK;
    }
    
    # Сброс всех состояний в ноль и очистка сессии
    function Logout() {
        if (!$this->_Auth) {
            $this->_Errno = E_PSL_USER_NOTLOGGED;
        } else {
            eval('global $' . _PHPSITELIB_USER_LN .', $' . _PHPSITELIB_USER_AN . ';');
            session_unregister(_PHPSITELIB_USER_LN);
            session_unregister(_PHPSITELIB_USER_AN);
            unset($_COOKIE['admin_login']);
            $this->_Fetched = false;
            $this->_Auth = false;
            $this->_Login = '';
            $this->_Id = 0;
            $this->_Errno = E_PSL_USER_OK;
        }
        return $this->_Errno == E_PSL_USER_OK;
    }
    
    # Если $login, то проверяет логин с паролем. Если не нашел, возвр. false. Если ок, то устанавлиает тек. логин и id
    function _FetchProps($check_pwd = false, $login = '', $pwd = '') {
        $this->_CheckDb();
        $this->_Fetched = false;
        $this->_Errno = E_PSL_USER_OK;
        $this->_FetchAcceptGroups();
        if ($login == '') $login = $this->_Login;
        
        if ($login == '')                         $this->_Errno = E_PSL_USER_NOLOGIN;
        if (!string_is_login($login))             $this->_Errno = E_PSL_USER_STRLOGIN;
        if ($pwd != '' && !string_is_login($pwd)) $this->_Errno = E_PSL_USER_STRPWD;
        
        if ($this->_Errno != E_PSL_USER_OK) return false;
        
        if ($this->mGroupsTable != '') {
            if ($this->mUGroupField == '' || $this->mGroupsTable == '' || $this->mGIdField == '' || 
                $this->mGCodeField == '' || $this->mGCodeAlias == '') {
            
                $this->_Errno = E_PSL_USER_NOGP;
                return false;
                
            } else {
            
                $gtbl  = $this->mGroupsTable; $ugrpid = $this->mUGroupField; $gid = $this->mGIdField;     
                $gcode = $this->mGCodeField;  $alias  = $this->mGCodeAlias;
            
            }
        }
        
        // Формирование строки запроса, выполнение запроса
        $pwds = $this->mPwdCrypt ? 'password("' . $pwd . '")' : '"' . $pwd . '"';
                                        $q = 'select u.*';
        if ($this->mGroupsTable != '')  $q .= ", g.$gcode as $alias";
                                        $q .= ' from ' . $this->mUsersTable . ' u';
        if ($this->mGroupsTable != '')  $q .= " inner join $gtbl g on g.$gid = u.$ugrpid";
                                        $q .= ' where u.' . $this->mLoginField . ' = "' . $login . '"';
        if ($check_pwd && $pwds != '')  $q .= ' and u.' . $this->mPwdField . ' = ' . $pwds;
        if ($this->mDeletedField != '') $q .= ' and u.' . $this->mDeletedField . ' = 0';
        
        if ($this->mGroupsTable != '') {
            $ag = array();
            foreach ($this->mAcceptGroups as $g) $ag[] = '"' . addslashes($g) . '"';
            $q .= ' and g.' . $this->mGCodeField . ' in (' . (count($this->mAcceptGroups) ? implode(', ', $ag) : '""') . ')';
        }
       
        $this->mDb->Query($q);
        
        // Вытаскиваем параметры
        if (!$this->mDb->NextRecord()) {
            $this->_Errno = E_PSL_USER_NOTFOUND;
        } else {
            $this->_Props   = $this->mDb->mRecord;
            $this->_Login   = $login;
            $this->_Id      = $this->mDb->F($this->mIdField);
            $this->_Fetched = true;
        }
        return $this->_Errno == E_PSL_USER_OK;
    }
    
    # Проверка, подключена ли БД
    function _CheckDb() {
        if (!isset($this->mDb)) die ('PslUser: Вы забыли подключить БД!');
    }
    
    # Проверка существования пользователя по логину
    function _Exists($login) {
        $this->_CheckDb();
        if (!string_is_login($login)) {
            $this->_Errno = E_PSL_USER_NOLOGIN;
            return false;
        }
        $this->mDb->Query('select * from ' . $this->mUsersTable . ' where ' . $this->mLoginField . ' = "' . $login . '"');
        $this->_Errno = $this->mDb->Nf() ?  E_PSL_USER_OK : E_PSL_USER_NOTFOUND;
        return $this->_Errno == E_PSL_USER_OK;
    }
    
    function _FetchAcceptGroups() {
        $this->_CheckDb();
        if ($this->mAutoAcceptGroups) {
            $q = 'select ' . $this->mGCodeField . ' from ' . $this->mGroupsTable;
            if ($this->mCheckAccessBackEnd || $this->mCheckAccessFrontEnd) $q .= ' where ';
            if ($this->mCheckAccessBackEnd) $q .= $this->mGAccessBackEnd . ' = ' . ($this->mAccessBackEnd ? '1' : '0');
            if ($this->mCheckAccessBackEnd && $this->mCheckAccessFrontEnd) $q .= ' and ';
            if ($this->mCheckAccessFrontEnd) $q .= $this->mGAccessFrontEnd . ' = ' . ($this->mAccessFrontEnd ? '1' : '0');
            $this->mDb->Query($q);
            $this->mAcceptGroups = array();
            while ($this->mDb->NextRecord()) $this->mAcceptGroups[] = $this->mDb->F(0);
        }
    }

}
?>