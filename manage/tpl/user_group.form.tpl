<input type=hidden name="{PREFIX}a" value="{ACTION}">
<input type=hidden name="{PREFIX}id" value="{ID}">
<input type=hidden name="{PREFIX}form[ID]" value="{ID}">
<table border=0>
    <tr>
        <td>Название группы:</td>
        <td><input name="{PREFIX}form[Name]" value="{NAME}" class="inp" style="width : 600px"></td>
    </tr>
    <tr>
        <td>Пользователи:</td>
        <td>
<!-- BEGIN users_item_ -->
            {USER_NAME}, 
<!-- END users_item_ -->
        </td>
    </tr>
    <tr>
        <td><input type="button" value="{BUTTON_CAPTION}" class="btn" onclick="this.form.elements['{PREFIX}a'].value='{ACTION}_proc';this.form.submit();"></td>
        <td>&nbsp;</td>
    </tr>
</table>
