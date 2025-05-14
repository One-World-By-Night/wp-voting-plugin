<?php
function user_search_vote_list_shortcode()
{

    $voteList = getUserVoteList();
    ob_start();
    // my_custom_cron_function();

    // Your HTML content or function calls to generate the preview content
?>
    <!-- <section class="owbnVP vote--result">
        <div class="vote--wrapper">
            <div class="vote--row">
                <div class="col">
                    <div class="vote--result--area desktop-table">
                        <div class="container">
                            <div class="result-table-responsive">
                                <table id="exampleee" class="custom--table display" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Proposal Name</th>
                                            <th>Ballot Type</th>
                                            <th>Opening Date</th>
                                            <th>Closing Date</th>
                                            <th>Voting Stage</th>
                                            <th>Total Votes</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>



                                    </tbody>
                                   
                                </table>
                            </div>
                        </div>
                    </div>


                    <div class="vote--result--area mobile-table">
                        <div class="container">
                            <div class="result-table-responsive">
                                <table id="example1e" class="custom--table display" style="width:100%">
                                    <thead>
                                        <tr>

                                            <th>Proposal Name</th>


                                            <th>Closing Date</th>

                                            <th>Total Votes</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>



                                    </tbody>
                                   
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->



    <section class="owbnVP custom-section election_dashboard">
        <div class="wrapper">
            <div class="tab--section">
                <div class="tab--wrapper">
                    <div class="tabs">
                        <ul class="tab-links">
                            <li class="active"><a href="#tab1">All Votes</a></li>
                            <li><a href="#tab2">Open vote</a></li>

                            <li><a href="#tab3">Closed vote</a></li>



                        </ul>
                        <div class="tab-content">
                            <!-- Outputs the tab options in the admin UI. This is a copy of the code that has been moved to utils. -->
                            <div id="tab1" class="tab active">

                                <div class="vote--result--area desktop-table">
                                    <div class="container">
                                        <div class="result-table-responsive">
                                            <table id="example" class="custom--table display" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Proposal Name</th>
                                                        <th>Ballot Type</th>
                                                        <th>Opening Date</th>
                                                        <th>Closing Date</th>
                                                        <th>Voting Stage</th>
                                                        <th>Total Votes</th>
                                                        <!-- <th>Result</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>



                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>


                                <div class="vote--result--area mobile-table">
                                    <div class="container">
                                        <div class="result-table-responsive">
                                            <table id="example1" class="custom--table display" style="width:100%">
                                                <thead>
                                                    <tr>

                                                        <th>Proposal Name</th>


                                                        <th>Closing Date</th>

                                                        <th>Total Votes</th>
                                                        <!-- <th>Result</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>



                                                </tbody>
                                                <!-- <tfoot>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot> -->
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="tab2" class="tab">
                                <div class="vote--result--area desktop-table">
                                    <div class="container">
                                        <div class="result-table-responsive">
                                            <table id="openvotes" class="custom--table display" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Proposal Name</th>
                                                        <th>Ballot Type</th>
                                                        <th>Opening Date</th>
                                                        <th>Closing Date</th>
                                                        <th>Voting Stage</th>
                                                        <th>Total Votes</th>
                                                        <!-- <th>Result</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>



                                                </tbody>
                                                <!-- <tfoot>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot> -->
                                            </table>
                                        </div>
                                    </div>
                                </div>


                                <div class="vote--result--area mobile-table">
                                    <div class="container">
                                        <div class="result-table-responsive">
                                            <table id="openvotes1" class="custom--table display" style="width:100%">
                                                <thead>
                                                    <tr>

                                                        <th>Proposal Name</th>


                                                        <th>Closing Date</th>

                                                        <th>Total Votes</th>
                                                        <!-- <th>Result</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>



                                                </tbody>
                                                <!-- <tfoot>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot> -->
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="tab3" class="tab">
                                <div class="vote--result--area desktop-table">
                                    <div class="container">
                                        <div class="result-table-responsive">
                                            <table id="closevotes" class="custom--table display" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Proposal Name</th>
                                                        <th>Ballot Type</th>
                                                        <th>Opening Date</th>
                                                        <th>Closing Date</th>
                                                        <th>Voting Stage</th>
                                                        <th>Total Votes</th>
                                                        <!-- <th>Result</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>



                                                </tbody>
                                                <!-- <tfoot>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot> -->
                                            </table>
                                        </div>
                                    </div>
                                </div>


                                <div class="vote--result--area mobile-table">
                                    <div class="container">
                                        <div class="result-table-responsive">
                                            <table id="closevotes1" class="custom--table display" style="width:100%">
                                                <thead>
                                                    <tr>

                                                        <th>Proposal Name</th>


                                                        <th>Closing Date</th>

                                                        <th>Total Votes</th>
                                                        <!-- <th>Result</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>



                                                </tbody>
                                                <!-- <tfoot>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot> -->
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>



                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>




    <script>
        jQuery(document).ready(function() {
            function handleTabSwitch(e) {
                e.preventDefault();
                var currentAttrValue = jQuery(this).attr('href');
                jQuery('.tab' + currentAttrValue).fadeIn(400).siblings().hide();
                jQuery(this).parent('li').addClass('active').siblings().removeClass('active');
            }

            jQuery('.tab-links a').on('click', handleTabSwitch);

            jQuery('#addTab').on('click', function() {
                var newTabIndex = jQuery('.tab-links li').length + 1;
                var newTabId = 'tab' + newTabIndex;
                jQuery('.tab-links').append('<li><a href="#' + newTabId + '">Tab ' + newTabIndex + '</a></li>');
                jQuery('.tab-content').append('<div id="' + newTabId + '" class="tab"><p>Content for Tab ' +
                    newTabIndex + '</p></div>');
                jQuery('.tab-links a').off('click').on('click', handleTabSwitch);
            });
        });
    </script>







    <script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();
            var currentDate = new Date(); // Get current date
            // Initialize DataTable
            var dataTable = $('#example').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_data_user_search_vote_list';
                        d.username = username;
                    },
                    //     dataSrc: function(json) {
                    //     // Filter out rows based on conditions
                    //     return json.data.filter(function(row) {
                    //         var closingDate = new Date(row.closing_date); // Convert closing_date to Date object
                    //         var isBlindVoting = row.blindvoting == 'BlindVotingtrue';

                    //         // Exclude rows where closingDate > currentDate AND blindVoting is true
                    //         return (closingDate > currentDate && isBlindVoting);
                    //     });
                    // }
                    dataSrc: function(json) {
                        var currentDate = new Date(); // Get current date

                        var count = 0;

                        return json.data.filter(function(row) {
                            console.log((JSON.parse(row.vote_box)).length);
                            count = (JSON.parse(row.vote_box)).length;
                            var closingDate = new Date(row.closing_date);
                            var openingDate = new Date(row.opening_date);
                            var isBlindVoting = row.blindVoting == "BlindVotingtrue";
                            console.log(currentDate, closingDate, openingDate, isBlindVoting, row.blindVoting)
                            // Condition 1: Include where closing_date <= currentDate
                            if (closingDate <= currentDate) {
                                return true;
                            }

                            // Condition 2: Include where opening_date <= currentDate AND (closing_date > currentDate AND blindVoting is true)
                            if (openingDate <= currentDate && closingDate > currentDate && isBlindVoting) {
                                return true;
                            }

                            // Exclude everything else
                            return false;
                        });
                    }

                },
                columns: [{
                        data: 'id',
                        orderable: false
                    },
                    // {
                    //     data: 'proposal_name',
                    //     orderable: false
                    // },

                    {
                        data: 'proposal_name',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">${data}</a>`;
                        }
                    },

                    {
                        data: 'vote_type',
                        orderable: false
                    },
                    {
                        data: 'opening_date',
                        orderable: false
                    },
                    {
                        data: 'closing_date',
                        orderable: false
                    },
                    {
                        data: 'voting_stage',
                        orderable: false
                    },

                    {
                        data: 'vote_box',
                        orderable: false,
                        render: (data) => (data ? (JSON.parse(data)).length : 0)
                    },
                    // {
                    //     data: 'vote_box',
                    //     orderable: false,
                    //     render: function(data, type, row) {
                    //         // return `<a href="<?php echo site_url(); ?>/owbn-voting-result/?id=${row.id}">View</a>`;

                    //         return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">View</a>`;
                    //     }
                    // }
                ],
                pageLength: 10,
                serverSide: true,
                processing: true,
                order: [
                    [3, 'desc']
                ], // Order by opening_date
            });
            console.log(dataTable)

            // Listen for changes in the page length dropdown
            $('#pageLength').change(function() {
                let newLength = $(this).val();
                dataTable.page.len(newLength).draw();
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();
            var currentDate = new Date(); // Get current date
            // Initialize DataTable
            var dataTable = $('#example1').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_data_user_search_vote_list';
                        d.username = username;
                    },
                    //     dataSrc: function(json) {
                    //     // Filter out rows based on conditions
                    //     return json.data.filter(function(row) {
                    //         var closingDate = new Date(row.closing_date); // Convert closing_date to Date object
                    //         var isBlindVoting = row.blindvoting == 'BlindVotingtrue';

                    //         // Exclude rows where closingDate > currentDate AND blindVoting is true
                    //         return (closingDate > currentDate && isBlindVoting);
                    //     });
                    // }
                    dataSrc: function(json) {
                        var currentDate = new Date(); // Get current date

                        var count = 0;

                        return json.data.filter(function(row) {
                            console.log((JSON.parse(row.vote_box)).length);
                            count = (JSON.parse(row.vote_box)).length;
                            var closingDate = new Date(row.closing_date);
                            var openingDate = new Date(row.opening_date);
                            var isBlindVoting = row.blindVoting == "BlindVotingtrue";
                            console.log(currentDate, closingDate, openingDate, isBlindVoting, row.blindVoting)
                            // Condition 1: Include where closing_date <= currentDate
                            if (closingDate <= currentDate) {
                                return true;
                            }

                            // Condition 2: Include where opening_date <= currentDate AND (closing_date > currentDate AND blindVoting is true)
                            if (openingDate <= currentDate && closingDate > currentDate && isBlindVoting) {
                                return true;
                            }

                            // Exclude everything else
                            return false;
                        });
                    }

                },
                columns: [

                    // {
                    //     data: 'proposal_name',
                    //     orderable: false
                    // },
                    {
                        data: 'proposal_name',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">${data}</a>`;
                        }
                    },


                    {
                        data: 'closing_date',
                        orderable: false
                    },

                    {
                        data: 'vote_box',
                        orderable: false,
                        render: (data) => (data ? (JSON.parse(data)).length : 0)
                    },
                    // {
                    //     data: 'vote_box',
                    //     orderable: false,
                    //     render: function(data, type, row) {
                    //         // return `<a href="<?php echo site_url(); ?>/owbn-voting-result/?id=${row.id}">View</a>`;

                    //         return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">View</a>`;
                    //     }
                    // }
                ],
                pageLength: 10,
                serverSide: true,
                processing: true,
                order: [
                    [2, 'desc']
                ], // Order by opening_date
            });
            console.log(dataTable)

            // Listen for changes in the page length dropdown
            $('#pageLength').change(function() {
                let newLength = $(this).val();
                dataTable.page.len(newLength).draw();
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();
            var currentDate = new Date(); // Get current date
            // Initialize DataTable
            var dataTable = $('#openvotes').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_data_user_search_openvote_list';
                        d.username = username;
                    },
                    //     dataSrc: function(json) {
                    //     // Filter out rows based on conditions
                    //     return json.data.filter(function(row) {
                    //         var closingDate = new Date(row.closing_date); // Convert closing_date to Date object
                    //         var isBlindVoting = row.blindvoting == 'BlindVotingtrue';

                    //         // Exclude rows where closingDate > currentDate AND blindVoting is true
                    //         return (closingDate > currentDate && isBlindVoting);
                    //     });
                    // }
                    dataSrc: function(json) {
                        var currentDate = new Date(); // Get current date

                        var count = 0;

                        console.log(json.data,"data")
                        return json.data.filter(function(row) {
                            console.log((JSON.parse(row.vote_box)).length);
                            count = (JSON.parse(row.vote_box)).length;
                            var closingDate = new Date(row.closing_date);
                            var votingStage = row.voting_stage;
                            var activeStatus = row.active_status;
                            var openingDate = new Date(row.opening_date);
                            var isBlindVoting = row.blindVoting == "BlindVotingtrue";
                            console.log(currentDate, closingDate, openingDate, isBlindVoting, row.blindVoting)
                            // Condition 1: Include where closing_date <= currentDate
                            if (
                             
                                votingStage != 'completed' &&
                                votingStage != 'withdrawn' &&
                                //                 $votingStage != 'autopass' &&
                                closingDate >= currentDate &&
                                activeStatus == 'activeVote'
                            ) {
                                return true;
                            }

                            if (openingDate <= currentDate && closingDate > currentDate && isBlindVoting) {
                                return true;
                            }

                            // Condition 2: Include where opening_date <= currentDate AND (closing_date > currentDate AND blindVoting is true)


                            // Exclude everything else
                            return false;
                        });
                    }

                },
                columns: [

                    // {
                    //     data: 'proposal_name',
                    //     orderable: false
                    // },
                    {
                        data: 'id',
                        orderable: false
                    },
                    {
                        data: 'proposal_name',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">${data}</a>`;
                        }
                    },


                    {
                        data: 'vote_type',
                        orderable: false
                    },
                    {
                        data: 'opening_date',
                        orderable: false
                    },
                    {
                        data: 'closing_date',
                        orderable: false
                    },
                    {
                        data: 'voting_stage',
                        orderable: false
                    },
                    {
                        data: 'vote_box',
                        orderable: false,
                        render: (data) => (data ? (JSON.parse(data)).length : 0)
                    },
                    // {
                    //     data: 'vote_box',
                    //     orderable: false,
                    //     render: function(data, type, row) {
                    //         // return `<a href="<?php echo site_url(); ?>/owbn-voting-result/?id=${row.id}">View</a>`;

                    //         return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">View</a>`;
                    //     }
                    // }
                ],
                pageLength: 10,
                serverSide: true,
                processing: true,
                order: [
                    [2, 'desc']
                ], // Order by opening_date
            });
            console.log(dataTable)

            // Listen for changes in the page length dropdown
            $('#pageLength').change(function() {
                let newLength = $(this).val();
                dataTable.page.len(newLength).draw();
            });
        });
    </script>




    <script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();
            var currentDate = new Date(); // Get current date
            // Initialize DataTable
            var dataTable = $('#openvotes1').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_data_user_search_openvote_list';
                        d.username = username;
                    },
                    //     dataSrc: function(json) {
                    //     // Filter out rows based on conditions
                    //     return json.data.filter(function(row) {
                    //         var closingDate = new Date(row.closing_date); // Convert closing_date to Date object
                    //         var isBlindVoting = row.blindvoting == 'BlindVotingtrue';

                    //         // Exclude rows where closingDate > currentDate AND blindVoting is true
                    //         return (closingDate > currentDate && isBlindVoting);
                    //     });
                    // }
                    dataSrc: function(json) {
                        var currentDate = new Date(); // Get current date

                        var count = 0;

                        return json.data.filter(function(row) {
                            console.log((JSON.parse(row.vote_box)).length);
                            count = (JSON.parse(row.vote_box)).length;
                            var closingDate = new Date(row.closing_date);
                            var votingStage = row.voting_stage;
                            var activeStatus = row.active_status;
                            var openingDate = new Date(row.opening_date);
                            var isBlindVoting = row.blindVoting == "BlindVotingtrue";
                            console.log(currentDate, closingDate, openingDate, isBlindVoting, row.blindVoting)
                            // Condition 1: Include where closing_date <= currentDate
                            if (
                             
                             votingStage != 'completed' &&
                             votingStage != 'withdrawn' &&
                             //                 $votingStage != 'autopass' &&
                             closingDate >= currentDate &&
                             activeStatus == 'activeVote'
                         ) {
                             return true;
                         }

                         if (openingDate <= currentDate && closingDate > currentDate && isBlindVoting) {
                             return true;
                         }

                            // Condition 2: Include where opening_date <= currentDate AND (closing_date > currentDate AND blindVoting is true)


                            // Exclude everything else
                            return false;
                        });
                    }

                },
                columns: [

                    // {
                    //     data: 'proposal_name',
                    //     orderable: false
                    // },
                    {
                        data: 'proposal_name',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">${data}</a>`;
                        }
                    },


                    {
                        data: 'closing_date',
                        orderable: false
                    },

                    {
                        data: 'vote_box',
                        orderable: false,
                        render: (data) => (data ? (JSON.parse(data)).length : 0)
                    },
                    // {
                    //     data: 'vote_box',
                    //     orderable: false,
                    //     render: function(data, type, row) {
                    //         // return `<a href="<?php echo site_url(); ?>/owbn-voting-result/?id=${row.id}">View</a>`;

                    //         return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">View</a>`;
                    //     }
                    // }
                ],
                pageLength: 10,
                serverSide: true,
                processing: true,
                order: [
                    [2, 'desc']
                ], // Order by opening_date
            });
            console.log(dataTable)

            // Listen for changes in the page length dropdown
            $('#pageLength').change(function() {
                let newLength = $(this).val();
                dataTable.page.len(newLength).draw();
            });
        });
    </script>








