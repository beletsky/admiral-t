<tr>
    <td>{NAME}:</td>
    <td>
        {EDITOR}
        <script type="text/javascript" src="{PATH_TO_ADMIN}/finder/ajex.js"></script>
        <script type="text/javascript">
            AjexFileManager.init({
                returnTo: 'ckeditor',
                path: '{PATH_TO_ADMIN}/finder/',
                editor: editor{FIELD},
                skin: 'light',
            });
            </script>
    </td>
</tr>
