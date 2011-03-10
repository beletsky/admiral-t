<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_admuser.inc.php                                                        #
#   Администрирование пользователей.                                           #
#                                                                              #
################################################################################
/*

   array GetList(string groupCode = '',               - Возвращает список пользователей группы group или всех (если ''), 
                 string sortField = '',                 отсортированный по sortfld начиная с started записи 
                    int started = 0,                    и объемом limit записей
                    int limit = 0)
                           
     int GetCount(string group = '')                  - Кол-во пользователей в группе group
   
     int GetErrno()                                   - Последняя ошибка (код)
     int GetError()                                   - Последняя ошибка (сообщение)

*/

define ('E_PSL_ADMUSER_OK',         0);

class PslAdminUser {
 
    # Настройки
    var $mUsersTable   = 'Users';      // Таблица с пользователями
    var $mIdField      = 'ID_User';    // Поле с ID пользователя
    var $mLoginField   = 'UserLogin';  // Поле с логином
    var $mPwdField     = 'UserPwd';    // Поле с паролем
    var $mDeletedField = 'UserDeleted';// Поле с флагом "удален", если пусто, то не используется
    var $mPwdCrypt     = false;        // Кодировать пароль с помощью функции PASSWORD
    
    var $mGroupsTable  = 'dwGroups';     // Таблица групп пользователей, если пусто, то группы не используются
    var $mUGroupField  = 'ID_Group';   // Поле в табл. пользователей со ссылкой на группу
    var $mGIdField     = 'ID_Group';   // Поле ID в таблице групп (на кот. идет ссылка)
    var $mGCodeField   = 'GroupCode';  // Поле с кодом группы в таблице групп (хранится в свойствах)
    var $mGCodeAlias   = 'GroupCode';  // Название, по которому осущ. доступ к коду группы в свойствах
    
    # public:
    var $mDb;                          // Объект доступа к БД
    
    # private (don't change it manually!):
    var $_Errno = E_PSL_ADMUSER_OK;
    
    
    function GetCount($groupCode = '') {
        $this->_CheckDb();
        $this->_Errno = E_PSL_ADMUSER_OK;
        
        $q = 'select count(u.*) from ' . $this->mUsersTable . ' u';
        if ($this->mGroupsTable != '' && $groupCode != '')
            $q .= ' inner join ' . $this->mGroupsTable . ' g on g.' . $this->mGIdField . ' = u.' . 
                  $this->mUGroupField . ' where g.' . $this->mGCodeField . ' = "' . addslashes($groupCode) . '"';
        $this->mDb->Query($q);
        return $this->mDb->NextRecord() ? $this->mDb->f(0) : 0;
    }
    
    function GetList(groupCode = '', sortField = '', started = 0, limit = 0) {
        $this->_CheckDb();
        $this->_Errno = E_PSL_ADMUSER_OK;

        $q  = 'select u.*';
        if ($this->mGroupsTable != '') $q .= ', g.' . $this->mGCodeField . ' as ' . $this->mGCodeAlias;
        $q .= ' from ' . $this->mUsersTable . ' u';
        $q .= ' inner join ' . $this->mGroupsTable . ' g on g.' . $this->mGIdField . ' = u.' . $this->mUGroupField
        if ($this->mGroupsTable != '' && $groupCode != '') 
          $q .= ' where g.' . $this->mGCodeField . ' = "' . addslashes($groupCode) . '"';
        $this->mDb->Query($q);
    
    
    }
    
    function GetErrno() {
        return $this->_Errno;
    }
    
    function GetError() {
        $e = array(E_PSL_ADMUSER_OK         => 'Выполнено без ошибок',
                  );
        return in_array($this->_Errno, array_keys($e)) ? $e[$this->_Errno] : 'Неизвестная ошибка';
    }
    
    # Проверка, подключена ли БД
    function _CheckDb() {
        if (!isset($this->mDb)) die ('PslAdminUser: Вы забыли подключить БД!');
    }
    
}
?>