{if $upload_success}
    <br />
    <div id = 'block_scantron_upload_success'>
        {$success_str}
    </div>
{/if}

{if $items}
    <br />
    <div id = 'block_scantron_upload'>
    <form action = 'manage.php?id={$courseid}' enctype = 'multipart/form-data' method = 'post'>
        <input type = 'hidden' name = 'MAX_FILE_SIZE' value = '1000000'/>
        {"key_file"|s}: <input name = 'key_file' type = 'file'/>
        {"student_file"|s}: <input name = 'students_file' type = 'file'/>
        {"grade_item"|s}: {$item_select}
        <input type = 'submit' value = '{"upload"|s}'/>
    </form>
    </div>
{else}
    <div id = 'block_scantron_upload_error'>{"no_items"|s}</div>
{/if}

{if $exams}
    <br />
    <h2 class = 'main'>{"files"|s}</h2>
    {$files_table_src}
{else}
    <br />
    <div id = 'block_scantron_files_error'>{"no_files"|s}</div>
{/if}
