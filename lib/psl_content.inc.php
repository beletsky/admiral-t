<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_content.inc.php                                                        #
#   Работа с контентом (клиентская).                                           #
#   Также обеспечивает работу с новостями, FAQ'ом и поиск                      #
#                                                                              #
################################################################################
/*
                                   
    bool SetLanguage(int idLanguage, string lngCode)  - Установка текущего языка. По ID, либо по коду
  string GetLanguageName()                            - Получить название текущего языка
     int GetLanguageId()                              - Получить ID_Language текущего языка
   array GetLanguagesList()                           - Получить список языков в виде массива
     
  string GetContent(string contentCode)               - Получить контент по его коду для текущего языка
  
     int GetNewsCount()                               - Получить число показываемых новостей
   array GetNews(int idNews)                          - Получить одну новости по ее ID_News для текущего языка. 
                                                        Возвращается массив с ключами:
                                                         name          - название новости
                                                         create_date   - дата создания новости
                                                         show_date     - дата начала показа новости
                                                         show          - boolean-переменная -- показывать новость или нет
                                                         header        - краткое описание новости
                                                         text          - текст новости
                                                         pictures      - массив картинок, ключом является порядковый номер 
                                                                         вывода картинки, значением -- имя файла
   array GetNewsList(int cnt, int start = 0)          - Получить список последних новостей (массив с ключами - ID_News и значениями - массивами,
                                                        описанными выше для функции GetNews()). Сортируется по дате показа в обратном порядке.
                                                        Параметр cnt - количество выводимых новостей.
                                                        Параметр start - номер новости от начала списка, с которой отдается список.

     int GetErrno()                                   - Последняя ошибка (код)
     int GetError()                                   - Последняя ошибка (сообщение)

*/

define ('_PHPSITELIB_CONTENT_LNG', '_phpsitelibcontent_lng');
define ('E_PSL_CONTENT_OK',       0);
define ('E_PSL_CONTENT_NOLANG',   1);
define ('E_PSL_CONTENT_BADLNG',   2);
define ('E_PSL_CONTENT_BADCCODE', 3);
define ('E_PSL_CONTENT_NOTFOUND', 4);
define ('E_PSL_CONTENT_BADNID',   5);

class PslContent {

    # Настройки
    var $mContentTbl        = 'dwContent';
    var $mContentId         = 'ID_Content';
    var $mContentCode       = 'ContentCode';
    var $mContentSearchable = 'ContentSearchable';
    
    var $mValuesTbl      = 'dwContentValues';
    var $mValuesContent  = 'ID_Content';
    var $mValuesLanguage = 'ID_Language';
    var $mValuesValue    = 'ContentValue';
    
    var $mLanguagesTbl     = 'dwLanguages';
    var $mLanguagesId      = 'ID_Language';
    var $mLanguagesCode    = 'LanguageCode';
    var $mLanguagesName    = 'LanguageName';
    var $mLanguagesDefault = 'LanguageIsDefault';
    
    var $mNewsTbl          = 'dwNews';
    var $mNewsId           = 'ID_News';
    var $mNewsCreateDate   = 'NewsCreateDate';
    var $mNewsShowDate     = 'NewsShowDate';
    var $mNewsShow         = 'NewsShow';
    
    var $mNewsValsTbl       = 'dwNewsValues';
    var $mNewsValsId        = 'ID_NewsValue';
    var $mNewsValsNews      = 'ID_News';
    var $mNewsValsLanguage  = 'ID_Language';
    var $mNewsValsName      = 'NewsName';
    var $mNewsValsHeader    = 'NewsHeader';
    var $mNewsValsText      = 'NewsText';
    
    var $mNewsPicsTbl       = 'NewsPics';
    var $mNewsPicsId        = 'ID_Pic';
    var $mNewsPicsNews      = 'ID_News';
    var $mNewsPicsFile      = 'NewsPicFile';
    var $mNewsPicsNum       = 'NewsPicNum';

