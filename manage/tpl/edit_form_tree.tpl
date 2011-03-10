<input type=hidden name="{PREFIX}a" value="{ACTION}">
<input type=hidden name="{PREFIX}a_after" value="">
<input type=hidden name="{PREFIX}id" value="{ID}">
<table border="0">
    {FIELDS}
    <tr>
        <td><input type="button" value="{BUTTON_CAPTION}" class="btn" onclick="this.form.elements['{PREFIX}a'].value='{ACTION}_proc';this.form.submit();"></td>
    </tr>
</table>