<script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();
            var currentDate = new Date(); // Get current date
            // Initialize DataTable
            var dataTable = $('#closevotes').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_data_user_search_closevote_list';
                        d.username = username;
                    },
                    //     dataSrc: function(json) {
                    //     // Filter out rows based on conditions
                    //     return json.data.filter(function(row) {
                    //         var closingDate = new Date(row.closing_date); // Convert closing_date to Date object
                    //         var isBlindVoting = row.blindvoting == 'BlindVotingtrue';

                    //         // Exclude rows where closingDate > currentDate AND blindVoting is true
                    //         return (closingDate > currentDate && isBlindVoting);
                    //     });
                    // }
                    dataSrc: function(json) {
                        var currentDate = new Date(); // Get current date

                        var count = 0;

                        return json.data.filter(function(row) {
                            console.log((JSON.parse(row.vote_box)).length);
                            count = (JSON.parse(row.vote_box)).length;
                            var closingDate = new Date(row.closing_date);
                            var votingStage = row.voting_stage;
                            var activeStatus = row.active_status;
                            var openingDate = new Date(row.opening_date);
                            var isBlindVoting = row.blindVoting == "BlindVotingtrue";
                            console.log(currentDate, closingDate, openingDate, isBlindVoting, row.blindVoting)
                            // Condition 1: Include where closing_date <= currentDate
                            if (
                                votingStage === 'completed') {
                                return true;
                            }

                            if (closingDate <= currentDate) {
                                return true;
                            }

                            // Condition 2: Include where opening_date <= currentDate AND (closing_date > currentDate AND blindVoting is true)


                            // Exclude everything else
                            return false;
                        });
                    }

                },
                columns: [

                    // {
                    //     data: 'proposal_name',
                    //     orderable: false
                    // },

                    {
                        data: 'id',
                        orderable: false
                    },
                    {
                        data: 'proposal_name',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">${data}</a>`;
                        }
                    },
                    {
                        data: 'vote_type',
                        orderable: false
                    },
                    {
                        data: 'opening_date',
                        orderable: false
                    },

                    {
                        data: 'closing_date',
                        orderable: false
                    },
                    {
                        data: 'voting_stage',
                        orderable: false
                    },

                    {
                        data: 'vote_box',
                        orderable: false,
                        render: (data) => (data ? (JSON.parse(data)).length : 0)
                    },
                    // {
                    //     data: 'vote_box',
                    //     orderable: false,
                    //     render: function(data, type, row) {
                    //         // return `<a href="<?php echo site_url(); ?>/owbn-voting-result/?id=${row.id}">View</a>`;

                    //         return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">View</a>`;
                    //     }
                    // }
                ],
                pageLength: 10,
                serverSide: true,
                processing: true,
                order: [
                    [2, 'desc']
                ], // Order by opening_date
            });
            console.log(dataTable)

            // Listen for changes in the page length dropdown
            $('#pageLength').change(function() {
                let newLength = $(this).val();
                dataTable.page.len(newLength).draw();
            });
        });
    </script>




