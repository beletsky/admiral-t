<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_gb.inc.php                                                             #
#   Гостевая книга. Размещение сообщений, поиск по сообщениям, вывод сообщений #
#                                                                              #
################################################################################
/*

    Пользовательские функции:
    
     int GetTotalMsgCount()                           - Получить число сообщений в текущей гостевой книге
   array GetMsgList()                                 - Получить список сообщений на текущей странице (все поля)
  string GetNavBar(string url)                        - Получить навигационную строку. Используется функция nav_draw_bar (psl_utils)
     int PostMessage(array props)                     - Постинг сообщения от имени текущего пользователя ($psl_user) 
                                                      - props - массив (ключи - имя поле в таблице Messages),
                                                      - Возвращает ID добавленного сообщения или false, если ошибка

    Администраторские функции:                                                  
                                                      
     int GetGbCount()                                 - Количество гостевых книг
   array GetGbList(string sortField)                  - Список гостевых книг, отсортированных по sortField
    bool AddGb(array props)                           - Добавить гостевую книгу
    bool EditGb(int gbId, array props)                - Редактировать гостевую книгу с ID = gbId
    
    bool EditMsg(int msgId, array props)              - Редактировать сообщение с ID = msgId
    
   array GetGbModerators(int gbId)                    - Получить список ID_User модераторов указанной книги
    bool AddGbModerator(int gbId, int userId)         - Добавить модератора гостевой книги
    bool DeleteGbModerator(int modId, int userId)     - Удалить модератора гостевой книги (если userId = текущему пользователю, то не дает)
    
   array GetGbSubscribers(int gbId)                   - Получить список (все поля) подписчиков указанной книги
    bool AddGbSubscriber(int gbId, array props)       - Добавить подписчика гостевой книги
    bool EditGbSubscriber(int subscrId, array props)  - Редактировать подписчика гостевой книги
     int DelGbSubscriber(int subscrId)                - Удалить подписчика
    
                                                      
    Служебные функции:                                                  
                                                      
     int GetErrno()                                   - Последняя ошибка (код)
     int GetError()                                   - Последняя ошибка (сообщение)

*/

define ('E_PSL_GB_OK',       0);
define ('E_PSL_GB_POST',     1);
define ('E_PSL_GB_DBL',      2);
define ('E_PSL_GB_DENIED',   3);

class PslGb {

    # Управление гостевой книгой со стороны пользователя
    var $mCurCode;                     // Код текущей гостевой книги, если пусто, то не используется
    var $mMsgInPage = 25;              // Число сообщений на странице, 0 - все
    var $mPage = 0;                    // Номер текущей страницы
    var $mSortField;                   // Сортировка при выводе списка сообщений
    
    var $mEmailTemplate = '';          // Шаблон для отправки HTML письма о новом сообщении
    var $mEmailSubject = '';           // Тема письма о новом сообщении
    var $mEmailHeaders = '';           // Заголовки письма о новом сообщении
    var $mEmailCharset = 'koi8-r';     // Кодировка письма о новом сообщении
    var $mEmailCharsetTplFld = 'CHARSET'; // Имя поля в шаблоне, в которое будет занесен charset
    
    var $mEmailConvertFrom = 'w';      // Кодировка исходного сообщения
    var $mEmailConvertTo = 'k';        // Кодировка, в которой отправится письмо
    
    var $mEmailConvertSpecial = true;  // Использовать при отсылке письма функцию string_to_html_special (для текста сообщения)
    var $mEmailConvertTags = '';       // Тэги, которые не вырезаются из текста сообщения при отсылке письма
    var $mEmailConvertNl2Br = true;    // Преобразовывать \n => <br> в тексте сообщения при отсылке
    
    
    # Настройки
    var $mGbTable     = 'Gb';              // Список гостевых книг. Имя таблицы
    var $mGbId        = 'ID_Gb';           // Список гостевых книг. ID
    var $mGbName      = 'GbName';          // Список гостевых книг. Название
    var $mGbCode      = 'GbCode';          // Список гостевых книг. Код
    
    var $mGbMTable    = 'GbModerators';    // Модераторы гостевых книг. Имя таблицы
    var $mGbMIdGb     = 'ID_Gb';           // Модераторы гостевых книг. ID гостевой книги
    var $mGbMIdM      = 'ID_User';         // Модераторы гостевых книг. ID пользователя - модератора
    
    var $mMsgTable    = 'GbMsg';           // Сообщения. Имя таблицы
    var $mMsgId       = 'ID_Msg';          // Сообщения. ID
    var $mMsgGb       = 'ID_Gb';           // Сообщения. ID гостевой книги
    var $mMsgUser     = 'ID_User';         // Сообщение. ID отправителя
    var $mMsgDate     = 'MsgDate';         // Сообщения. Дата и время
    var $mMsgText     = 'MsgText';         // Сообщения. Текст
    
