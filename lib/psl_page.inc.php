<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_page.inc.php                                                           #
#   Контроль доступа к ресурсам системы и страницам.                           #
#   Методы изменения информации о страницах, ресурсах и привязках.             #
#                                                                              #
################################################################################
/*

    bool IsPageAccessible(string pageCode = '')       - Доступна ли страница в целом (хотя бы один ресурс страницы)
    bool IsResourceAccessible(string resourceCode)    - Доступен ли ресурс
    
   array GetMenuOf(string parentCode = '')            - Вернуть пункты меню, принадлежащие parentCode и доступные текущему пользователю
     int GetMenuCodeByPageCode(string pageCode)       - Вернуть код меню по известному коду страницы
  
     int GetErrno()                                   - Последняя ошибка (код)
     int GetError()                                   - Последняя ошибка (сообщение)

*/

define ('E_PSL_PAGE_OK',         0);
define ('E_PSL_PAGE_GRPID',      1);
define ('E_PSL_PAGE_RESCODE',    2);
define ('E_PSL_PAGE_GRPCODE',    3);
define ('E_PSL_PAGE_PAGECODE',   4);
define ('E_PSL_PAGE_MENUCODE',   5);

class PslPage {
 
    # Настройки
    var $mThisPage      = '';                // Код текущей страницы
    
    var $mResTbl        = 'dwResources';       // Ресурсы. Имя таблицы
    var $mResId         = 'ID_Resource';     // Ресурсы. ID
    var $mResCode       = 'ResourceCode';    // Ресурсы. Код
    var $mResName       = 'ResourceName';    // Ресурсы. Наименование
    
    var $mPageTbl       = 'dwPages';           // Страницы. Имя таблицы
    var $mPageId        = 'ID_Page';         // Страницы. ID
    var $mPageCode      = 'PageCode';        // Страницы. Код
    var $mPageName      = 'PageName';        // Страницы. Наименование
    var $mPagePath      = 'PagePath';        // Страницы. Путь до страницы
    
    var $mPageResTbl    = 'dwPageResources';   // Ресурсы страниц. Имя таблицы
    var $mPageResIdPage = 'ID_Page';         // Ресурсы страниц. ID страницы
    var $mPageResIdRes  = 'ID_Resource';     // Ресурсы страниц. ID ресурсы
    
    var $mGrpResTbl     = 'dwGroupResources';  // Ресурсы, доступные группам. Имя таблицы
    var $mGrpResIdGrp   = 'ID_Group';        // Ресурсы, доступные группам. ID группы
    var $mGrpResIdRes   = 'ID_Resource';     // Ресурсы, доступные группам. ID ресурсы
    
    var $mGrpTbl        = 'dwGroups';          // Группы. Название таблицы
    var $mGrpId         = 'ID_Group';        // Группы. ID
    var $mGrpCode       = 'GroupCode';       // Группы. Код
    
    var $mMenuTbl       = 'dwMenu';            // Пункты меню. Имя таблицы
    var $mMenuId        = 'ID_Menu';         // Пункты меню. ID пункта
    var $mMenuParent    = 'ID_Parent';       // Пункты меню. Вышестоящий уровень
    var $mMenuPage      = 'ID_Page';         // Пункты меню. Ссылка на страницу, к которой привязан пункт
    var $mMenuCode      = 'MenuCode';        // Пункты меню. Код пункта
    var $mMenuName      = 'MenuName';        // Пункты меню. Имя пункта
    var $mMenuOrder     = 'MenuOrder';       // Пункты меню. Последовательность вывода
    
    # public:
    var $mDb;                          // Объект доступа к БД
    var $mUser;                        // Объект контроля пользователя PslUser
    
    # private (don't change it manually!):
    var $_Errno = E_PSL_PAGE_OK;
    var $_Menu = array();
    var $_MenuLoaded = false;
    
