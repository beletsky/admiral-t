<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_admtbl.inc.php                                                         #
#   Универсальный класс для отображения таблиц с данными.                      #
#                                                                              #
################################################################################
/*

   bool SetInPageOptions(array data)     // установка значений выбиралки по сколько показывать записей,
                                            возможно значение -1 - показывать все записи
   
   bool SetHead(string link,             // ссылка на колонки (сортировка), выбор страницы и кол-ва записей на стр.
                 array captions,         // массив с названиями заголовков
                 array sortFields,       // массив с названиями полей
                 array titles,           // массив с подсказками
                string linkEnd = '')     // ссылка, добавляемая в конец ссылок
                 
   bool SetRow(array data,               // массив с данными
              string class = '')
   
   string GetTable()                     // результирующая строка с таблицей
     bool Reset()                        // очистка результата
   string GetLimitClause()               // вернуть выражение LIMIT для SQL
   string GetOrderByClause()             // вернуть выражение ORDER BY для SQL

*/

class PslAdmTbl {
 

    # Настройки
    // сортировка
    var $mSortParam      = 's';
    var $mSortTypeParam  = 't';
    var $mSortDefault    = '';          // Дефолтное поле сортировки
    var $mSortMain    = '';             // Дефолтное поле сортировки
    var $mSortTypeDefault= '';          // Дефолтное направление сортировки
    var $mSortFields     = array();     // Массив: ключи - параметры для определения сортировки, значение - названия полей
    
    // навигация по страницам и выбор кол-ва записей на стр
    var $mPageParam      = 'p';
    var $mShowPageNav    = true;        // Показ ходилки по страницам
    var $mInPageParam    = 'i';
    var $mShowInPageSel  = true;        // Показ выбиралки по скольку показывать
    var $mInPageDefault  = _PHPSITELIB_TBL_DEFINPAGE;        // По скольку показывать - по умолчанию
    var $mRecordsCnt     = 0;           // Количество записей
    
    // показ количества записей
    var $mRecCntShow     = false;
    var $mRecCntTpl      = '&nbsp;Всего&nbsp;{CNT}&nbsp;{WORD}.&nbsp;'; // Шаблон. {CNT} - число записей, {WORD} - слово (напр. "записей")
    var $mRecCntWords    = array('запись', 'записи', 'записей');        // Массив со словами для {WORD}
    
    // параметры сохранения настроек в сессии
    var $mSaveInSession  = true;        // Запоминать настройки (сортировку и пр.) в сессии
    var $mSessionPrefix  = 'a_';        // Префикс для запоминания настроек в сессии
    
    // визуальные настройки
    var $mUseInsideForm = false;
    var $mInsideFormName = 'InsideForm';
    var $mTblParentCls   = 'outer2';      // Класс ячейки, в которой лежит внутрення таблица (см SetHead)
    var $mCaptionCls     = 'nonselected'; // Класс для нетекущей колонки
    var $mSelCaptionCls  = 'selected';    // Класс для текущей колонки
    var $mDownImg        = '';            // Картинка "стрелка вниз"
    var $mUpImg          = '';            // Картинка "стрелка вверх"
    var $mInPageSelCls   = 'inp';         // Класс выбиралки записей на странице
    