    var $mGbSTable    = 'GbSubscribers';   // Подписчики на гостевую. Имя таблицы (если пусто, то рассылка сообщений не осущ.)
    var $mGbSId       = 'ID_GbSubscriber'; // Подписчики на гостевую. ID 
    var $mGbSGb       = 'ID_Gb';           // Подписчики на гостевую. ID гостевой книги
    var $mGbSEmail    = 'SubscriberEmail'; // Подписчики на гостевую. Email подписчика
    
    var $mGbBTable    = 'GbBans';          // Блокировки IP на постинг сообщений. Имя таблицы (если пусто, то не проверка IP не осуществляется)
    var $mGbBId       = 'ID_GbBan';        // Блокировки IP на постинг сообщений. ID
    var $mGbBGb       = 'ID_Gb';           // Блокировки IP на постинг сообщений. ID гостевой книги
    var $mGbBIP       = 'BanIP';           // Блокировки IP на постинг сообщений. Блокированный IP
    

    # public:
    var $mDb;                          // Объект доступа к БД
    var $mUser;                        // Текущий пользователь (если пусто, то не используется)
    
    
    # private (don't change it manually!):
    var $_Errno = E_PSL_GB_OK;
    
    
    function GetTotalMsgCount() {
        $this->_CheckDb();
        
        $q = 'select count(*) from ' . $this->mMsgTable;
        $gb = $this->_GetGbId();
        if (string_is_id($gb)) $q .= ' where ' . $this->mMsgGb . ' = ' . $gb;
        $this->mDb->Query($q);
        return $this->mDb->NextRecord() ? $this->mDb->F(0) : 0;
    }
    
    function GetNavBar($url, $page = '') {
        if ($page != '') $this->mPage = $page;
        return nav_draw_bar($this->mPage, $this->GetTotalMsgCount(), $this->mMsgInPage, $url);
    }
    
    function GetMsgList() {
        $this->_CheckDb();
        
        $q = 'select * from ' . $this->mMsgTable;
        $gb = $this->_GetGbId();
        if (string_is_id($gb)) $q .= ' where ' . $this->mMsgGb . ' = ' . $gb;
        if ($this->mSortField != '') $q .= ' order by ' . $this->mSortField;
        $q .= nav_get_limit($this->mPage, $this->GetTotalMsgCount(), $this->mMsgInPage);
        $this->mDb->Query($q);
        $r = array();
        while ($this->mDb->NextRecord()) $r[] = $this->mDb->mRecord;
        
        return $r;
    }
    
    function PostMessage($props) {
        $this->_CheckDb();
        
        // Получим ID_Gb, если указан код текущей книги
        $gb = $this->_GetGbId();
        
        // Получим ID_User, если используется контроль пользователя
        $user = isset($this->mUser) ? $this->mUser->GetId() : 0;
        
        // Проверим - записано ли уже сообщение
        if ($this->_MsgExists($gb, $user, $props)) return false;
        
        // Проверим, имеет ли право текущий пользователь запись в эту книгу
        if (!$this->_CanPost()) return false;
        
        // Пишем сообщение в базу
        $q = 'insert ' . $this->mMsgTable . ' set ';
        $f = array();
        if ($this->mMsgGb != '' && string_is_id($gb))     $f[] = $this->mMsgGb . ' = ' . $gb;
        if ($this->mMsgUser != '' && string_is_id($user)) $f[] = $this->mMsgUser . ' = ' . $user;
        if ($this->mMsgDate != '') 
            $f[] = $this->mMsgDate . (in_array($this->mMsgDate, array_keys($props)) ? (' = "' . $props[$this->mMsgDate]) : ' = now()');
        foreach ($props as $k => $v)
            if ($k != $this->mMsgDate)
                $f[] = $k . ' = "' . addslashes($v) . '"';
                
        $q .= implode(', ', $f);
        $this->mDb->Query($q);
        if ($this->mDb->mErrno) $this->_Errno = E_PSL_GB_POST;
        
        // Если ошибок при записи не возникло, получим вставленный ID и выполним рассылку
        if ($this->_Errno == E_PSL_GB_OK) {
            $props[$this->mMsgId] = $this->mDb->GetInsertId();
            $this->_Mail($props);
        }
        
        return $this->_Errno == E_PSL_GB_OK;
    }
        
    function GetErrno() {
        return $this->_Errno;
    }
    
    function GetError() {
        $e = array(E_PSL_GB_OK         => 'Выполнено без ошибок',
                   E_PSL_GB_POST       => 'Ошибка записи сообщения',
                   E_PSL_GB_DBL        => 'Такое сообщение уже записано',
                   E_PSL_GB_DENIED     => 'Постинг сообщений с IP ' . get_user_ip() . ' запрещен',
                  );
        return in_array($this->_Errno, array_keys($e)) ? $e[$this->_Errno] : 'Неизвестная ошибка';
    }
    
