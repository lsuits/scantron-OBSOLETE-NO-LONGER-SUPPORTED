{if $exam_options}
    <div id = 'block_scantron_select_row'>
        <form action = 'view.php?id={$courseid}' method = 'post'>
            {"exam"|s}: {$exam_select}

    {if $is_teacher}
            {"student"|s}: {$student_select}
    {/if}

            <input type = 'submit' value = '{"view"|s}'/>
        </form>
    </div>
    <br />
{else}
    <div id = 'block_scantron_view_error'>
        {"no_exams"|s}
    </div>
{/if}

{if $error_str}
    {$error_str}
{else}
    {$form_facsimile}
    <div class='scantron_key'>
        <ul>
            <p class='scinvisitext'>Key</p>
            <li>
                <span class='scinvisitext'>Blank Cell: </span>
                Unmarked Answer
            </li>
            <li class='correct_response'>
                <span class='scinvisitext'>
                    Letter: </span>Correct Student Response
            </li>
            <li class='correct_answer'>
                <span class='scinvisitext'>
                Exclamation Mark: </span>Correct Answer
            </li>
            <li class='incorrect_response'>
                <span class='scinvisitext'>Strikethrough: </span>
                Incorrect Student Response
            </li>
        </ul>
    </div>
{/if}
