/*
SQLyog Enterprise - MySQL GUI v8.12 
MySQL - 5.0.45-community-nt : Database - metiza
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

/*Table structure for table `dwGroupResources` */

CREATE TABLE `dwGroupResources` (
  `ID_Group` int(10) unsigned default NULL,
  `ID_Resource` int(10) unsigned default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `dwGroupResources` */

insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,13);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,12);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,1);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,4);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,3);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,9);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,10);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,5);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,6);
insert  into `dwGroupResources`(`ID_Group`,`ID_Resource`) values (1,2);

/*Table structure for table `dwGroups` */

CREATE TABLE `dwGroups` (
  `ID_Group` int(10) unsigned NOT NULL auto_increment,
  `GroupCode` varchar(50) default NULL,
  `GroupName` varchar(255) default NULL,
  `GroupAccessBackEnd` tinyint(1) NOT NULL default '0',
  `GroupAccessFrontEnd` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ID_Group`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `dwGroups` */

insert  into `dwGroups`(`ID_Group`,`GroupCode`,`GroupName`,`GroupAccessBackEnd`,`GroupAccessFrontEnd`) values (1,'admin','Administrator',1,1);

/*Table structure for table `dwMenu` */

CREATE TABLE `dwMenu` (
  `ID_Menu` int(10) unsigned NOT NULL auto_increment,
  `ID_Parent` int(10) unsigned default NULL,
  `ID_Page` int(10) unsigned default NULL,
  `MenuCode` varchar(50) default NULL,
  `MenuName` varchar(255) default NULL,
  `MenuOrder` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID_Menu`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

/*Data for the table `dwMenu` */

insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (1,NULL,1,'home','Главная',0);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (2,NULL,2,'adm','Администрирование',1000);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (3,2,3,'resources','Ресурсы',5);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (4,2,4,'adm_pages','Страницы',10);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (5,2,5,'adm_menu','Меню',30);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (6,2,8,'adm_users','Пользователи',2);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (7,2,6,'adm_groups','Группы',4);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (8,2,10,'adm_options','Настройки',60);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (10,NULL,12,'static_page','Страницы сайта',10);
insert  into `dwMenu`(`ID_Menu`,`ID_Parent`,`ID_Page`,`MenuCode`,`MenuName`,`MenuOrder`) values (11,NULL,13,'user_tpl','Шаблоны писем',990);

/*Table structure for table `dwOptions` */

CREATE TABLE `dwOptions` (
  `ID_Option` int(10) unsigned NOT NULL auto_increment,
  `ID_Parent` int(10) unsigned default NULL,
  `OptionCode` varchar(50) default NULL,
  `OptionSubCode` varchar(50) default NULL,
  `OptionName` varchar(255) default NULL,
  `OptionValue` varchar(255) default NULL,
  `OptionOrder` int(10) unsigned default '0',
  PRIMARY KEY  (`ID_Option`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

/*Data for the table `dwOptions` */

insert  into `dwOptions`(`ID_Option`,`ID_Parent`,`OptionCode`,`OptionSubCode`,`OptionName`,`OptionValue`,`OptionOrder`) values (1,NULL,'menus','none','Виды меню','Не отображать в меню',1);
insert  into `dwOptions`(`ID_Option`,`ID_Parent`,`OptionCode`,`OptionSubCode`,`OptionName`,`OptionValue`,`OptionOrder`) values (2,1,NULL,'top',NULL,'Верхнее меню',2);
insert  into `dwOptions`(`ID_Option`,`ID_Parent`,`OptionCode`,`OptionSubCode`,`OptionName`,`OptionValue`,`OptionOrder`) values (3,1,NULL,'top2',NULL,'Дополнительное верхнее меню',3);
insert  into `dwOptions`(`ID_Option`,`ID_Parent`,`OptionCode`,`OptionSubCode`,`OptionName`,`OptionValue`,`OptionOrder`) values (4,1,NULL,'production',NULL,'Продукция',4);

/*Table structure for table `dwPageResources` */

CREATE TABLE `dwPageResources` (
  `ID_Page` int(10) unsigned default NULL,
  `ID_Resource` int(10) unsigned default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `dwPageResources` */

insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (1,1);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (2,2);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (3,3);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (4,4);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (5,5);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (6,6);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (8,9);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (10,10);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (12,12);
insert  into `dwPageResources`(`ID_Page`,`ID_Resource`) values (13,13);

/*Table structure for table `dwPages` */

CREATE TABLE `dwPages` (
  `ID_Page` int(10) unsigned NOT NULL auto_increment,
  `PageCode` varchar(50) default NULL,
  `PageName` varchar(255) default NULL,
  `PagePath` varchar(255) default NULL,
  PRIMARY KEY  (`ID_Page`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

/*Data for the table `dwPages` */

insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (1,'home','Главная','home/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (2,'adm','Администрирование','adm/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (3,'adm_resources','Администрирование. Ресурсы','adm/resources/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (4,'adm_pages','Администрирование. Страницы','adm/pages/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (5,'adm_menu','Администрирование. Меню','adm/menu/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (6,'adm_groups','Администрирование. Группы пользователей','adm/groups/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (7,'adm_content','Администрирование. Контент','adm/content/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (8,'adm_users','Администрирование. Пользователи','adm/users/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (10,'adm_options','Администрирование. Настройки','adm/options/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (12,'static_page','Страницы сайта','static_page/');
insert  into `dwPages`(`ID_Page`,`PageCode`,`PageName`,`PagePath`) values (13,'user_tpl','Шаблоны писем','user_tpl/');

/*Table structure for table `dwResources` */

CREATE TABLE `dwResources` (
  `ID_Resource` int(10) unsigned NOT NULL auto_increment,
  `ResourceCode` varchar(50) default NULL,
  `ResourceName` varchar(255) default NULL,
  PRIMARY KEY  (`ID_Resource`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

/*Data for the table `dwResources` */

insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (1,'home','Главная');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (2,'adm','Администрирование');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (3,'adm_resources','Администрирование. Ресурсы');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (4,'adm_pages','Администрирование. Страницы');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (5,'adm_menu','Администрирование. Меню');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (6,'adm_groups','Администрирование. Группы пользователей');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (9,'adm_users','Администрирование. Пользователи');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (10,'adm_options','Администрирование. Настройки');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (12,'static_page','Страницы сайта');
insert  into `dwResources`(`ID_Resource`,`ResourceCode`,`ResourceName`) values (13,'user_tpl','Шаблоны писем');

/*Table structure for table `dwStaticPage` */

CREATE TABLE `dwStaticPage` (
  `ID` int(11) NOT NULL auto_increment,
  `Host` varchar(255) NOT NULL default '',
  `PageCode` varchar(255) NOT NULL default '',
  `Title` varchar(255) NOT NULL default '',
  `Description` varchar(255) NOT NULL,
  `Keywords` varchar(255) NOT NULL,
  `Name` varchar(255) NOT NULL default '',
  `Announce` text NOT NULL,
  `Text` text NOT NULL,
  `Menu` varchar(255) NOT NULL default '',
  `MenuText` varchar(255) NOT NULL,
  `Image` varchar(255) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Data for the table `dwStaticPage` */

insert  into `dwStaticPage`(`ID`,`Host`,`PageCode`,`Title`,`Description`,`Keywords`,`Name`,`Announce`,`Text`,`Menu`,`MenuText`,`Image`) values (1,'metiza','index','Главная страница','','','Главная страница','','','none','','');
insert  into `dwStaticPage`(`ID`,`Host`,`PageCode`,`Title`,`Description`,`Keywords`,`Name`,`Announce`,`Text`,`Menu`,`MenuText`,`Image`) values (2,'','','','','','','','','','','');

/*Table structure for table `dwTree` */

CREATE TABLE `dwTree` (
  `Host` varchar(50) NOT NULL,
  `ObjectType` varchar(50) NOT NULL,
  `ObjectID` varchar(50) NOT NULL,
  `NodeID` int(11) NOT NULL,
  `Lft` int(11) NOT NULL,
  `Rgt` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Data for the table `dwTree` */

insert  into `dwTree`(`Host`,`ObjectType`,`ObjectID`,`NodeID`,`Lft`,`Rgt`) values ('metiza','staticpagetree','0',0,0,1);

/*Table structure for table `dwUserTPL` */

CREATE TABLE `dwUserTPL` (
  `ID` int(11) NOT NULL auto_increment,
  `Host` varchar(255) default '',
  `Name` varchar(255) NOT NULL default '',
  `Code` varchar(255) NOT NULL default '',
  `Text` text NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `dwUserTPL` */

insert  into `dwUserTPL`(`ID`,`Host`,`Name`,`Code`,`Text`) values (1,'metiza','Заказ календаря с сайта','message_feedback','From: info@{HOST}\r\nTo: ab@arttech.ru\r\nSubject: С сайта www.{HOST} отправлен заказ\r\n\r\nДата отправки     : {DATE} {TIME}\r\nНазвание компании : {FIRM}\r\nТелефон           : {PHONE}\r\nКонтактное лицо   : {NAME}\r\nE-mail            : {EMAIL}\r\nФормат            : {TYPE}\r\nДругой формат     : {FORMAT}\r\nТираж             : {AMOUNT}\r\n\r\nКомментарии:\r\n{TEXT}\r\n\r\nС уважением, www.{HOST}.\r\n');

/*Table structure for table `dwUsers` */

CREATE TABLE `dwUsers` (
  `ID_User` int(10) unsigned NOT NULL auto_increment,
  `ID_Group` int(10) unsigned default NULL,
  `UserDeleted` tinyint(1) NOT NULL default '0',
  `UserLogin` varchar(100) default NULL,
  `UserPwd` varchar(100) default NULL,
  `UserComment` varchar(255) default NULL,
  PRIMARY KEY  (`ID_User`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `dwUsers` */

insert  into `dwUsers`(`ID_User`,`ID_Group`,`UserDeleted`,`UserLogin`,`UserPwd`,`UserComment`) values (1,1,0,'admin','a',NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;