    // прочее
    var $mTblForm      = '';              // Открытие формы для списка (будет помещен после выбиралки и до начала таблицы). Закрывается автоматически

        
    # private
    var $_mContent       = '';
    var $_mInPageOptions = array();
    var $_mSort          = '';
    var $_mSortType      = '';
    var $_mPage          = 0;
    var $_mInPage        = 0;
    var $_mLink          = '';
    var $_mAddParams     = '';
    var $_mFieldCount    = 0;
    
    
    function SetHead($link, $captions, $titles, $linkEnd = '') {
        $this->Reset();
        $this->_FetchParams();
        
        $this->_mLink = $link;
        $this->_mAddParams = $linkEnd;
        $s = $this->_NavStart();
        
        $sortFields = array_values($this->mSortFields);
        
        $s .= '<table border=0 cellspacing=0 cellpadding=0 ><tr><td ';
        if ($this->mTblParentCls != '') $s .= ' class="' . $this->mTblParentCls . '"';
        $s .= "><table border=0 cellspacing=1 cellpadding=2 >" . $this->mTblForm . "\n";
        
        $this->_mContent = $s;
                                           
        if (!is_array($captions) || $link == '') return false;   
        if (!in_array($this->_mSort, $sortFields)) $this->_mSort = $this->mSortDefault; 
        if ($this->_mSortType != 'desc' && $this->_mSortType != 'asc') $this->_mSortType = $this->mSortTypeDefault;
                                                   
        $s .= "<tr>\n";
        
        $this->_mFieldCount = count($captions);
        for ($i = 0, $cnt = count($captions); $i < $cnt; $i++) {
                                                   
            $caption = isset($captions[$i]) ? $captions[$i] : '';
            $sort    = isset($sortFields[$i]) ? $sortFields[$i] : '';
            $title   = isset($titles[$i]) ? $titles[$i] : '';
            
            $current_col = $sort == $this->_mSort;
            $cls = $current_col ? $this->mSelCaptionCls : $this->mCaptionCls;
            
            $show_link = $sort != '';
            
            $s .= ' <th>';
            if ($current_col && $this->mDownImg != '' && $this->mUpImg != '') {
                $s .= '<img src="';
                $s .= $this->_mSortType == 'desc' ? $this->mUpImg : $this->mDownImg;
                $s .= '">';
            }
            
            if ($show_link) {
                $s .= '<a href="' . $link;
                if ($this->mSortParam != '' || $linkEnd != '') $s .= '&';
                if ($this->mSortParam != '') $s .= $this->mSortParam . '=' . $sort;
                if ($this->mSortParam != '' && $this->mSortTypeParam != '') $s .= '&';
                if ($this->mSortTypeParam != '') 
                    $s .= $this->mSortTypeParam . '=' . ($this->_mSortType == 'desc' || !$current_col ? 'asc' : 'desc');
                if (($this->mSortParam != '' || $this->mSortTypeParam != '') && $linkEnd != '') $s .= '&';
                $s .= $linkEnd;
                $s .= '"';
                if ($title != '') $s .= ' title="' . $title . '"';
            } else {
                $s .= '<span';
            }
            if ($cls != '') $s .= ' class="' . $cls . '"';
            
            $s .= '>' . $caption . ($show_link ? '</a>' : '</span>') . "</th>\n";
        }
        $s .= "</tr>\n";
        $this->_mContent = $s;
    }
    
    function SetRow($data, $cls = '') {
        if (!is_array($data)) return false;
        $s = "<tr>\n";
        $i = 0;
        $cnt = count($data);
        foreach ($data as $v) {
            $i++;
            $s .= '<td';
            if ($cls != '') $s .= ' class="' . $cls . '"';
            if ($i == $cnt && $i < $this->_mFieldCount) $s .= ' colspan=' . ($this->_mFieldCount - $i + 1);
            $s .= '>'.$v."</td>\n";
        }
        $s .= "</tr>\n";
        $this->_mContent .= $s;
    }
    
    function GetTable() {
        return $this->_mContent != '' ? $this->_mContent . ($this->mTblForm != '' ? '</form>' : '') . '</table></td></tr></table>' . $this->_NavFinish() : '';
    }
    
    function Reset() {
        $this->_mContent = '';
    }
    
    function SetInPageOptions($data) {
        $this->_mInPageOptions = $data;
    }
    
    function GetLimitClause() {
        return nav_get_limit($this->_mPage, $this->mRecordsCnt, $this->_mInPage);
    }
    
    function GetOrderByClause() {
        $main_order = $this->mSortMain == '' ? '' : $this->mSortMain.', ';
//        return $this->mSortFields[$this->_mSort] == '' ? '' : 
//               ' order by '. $main_order .  $this->mSortFields[$this->_mSort] . ($this->_mSortType == 'desc' ? ' desc' : '');
        return $this->_mSort == '' ? '' : 
               ' order by '. $main_order .  $this->_mSort . ($this->_mSortType == 'desc' ? ' desc' : '');
    }
    
    function _FetchParams() {
        
        $this->_mSort     = $this->_FetchOneParam($this->mSortParam,     $this->mSortDefault);
        $this->_mSortType = $this->_FetchOneParam($this->mSortTypeParam, '');
        $this->_mPage     = $this->_FetchOneParam($this->mPageParam,     '0');
        $this->_mInPage   = $this->_FetchOneParam($this->mInPageParam,   $this->mInPageDefault);
        
        $this->_SaveParams();
    }
    