    function IsPageAccessible($pageCode = '') {
        $this->_CheckDb();
        $this->_CheckUser();
        $this->_CheckPage();
        $this->_FlushError();
        
        $group_id = $this->mUser->GetGroupId();
        if ($pageCode == '') $pageCode = $this->mThisPage;
        
        if (!string_is_id($group_id)) {
            $this->_Errno = E_PSL_PAGE_GRPID;
            return false;
            
        } else {
            $q = 'select count(*) from ' . $this->mPageTbl . ' p'.
                 ' inner join ' . $this->mPageResTbl . ' pr on pr.' . $this->mPageResIdPage . ' = p.' . $this->mPageId .
                 ' inner join ' . $this->mGrpResTbl . ' gr on gr.' . $this->mGrpResIdRes . ' = pr.' . $this->mPageResIdRes .
                 ' where gr.' . $this->mGrpResIdGrp . ' = ' . $group_id . ' and p.' . $this->mPageCode . ' = "' . $pageCode . '"';
            $this->mDb->Query($q);

            if ($this->mDb->NextRecord())
                return $this->mDb->F(0) ? true : false;
            else
                return false;
                 
        }
    }
    
    function IsResourceAccessible($resCode) {
        $this->_CheckDb();
        $this->_CheckUser();
        $this->_FlushError();
        
        $group_id = $this->mUser->GetGroupId();
        if (!string_is_login($resCode)) {
            $this->_Errno = E_PSL_PAGE_RESCODE;
            return false;
            
        } elseif (!string_is_id($group_id)) {
            $this->_Errno = E_PSL_PAGE_GRPID;
            return false;

        } else {
            $q = 'select count(*) from ' . $this->mResTbl . ' r'.
                 ' inner join ' . $this->mGrpResTbl . ' gr on gr.' . $this->mGrpResIdRes . ' = r.' . $this->mResId .
                 ' where gr.' . $this->mGrpResIdGrp . ' = ' . $group_id .
                 ' and r.' . $this->mResCode . ' = "' . addslashes($resCode) . '"';
            $this->mDb->Query($q);

            if ($this->mDb->NextRecord())
                return $this->mDb->F(0) ? true : false;
            else
                return false;

        }
    }
    
    function GetMenuOf($parentCode = '') {
        $this->_LoadMenu();
        $this->_FlushError();
        
        // Получим массив элементов меню, принадлежащих $parentCode
        $r = !string_is_login($parentCode) ? $this->_Menu : $this->_FindItem($parentCode, $this->_Menu);
        
        return $this->GetErrno() == E_PSL_PAGE_OK ? $r : false;
    }
    
    function GetMenuCodeByPageCode($pageCode) {
        $this->_CheckDb();
        $this->_FlushError();
        $r = '';
        if (string_is_login($pageCode)) {
            $q = 'select m.' . $this->mMenuCode . ' from ' . $this->mMenuTbl . ' m inner join ' . $this->mPageTbl . 
                 ' p on p.' . $this->mPageId . ' = m.' . $this->mMenuPage . ' and p.' . $this->mPageCode . ' = "' . 
                 addslashes($pageCode) . '"';
            $this->mDb->Query($q);
            $r = $this->mDb->NextRecord() ? $this->mDb->F(0) : '';
        }
        return $r;
    }

    function GetErrno() {
        return $this->_Errno;
    }
    
    function GetError() {
        $e = array(E_PSL_PAGE_OK         => 'Выполнено без ошибок',
                   E_PSL_PAGE_GRPID      => 'Неверный ID группы текущего пользователя',
                   E_PSL_PAGE_RESCODE    => 'Неверный код ресурса',
                   E_PSL_PAGE_GRPCODE    => 'Неверный код группы',
                   E_PSL_PAGE_PAGECODE   => 'Неверный код страницы',
                   E_PSL_PAGE_MENUCODE   => 'Неверный код меню',
                  );
        return in_array($this->_Errno, array_keys($e)) ? $e[$this->_Errno] : 'Неизвестная ошибка';
    }
    
    # Проверка, подключена ли БД
    function _CheckDb() {
        if (!isset($this->mDb)) die ('PslPage: Вы забыли подключить БД!');
    }
    
    function _CheckUser() {
        if (!isset($this->mUser)) die ('PslPage: Вы забыли подключить PslUser!');
    }
    
