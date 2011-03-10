<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{TITLE}</title>
    <meta name="description" content="{PAGE_DESCRIPTION}" />
    <meta name="keywords" content="{PAGE_KEYWORDS}" />
    <link rel="icon" href="{__ROOT}favicon.ico" />
    <link rel="stylesheet" type="text/css" href="{__ROOT}css/css.css" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <script type="text/javascript" src="{__ROOT}js/jquery-1.4.2.min.js"></script>
    <script type="text/javascript" src="{__ROOT}js/script-tony.js"></script>
    <script type="text/javascript" src="{__ROOT}js/block_eq.js"></script>
    <!--[if lte IE 7]>
        <link rel="stylesheet" type="text/css" href="{__ROOT}css/ie.css" />
    <![endif]-->        
    <!--[if lte IE 6]>
        <script src="{__ROOT}js/DD_belatedPNG.js"></script>
        <script src="{__ROOT}js/DD_belatedPNG-img.js"></script>
    <![endif]-->
</head>
<body>
<div id="wrap"> 
</div>

<!-- header -->
<div id="header">
    <div class="wraper">
        <noindex>
            <a href="{__ROOT}" title="{SET_HEADERTITLE}" rel="nofollow"><img src="{__ROOT}images/admiral-logo.jpg" alt="{SET_HEADERTITLE}" id="logo" /></a>
            <ul id="h-icons">
                <li><a href="{__ROOT}" title="На&nbsp;главную" rel="nofollow"><img src="{__ROOT}images/ico-home.png" alt="На&nbsp;главную" /></a></li>
                <li><a href="{__ROOT}{SET_HEADERPAGE1_PAGECODE}{__EXT}" rel="nofollow"><img src="{__ROOT}images/ico-about.png" alt="{SET_HEADERPAGE1_NAME}" /></a></li>
                <li><a href="{__ROOT}{SET_HEADERPAGE2_PAGECODE}{__EXT}" rel="nofollow"><img src="{__ROOT}images/ico-phone.png" alt="{SET_HEADERPAGE2_NAME}" /></a></li>
                <li><a href="{__ROOT}{SET_HEADERPAGE3_PAGECODE}{__EXT}" rel="nofollow"><img src="{__ROOT}images/ico-taganyork.png" alt="{SET_HEADERPAGE3_NAME}" /></a></li>
            </ul>
        </noindex>
        <div id="h-slogan">{SET_HEADERSLOGAN_WITHOUTHTMLSPECIALCHARS}</div>
        {MENU_TOP}
    </div>
</div>
<!-- /header -->

<div class="wraper">
<!-- content -->
<div id="content">
    {PATH}
    {BODY}
</div>
<!-- /content -->

<!-- sidebar -->
<div id="sidebar-bg">
<div id="sidebar-bg2">
<div id="sidebar">
    {MENU_LEFT}
    <img src="{__ROOT}images/sxema-proezda.jpg" alt="Схема проезда" />
</div>
</div>
</div>
<!-- /sidebar -->

<div class="clear"></div>
</div>

<!-- footer -->
<div id="footer">
    <div class="wraper">
        <div id="f-col1">{SET_FOOTERLEFT}</div>
        <div id="f-col2">{SET_FOOTERCENTER}</div>
        <div id="f-col3">{SET_FOOTERCOUNTER_WITHOUTHTMLSPECIALCHARS}</div>
    </div>
</div>
<!-- /footer -->

</body>
</html>