    var $mNewsListAddWhere  = '';      // Дополнительное условие для WHERE при выборке списка новостей (см ф-ю GetNewsList)
        
    # public:
    var $mDb;                          // Объект доступа к БД
    var $mNewsPicDir;                  // Путь до каталога с картинками (с замыкающим слэшем)
    
    # private (don't change it manually!):
    var $_Errno = E_PSL_CONTENT_OK;
    var $_CurLng = 0;
    var $_Fetched = false;
    var $_Strings = array();
    
    function SetLanguage($idLanguage, $lngCode = '') {
        $this->_CheckDb();
        $this->_FlushError();
        $this->_GetCurLng();

        if (!string_is_id($idLanguage)) $idLanguage = $this->_GetLngByCode($lngCode);
        
        if (string_is_id($idLanguage)) {
            $this->_CurLng = $idLanguage;
            $this->_SaveCurLng();
            $this->_Fetched = false;
        } else {
            $this->_Errno = E_PSL_CONTENT_BADLNG;
        }
        
        return $this->GetErrno() ? false : true;
    }
    
    function GetLanguageName() {
        $this->_CheckDb();
        $this->_FlushError();
        $this->_GetCurLng();
    
        if (!$this->Errno()) {
            $this->mDb->Query('select ' . $this->mLanguagesName . ' from ' . $this->mLanguagesTbl . ' where ' . $this->mLanguagesId . ' = ' . $this->_CurLng);
            return $this->mDb->NextRecord() ? $this->mDb->F(0) : '';
        }
            
        return $this->GetErrno() ? false : true;
    }
    
    function GetLanguageId() {
        $this->_GetCurLng();
        return $this->_CurLng;
    }
    
    function GetLanguagesList() {
        $this->_CheckDb();
        $this->_FlushError();
        $r = array();
        $this->mDb->Query('select * from ' . $this->mLanguagesTbl);
        while ($this->mDb->NextRecord()) $r[$this->mDb->F($this->mLanguagesId)] = $this->mDb->mRecord;
        return $r;
    }
    
    function GetContent($contentCode) {
        $this->_CheckDb();
        $this->_FlushError();
        $this->_FetchStrings();
    
        if (!string_is_login($contentCode)) {
            $this->_Errno = E_PSL_CONTENT_BADCCODE;
        } else {
            if (isset($this->_Strings[$contentCode])) {
                return $this->_Strings[$contentCode];
            } else {
                $q = 'select c.' . $this->mContentId . ', v.' . $this->mValuesValue . ' from ' . $this->mValuesTbl . ' v inner join ' . 
                     $this->mContentTbl . ' c on c.' . $this->mContentId . ' = v.' . $this->mValuesContent . ' and c.' . 
                     $this->mContentCode . ' = "' . addslashes($contentCode) . '" where v.' . $this->mValuesLanguage . ' = ' . $this->_CurLng;
                $this->mDb->Query($q);
                if ($this->mDb->NextRecord()) 
                    return $this->mDb->F(1);
                else
                    $this->_Errno = E_PSL_CONTENT_NOTFOUND;
            }
        }
        return $this->GetErrno() ? false : true;
    }
    
    function GetNewsCount() {
        $this->_CheckDb();
        $this->_FlushError();
        
        $q = 'select count(*) from ' . $this->mNewsTbl . ' where ' . $this->mNewsShow . ' <> 0 and ' . $this->mNewsShowDate . ' <= now()';
        $this->mDb->Query($q);
        
        return $this->mDb->NextRecord() ? $this->mDb->F(0) : 0;
    }
    
