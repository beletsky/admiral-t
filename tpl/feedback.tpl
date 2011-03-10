<h1>{PAGE_NAME}</h1>
<!-- BEGIN page_image_ -->
<img src="{PAGE_IMAGE_URL}" alt="{PAGE_IMAGE_ALT}" align="right" />
<!-- END page_image_ -->
{PAGE_ANNOUNCE}
{PAGE_TEXT}
<div class="space_20 border_grey margT_20">&nbsp;</div>
<div class="box_1_1 margR_80 margL_10">
    <div class="top">&nbsp;</div><div class="bottom">&nbsp;</div><div class="bottom_1">&nbsp;</div>
    <div class="head_block_1"><span>Задайте свой вопрос специалисту</span></div>
    <div class="quest_form">
<!-- BEGIN form_ -->
        <form action="{FORM_URL}{__EXT}" method="post">
            <input type="hidden" name="form[Form]" value="form_">
            <input type="hidden" name="MAX_FILE_SIZE" value="1048576" />
            <ul>
                <li id="errors">{FORM_ERRORS}</li>
                <li>
                    <label>Ваше имя</label>
                    <input type="text" name="form[Name]" value="{FORM_NAME}" />
                </li>
                <li>
                    <label>Ваш e-mail</label>
                    <input type="text" name="form[Email]" value="{FORM_EMAIL}" />
                </li>
                <li>
                    <label>Ваш вопрос</label>
                    <textarea name="form[Text]">{FORM_TEXT}</textarea>
                </li>
                <li class="last">
                    <input type="submit" class="button_send" value="" />
                </li>
            </ul>
        </form>
<!-- END form_ -->
    </div>
</div>
