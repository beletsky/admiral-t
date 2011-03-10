<h1>{ITEM_NAME}</h1>
<div class="post format">
<!-- BEGIN item_image_ -->
    <a href="{ITEM_IMAGE_FULL_SIZE_URL}" title="{ITEM_IMAGE_ALT}"><img src="{ITEM_IMAGE_URL}" alt="{ITEM_IMAGE_ALT}" class="pic" width="296" /></a>
<!-- END item_image_ -->
    <div class="info">
<!-- BEGIN item_region_ -->
        <b>Местоположение</b>: {ITEM_REGION}<br /><br />
<!-- END item_region_ -->
<!-- BEGIN item_address_ -->
        Точный адрес: {ITEM_ADDRESS}<br /><br />
<!-- END item_address_ -->
        Дата: {ITEM_INSERTSTAMP_DATESTR}&nbsp;г.<br /><br />
<!-- BEGIN item_cost_ -->
        Цена: {ITEM_COST}<br /><br />
<!-- END item_cost_ -->
    </div>
    <div class="clear"><!----></div>    
<!-- BEGIN item_announce_ -->
    <div class="bgcss">
        {ITEM_ANNOUNCE}
    </div>
<!-- END item_announce_ -->
    {ITEM_TEXT}
</div>