    function _CheckPage() {
        if (!string_is_login($this->mThisPage)) die ('PslPage: Не установлен код текущей страницы');
    }
    
    function _FlushError() {
        $this->_Errno = E_PSL_PAGE_OK;
    }
    
    function _GetGrpId($grpCode) {
        if (!string_is_login($grpCode)) {
            $this->_Errno = E_PSL_PAGE_GRPCODE;
            return false;
        } else {
            $q = 'select ' . $this->mGrpId . ' from ' . $this->mGrpTbl . ' where ' . $this->mGrpCode . ' = "' . $grpCode . '"';
            $this->mDb->Query($q);
            if ($this->mDb->NextRecord()) {
                return $this->mDb->F(0);
            } else {
                $this->_Errno = E_PSL_PAGE_GRPCODE;
                return false;
            }
        }
    }
    
    function _GetResId($resCode) {
        if (!string_is_login($resCode)) {
            $this->_Errno = E_PSL_PAGE_RESCODE;
            return false;
        } else {
            $q = 'select ' . $this->mResId . ' from ' . $this->mResTbl . ' where ' . $this->mResCode . ' = "' . $resCode . '"';
            $this->mDb->Query($q);
            if ($this->mDb->NextRecord()) {
                return $this->mDb->F(0);
            } else {
                $this->_Errno = E_PSL_PAGE_RESCODE;
                return false;
            }
        }
    }
    
    function _GetPageId($pageCode) {
        if (!string_is_login($pageCode)) {
            $this->_Errno = E_PSL_PAGE_PAGECODE;
            return false;
        } else {
            $q = 'select ' . $this->mPageId . ' from ' . $this->mPageTbl . ' where ' . $this->mPageCode . ' = "' . $pageCode . '"';
            $this->mDb->Query($q);
            if ($this->mDb->NextRecord()) {
                return $this->mDb->F(0);
            } else {
                $this->_Errno = E_PSL_PAGE_PAGECODE;
                return false;
            }
        }
    }
    
    function _GetMenuId($menuCode) {
        if (!string_is_login($menuCode)) {
            $this->_Errno = E_PSL_PAGE_MENUCODE;
            return false;
        } else {
            $q = 'select ' . $this->mMenuId . ' from ' . $this->mMenuTbl . ' where ' . $this->mMenuCode . ' = "' . $menuCode . '"';
            $this->mDb->Query($q);
            if ($this->mDb->NextRecord()) {
                return $this->mDb->F(0);
            } else {
                $this->_Errno = E_PSL_PAGE_MENUCODE;
                return false;
            }
        }
    }
    
    function _IsGrpResExists($grpCode, $resCode) {
        $grp_id = $this->_GetGrpId($grpCode);
        $res_id = $this->_GetResId($resCode);
        if (!$this->GetErrno()) {
            $q = 'select * from ' . $this->mGrpResTbl . ' where ' . $this->mGrpResIdGrp . ' = ' . $grp_id . 
                 ' and ' . $this->mGrpResIdRes . ' = ' . $res_id;
            $this->mDb->Query($q);
            if ($this->mDb->Nf()) $this->_Errno = E_PSL_PAGE_GRXSTS;
        }
        return $this->GetErrno() == E_PSL_PAGE_GRXSTS ? true : false;
    }
    
    function _IsPageResExists($pageCode, $resCode) {
        $page_id = $this->_GetPageId($pageCode);
        $res_id = $this->_GetResId($resCode);
        if (!$this->GetErrno()) {
            $q = 'select * from ' . $this->mPageResTbl . ' where ' . $this->mPageResIdPage . ' = ' . $page_id . 
                 ' and ' . $this->mPageResIdRes . ' = ' . $res_id;
            $this->mDb->Query($q);
            if ($this->mDb->Nf()) $this->_Errno = E_PSL_PAGE_PRXSTS;
        }
        return $this->GetErrno() == E_PSL_PAGE_PRXSTS ? true : false;
    }
    