    function _SaveParams() {
        if ($this->mSaveInSession) {
        
            $this->_SaveOneParam($this->mSortParam,     $this->_mSort);
            $this->_SaveOneParam($this->mSortTypeParam, $this->_mSortType);
            $this->_SaveOneParam($this->mPageParam,     $this->_mPage);
            $this->_SaveOneParam($this->mInPageParam,   $this->_mInPage);
        
        }
    }
    
    function _FetchOneParam($paramName, $defaultValue) {
        global $_GET, $_POST, $_SESSION;
        
        if (isset($_GET[$paramName]))
            $r = $_GET[$paramName];
        elseif (isset($_POST[$paramName]))
            $r = $_POST[$paramName];
        elseif ($this->mSaveInSession && isset($_SESSION[$this->mSessionPrefix . $paramName]))
            $r = $_SESSION[$this->mSessionPrefix . $paramName];
        else
            $r = $defaultValue;
            
        return $r;
    }
    
    function _SaveOneParam($paramName, $paramValue) {
        global $_SESSION;
        $name = $this->mSessionPrefix . $paramName;
        $_SESSION[$name] = $paramValue;
    }
    
    function _NavStart() {
        $r = '';
        if ($this->mShowInPageSel || $this->mShowPageNav || $this->mRecCntShow) {
            $r .= '<table border=0 cellspacing=0 cellpadding=0><tr>';
            $r .= $this->_GetNavigation($this->mRecCntShow);
            $r .= '</tr><tr><td colspan=3>';
        }
        return $r;
    }
    
    function _NavFinish() {
        $r = '';
        if ($this->mShowInPageSel || $this->mShowPageNav) {
            $r .= '</td></tr><tr>';
//            $r .= $this->_GetNavigation();
            $r .= '</tr></table>';
        }
        return $r;
    }
    
    function _GetNavigation($showRecCnt = false) {
        $r = '';
        
        if ($this->_mInPage == '') $this->_mInPage = $this->mInPageDefault;
        
        if (!$this->mShowInPageSel) {
            $r .= '<td>&nbsp;</td>';
        } else {
//            $r .= '<form action="' . $this->_mLink . '" method=get>';
            $r .= $this->_GetAddParamsStr() . '<td>';
            $r .= '<select name="' . $this->mInPageParam . '"';
            if ($this->mInPageSelCls != '') $r .= ' class="' . $this->mInPageSelCls . '"';
            $r .= ' onchange="this.form.submit();">';
            $r .= get_select_options($this->_mInPage, $this->_mInPageOptions);
            $r .= '</select></td>';
//            $r .= '</form>';
        }
        
        if (!$showRecCnt) {
            $r .= '<td>&nbsp;</td>';
        } else {
            $r .= '<td>' . $this->_GetRecCnt() . '</td>';
        }
        
        if (!$this->mShowPageNav) {
            $r .= '<td>&nbsp;</td>';
        } else {
            $r .= '<td align=center width=100%>';
            $n  = nav_draw_bar($this->_mPage, $this->mRecordsCnt, $this->_mInPage, $this->_mLink . '&' . 
                  ($this->_mAddParams ? $this->_mAddParams . '&' : '') . $this->mPageParam . '=');
            $r .= $n != '' ? $n : '&nbsp;';
            $r .= '</td>';
        }
        
        return $r;
    }
    
    function _GetAddParamsStr() {
        $r = '';
        if ($this->_mAddParams) {
            $a1 = explode('&', $this->_mAddParams);
            foreach ($a1 as $aa1) {
                $a2 = explode('=', $aa1);
                if (isset($a2[0]) && isset($a2[1])) 
                    $r .= '<input type=hidden name="' . $a2[0] . '" value="' . $a2[1] . '">';
            }
        }
        return $r;
    }
    
    function _GetRecCnt() {
        return str_replace(
            array('{CNT}', '{WORD}'), 
            array($this->mRecordsCnt, get_word_form($this->mRecordsCnt, $this->mRecCntWords)), 
            $this->mRecCntTpl);
    }
    
}