<tr>
    <td>{NAME}:</td>
    <td>
        <input type="hidden" name="{PREFIX}form[{FIELD}]" value="{VALUE}">
        <table cellspacing="3" cellpadding="0">
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>
                    <input type="checkbox" name="{PREFIX}form[{FIELD}_Resize]" checked value="1" onclick="getElementById( '{PREFIX}file_{FIELD}_Small' ).disabled = this.checked;">
                    Масштабировать изображения до ширины:
                </td>
            </tr>
            <tr>
                <td>малое:</td>
                <td><input id="{PREFIX}file_{FIELD}_Small" type="file" name="{FIELD}_Small" disabled></td>
                <td><input type="edit" name="{PREFIX}form[{FIELD}_SmallWidth]" value="{SMALLWIDTH}" style="width: 50px;">&nbsp;точек (0&nbsp;&mdash; не менять размер)</td>
            </tr>
            <tr>
                <td>большое:</td>
                <td><input type="file" name="{FIELD}"></td>
                <td><input type="edit" name="{PREFIX}form[{FIELD}_LargeWidth]" value="{LARGEWIDTH}" style="width: 50px;">&nbsp;точек (0&nbsp;&mdash; не менять размер)</td>
            </tr>
        </table>
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