    function _LoadMenu() {
    
        if (!$this->_MenuLoaded) {
        
            // Получим все меню из базы и разобъем его по ID_Parent
            $q = 'select distinct m.*, p.' . $this->mPageCode . ', p.' . $this->mPagePath . ' from ' . $this->mMenuTbl . ' m' .
                 ' inner join ' . $this->mPageTbl . ' p on p.' . $this->mPageId . ' = m.' . $this->mMenuPage .
                 ' inner join ' . $this->mPageResTbl . ' pr on pr.' . $this->mPageResIdPage . ' = p.' . $this->mPageId .
                 ' inner join ' . $this->mGrpResTbl . ' gr on gr.' . $this->mGrpResIdRes . ' = pr.' . $this->mPageResIdRes . 
                 ' and gr.' . $this->mGrpResIdGrp . ' = ' . $this->mUser->GetGroupId() .
                 ' order by m.' . $this->mMenuParent . ', m.' . $this->mMenuOrder;
            $this->mDb->Query($q);
            $menuArray = array();
            $codesArray = array('NULL' => 'NULL');
            while ($this->mDb->NextRecord()) {
                $menuArray[$this->mDb->F($this->mMenuParent) ? $this->mDb->F($this->mMenuParent) : 'NULL'][] = $this->mDb->mRecord;
                $codesArray[$this->mDb->F($this->mMenuCode)] = $this->mDb->F($this->mMenuId);
            }
            
            // Загрузим 
            $this->_Menu = $this->_LoadMenuLevel($menuArray, $codesArray);
            
            // Получим список кодов страниц, привязанных к найденным пунктам меню и ниже
            $this->_CollectPageCodes($this->_Menu, $a);
            $this->_MenuLoaded = true;
        }
        
    }
    
    function _LoadMenuLevel($menuArray, $codesArray, $parentCode = '') {
        $this->_CheckDb();
        $this->_FlushError();
        
        $r = array();
        if ($parentCode != '' && !$this->_GetMenuId($parentCode)) {
            $this->_Errno = E_PSL_PAGE_MENUCODE;
        } else {
            if (isset($codesArray[$parentCode ? $parentCode : 'NULL']) && 
                isset($menuArray[$codesArray[$parentCode ? $parentCode : 'NULL']]) && 
                is_array($menuArray[$codesArray[$parentCode ? $parentCode : 'NULL']])) {
                
                foreach ($menuArray[$codesArray[$parentCode ? $parentCode : 'NULL']] as $m) $r[] = $m;
                
            }
                
            foreach ($r as $k => $v) $r[$k]['_children'] = $this->_LoadMenuLevel($menuArray, $codesArray, $v[$this->mMenuCode]);
        }
        
        return $r;
    }
    
    # Осуществляет рекурсивный поиск по дереву меню, ищет элемент по коду, 
    # возвращает массив элементов, подлежащих под меню с кодом $parentCode
    function _FindItem($searchCode, $parentArray) {
        $item = false;
        if (is_array($parentArray) || string_is_login($searchCode))
            foreach ($parentArray as $k => $v)
                if (is_array($v)) {
                    if (isset($v[$this->mMenuCode]) && $v[$this->mMenuCode] == $searchCode)
                        $item = $v['_children'];
                    else
                        $item = $this->_FindItem($searchCode, $v['_children']);
                    if (is_array($item)) break;
                }
        return $item;
    }
    
    # Осуществует рекурсивный обход дерева меню и собирает коды всех страниц, привязанных к пунктам меню
    function _CollectPageCodes(&$menu, &$codesArray) {
        if (!is_array($codesArray)) $codesArray = array();
        if (!is_array($menu)) return $codesArray;
        foreach ($menu as $k => $v) {
            if (isset($v[$this->mPageCode]) && string_is_login($v[$this->mPageCode])) $menu[$k]['_codes'][] = $v[$this->mPageCode];
            $this->_CollectPageCodes($menu[$k]['_children'], $menu[$k]['_codes']);
            $codesArray = array_merge($codesArray, $menu[$k]['_codes']);
        }
    }
    
}
?>