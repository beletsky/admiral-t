<input type=hidden name="{PREFIX}a" value="{ACTION}">
<input type=hidden name="{PREFIX}id" value="{ID}">
<input type=hidden name="{PREFIX}form[ID]" value="{ID}">
<input type=hidden name="{PREFIX}form[Gender]" value="Q">
<input type=hidden name="{PREFIX}form[Avatar]" value="{AVATAR}">
<input type=hidden name="{PREFIX}form[InsertStamp]" value="{INSERTSTAMP}">
<input type=hidden name="{PREFIX}form[Activated]" value="{ACTIVATED}">
<input type=hidden name="{PREFIX}form[Activated_CheckBox]" value="{ACTIVATED}">
<input type=hidden name="{PREFIX}form[ActivateMailStamp]" value="{ACTIVATEMAILSTAMP}">
<input type=hidden name="{PREFIX}form[ActivatedStamp]" value="{ACTIVATEDSTAMP}">
<table border=0>
    <tr>
        <td>Запрещен:</td>
        <td><input name="{PREFIX}form[Obsolete_CheckBox]" type="checkbox" {OBSOLETE_CHECKBOX}></td>
    </tr>
    <tr>
        <td>Полное имя:</td>
        <td><input name="{PREFIX}form[Name]" value="{NAME}" class="inp" style="width: 600px;"></td>
    </tr>
    <tr>
        <td>Ник:</td>
        <td><input name="{PREFIX}form[Nick]" value="{NICK}" class="inp" style="width: 600px;"></td>
    </tr>
    <tr>
        <td>Логин:</td>
        <td><input name="{PREFIX}form[Login]" value="{LOGIN}" class="inp" style="width: 200px;"></td>
    </tr>
    <tr>
        <td>Пароль:</td>
        <td><input name="{PREFIX}form[Password]" value="{PASSWORD}" class="inp" style="width: 200px;"></td>
    </tr>
    <tr>
        <td>Адрес e-mail:</td>
        <td><input name="{PREFIX}form[Email]" value="{EMAIL}" class="inp" style="width: 200px;"></td>
    </tr>
    <tr>
        <td valign=top>День рождения:</td>
        <td>
            <input type="hidden" id="birthday_input" name="{PREFIX}form[Birthday]" value="{BIRTHDAY}">
            <span id="birthday_show">{BIRTHDAY_DATE} {BIRTHDAY_TIME}</span>
            <img src="{PATH_TO_ADMIN}/img/calendar.gif" id="birthday_trigger" style="cursor: pointer; border: 1px solid red; vertical-align: bottom;" title="Выбор даты" onmouseover="this.style.background='red';" onmouseout="this.style.background=''" />
            <script type="text/javascript">
                Calendar.setup({
                    inputField     :    "birthday_input",    // id of the input field
                    ifFormat       :    "%Y-%m-%d 00:00:00", // format of the input field (even if hidden, this format will be honored)
                    displayArea    :    "birthday_show",     // ID of the span where the date is to be shown
                    daFormat       :    "%d.%m.%Y",          // format of the displayed date
                    button         :    "birthday_trigger",  // trigger button (well, IMG in our case)
                    align          :    "Tr",                // alignment (defaults to "Bl")
                    singleClick    :    true
                });
            </script>
        </td>
    </tr>
<!--
    <tr>
        <td>Пол:</td>
        <td>
            <input type="radio" name="{PREFIX}form[Gender]" value="M" class="inp" {M_GENDER}>М
            <input type="radio" name="{PREFIX}form[Gender]" value="F" class="inp" {F_GENDER}>Ж
            <input type="radio" name="{PREFIX}form[Gender]" value="Q" class="inp" {Q_GENDER}>?
        </td>
    </tr>
-->
    <tr>
        <td>Аватар:</td>
        <td>
            <input type="file" name="Avatar">
<!-- BEGIN avatar -->
            <a href="/{AVATAR_URL}" target="_blank">{AVATAR}</a>
            &nbsp;
            <a href="{PARENT}&a=del_image_proc&id={ID}&{PREFIX}del_image_name={AVATAR}"
                onclick="return confirm('Удалить аватар \'{AVATAR}\'?' );">
                <img src="/admin/img/del.gif" alt="Удалить аватар" title="Удалить аватар" border="0">
            </a>
<!-- END avatar -->
        </td>
    </tr>
    <tr>
        <td valign="top">Краткие сведения:</td>
        <td>{ABOUT_EDIT}</td>
    </tr>
    <tr>
        <td></td>
        <td><hr /></td>
    </tr>
    <tr>
        <td>Входит в группы:</td>
        <td>
<!-- BEGIN groups_item -->
            <input type=checkbox name="{PREFIX}form[Groups][{GROUP_ID}]" {GROUP_SELECTED}>{GROUP_NAME}
            <br />
<!-- END groups_item -->
        </td>
    </tr>
    <tr>
        <td>Добавлен:</td>
        <td>{INSERTSTAMP_DATE} {INSERTSTAMP_TIME}</td>
    </tr>
    <tr>
        <td>Активация:</td>
        <td>
            {ACTIVATED_TEXT}
<!-- BEGIN not_activated -->
            <br />
            <input type="button" value="Отправить письмо" class="btn" onclick="this.form.elements['{PREFIX}a'].value='send_proc';this.form.submit();">
<!-- END not_activated -->
        </td>
    </tr>
    <tr>
        <td><input type="button" value="{BUTTON_CAPTION}" class="btn" onclick="this.form.elements['{PREFIX}a'].value='{ACTION}_proc';this.form.submit();"></td>
        <td>&nbsp;</td>
    </tr>
</table>