<script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();
            var currentDate = new Date(); // Get current date
            // Initialize DataTable
            var dataTable = $('#closevotes1').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_data_user_search_closevote_list';
                        d.username = username;
                    },
                    //     dataSrc: function(json) {
                    //     // Filter out rows based on conditions
                    //     return json.data.filter(function(row) {
                    //         var closingDate = new Date(row.closing_date); // Convert closing_date to Date object
                    //         var isBlindVoting = row.blindvoting == 'BlindVotingtrue';

                    //         // Exclude rows where closingDate > currentDate AND blindVoting is true
                    //         return (closingDate > currentDate && isBlindVoting);
                    //     });
                    // }
                    dataSrc: function(json) {
                        var currentDate = new Date(); // Get current date

                        var count = 0;

                        return json.data.filter(function(row) {
                            console.log((JSON.parse(row.vote_box)).length);
                            count = (JSON.parse(row.vote_box)).length;
                            var closingDate = new Date(row.closing_date);
                            var votingStage = row.voting_stage;
                            var activeStatus = row.active_status;
                            var openingDate = new Date(row.opening_date);
                            var isBlindVoting = row.blindVoting == "BlindVotingtrue";
                            console.log(currentDate, closingDate, openingDate, isBlindVoting, row.blindVoting)
                            // Condition 1: Include where closing_date <= currentDate
                            if (
                                votingStage === 'completed') {
                                return true;
                            }

                            // Condition 2: Include where opening_date <= currentDate AND (closing_date > currentDate AND blindVoting is true)


                            // Exclude everything else
                            return false;
                        });
                    }

                },
                columns: [

                    // {
                    //     data: 'proposal_name',
                    //     orderable: false
                    // },
                    {
                        data: 'proposal_name',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">${data}</a>`;
                        }
                    },


                    {
                        data: 'closing_date',
                        orderable: false
                    },

                    {
                        data: 'vote_box',
                        orderable: false,
                        render: (data) => (data ? (JSON.parse(data)).length : 0)
                    },
                    // {
                    //     data: 'vote_box',
                    //     orderable: false,
                    //     render: function(data, type, row) {
                    //         // return `<a href="<?php echo site_url(); ?>/owbn-voting-result/?id=${row.id}">View</a>`;

                    //         return `<a href="<?php echo site_url(); ?>/owbn-voting-result/${row.id}">View</a>`;
                    //     }
                    // }
                ],
                pageLength: 10,
                serverSide: true,
                processing: true,
                order: [
                    [2, 'desc']
                ], // Order by opening_date
            });
            console.log(dataTable)

            // Listen for changes in the page length dropdown
            $('#pageLength').change(function() {
                let newLength = $(this).val();
                dataTable.page.len(newLength).draw();
            });
        });
    </script>


