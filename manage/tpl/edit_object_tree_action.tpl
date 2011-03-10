<!-- BEGIN add_ -->
Родительский элемент:&nbsp;
<select class="inp" name="{PREFIX}form[__Parent]">{PARENT_OPTIONS}</select> 
<!-- END add_ -->
<!-- BEGIN edit_ -->
<table cellspacing="2" cellpadding="0">
<tr>
    <td>
        Изменить родительский элемент на:&nbsp;
    </td>
    <td>
        <select class="inp" name="{PREFIX}form[__Parent]">{PARENT_OPTIONS}</select>
    </td>
    <td>
        <input type="button" value="OK" class="btn" onclick="if( confirm('Вы уверены?') ) { this.form.elements['{PREFIX}a'].value='parent_proc';this.form.submit(); }">
    </td>
</tr>
<tr>
    <td>
        Поместить после:&nbsp;
    </td>
    <td>
        <select class="inp" name="{PREFIX}form[__After]">{AFTER_OPTIONS}</select>
    </td>
    <td>
        <input type="button" value="OK" class="btn" onclick="if( confirm('Вы уверены?') ) { this.form.elements['{PREFIX}a'].value='move_proc';this.form.submit(); }">
    </td>
</tr>
</table>
<!-- END edit_ -->