    # Получить ID_Gb
    function _GetGbId ($code = '') {
        $this->_CheckDb();
        
        if ($code == '') $code = $this->mCurCode;
        if (string_is_login($code)) {
            $q = 'select ' . $this->mGbId . ' from ' . $this->mGbTable . ' where ' . $this->mGbCode . ' = "' . addslashes($code) . '"';
            $this->mDb->Query($q);
            return $this->mDb->NextRecord() ? $this->mDb->F(0) : 0;
        } else {
            return 0;
        }
    }
    
    # Проверить существование сообщения
    function _MsgExists($gb, $user, $props) {
        $this->_CheckDb();
        
        $q = 'select * from ' . $this->mMsgTable . ' where ';
        $f = array();
        if ($this->mMsgGb != '' && string_is_id($gb))     $f[] = $this->mMsgGb . ' = ' . $gb;
        if ($this->mMsgUser != '' && string_is_id($user)) $f[] = $this->mMsgUser . ' = ' . $user;
        if ($this->mMsgDate != '' && in_array($this->mMsgDate, array_keys($props)))
            $f[] = $this->mMsgDate . ' = "' . $props[$this->mMsgDate] . '"';
        foreach ($props as $k => $v)
            if ($k != $this->mMsgDate)
                $f[] = $k . ' = "' . addslashes($v) . '"';

        $q .= implode(' and ', $f);
        $this->mDb->Query($q);
        if ($this->mDb->Nf()) $this->_Errno = E_PSL_GB_DBL;
        return $this->_Errno != E_PSL_GB_OK;
    }
    
    # Проверить, имеет ли право текущий пользователь постить в гостевую
    function _CanPost() {
        $this->_CheckDb();
        
        if ($this->mGbBTable != '') {
            $q = 'select * from ' . $this->mGbBTable . ' where ' . $this->mGbBGb . ' = ' . $this->_GetGbId() . 
                 ' and ' . $this->mGbBIP  . ' = "' . addslashes(get_user_ip()) . '"';
            $this->mDb->Query($q);
            if ($this->mDb->Nf()) $this->_Errno = E_PSL_GB_DENIED;
        }        
        return $this->_Errno == E_PSL_GB_OK;
    }
    
    # Рассылка сообщений о новом постинге
    function _Mail($props) {
        $this->_CheckDb();
        
        if ($this->mGbSTable) {
            $q = 'select ' . $this->mGbSEmail . ' from ' . $this->mGbSTable . ' where ' . $this->mGbSGb . ' = ' . $this->_GetGbId();
            $this->mDb->Query($q);
            while ($this->mDb->NextRecord()) $this->_MailOne($this->mDb->F(0), $props);
        }
        return $this->_Errno == E_PSL_GB_OK;
    }
    
    # Отсылка одного сообщения о новом постинге
    function _MailOne($email, $props) {
        // Если требуется, преобразование текста сообщения
        if ($this->mEmailConvertSpecial) $props[$this->mMsgText] = string_to_html_special($props[$this->mMsgText], $this->mEmailConvertTags, $this->mEmailConvertNl2Br);
        // Кодировка
        if ($this->mEmailCharsetTplFld != '') $props[$this->mEmailCharsetTplFld] = $this->mEmailCharset;
        
        $props2 = array();
        foreach ($props as $k => $v) $props2['{' . $k . '}'] = $v;
        $b = $this->mEmailTemplate != '' ? $this->_PrepareMailStr($this->mEmailTemplate, $props2) : '';
        if ($b == '') foreach ($props as $k => $v) $b .= $k . ' = ' . $v . '<br>';
        $s = $this->_PrepareMailStr($this->mEmailSubject != '' ? $this->mEmailSubject : 'New post', $props2);
        $h = $this->_PrepareMailStr($this->mEmailHeaders, $props2);
        
        $s = $this->_ConvertStr($s);
        $h = $this->_ConvertStr($h);
        
        send_html_mail($email, $s, $b, $h, $this->mEmailCharset);
    
        return $this->_Errno == E_PSL_GB_OK;
    }
    
    # Подставляет значения полей сообщения в строку
    function _PrepareMailStr($str, $props) {
        return str_replace(array_keys($props), $props, $str);
    }
    
    # Конвертирует строку (кодировки)
    function _ConvertStr($str) {
        return $this->mEmailConvertFrom != $this->mEmailConvertTo ? convert_cyr_string($str, $this->mEmailConvertFrom, $this->mEmailConvertTo) : $str;
    }
    
    # Проверка, подключена ли БД
    function _CheckDb() {
        if (!isset($this->mDb)) die ('PslGb: Вы забыли подключить БД!');
    }
        
}

?>