<?php

    return ob_get_clean();
    // }
}
add_shortcode('user_search_vote_list', 'user_search_vote_list_shortcode');

// Add AJAX action for logged-in users
add_action('wp_ajax_fetch_data_user_search_vote_list', 'fetch_data_user_search_vote_list');

// Add AJAX action for non-logged-in users (if needed)
add_action('wp_ajax_nopriv_fetch_data_user_search_vote_list', 'fetch_data_user_search_vote_list');





add_action('wp_ajax_fetch_data_user_search_openvote_list', 'fetch_data_user_search_openvote_list');

// Add AJAX action for non-logged-in users (if needed)
add_action('wp_ajax_nopriv_fetch_data_user_search_openvote_list', 'fetch_data_user_search_openvote_list');


add_action('wp_ajax_fetch_data_user_search_closevote_list', 'fetch_data_user_search_closevote_list');

// Add AJAX action for non-logged-in users (if needed)
add_action('wp_ajax_nopriv_fetch_data_user_search_closevote_list', 'fetch_data_user_search_closevote_list');

function fetch_data_user_search_vote_list()
{
    global $wpdb;

    // Retrieve pagination and search parameters
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

    // Validate username
    // $user = get_user_by('login', $username);
    // if (!$user) {
    //     wp_send_json_error('User not found');
    //     wp_die();
    // }

    // Prepare SQL query with search and LIMIT for pagination
    $table_name = $wpdb->prefix . 'voting';

    // Construct query
    // $query = $wpdb->prepare(
    //     "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box 
    //      FROM $table_name 
    //      WHERE voting_stage = %s AND visibility = %s 
    //      AND (proposal_name LIKE %s OR vote_type LIKE %s)
    //      ORDER BY opening_date DESC 
    //      LIMIT %d, %d",
    //     'completed',
    //     'both',
    //     '%' . $wpdb->esc_like($search_value) . '%',
    //     '%' . $wpdb->esc_like($search_value) . '%',
    //     $start,
    //     $length
    // );
    //     $query = $wpdb->prepare(
    //         "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage
    //          FROM $table_name 
    //          WHERE (voting_stage = %s OR voting_stage = %s) 
    //          AND visibility = %s 
    //          AND (proposal_name LIKE %s OR vote_type LIKE %s) 
    //          AND closing_date <= CURDATE()
    //          ORDER BY opening_date DESC 
    //          LIMIT %d, %d",
    //         'completed',
    //         'withdrawn',
    //         'both',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         $start,
    //         $length
    //     );

    //     // Execute query
    //     $results = $wpdb->get_results($query, ARRAY_A);
    // // print_r($results);
    //     // Count total records (for pagination)
    //     $total_records = $wpdb->get_var($wpdb->prepare(
    //         "SELECT COUNT(*) 
    //          FROM $table_name 
    //            WHERE (voting_stage = %s OR voting_stage = %s) 
    //          AND visibility = %s 
    //          AND (proposal_name LIKE %s OR vote_type LIKE %s)
    //          AND closing_date <= CURDATE()

    //          ",

    //         'completed',
    //         'withdrawn',
    //         'both',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         '%' . $wpdb->esc_like($search_value) . '%'
    //     ));



    if (!is_user_logged_in()) {
        $query = $wpdb->prepare(
            "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage,blindVoting
         FROM $table_name 
         WHERE (voting_stage = %s OR voting_stage = %s OR voting_stage = %s) 
         AND visibility = %s 
         AND (proposal_name LIKE %s OR vote_type LIKE %s) 
         AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ORDER BY opening_date DESC 
         LIMIT %d, %d",
            'completed',
            'withdrawn',
            'normal',
            'both',
            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%',
            $start,
            $length
        );

        // Execute query
        $results = $wpdb->get_results($query, ARRAY_A);
        error_log($query);

        // print_r($results);
        // Count total records (for pagination)
        $total_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
         FROM $table_name 
           WHERE (voting_stage = %s OR voting_stage = %s OR voting_stage = %s) 
         AND visibility = %s 
         AND (proposal_name LIKE %s OR vote_type LIKE %s)
           AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ",

            'completed',
            'withdrawn',
            'normal',
            'both',
            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%'
        ));
    }
    if (is_user_logged_in()) {

        $query = $wpdb->prepare(
            "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage,blindVoting
         FROM $table_name 
         WHERE (voting_stage = %s OR voting_stage = %s OR voting_stage = %s) 
       
         AND (proposal_name LIKE %s OR vote_type LIKE %s) 
        AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ORDER BY opening_date DESC 
         LIMIT %d, %d",
            'completed',
            'withdrawn',
            'normal',

            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%',
            $start,
            $length
        );

        // Execute query
        $results = $wpdb->get_results($query, ARRAY_A);
        error_log($query);

        // print_r($results);
        // Count total records (for pagination)
        $total_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
         FROM $table_name 
           WHERE (voting_stage = %s OR voting_stage = %s OR voting_stage = %s) 
      
         AND (proposal_name LIKE %s OR vote_type LIKE %s)
           AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ",

            'completed',
            'withdrawn',
            'normal',

            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%'
        ));
    }
    // Return data as JSON
    echo json_encode([
        'draw' => intval($_POST['draw']),
        'recordsTotal' => intval($total_records),
        'recordsFiltered' => intval($total_records),
        'data' => $results,
        'user' => $user
    ]);
    wp_die();
}



