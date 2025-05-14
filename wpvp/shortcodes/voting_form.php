<?php
function voting_form_shortcode()
{

    get_header();

    $id = get_query_var('id', 'default_value');
    // echo $id;

    if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))) {
        wp_die('You do not have permission to access this page.');
    }
    // $vote_id = isset($_GET['id']) ? intval($_GET['id']) : null;

    $vote_id = $id ? intval($id) : null;


    // if ($id) {
    //     if ($id === 0) {
    //         $vote_id = null;  // If $id is exactly 0, set $vote_id to null
    //     } else {
    //         $vote_id = intval($id);  // Convert $id to an integer if it's not 0
    //     }
    // }

    // echo $vote_id;  // Output the final vote_id

    // echo 'id Var: ' . esc_html( $vote_id );


    // Retrieve vote data based on the ID
    $vote_data = $vote_id !== null ? get_vote_data_by_id($vote_id) : null;

    // If vote data is found, populate form fields for editing
    if ($vote_data) {
        $proposal_name = $vote_data['proposal_name'];
        $content = $vote_data['content'];
        $remark = $vote_data['remark'];

        $voting_choice = $vote_data['voting_choice'];
        $files_name = unserialize($vote_data['files_name']);
        $voting_options = unserialize($vote_data['voting_options']);
        $vote_type = $vote_data['vote_type'];
        $voting_stage = $vote_data['voting_stage'];
        $proposed_by = $vote_data['proposed_by'];
        $seconded_by = $vote_data['seconded_by'];
        $create_date = $vote_data['create_date'];
        $opening_date = $vote_data['opening_date'];
        $closing_date = $vote_data['closing_date'];
        $visibility = $vote_data['visibility'];
        $maximum_choices = $vote_data['maximum_choices'];
        $number_of_winner = $vote_data['number_of_winner'];
        $withdrawn_time = $vote_data['withdrawn_time'];
        $active_status = $vote_data['active_status'];
        $blindVoting = $vote_data['blindVoting'];

        // Encode
        $voting_options_json = json_encode($voting_options);
        $files_name_json = json_encode($files_name);
    } else {
        $files_name_json = json_encode([]);
        $voting_options_json = json_encode([]);
    }


    $all_users = [];

    if (is_multisite()) {
        // Multisite: Collect all users from all sites
        $sites = get_sites();

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);


            $author_users = get_users([
                'role'   => 'armember',
                'fields' => ['user_email', 'display_name']
            ]);

            // Merge users
            $all_users = array_merge($all_users, $author_users);

            restore_current_blog();
        }
    } else {
        // Single site: Collect admins and authors

        $author_users = get_users([
            'role'   => 'armember',
            'fields' => ['user_email', 'display_name']
        ]);

        // Merge users
        $all_users = array_merge($author_users);
    }

    $users_json = json_encode($all_users);

    $current_user = wp_get_current_user();
    //print_r($current_user->roles);
    if ($vote_id) {
        if (in_array('author', $current_user->roles) && $vote_data['voting_stage'] === 'completed') {
            wp_die('Not Authorized to Edit: Voting stage is complete.');
        }



        if (in_array('author', $current_user->roles) && (int)$vote_data['created_by'] !== (int)$current_user->ID) {
            wp_die('Not Authorized to Edit.');
        }
    }
    // Display the voting form HTML here
    ob_start();
