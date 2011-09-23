<div id = 'block_scantron_delete_confirm'>
<form action = 'delete.php?id={$courseid}&examid={$examid}' method = 'post'>
    {$confirm_str}
    <br />
    <br />
    <input type = 'hidden' name = 'delete_confirm' value = 'true'/>
    <input type = 'submit' value = '{"delete"|s}'/>
</form>
</div>