function fetch_data_user_search_openvote_list()
{
    global $wpdb;

    // Retrieve pagination and search parameters
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

    // Validate username
    // $user = get_user_by('login', $username);
    // if (!$user) {
    //     wp_send_json_error('User not found');
    //     wp_die();
    // }

    // Prepare SQL query with search and LIMIT for pagination
    $table_name = $wpdb->prefix . 'voting';

    // Construct query
    // $query = $wpdb->prepare(
    //     "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box 
    //      FROM $table_name 
    //      WHERE voting_stage = %s AND visibility = %s 
    //      AND (proposal_name LIKE %s OR vote_type LIKE %s)
    //      ORDER BY opening_date DESC 
    //      LIMIT %d, %d",
    //     'completed',
    //     'both',
    //     '%' . $wpdb->esc_like($search_value) . '%',
    //     '%' . $wpdb->esc_like($search_value) . '%',
    //     $start,
    //     $length
    // );
    //     $query = $wpdb->prepare(
    //         "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage
    //          FROM $table_name 
    //          WHERE (voting_stage = %s OR voting_stage = %s) 
    //          AND visibility = %s 
    //          AND (proposal_name LIKE %s OR vote_type LIKE %s) 
    //          AND closing_date <= CURDATE()
    //          ORDER BY opening_date DESC 
    //          LIMIT %d, %d",
    //         'completed',
    //         'withdrawn',
    //         'both',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         $start,
    //         $length
    //     );

    //     // Execute query
    //     $results = $wpdb->get_results($query, ARRAY_A);
    // // print_r($results);
    //     // Count total records (for pagination)
    //     $total_records = $wpdb->get_var($wpdb->prepare(
    //         "SELECT COUNT(*) 
    //          FROM $table_name 
    //            WHERE (voting_stage = %s OR voting_stage = %s) 
    //          AND visibility = %s 
    //          AND (proposal_name LIKE %s OR vote_type LIKE %s)
    //          AND closing_date <= CURDATE()

    //          ",

    //         'completed',
    //         'withdrawn',
    //         'both',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         '%' . $wpdb->esc_like($search_value) . '%'
    //     ));



    if (!is_user_logged_in()) {
        $query = $wpdb->prepare(
            "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage,blindVoting
         FROM $table_name 
         WHERE (voting_stage = %s) 
         AND visibility = %s 
         AND (proposal_name LIKE %s OR vote_type LIKE %s) 
         AND (
        closing_date > NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ORDER BY opening_date DESC 
         LIMIT %d, %d",
           
            'normal',
            'both',
            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%',
            $start,
            $length
        );

        // Execute query
        $results = $wpdb->get_results($query, ARRAY_A);
        error_log($query);

        // print_r($results);
        // Count total records (for pagination)
        $total_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
         FROM $table_name 
           WHERE (voting_stage = %s) 
         AND visibility = %s 
         AND (proposal_name LIKE %s OR vote_type LIKE %s)
           AND (
        closing_date > NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ",

          
            'normal',
            'both',
            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%'
        ));
    }
    if (is_user_logged_in()) {

        $query = $wpdb->prepare(
            "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage,blindVoting
         FROM $table_name 
         WHERE (voting_stage = %s ) 
       
         AND (proposal_name LIKE %s OR vote_type LIKE %s) 
        AND (
        closing_date > NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ORDER BY opening_date DESC 
         LIMIT %d, %d",
           
            'normal',

            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%',
            $start,
            $length
        );

        // Execute query
        $results = $wpdb->get_results($query, ARRAY_A);
        error_log($query);

        // print_r($results);
        // Count total records (for pagination)
        $total_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
         FROM $table_name 
           WHERE (voting_stage = %s) 
      
         AND (proposal_name LIKE %s OR vote_type LIKE %s)
           AND (
        closing_date > NOW() -- Condition 1: Include where closing_date is in the past or today
        OR 
        (opening_date <= NOW() AND closing_date > NOW() AND blindVoting = 'BlindVotingtrue') -- Condition 2
     )
         ",

         
            'normal',

            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%'
        ));
    }
    // Return data as JSON
    echo json_encode([
        'draw' => intval($_POST['draw']),
        'recordsTotal' => intval($total_records),
        'recordsFiltered' => intval($total_records),
        'data' => $results,
        'user' => $user
    ]);
    wp_die();
}