?>
    <section class="owbnVP bg--black">
        <div class="custtom--contaner">
            <h2 class="heading--sec"> <?php echo $vote_id ? 'Edit Vote' : 'Creating Vote'; ?></h2>

            <div class="databox--sec">

                <!-- proposal name -->
                <div class="proposal--name mb--30">
                    <h5>Proposal Name <Sup class="req">*</Sup></h5>
                    <input type="text" id="proposal_name" name="proposal_name" value="<?php echo $vote_id ? esc_attr($proposal_name) : ''; ?>" required>
                    <!-- <input type="hidden" name="action" value="submit_voting_box" id="actionid"/> -->

                </div>
                <!-- body -->
                <h5>Proposal Description</h5>
                <textarea id="custom-editor" name="content"><?php echo $vote_id ? esc_textarea($content) : ''; ?></textarea>

                <!-- voting options -->
                <div class="proposal--name mb--30">

                    <!-- voting choice -->
                    <div class="proposal--name mb--30">
                        <h5>Result type : <Sup class="req">*</Sup></h5>
                        <div class="cholce-box" id="result-type">

                            <!-- <div class="vote--radio--box">
                            <input type="radio" class="radiobtn" id="multipleChoice" name="votingChoice"
                                value="multiple"
                                <?php echo ($vote_id && $voting_choice === 'multiple' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>

                            <label for="multipleChoice">Multiple </label>
                        </div> -->
                            <div class="vote--radio--box">
                                <input type="radio" class="radiobtn" onclick="setballotoption('single')" id="singleChoice" name="votingChoice" value="single" <?php echo ($vote_id ? $voting_choice === 'single' ? 'checked' : '' : 'checked'); ?>>
                                <label for="singleChoice">Single</label>
                            </div>
                            <div class="vote--radio--box">
                                <input type="radio" class="radiobtn" id="irv"
                                    onclick="setballotoption('irv')"
                                    name="votingChoice" value="irv" <?php echo ($vote_id && $voting_choice === 'irv' ? 'checked' : ''); ?>>
                                <label for="irv">IRV</label>
                            </div>
                            <div class="vote--radio--box">
                                <input type="radio" class="radiobtn" id="stv"
                                    onclick="setballotoption('stv')"
                                    name="votingChoice" value="stv" <?php echo ($vote_id && $voting_choice === 'stv' ? 'checked' : ''); ?>>
                                <label for="stv">STV</label>
                            </div>
                            <div class="vote--radio--box">
                                <input type="radio" class="radiobtn" id="condorcet"
                                    onclick="setballotoption('condorcet')"
                                    name="votingChoice" value="condorcet" <?php echo ($vote_id && $voting_choice === 'condorcet' ? 'checked' : ''); ?>>
                                <label for="condorcet">Condorcet</label>
                            </div>
                            <div class="vote--radio--box">
                                <input type="radio" class="radiobtn" id="punishment"
                                    onclick="setballotoption('punishment')"

                                    name="votingChoice" value="punishment" <?php echo ($vote_id && $voting_choice === 'punishment' ? 'checked' : ''); ?>>
                                <label for="Punishment">Punishment</label>
                            </div>
                        </div>
                        <div class="number_of_winner" style="<?php echo ($vote_id && $voting_choice === 'stv') ? '' : 'display: none;'; ?>">
                            <label for="number_of_winner">Number Of Winners </label>

                            <input type="number" id="number_of_winner" name="number_of_winner" min=1 value="<?php echo $vote_id ? esc_attr($number_of_winner) : 1; ?>">
                        </div>
                    </div>

                    <div class="voting-choice-box">
                        <h6 class="voting--txt">Ballot Options <Sup class="req">*</Sup></h6>
                        <p style="font-size: 12px;"> <span style="color: red;">*</span>Leave a line blank to remove it upon saving.</p>
                        <p style="display: none;" id="votechoiceid"> <?php echo $voting_choice ?></p>
                        <div class="cholce-box">
                            <div id="anoteritembox">
                                <script>
                                    var vote_choice = document.getElementById('votechoiceid').textContent;
                                    var additionalBallotOptions = vote_choice !== "punishment" ? json_encode($voting_options) : '[]';
                                    var votingChoice = vote_choice;
                                </script>


                                <?php
                                if ($vote_id) {
                                    if (!empty($voting_options)) {
                                        if ($voting_choice === "punishment") {
                                            echo ' <div id="ballot-option-punishment">';
                                            foreach ($voting_options as $index => $option) {
                                                $input_id = 'input_' . ($index + 1); // Generate unique input ID
                                                $readonly = "";
                                                if (!empty(json_decode($vote_data['vote_box'], true))) {
                                                    $readonly = 'readonly';
                                                }
                                                // Output HTML for each option
                                                echo '<div class="cholce-box-inpt">';
                                                echo '<input type="text" id="' . $option['id'] . '" value="' . $option['text'] . '"
                                onblur="addingToOptions(this.id, this.value)" ' . $readonly . '>';
                                                echo '</div>';
                                            }
                                            echo '</div>';
                                        } else {

                                            echo ' <div id="ballot-option-not-punishment">';
                                            foreach ($voting_options as $index => $option) {
                                                $input_id = 'input_' . ($index + 1); // Generate unique input ID
                                                $readonly = "";
                                                $hideButton = false;
                                                if (!empty(json_decode($vote_data['vote_box'], true))) {
                                                    $readonly = 'readonly';
                                                    $hideButton = true;

                                                    $style = $hideButton ? 'style="display: none;"' : '';
                                                }
                                                // Output HTML for each option
                                                echo '<div class="cholce-box-inpt">';
                                                echo '<input type="text" id="' . $option['id'] . '" value="' . $option['text'] . '"
                                onblur="addingToOptions(this.id, this.value)" ' . $readonly . '>';
                                                echo '<button class="delete-btn" ' . $style . '>−</button>';
                                                echo '</div>';
                                            }
                                            echo '</div>';
                                        }
                                    }
                                } else {
                                ?>

                                    <div id="ballot-option-not-punishment">
                                        <div class="cholce-box-inpt">
                                            <input type="text" id='input_1' value="Abstain" onblur="addingToOptions(this.id, this.value)">
                                            <button class="delete-btn">−</button>
                                        </div>
                                        <div class="cholce-box-inpt">
                                            <input type="text" id='input_2' value="Reject All" onblur="addingToOptions(this.id, this.value)">
                                            <button class="delete-btn">−</button>
                                        </div>
                                    </div>


                                <?php
                                }

                                ?>

                            </div>
                            <?php if (isset($vote_data['vote_box'])) {
                                if (empty(json_decode($vote_data['vote_box'], true))) {
                            ?>
                                    <?php
                                    if ($voting_choice !== "punishment") {
                                    ?>
                                        <button class="cust--btn" id="addanotheritem" onclick="addingVotingOption()">
                                            Add Another Option
                                        </button>

                                    <?php
                                    }
                                    ?>
                                <?php }
                            } else { ?>
                                <?php
                                if ($voting_choice !== "punishment") {
                                ?>
                                    <button class="cust--btn" id="addanotheritem" onclick="addingVotingOption()">
                                        Add Another Option
                                    </button>


                                <?php
                                }
                                ?>
                            <?php }
                            ?>
                        </div>

                    </div>
                </div>


                <!-- files -->
                <div class="proposal--name file--document">
                    <h5>File / Document</h5>
                    <p>Optional. This is for instances where Council is voting on a chronicle admission or packet.</p>

                    <div class="table--box">
                        <table class="file--information--table" id="fileInfoTable">
                            <thead>
                                <tr>
                                    <th>File Information</th>
                                    <th>Display</th>
                                    <th>Operations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($vote_id) {
                                    if (!empty($files_name)) {
                                        foreach ($files_name as $option) {
                                            //print_r($option);
                                ?>
                                            <tr>
                                                <td>
                                                    <div class="file--name">
                                                        <p><a target="_blank" href="<?php if (isset($option['id'])) {
                                                                                        echo wp_get_attachment_url($option['id']);
                                                                                    } ?>"><?php echo esc_html($option['fileName']); ?><span>(<?php echo esc_html($option['fileSize']); ?>
                                                                    bytes)</span></a></p>
                                                        <div>
                                                            <h3>Description</h3>
                                                        </div>
                                                        <div><input type='text' data-file-id="<?php if (isset($option['id'])) {
                                                                                                    echo esc_html($option['id']);
                                                                                                } ?>" value="<?php echo esc_html($option['description']); ?>" onblur="updateDescription(this)"></div>
                                                        <div>
                                                            <p>The description may be used as the label of the
                                                                link to the file</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><input type="checkbox" data-file-id="<?php if (isset($option['id'])) {
                                                                                                echo esc_html($option['id']);
                                                                                            } ?>" onchange="updateDisplay(this)" <?php echo $option['display'] ? 'checked' : ''; ?>></td>
                                                <td><button class="remove--btn" data-file-id="<?php if (isset($option['id'])) {
                                                                                                    echo esc_html($option['id']);
                                                                                                } ?>" onclick="removeFile(this)">Remove</button></td>
                                            </tr>

                                <?php }
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="add--new-file ">
                        <div>
                            <h3>Add A New File</h3>
                        </div>
                        <div class="add--file">
                            <input type="file" id="uploadFile">
                            <button onclick="uploadFile()">Upload</button>
                        </div>

                        <div>
                            <p><span>Files must be less than</span>: <strong>20 MB</strong></p>
                            <p><span>Allowed file types</span>: <strong>.txt, .rtf, .doc, and .pdf</strong></p>
                        </div>
                    </div>
                </div>

                <!-- vote type and voting stage -->
                <div class="proposal--name  mb--30">
                    <div class="two--box--wrap">
                        <!-- vote type -->
                        <div class="proposal--name mb--30">
                            <h5> Vote Type <Sup class="req">*</Sup></h5>
                            <div class="cholce-box">

                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="ChronicleAdmission" name="votetype" value="Chronicle Admission" <?php echo ($vote_id && $vote_type === 'Chronicle Admission' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>

                                    <label for="ChronicleAdmission">Chronicle Admission</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="BylawRevision" name="votetype" value="Bylaw Revision" <?php echo ($vote_id && $vote_type === 'Bylaw Revision' ? 'checked' : ''); ?>>
                                    <label for="BylawRevision">Bylaw Revision</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="CoordinatorElections" name="votetype" value="Coordinator Elections (Full Term)" <?php echo ($vote_id && $vote_type === 'Coordinator Elections (Full Term)' ? 'checked' : ''); ?>>
                                    <label for="CoordinatorElections">Coordinator Elections (Full Term)</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input class="radiobtn" type="radio" id="CoordinatorElectionsspl" name="votetype" value="Coordinator Elections (Special)" <?php echo ($vote_id && $vote_type === 'Coordinator Elections (Special)' ? 'checked' : ''); ?>>
                                    <label for="CoordinatorElectionsspl">Coordinator Elections (Special)</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input class="radiobtn" type="radio" id="Disciplinary" name="votetype" value="Disciplinary" <?php echo ($vote_id && $vote_type === 'Disciplinary' ? 'checked' : ''); ?>>
                                    <label for="Disciplinary">Disciplinary</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input class="radiobtn" type="radio" id="GenrePacket" name="votetype" value="Genre Packet" <?php echo ($vote_id && $vote_type === 'Genre Packet' ? 'checked' : ''); ?>>
                                    <label for="GenrePacket">Genre Packet</label>

                                </div>
                                <div class="vote--radio--box">
                                    <input class="radiobtn" type="radio" id="MetaPlot" name="votetype" value="Global/Meta Plot" <?php echo ($vote_id && $vote_type === 'Global/Meta Plot' ? 'checked' : ''); ?>>
                                    <label for="MetaPlot">Global/Meta Plot</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="OpinionPoll" name="votetype" value="Opinion Poll" <?php echo ($vote_id && $vote_type === 'Opinion Poll' ? 'checked' : ''); ?>>
                                    <label for="OpinionPoll">Opinion Poll</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="OtherPrivate" name="votetype" value="Other Private" <?php echo ($vote_id && $vote_type === 'Other Private' ? 'checked' : ''); ?>>
                                    <label for="OtherPrivate">Other Private</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="OtherPub" name="votetype" value="Other Public" <?php echo ($vote_id && $vote_type === 'Other Public' ? 'checked' : ''); ?>>
                                    <label for="OtherPub">Other Public</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="R&U" name="votetype" value="R&U Submission" <?php echo ($vote_id && $vote_type === 'R&U Submission' ? 'checked' : ''); ?>>
                                    <label for="R&U">R&U Submission</label>
                                </div>

                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="Territory" name="votetype" value="Territory" <?php echo ($vote_id && $vote_type === 'Territory' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>

                                    <label for="Territory">Territory</label>
                                </div>
                            </div>
                        </div>

                        <!-- voting stage -->
                        <div class="proposal--name">
                            <h5> Voting Stage<Sup class="req">*</Sup></h5>
                            <div class="cholce-box">
                                <div class="selact--box">

                                    <select id="votingStage" onchange="checkautopass()">
                                        <?php if (!$vote_id): ?>
                                            <!-- When vote_id is not available, show 'Draft', 'Normal', and 'Autopass' -->
                                            <option value="draft">Draft</option>
                                            <option value="normal">Normal</option>
                                            <option value="autopass">Autopass</option>
                                        <?php else: ?>
                                            <!-- When vote_id is available -->
                                            <?php if ($voting_stage === 'draft'): ?>
                                                <!-- Show only 'Draft' if the stage is 'Draft' -->
                                                <option value="draft" <?php echo ($voting_stage === 'draft' ? 'selected' : ''); ?>>Draft</option>
                                                <option value="normal" <?php echo ($voting_stage === 'normal' ? 'selected' : ''); ?>>Normal</option>
                                                <option value="autopass" <?php echo ($voting_stage === 'autopass' ? 'selected' : ''); ?>>Autopass</option>
                                            <?php elseif ($voting_stage === 'completed'): ?>
                                                <!-- Show all options for other stages -->
                                                <option value="completed" <?php echo ($voting_stage === 'completed' ? 'selected' : ''); ?>>Vote Completed</option>

                                                <option value="draft" <?php echo ($voting_stage === 'draft' ? 'selected' : ''); ?>>Draft</option>
                                            <?php elseif ($voting_stage === 'withdrawn'): ?>
                                                <!-- Show only 'Withdrawn' if the stage is 'Withdrawn' -->
                                                <option value="withdrawn" <?php echo ($voting_stage === 'withdrawn' ? 'selected' : ''); ?>>Withdrawn</option>
                                                <option value="normal" <?php echo ($voting_stage === 'normal' ? 'selected' : ''); ?>>Normal</option>
                                                <option value="autopass" <?php echo ($voting_stage === 'autopass' ? 'selected' : ''); ?>>Autopass</option>
                                            <?php elseif ($voting_stage === 'autopass'): ?>
                                                <!-- Show all options for other stages -->
                                                <option value="autopass" <?php echo ($voting_stage === 'autopass' ? 'selected' : ''); ?>>Autopass</option>
                                                <option value="draft" <?php echo ($voting_stage === 'draft' ? 'selected' : ''); ?>>Draft</option>
                                                <option value="normal" <?php echo ($voting_stage === 'normal' ? 'selected' : ''); ?>>Normal</option>

                                                <option value="completed" <?php echo ($voting_stage === 'completed' ? 'selected' : ''); ?>>Vote Completed</option>
                                                <option value="withdrawn" <?php echo ($voting_stage === 'withdrawn' ? 'selected' : ''); ?>>Withdrawn</option>

                                            <?php elseif ($voting_stage === 'normal'): ?>
                                                <!-- Show all options for other stages -->
                                                <option value="normal" <?php echo ($voting_stage === 'normal' ? 'selected' : ''); ?>>Normal</option>
                                                <option value="draft" <?php echo ($voting_stage === 'draft' ? 'selected' : ''); ?>>Draft</option>

                                                <!-- <option value="autopass" <?php echo ($voting_stage === 'autopass' ? 'selected' : ''); ?>>Autopass</option> -->
                                                <option value="completed" <?php echo ($voting_stage === 'completed' ? 'selected' : ''); ?>>Vote Completed</option>
                                                <option value="withdrawn" <?php echo ($voting_stage === 'withdrawn' ? 'selected' : ''); ?>>Withdrawn</option>



                                            <?php endif; ?>


                                        <?php endif; ?>
                                    </select>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- proposed and seconded by -->
                <div class="proposal--name  mb--30">
                    <div class="two--box--wrap">
                        <div class="proposal--name mb--30">
                            <h5>Proposed By</h5>
                            <div class="choice-box">
                                <div class="selact--box">
                                    <select id="proposedBy" name="proposedBy">
                                        <?php
                                        foreach ($all_users as $user) {
                                            $selected = ($proposed_by === $user->display_name) ? 'selected' : '';
                                            echo "<option value='{$user->display_name}' {$selected}>{$user->display_name}</option>";
                                        }
                                        ?>

                                    </select>
                                </div>
                            </div>






                            <div class="cholce-box">
                                <!-- <div class="selact--box">
                                    <input type="text" id="proposedBy" name="proposedBy" value="<?php echo $vote_id ? esc_attr($proposed_by) : ''; ?>">
                                </div> -->
                                <input type="text" hidden id="votingstage2" name="votingstage2" value="<?php echo ($vote_id && $voting_stage ? $voting_stage : ''); ?>">
                                <input type="text" hidden id="withdrawntime" name="withdrawntime" value="<?php echo ($vote_id && $withdrawn_time ? $withdrawn_time : ''); ?>">


                                <p>Chronicle that initiated this proposal</p>
                            </div>
                        </div>
                        <div class="proposal--name">
                            <h5>Seconded By</h5>
                            <div class="cholce-box">

                                <div class="selact--box">

                                    <select id="secondedBy" name="secondedBy">
                                        <?php
                                        echo "<option value='' {$selected}></option>";
                                        foreach ($all_users as $user) {
                                            $selected = ($seconded_by === $user->display_name) ? 'selected' : '';
                                            echo "<option value='{$user->display_name}' {$selected}>{$user->display_name}</option>";
                                        }
                                        ?>

                                    </select>
                                    <!-- <input type="text" id="secondedBy" name="secondedBy" value="<?php echo $vote_id ? esc_attr($seconded_by) : ''; ?>"> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- calender -->
                <div class="proposal--name  mb--50">
                    <div class="two--box--wrap">
                        <div class="proposal--name mb--50">
                            <h5>Create Date</h5>
                            <div class="cholce-box">
                                <Div class="date--picker--box">
                                    <div class="close--date">
                                        <!-- <?php echo $vote_id ? esc_attr($create_date) : ''; ?> -->
                                        <input type="date" onchange="updateDates()" id="createDate" name="createDate" value="<?php echo $vote_id ? esc_attr($create_date) : ''; ?>">
                                    </div>
                                </Div>
                            </div>
                        </div>
                        <div class="proposal--name mb--50" id="openingDatecard">
                            <h5>Opening Date</h5>
                            <div class="cholce-box">
                                <Div class="date--picker--box">
                                    <div class="close--date">
                                        <input type="date" onchange="updateDates1()" id="openingDate" name="openingDate" value="<?php echo $vote_id ? esc_attr($opening_date) : ''; ?>">
                                    </div>
                                </Div>
                            </div>
                        </div>
                        <div class="proposal--name mb--50" id="closingDateContainer" stylee="display: none;">
                            <h5>Closing Date</h5>
                            <div class="cholce-box">
                                <Div class="date--picker--box">
                                    <input type="date" id="closingDate" value="<?php echo $vote_id ? esc_attr($closing_date) : ''; ?>">
                                </Div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="proposal--name vote--setting">
                    <h5> Vote Settings </h5>
                    <div class="two--box--wrap">
                        <div class="voting-choice-box">
                            <h6 class="voting--txt">Visibility <Sup class="req">*</Sup> </h6>
                            <div class="cholce-box">
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="Councilonly" name="visibility" value="council" <?php echo ($vote_id && $visibility === 'council' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>
                                    <label for="Councilonly">Visible to Council only</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="CouncilPublic" name="visibility" value="both" <?php echo ($vote_id && $visibility === 'both' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>
                                    <label for="CouncilPublic">Visible to Council and Public</label>
                                </div>
                            </div>
                        </div>
                        <div class="voting-choice-box" style="display: none;">
                            <h6 class="voting--txt">Maximum Choices<Sup class="req">*</Sup> </h6>
                            <div class="cholce-box">
                                <div class="selact--box">
                                    <select id="maximumChoices">
                                        <!-- <option value="unlimited" <?php echo ($vote_id && $maximum_choices === 'unlimited' ? 'selected' : (!$vote_id ? 'selected' : '')); ?>>
                                            Unlimited</option> -->
                                        <option value="1" <?php echo ($vote_id && $maximum_choices === '1' ? 'selected' : ''); ?>>1
                                        </option>
                                        <option value="2" <?php echo ($vote_id && $maximum_choices === '2' ? 'selected' : ''); ?>>2
                                        </option>
                                        <option value="3" <?php echo ($vote_id && $maximum_choices === '3' ? 'selected' : ''); ?>>3
                                        </option>
                                        <option value="4" <?php echo ($vote_id && $maximum_choices === '4' ? 'selected' : ''); ?>>4
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="voting-choice-box">
                            <h6 class="voting--txt">Blind Voting Options <Sup class="req">*</Sup> </h6>
                            <div class="cholce-box">
                                <!-- <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="BlindVotingtrue" name="blindVoting" value="BlindVotingtrue" <?php echo ($vote_id && $blindVoting === 'BlindVotingtrue' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>
                                    <label for="BlindVotingtrue">Visibility of individual votes open during voting phase</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="BlindVotingfalse" name="blindVoting" value="BlindVotingfalse" <?php echo ($vote_id && $blindVoting === 'BlindVotingfalse' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>
                                    <label for="BlindVotingfalse">Visibility of individual votes restricted during voting phase</label>
                                </div> -->
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="BlindVotingtrue" name="blindVoting" value="BlindVotingtrue"
                                        <?php
                                        if ($vote_id && $blindVoting === 'BlindVotingtrue') {
                                            echo 'checked';
                                        } elseif (!$vote_id || empty($blindVoting)) {
                                            echo 'checked'; // Default check if blindVoting is empty
                                        } elseif ($vote_id || empty($blindVoting)) {
                                            echo 'checked'; // Default check if blindVoting is empty
                                        }
                                        ?>>
                                    <label for="BlindVotingtrue">Visibility of individual votes open during voting phase</label>
                                </div>

                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="BlindVotingfalse" name="blindVoting" value="BlindVotingfalse"
                                        <?php
                                        if ($vote_id && $blindVoting === 'BlindVotingfalse') {
                                            echo 'checked';
                                        }
                                        ?>>
                                    <label for="BlindVotingfalse">Visibility of individual votes restricted during voting phase</label>
                                </div>

                            </div>
                        </div>

                        <div class="voting-choice-box" hidden>
                            <h6 class="voting--txt">Active<Sup class="req">*</Sup></h6>
                            <div class="cholce-box">
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="periodcomplete" name="activeStatus" value="InactiveVote" <?php echo ($vote_id && $active_status === 'InactiveVote' ? 'checked' : ''); ?>>
                                    <label for="periodcomplete">Inactive (after voting period is complete)</label>
                                </div>
                                <div class="vote--radio--box">
                                    <input type="radio" class="radiobtn" id="votingperiod" name="activeStatus" value="activeVote" <?php echo ($vote_id && $active_status === 'activeVote' ? 'checked' : (!$vote_id ? 'checked' : '')); ?>>
                                    <label for="votingperiod">Active (either pre or during voting period)</label>
                                </div>
                            </div>
                        </div>




                    </div>
                </div>



                <div class="proposal--name mb--30" style="display: <?php echo $vote_id ? 'block' : 'none'; ?>;">
                    <h5>Result Remarks</h5><input type="text" id="add_comment" name="add_comment" value="<?php echo esc_attr($remark) ? esc_attr($remark) : ''; ?>">
                </div>

                <div class="save--btn-box mb--30 custom--btns">
                    <button class="cust--btn submitbtn votebtn" onclick="submitForm(<?php echo $vote_id; ?>)">Save</button>

                </div>
            </div>
    </section>


    <script type="text/javascript">
        // Embed JSON data into the HTML
        var phpFilesName = <?php echo $files_name_json; ?>;
        var phpVotingOptions = <?php echo $voting_options_json; ?>;


        var domContentLoadedExecuted = false;

        document.addEventListener("DOMContentLoaded", function() {
            const selectElement1 = document.getElementById('votingstage2');
            // alert(selectElement1.value);
            if (selectElement1.value === "autopass") {
                const opendate = document.getElementById('openingDatecard');
                opendate.style.display = 'none';
            }

            if (!domContentLoadedExecuted) {
                domContentLoadedExecuted = true;

                // Ensure phpFilesName and phpProposalName are defined and update global variables
                if (typeof phpFilesName !== "undefined") {
                    uploadedFiles = phpFilesName;
                }

                if (typeof phpVotingOptions !== "undefined") {
                    proposalName = phpVotingOptions;
                }

                // Get the table body element
                var tableBody = document.getElementById('fileInfoTable').getElementsByTagName('tbody')[0];


            }
        });
    </script>
    <script type="text/javascript">
        console.log("LOGGER ====> ", additionalBallotOptions, votingChoice);
        // for calendar date adjustment function
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById("ballot-option-not-punishment");

            // Delete button functionality
            container.addEventListener("click", function(event) {
                if (event.target.classList.contains("delete-btn")) {
                    event.target.parentElement.remove();
                }
            });


            // const value = document.getElementById('ballot-option-punishment')
            // value.style.display = "none";
            var votingStage = document.getElementById('votingStage');
            var periodCompleteRadio = document.getElementById('periodcomplete');
            var votingPeriodRadio = document.getElementById('votingperiod');

            function setActiveStatus() {
                if (votingStage.value === 'draft' || votingStage.value === 'completed' || votingStage.value ===
                    'withdrawn') {
                    periodCompleteRadio.checked = true;
                } else {
                    votingPeriodRadio.checked = true;
                }
            }

            // Set the initial active status based on the current votingStage value
            setActiveStatus();

            // Add event listener to change the active status when votingStage changes
            votingStage.addEventListener('change', setActiveStatus);

            //Yash Comment 25-09-2K24
            // var today = new Date();
            // var year = today.getFullYear();
            // var month = String(today.getMonth() + 1).padStart(2, '0');
            // var day = String(today.getDate()).padStart(2, '0');
            // var minDate = year + '-' + month + '-' + day;



            //Add 7 days to today's date
            // var today = new Date();
            // today.setDate(today.getDate() + 7);            
            // var year = today.getFullYear();
            // var month = String(today.getMonth() + 1).padStart(2, '0');
            // var day = String(today.getDate()).padStart(2, '0');
            // var minDate = year + '-' + month + '-' + day;
            // document.getElementById('openingDate').setAttribute('min', minDate);

            // var openingDateInput = document.getElementById('openingDate');
            // var closingDateInput = document.getElementById('closingDate');
            // var closingDateContainer = document.getElementById('closingDateContainer');

            // if (openingDateInput) {
            //     openingDateInput.addEventListener('change', function() {
            //         if (openingDateInput.value) {
            //             // Show the closing date input
            //             closingDateContainer.style.display = 'block';

            //             // Get the selected opening date
            //             var openingDate = new Date(openingDateInput.value);

            //             var selectedOpeningDate = new Date(openingDateInput.value);

            //             // Check if the selected opening date is prior to today's date
            //             if (selectedOpeningDate < today) {
            //                 // Reset the opening date to today's date
            //                 openingDateInput.value = minDate;
            //                 selectedOpeningDate = today;
            //             }

            //             // Calculate the minimum closing date (opening date + 7 days)
            //             var minimumClosingDate = new Date(selectedOpeningDate);
            //             minimumClosingDate.setDate(minimumClosingDate.getDate() + 7);

            //             // Format the minimum closing date as "YYYY-MM-DD" for input field value
            //             var minimumClosingDateFormatted = minimumClosingDate.toISOString().split('T')[0];

            //             // Set the min attribute of the closing date input to the opening date
            //             closingDateInput.setAttribute('min', minimumClosingDateFormatted);

            //             // Set the default closing date if the voting stage is 'withdrawn'
            //             var votingStageSelect = document.getElementById('votingStage');
            //             if (votingStageSelect.value === 'withdrawn') {
            //                 closingDateInput.value = minDate;
            //             }
            //         } else {
            //             // Hide the closing date input if no opening date is selected
            //             closingDateContainer.style.display = 'none';
            //         }
            //     });
            // }

            // // Prevent selection of prior and subsequent dates if the voting stage is 'withdrawn'
            // var votingStageSelect = document.getElementById('votingStage');
            // if (votingStageSelect) {
            //     votingStageSelect.addEventListener('change', function() {
            //         if (votingStageSelect.value === 'withdrawn') {
            //             closingDateInput.value = minDate;
            //             closingDateInput.setAttribute('min', minDate);
            //             closingDateInput.setAttribute('max', minDate);
            //         } else {
            //             // Re-enable date selection for other voting stages
            //             closingDateInput.removeAttribute('min');
            //             closingDateInput.removeAttribute('max');
            //         }
            //     });
            // }

            // // Pre-fill the closing date if opening date is already set (for edit cases)
            // if (openingDateInput.value) {
            //     closingDateContainer.style.display = 'block';
            //     closingDateInput.setAttribute('min', openingDateInput.value);
            // }

            document.querySelectorAll('[name="votingChoice"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value == 'stv') {
                        document.querySelector('.number_of_winner').style.display = "block"
                    } else {
                        document.querySelector('.number_of_winner').style.display = "none"
                    }
                });
            });

        });

        function setMinDate() {
            var createDateInput = document.getElementById('createDate');
            var today = new Date();
          
            var minDate = new Date();

            // Set minDate to 7 days in the past
            minDate.setDate(today.getDate() - 7);

            // Format the date to YYYY-MM-DD
            var year = minDate.getFullYear();
            var month = String(minDate.getMonth() + 1).padStart(2, '0'); // Months are 0-based
            var day = String(minDate.getDate()).padStart(2, '0');

            createDateInput.setAttribute('min', `${year}-${month}-${day}`);
        }
        setMinDate()

        function updateDates() {
            //alert("dhew");
            var createDateInput = document.getElementById('createDate');
            var openingDateInput = document.getElementById('openingDate');
            var closingDateInput = document.getElementById('closingDate');

            // Get the create date value
            var createDateValue = new Date(createDateInput.value);
            const selectElement = document.getElementById('votingStage');
            const selectedValue = selectElement.value;
            if (selectedValue != "autopass") {

                if (!isNaN(createDateValue.getTime())) {
                    // Calculate opening date (createDate + 7 days)
                    var openingDateValue = new Date(createDateValue);
                    openingDateValue.setDate(openingDateValue.getDate() + 7);

                    // Calculate closing date (openingDate + 7 days)
                    var closingDateValue = new Date(openingDateValue);
                    closingDateValue.setDate(closingDateValue.getDate() + 7);

                    // Format dates to YYYY-MM-DD for the input
                    var formatDate = (date) => {
                        var year = date.getFullYear();
                        var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                        var day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    // Set the opening and closing date values
                    openingDateInput.value = formatDate(openingDateValue);
                    closingDateInput.value = formatDate(closingDateValue);
                } else {
                    openingDateInput.value = ''; // Clear if create date is invalid
                    closingDateInput.value = ''; // Clear if create date is invalid
                }
            } else {
                //alert("autopass");
                if (!isNaN(createDateValue.getTime())) {
                    // Calculate opening date (createDate + 7 days)
                    var openingDateValue = new Date(createDateValue);
                    openingDateValue.setDate(openingDateValue.getDate() + 8); //for autopass closing date need to 1 more day 

                    // Calculate closing date (openingDate + 7 days)
                    var closingDateValue = new Date(openingDateValue);
                    closingDateValue.setDate(closingDateValue.getDate() + 7);
                    console.log(openingDateValue, createDateValue, closingDateValue);
                    // Format dates to YYYY-MM-DD for the input
                    var formatDate = (date) => {
                        var year = date.getFullYear();
                        var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                        var day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    // Set the opening and closing date values
                    openingDateInput.value = null;
                    closingDateInput.value = formatDate(openingDateValue);
                } else {
                    openingDateInput.value = ''; // Clear if create date is invalid
                    closingDateInput.value = ''; // Clear if create date is invalid
                }
            }

        }


        function updatedraftDates() {
            //alert("hedu");
            var createDateInput = document.getElementById('createDate');

            var openingDateInput = document.getElementById('openingDate');
            var closingDateInput = document.getElementById('closingDate');

            // Get the create date value
            var createDateValue = new Date(createDateInput.value);

            var currentDate = new Date();
            var formatDate = (date) => {
                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                var day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            createDateInput.value = formatDate(currentDate);
            var createDateValue = new Date(createDateInput.value);
            const selectElement = document.getElementById('votingStage');
            const selectedValue = selectElement.value;





            if (!isNaN(createDateValue.getTime())) {
                // Calculate opening date (createDate + 7 days)
                var openingDateValue = new Date(createDateValue);
                openingDateValue.setDate(openingDateValue.getDate() + 7);

                // Calculate closing date (openingDate + 7 days)
                var closingDateValue = new Date(openingDateValue);
                closingDateValue.setDate(closingDateValue.getDate() + 7);

                // Format dates to YYYY-MM-DD for the input
                var formatDate = (date) => {
                    var year = date.getFullYear();
                    var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                    var day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                // Set the opening and closing date values
                openingDateInput.value = formatDate(openingDateValue);
                closingDateInput.value = formatDate(closingDateValue);
            } else {
                openingDateInput.value = ''; // Clear if create date is invalid
                closingDateInput.value = ''; // Clear if create date is invalid
            }


        }

        function updateClosingDate() {
            //alert("yt");
            // Get the closing date input element
            var closingDateInput = document.getElementById('closingDate');
            var withdrawntime = document.getElementById('withdrawntime');


            // Get the current date and time
            var currentDate = new Date();

            // Add 24 hours to the current date
            currentDate.setHours(currentDate.getHours() + 24);

            // Format the date to YYYY-MM-DD
            var formatDate = (date) => {
                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                var day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            };


            var hours = String(currentDate.getHours()).padStart(2, '0');
            var minutes = String(currentDate.getMinutes()).padStart(2, '0');
            var seconds = String(currentDate.getSeconds()).padStart(2, '0');
            // var time = `${hours}:${minutes}:${seconds}`;
            var year = currentDate.getFullYear();
            var month = String(currentDate.getMonth() + 1).padStart(2, '0'); // Months are 0-based
            var day = String(currentDate.getDate()).padStart(2, '0');
            var time = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            console.log(time);

            // alert(time)
            // Update the closing date input with the calculated date
            closingDateInput.value = formatDate(currentDate);
            withdrawntime.value = time;


        }

        function checkautopass() {
            const selectElement = document.getElementById('votingStage');
            const selectElement1 = document.getElementById('votingstage2');
            console.log("selectElement1", selectElement1)
            const selectedValue = selectElement.value;
            if (selectedValue != 'autopass') {

                const opendate = document.getElementById('openingDatecard');
                opendate.style.display = 'block';
            }

            if (selectedValue === 'autopass') {

                //alert('Autopass option selected!');
                const opendate = document.getElementById('openingDatecard');
                opendate.style.display = 'none';
                updateDates();

                // Add any additional logic here
            }
            if (selectElement.value != "withdrawn" && selectedValue != 'autopass') {
                //alert('not withdrawn  selected!');
                updatedraftDates();
            }
            if (selectElement.value === "withdrawn") {
                if ((selectElement1.value == 'autopass') || (selectElement1.value == 'normal')) {
                    updateClosingDate();
                }
                //alert(' withdrawn  selected!');


            }

            if (selectElement.value == 'normal') {
    if (selectElement1.value == 'autopass') {
        var date = "<?php echo $vote_id ? esc_attr($create_date) : ''; ?>";
      
        updateautopassdateDates(date);
    }
}

        }


    
        function updateautopassdateDates(createdate) {
    var createDateInput = document.getElementById('createDate');

    var openingDateInput = document.getElementById('openingDate');
    var closingDateInput = document.getElementById('closingDate');

    // Convert to a Date object
    var createDateValue = new Date(createdate);

    if (!isNaN(createDateValue.getTime())) {
        // Calculate opening date (same as createDateValue)
        var openingDateValue = new Date(createDateValue);
        openingDateValue.setDate(openingDateValue.getDate() + 7);

        // Calculate closing date (openingDate + 7 days)
        var closingDateValue = new Date(openingDateValue);
        closingDateValue.setDate(closingDateValue.getDate() + 7);

        // Format dates to YYYY-MM-DD for input fields
        var formatDate = (date) => {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        createDateInput.value = formatDate(createDateValue);

        openingDateInput.value = formatDate(openingDateValue);
        closingDateInput.value = formatDate(closingDateValue);
    } else {
        // Invalid date, clear inputs
        openingDateInput.value = '';
        closingDateInput.value = '';
    }
}


        function updateDates1() {
            // var createDateInput = document.getElementById('createDate');
            var openingDateInput = document.getElementById('openingDate');
            var closingDateInput = document.getElementById('closingDate');

            // Get the create date value
            var createDateValue = new Date(openingDateInput.value);

            // Check if the create date is valid
            if (!isNaN(createDateValue.getTime())) {
                // Calculate opening date (createDate + 7 days)
                var openingDateValue = new Date(createDateValue);
                openingDateValue.setDate(openingDateValue.getDate());

                // Calculate closing date (openingDate + 7 days)
                var closingDateValue = new Date(openingDateValue);
                closingDateValue.setDate(closingDateValue.getDate() + 7);

                // Format dates to YYYY-MM-DD for the input
                var formatDate = (date) => {
                    var year = date.getFullYear();
                    var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
                    var day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                // Set the opening and closing date values
                openingDateInput.value = formatDate(openingDateValue);
                closingDateInput.value = formatDate(closingDateValue);
            } else {
                openingDateInput.value = ''; // Clear if create date is invalid
                closingDateInput.value = ''; // Clear if create date is invalid
            }
        }

        function setballotoption(resulttype) {
            const value = document.getElementById('ballot-option-punishment')
            const value1 = document.getElementById('ballot-option-not-punishment')
            const addbuttonm = document.getElementById('addanotheritem')

            const anoteritembox = document.querySelector('#anoteritembox');

            let punisment = `                                <div id="ballot-option-punishment">
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_1' value="Permanent Ban" readonly
                                            onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_2' readonly value="Indefinite Ban/3 Strikes" onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_3' value="Temporary Ban" readonly onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_4' readonly
                                            value="2 Strikes" onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_5' readonly
                                            value="1 Strike" onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_6' readonly
                                            value="Probation" onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_7' readonly
                                            value="Censure" onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_8' readonly value="Condemnation" onblur="addingToOptions(this.id, this.value)">
                                    </div>
                                </div>
`

            let notPunishment = `
 <div id="ballot-option-not-punishment">
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_1' value="Abstain" onblur="addingToOptions(this.id, this.value)">
                                         <button class="delete-btn" onclick="removeOption('${this.id}')">−</button>
                                        
                                    </div>
                                    <div class="cholce-box-inpt">
                                        <input type="text" id='input_2' value="Reject All" onblur="addingToOptions(this.id, this.value)">
                                         <button class="delete-btn" onclick="removeOption('${this.id}')">−</button>
                                    </div>
                                </div>
`

            var result_type = resulttype
            console.log(result_type, votingChoice, result_type === votingChoice);

            if (result_type === votingChoice) {

                anoteritembox.innerHTML = `
                 <div id="ballot-option-not-punishment">
            ${
               additionalBallotOptions.map((ele)=>(
                `
                <div class="cholce-box-inpt">
                                        <input type="text" id='${ele.id}' value="${ele.text}" onblur="addingToOptions(this.id, this.value)">
                                          <button class="delete-btn" onclick="removeOption('${ele.id}')">−</button>
                                    </div>
                `
               )).join('')
            }
                 </div>
                `

            }

            if (result_type == "punishment") {

                anoteritembox.innerHTML = punisment;
                // value.style.display = 'block';
                // value1.style.display = 'none'
                addbuttonm.style.display = 'none'

            } else if (votingChoice !== result_type) {
                anoteritembox.innerHTML = notPunishment;
                // value.style.display = 'none';
                // value1.style.display = 'block';
                addbuttonm.style.display = 'block'


            }
        }


        function removeOption(id) {
            // Find the parent div and remove it
            const inputElement = document.getElementById(id);
            if (inputElement) {
                inputElement.parentElement.remove();
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            CKEDITOR.replace('custom-editor');



        })
    </script>

<?php
    return ob_get_clean();
}
add_shortcode('voting_form', 'voting_form_shortcode');
?>