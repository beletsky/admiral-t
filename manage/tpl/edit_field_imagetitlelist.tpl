<tr>
    <td>{NAME}:</td>
    <td>
        <input type="hidden" name="{PREFIX}form[{FIELD}]" value="{VALUE}">
        <table>
<!-- BEGIN image_item_ -->
            <tr>
                <td>{INDEX_1})</td>
                <td><a href="{URL}" target="_blank">{FILENAME}</a></td>
                <td>(<a href="{FULL_SIZE_URL}" target="_blank">полный размер</a>)</td>
                <td align="center">
                    <a href="{PARENT}&{PREFIX}a=delete_file_proc&{PREFIX}id={ID}&{PREFIX}delete_field={FIELD}&{PREFIX}delete_name={FILENAME}"
                        onclick="return confirm('Удалить изображение \'{FILENAME}\'?' );">
                        <img src="{PATH_TO_ADMIN}img/del.gif" alt="Удалить изображение" title="Удалить изображение" border="0">
                    </a>
                </td>
                <td>
                    Описание: 
                    <input type="text" name="{PREFIX}form[{FIELD}_Notes][{INDEX}]" value="{TITLE}" style="width: 300px;" />
                </td>
           </tr>
<!-- END image_item_ -->
        </table>
        Добавить:<br />
        <table border="0">
<!-- BEGIN image_add_item_ -->
            <tr>
                <td>{INDEX_1})</td>
                <td><input type="file" name="{FIELD}[{INDEX}]"></td>
                <td>
                    Описание: 
                    <input type="text" name="{PREFIX}form[{FIELD}_Notes][{INDEX}]" value="" style="width: 300px;" />
                </td>
            </tr>
<!-- END image_add_item_ -->
        </table>
    </td>
</tr>