function fetch_data_user_search_closevote_list()
{
    global $wpdb;

    // Retrieve pagination and search parameters
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

    // Validate username
    // $user = get_user_by('login', $username);
    // if (!$user) {
    //     wp_send_json_error('User not found');
    //     wp_die();
    // }

    // Prepare SQL query with search and LIMIT for pagination
    $table_name = $wpdb->prefix . 'voting';

    // Construct query
    // $query = $wpdb->prepare(
    //     "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box 
    //      FROM $table_name 
    //      WHERE voting_stage = %s AND visibility = %s 
    //      AND (proposal_name LIKE %s OR vote_type LIKE %s)
    //      ORDER BY opening_date DESC 
    //      LIMIT %d, %d",
    //     'completed',
    //     'both',
    //     '%' . $wpdb->esc_like($search_value) . '%',
    //     '%' . $wpdb->esc_like($search_value) . '%',
    //     $start,
    //     $length
    // );
    //     $query = $wpdb->prepare(
    //         "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage
    //          FROM $table_name 
    //          WHERE (voting_stage = %s OR voting_stage = %s) 
    //          AND visibility = %s 
    //          AND (proposal_name LIKE %s OR vote_type LIKE %s) 
    //          AND closing_date <= CURDATE()
    //          ORDER BY opening_date DESC 
    //          LIMIT %d, %d",
    //         'completed',
    //         'withdrawn',
    //         'both',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         $start,
    //         $length
    //     );

    //     // Execute query
    //     $results = $wpdb->get_results($query, ARRAY_A);
    // // print_r($results);
    //     // Count total records (for pagination)
    //     $total_records = $wpdb->get_var($wpdb->prepare(
    //         "SELECT COUNT(*) 
    //          FROM $table_name 
    //            WHERE (voting_stage = %s OR voting_stage = %s) 
    //          AND visibility = %s 
    //          AND (proposal_name LIKE %s OR vote_type LIKE %s)
    //          AND closing_date <= CURDATE()

    //          ",

    //         'completed',
    //         'withdrawn',
    //         'both',
    //         '%' . $wpdb->esc_like($search_value) . '%',
    //         '%' . $wpdb->esc_like($search_value) . '%'
    //     ));



    if (!is_user_logged_in()) {
        $query = $wpdb->prepare(
            "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage,blindVoting
         FROM $table_name 
         WHERE (voting_stage = %s) 
         AND visibility = %s 
         AND (proposal_name LIKE %s OR vote_type LIKE %s) 
         AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
      
     )
         ORDER BY closing_date DESC 
         LIMIT %d, %d",
            'completed',
           
            'both',
            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%',
            $start,
            $length
        );

        // Execute query
        $results = $wpdb->get_results($query, ARRAY_A);
        error_log($query);

        // print_r($results);
        // Count total records (for pagination)
        $total_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
         FROM $table_name 
           WHERE (voting_stage = %s) 
         AND visibility = %s 
         AND (proposal_name LIKE %s OR vote_type LIKE %s)
           AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
      
     )
         ",

            'completed',
         
            'both',
            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%'
        ));
    }
    if (is_user_logged_in()) {

        $query = $wpdb->prepare(
            "SELECT id, proposal_name, vote_type, opening_date, closing_date, vote_box, voting_stage,blindVoting
         FROM $table_name 
         WHERE (voting_stage = %s) 
       
         AND (proposal_name LIKE %s OR vote_type LIKE %s) 
        AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today
      
     )
         ORDER BY closing_date DESC 
         LIMIT %d, %d",
            'completed',
        

            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%',
            $start,
            $length
        );

        // Execute query
        $results = $wpdb->get_results($query, ARRAY_A);
        error_log($query);

        // print_r($results);
        // Count total records (for pagination)
        $total_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
         FROM $table_name 
           WHERE (voting_stage = %s) 
      
         AND (proposal_name LIKE %s OR vote_type LIKE %s)
           AND (
        closing_date <= NOW() -- Condition 1: Include where closing_date is in the past or today

     )
         ",

            'completed',
           

            '%' . $wpdb->esc_like($search_value) . '%',
            '%' . $wpdb->esc_like($search_value) . '%'
        ));
    }
    // Return data as JSON
    echo json_encode([
        'draw' => intval($_POST['draw']),
        'recordsTotal' => intval($total_records),
        'recordsFiltered' => intval($total_records),
        'data' => $results,
        'user' => $user
    ]);
    wp_die();
}