<?php
function preview_vote_shortcode()
{

    get_header();

$id = get_query_var( 'id', 'default_value' );
    // if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))) {
    //     wp_die('You do not have permission to access this page.');
    // }
    // $vote_id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $vote_id = $id ? intval($id) : null;


    // Retrieve vote data based on the ID
    $vote_data = $vote_id !== null ? get_vote_data_by_id($vote_id) : null;

    // If vote data is found, populate form fields for editing
    if ($vote_data) {
        $proposal_name = $vote_data['proposal_name'];
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
        $content = $vote_data['content'];
        $maximum_choices = $vote_data['maximum_choices'];
        $active_status = $vote_data['active_status'];

        // Encode
        $voting_options_json = json_encode($voting_options);
        $files_name_json = json_encode($files_name);

        if($visibility!=="both"){
            if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))) {
               wp_die('You do not have permission to access this page.');
           }
       }
    } else {
        $files_name_json = json_encode([]);
        $voting_options_json = json_encode([]);
    }

    ob_start();
?>
    <section class="owbnVP bg--black">
        <div class="custtom--contaner">
            <h2 class="heading--sec">Votes Preview</h2>

            <div class="databox--sec">
                <div class="preview--deats--user mb--50">
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Proposal Name</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($proposal_name) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Voting Options </h5> <span>-</span>
                        <h6 class="detals--box-data" id="votingOptions"
                            data-voting-options='<?php echo $voting_options_json; ?>'></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Vote Type</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($vote_type) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Voting Stage</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($voting_stage) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Proposed By</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($proposed_by) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Seconded By</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($seconded_by) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Create Date</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($create_date) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Opening Date</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($opening_date) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Closing Date</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($closing_date) : ''; ?></h6>
                    </div>
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Visibility</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($visibility) : ''; ?></h6>
                    </div>
                    <div class="detals--box preview_description">
                        <h5 class="detals--box-hading">Description</h5> <span>-</span>
                        <div class="preview_descriptionData"><?php echo $vote_id ? $content : ''; ?></div>
                    </div>
                    <!-- <div class="detals--box">
                    <h5 class="detals--box-hading">Maximum Choices</h5> <span>-</span>
                    <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($maximum_choices) : ''; ?></h6>
                </div> -->
                    <div class="detals--box">
                        <h5 class="detals--box-hading">Active</h5> <span>-</span>
                        <h6 class="detals--box-data"><?php echo $vote_id ? esc_attr($active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote') : ''; ?></h6>
                    </div>
                </div>

                <div class="detals--box">
                    <h5 class="detals--box-hading">Flle / Document</h5>
                </div>
                <div class="table--box">
                    <table class="file--informaction--table preview--table" id="fileInfoTable">
                        <thead>
                            <tr>
                                <th>File Information</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>

                </div>

                <!-- <div class="save--btn-box mb--50">
                <button class="cust--btn">Save</button>
            </div> -->
            </div>
            <?php
            $files_id= json_decode($files_name_json,true);
            $file_ids = array_column($files_id, 'id');
           
            $file_urls = [];
            foreach ($file_ids as $id) {
                $file_urls[$id] = wp_get_attachment_url($id);
            }
            ?>
    </section>
    <script type="text/javascript">
        // Embed JSON data into the HTML
        var fileUrls = <?php echo json_encode($file_urls); ?>;
        var phpFilesName = <?php echo $files_name_json; ?>;
        var phpVotingOptions = <?php echo $voting_options_json; ?>;

        console.log({
            f: phpFilesName,
            v: phpVotingOptions
        })

        var domContentLoadedExecuted = false;

        document.addEventListener("DOMContentLoaded", function() {
            if (!domContentLoadedExecuted) {
                domContentLoadedExecuted = true;

                // Get the table body element
                var tableBody = document.getElementById('fileInfoTable').getElementsByTagName('tbody')[0];

                // Iterate over the uploadedFiles array
                phpFilesName.forEach(function(file) {
                    // Create a new row for the table
                    var newRow = fileInfoTable.insertRow();


                    // Insert cells into the new row
                    var fileNameCell = newRow.insertCell(0);
                    var fileUrl;
                    var fileId = file.id;

                    if(fileId){
                        var fileUrl = fileUrls[fileId];
                    }else{
                        var fileUrl = "";
                    }
                    

                    // Set the file name, size, and description
                    fileNameCell.innerHTML =
    '<div class="file--name">' +
        '<a target="_blank" href="' + fileUrl + '">' +
            '<p>' +
                file.fileName +
                '<span>(' + file.fileSize + ' bytes)</span>' +
            '</p>' +
        '</a>' +
        '<p>File Description: ' + file.description + '</p>' +
    '</div>';

                        

//                         var descriptionCell = row.insertCell(-1);
// descriptionCell.innerHTML =
//     '<div class="file--description"><p>' +
//     (file.description || "No description available") +
//     "</p></div>";
                });
            }
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Get the h6 element
            var votingOptionsElement = document.getElementById('votingOptions');

            // Get the voting options from the data attribute
            var votingOptions = JSON.parse(votingOptionsElement.getAttribute('data-voting-options'));

            // Extract the 'text' values and join them with commas
            var votingOptionsText = votingOptions.map(function(option) {
                return option.text;
            }).join(', ');

            // Update the h6 element with the comma-separated 'text' values
            votingOptionsElement.textContent = votingOptionsText;
        });
    </script>
<?php

    return ob_get_clean();
}

add_shortcode('preview_vote', 'preview_vote_shortcode');
