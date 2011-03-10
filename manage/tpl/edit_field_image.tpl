<tr>
    <td>{NAME}:</td>
    <td>
        <input type="hidden" name="{PREFIX}form[{FIELD}]" value="{VALUE}">
        <input type="file" name="{FIELD}">
<!-- BEGIN image_ -->
        <a href="{URL}" target="_blank">{FILENAME}</a>
        &nbsp;
        (<a href="{FULL_SIZE_URL}" target="_blank">полный размер</a>)
        &nbsp;
        <a href="{PARENT}&{PREFIX}a=delete_file_proc&{PREFIX}id={ID}&{PREFIX}delete_field={FIELD}&{PREFIX}delete_name={FILENAME}"
            onclick="return confirm('Удалить изображение \'{FILENAME}\'?' );">
            <img src="{PATH_TO_ADMIN}img/del.gif" alt="Удалить изображение" title="Удалить изображение" border="0">
        </a>
<!-- END image_ -->
    </td>
</tr>
