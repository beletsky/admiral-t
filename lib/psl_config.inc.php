<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_config.inc.php                                                         #
#   Настройки библиотеки                                                       #
#                                                                              #
################################################################################


include( PATH_TO_ROOT . 'inc/config.php' );
include( PATH_TO_ROOT . 'inc/config_db.php' );

// DB: Доступ к БД
define ('_PHPSITELIB_DB_HOST'   , DB_HOST );
define ('_PHPSITELIB_DB_NAME'   , DB_NAME );
define ('_PHPSITELIB_DB_USER'   , DB_USER );
define ('_PHPSITELIB_DB_PWD'    , DB_PASSWORD );
define ('_PHPSITELIB_DB_CHARSET', DB_CHARSET  );

// Session: Использовать сессию
if( !defined( '_PHPSITELIB_USESESS' ) ) define ('_PHPSITELIB_USESESS', true);
// Session: Уровень кеширования сессии 
define ('_PHPSITELIB_SESS_CACHE', 'nocache');
// Session: Время истечения сессии (в минутах)
define ('_PHPSITELIB_SESS_EXPIRE', 30);

// Cache: Уровень кеширования ("passive", "no", "private", "public")
define ('_PHPSITELIB_CACHE', 'no');
// Cache: Если кеширование включено, то определяет срок истечения актуальности в мин.
define ('_PHPSITELIB_CACHEEXP', 1440);

// Debug: Установка отладочного режима для системы (будут выводиться все запросы и ошибки)
define ('_PHPSITELIB_DEBUG', false);
// Debug: Показывать время генерации страницы
define ('_PHPSITELIB_GENTIME', true);

// Errors: Установка режима отображения ошибок для объекта работы с БД:
// report - Отображать ошибки и продолжать выполнение
// yes    - Отображать ошибки и прекращать выполнение 
// no     - Не обращать внимания на ошибки
define ('_PHPSITELIB_DB_REPORT', 'report');
// Errors: Email, куда отсылаются все сообщения об ошибках, если не установлено, то не отсылаются
define ('_PHPSITELIB_ERRMAIL', '');
// Errors: Имя индексного файла, которое будет вырезаться из ссылок
define ('_PHPSITELIB_CUTINDEX', 'index.php');
// Errors: Уровень обработки ошибок
define ('_PHPSITELIB_ERRLVL', E_ALL);

// Tables: дефолтное количество записей в таблицах
define ('_PHPSITELIB_TBL_DEFINPAGE', 100);

// Использование дополнительных модулей
define ('_PHPSITELIB_USE_TEMPLATE', true);
if( !defined( '_PHPSITELIB_USE_USER' ) ) define ('_PHPSITELIB_USE_USER',     true);
define ('_PHPSITELIB_USE_PAGE',     true);
define ('_PHPSITELIB_USE_CONTENT',  false);
define ('_PHPSITELIB_USE_OPTIONS',  true);
define ('_PHPSITELIB_USE_GB',       true);

define ('_PHPSITELIB_USE_SPY',      false);
define ('_PHPSITELIB_USE_FORUM',    false);
define ('_PHPSITELIB_USE_VOTER',    false);
define ('_PHPSITELIB_USE_CART',     false);

//Use password generation module;
define ('_PHPSITE_USE_PASSWORD_GND', true);


?>