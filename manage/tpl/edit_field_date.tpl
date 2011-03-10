<tr>
    <td valign="top">{NAME}:</td>
    <td>
        <input type="hidden" id="{PREFIX}{FIELD}_input" name="{PREFIX}form[{FIELD}]" value="{VALUE}">
        <span id="{PREFIX}{FIELD}_show">{DATE}</span>
        <img src="{PATH_TO_ADMIN}/img/calendar.gif" id="{PREFIX}{FIELD}_trigger" style="cursor: pointer; border: 1px solid red; vertical-align: bottom;" title="Выбор даты" onmouseover="this.style.background='red';" onmouseout="this.style.background=''" />
        <script type="text/javascript">
            Calendar.setup({
                inputField     :    "{PREFIX}{FIELD}_input",   // id of the input field
                ifFormat       :    "%Y-%m-%d 00:00:00",       // format of the input field (even if hidden, this format will be honored)
                displayArea    :    "{PREFIX}{FIELD}_show",    // ID of the span where the date is to be shown
                daFormat       :    "%d.%m.%Y",                // format of the displayed date
                button         :    "{PREFIX}{FIELD}_trigger", // trigger button (well, IMG in our case)
                align          :    "Tr",                      // alignment (defaults to "Bl")
                singleClick    :    true
            });
        </script>
    </td>
</tr>