    function GetNews($idNews) {
        $this->_CheckDb();
        $this->_FlushError();
        $this->_GetCurLng();
        $r = array();
        
        if (!string_is_id($idNews)) {
            $this->_Errno = E_PSL_CONTENT_BADNID;
        } else {
        
            // Получим параметры новости
            $q = 'select n.*, v.' . $this->mNewsValsName . ', v.' . $this->mNewsValsHeader . ', v.' . $this->mNewsValsText . 
                 ' from ' . $this->mNewsTbl . ' n left join ' . $this->mNewsValsTbl . ' v on v.' . $this->mNewsValsNews . 
                 ' = n.' . $this->mNewsId . ' and v.' . $this->mNewsValsLanguage . ' = ' . $this->_CurLng .
                 ' where n.' . $this->mNewsId . ' = ' . $idNews;
            $this->mDb->Query($q);
            if (!$this->mDb->NextRecord()) {
                $this->_Errno = E_PSL_CONTENT_NOTFOUND;
            } else {
                $r = array('name'        => $this->mDb->F($this->mNewsValsName),
                           'create_date' => $this->mDb->F($this->mNewsCreateDate),
                           'show_date'   => $this->mDb->F($this->mNewsShowDate),
                           'show'        => $this->mDb->F($this->mNewsShow) ? true : false,
                           'header'      => $this->mDb->F($this->mNewsValsHeader),
                           'text'        => $this->mDb->F($this->mNewsValsText),
                           );
                $r = array_merge($r, $this->mDb->mRecord);

                // Получим картинки в порядке следования
                $p = array();
                $q = 'select * from ' . $this->mNewsPicsTbl . ' where ' . $this->mNewsPicsNews . ' = ' . $idNews . 
                     ' order by ' . $this->mNewsPicsNum; 
                $this->mDb->Query($q);
                while ($this->mDb->NextRecord()) 
                    $p[$this->mDb->F($this->mNewsPicsNum)] = $this->mDb->F($this->mNewsPicsFile);
                    
                // Произведем замену спец-тега %IMAGEn% для необходимых полей
                $r['header'] = $this->_NewsTextPrepare($r['header'], $p);
                $r['text'] = $this->_NewsTextPrepare($r['text'], $p);
                    
                $r['pictures'] = $p;
                
            }
        }
        
        return $this->GetErrno() ? false : $r;
    }
    
    function GetNewsList($cnt, $start = 0) {
        $this->_CheckDb();
        $this->_FlushError();
        $this->_GetCurLng();
        $r = array();
        
        if (!string_is_int($cnt)) $cnt = 0;
        if (!string_is_int($start)) $start = 0;
        
        // Получим список ID новостей
        $q = 'select ' . $this->mNewsId . ' from ' . $this->mNewsTbl . 
             ' where ' . $this->mNewsShow . ' <> 0 and ' . $this->mNewsShowDate . ' <= now()' . ($this->mNewsListAddWhere != '' ? ' and ' . $this->mNewsListAddWhere : '') . 
             ' order by ' . $this->mNewsShowDate . ' desc' .
             ' limit ' . $start . ', ' . $cnt;
        $this->mDb->Query($q);
        $news = array();
        while ($this->mDb->NextRecord()) $news[] = $this->mDb->F(0);
        
        // Загрузим новости
        foreach ($news as $id) $r[$id] = $this->GetNews($id);
    
        return $this->GetErrno() ? false : $r;
    }
    
    function GetErrno() {
        return $this->_Errno;
    }
    
    function GetError() {
        $e = array(E_PSL_CONTENT_OK         => 'Выполнено без ошибок',
                   E_PSL_CONTENT_NOLANG     => 'Таблица языков пуста',
                   E_PSL_CONTENT_BADLNG     => 'Неверный ID языка',
                   E_PSL_CONTENT_BADCCODE   => 'Код контента содержит недопустимые символы',
                   E_PSL_CONTENT_NOTFOUND   => 'Контент не найден',
                   E_PSL_CONTENT_BADNID     => 'Неверный ID новости',
                  );
        return in_array($this->_Errno, array_keys($e)) ? $e[$this->_Errno] : 'Неизвестная ошибка';
    }
    
    # Получить ID текущего языка
    function _GetCurLng() {
        $this->_CheckDb();
        $this->_FlushError();
        // если не установлен язык в сессии, то получим дефолтный и установим
        // вернем текущий язык
        if (!isset($_SESSION[_PHPSITELIB_CONTENT_LNG]) && !$this->_CurLng) 
            $this->_CurLng = $this->_GetDefLng();
        else 
            $this->_CurLng = $_SESSION[_PHPSITELIB_CONTENT_LNG];
            
        if (!string_is_id($this->_CurLng)) {
            $this->_CurLng = 0;
            $this->_Errno = E_PSL_CONTENT_BADLNG;
        }
        if (!$this->GetErrno()) $this->_SaveCurLng();
        return !$this->GetErrno() ? $this->_CurLng : false;
    }
    
    # Записать ID текущего языка в сессию
    function _SaveCurLng() {
        $this->_CheckDb();
        $this->_FlushError();
        $_SESSION[_PHPSITELIB_CONTENT_LNG] = $this->_CurLng;
        return $this->GetErrno() ? false : true;
    }
    
    # Получить язык по умолчанию
    function _GetDefLng() {
        $this->_CheckDb();
        $this->_FlushError(); 
        $this->mDb->Query('select ' . $this->mLanguagesId . ' from ' . $this->mLanguagesTbl . ' where ' . $this->mLanguagesDefault . ' = 1');
        if (!$this->mDb->NextRecord()) {
            $this->mDb->Query('select ' . $this->mLanguagesId . ' from ' . $this->mLanguagesTbl . ' order by ' . $this->mLanguagesId . ' limit 1');
            if (!$this->mDb->NextRecord()) $this->_Errno = E_PSL_CONTENT_NOLANG;
        }
        return !$this->GetErrno() ? $this->mDb->F(0) : false;
    }

    # Получим ID_Language по коду LanguageCode
    function _GetLngByCode($lngCode) {
        $this->_CheckDb();
        $this->_FlushError(); 
        $this->mDb->Query('select ' . $this->mLanguagesId . ' from ' . $this->mLanguagesTbl . ' where ' . $this->mLanguagesCode . ' = "' . addslashes($lngCode) . '"');
        if (!$this->mDb->NextRecord()) {
            $this->_Errno = E_PSL_CONTENT_NOLANG;
        }
        return !$this->GetErrno() ? $this->mDb->F(0) : false;
    }
    
    # Загрузить строки
    function _FetchStrings() {
        $this->_CheckDb();
        $this->_FlushError();
        $this->_GetCurLng();
        if (!$this->_Fetched) {
            $q = 'select c.' . $this->mContentCode . ', v.' . $this->mValuesValue . ', c.' . $this->mContentId . ' from ' . 
                 $this->mValuesTbl . ' v inner join ' . $this->mContentTbl . ' c on c.' . $this->mContentId . ' = v.' . 
                 $this->mValuesContent . ' and c.' . $this->mContentSearchable . ' = 0'.
                 ' where v.' . $this->mValuesLanguage . ' = ' . $this->_CurLng;
            $this->mDb->Query($q);
            while ($this->mDb->NextRecord()) $this->_Strings[$this->mDb->F(0)] = $this->mDb->F(1);
            $this->_Fetched = true;
        }
    }
    
    # Подготовка строк новостей (замена спец-тэга %IMAGEn% на названия файлов картинок)
    function _NewsTextPrepare($str, $images) {
        if (is_array($images)) 
            foreach ($images as $num => $img) 
                $str = str_replace('%IMAGE' . $num . '%', $this->mNewsPicDir . $img, $str);
        return $str;
    }

    # Проверка, подключена ли БД
    function _CheckDb() {
        if (!isset($this->mDb)) die ('PslContent: Вы забыли подключить БД!');
    }
    
    # Сброс кода последней ошибки
    function _FlushError() {
        $this->_Errno = E_PSL_CONTENT_OK;
    }
    
}

?>