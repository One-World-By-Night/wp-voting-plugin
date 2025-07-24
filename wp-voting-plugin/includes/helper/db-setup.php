<?php

/**
 * File: includes/helper/db-setup.php
 * Database schema - Three table strategy
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Create plugin database tables
 */
function wpvp_create_tables()
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // Table 1: Votes - Configuration and options
    $sql_votes = "CREATE TABLE {$wpdb->prefix}wpvp_votes (
        id INT NOT NULL AUTO_INCREMENT,
        -- Basic info
        proposal_name VARCHAR(255) NOT NULL,
        proposal_description LONGTEXT,
        
        -- Voting configuration
        voting_type VARCHAR(50) NOT NULL DEFAULT 'single',
        voting_options LONGTEXT NOT NULL,
        number_of_winners INT DEFAULT 1,
        
        -- Access control (using accessschema-client)
        allowed_roles TEXT,
        visibility VARCHAR(50) DEFAULT 'private',
        
        -- Dates and status
        voting_stage VARCHAR(50) NOT NULL DEFAULT 'draft',
        created_by INT NOT NULL,
        created_at DATETIME NOT NULL,
        opening_date DATETIME,
        closing_date DATETIME,
        
        -- Additional settings
        settings LONGTEXT,
        
        PRIMARY KEY (id),
        KEY created_by (created_by),
        KEY voting_stage (voting_stage),
        KEY date_range (opening_date, closing_date)
    ) $charset_collate;";

    // Table 2: Ballots - Individual votes cast
    $sql_ballots = "CREATE TABLE {$wpdb->prefix}wpvp_ballots (
        id INT NOT NULL AUTO_INCREMENT,
        vote_id INT NOT NULL,
        user_id INT NOT NULL,
        
        -- Vote data (JSON for flexibility with different voting types)
        ballot_data LONGTEXT NOT NULL,
        
        -- Metadata
        voted_at DATETIME NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        
        PRIMARY KEY (id),
        UNIQUE KEY unique_vote (vote_id, user_id),
        KEY vote_id (vote_id),
        KEY user_id (user_id),
        KEY voted_at (voted_at)
    ) $charset_collate;";

    // Table 3: Results - Calculated metrics and rounds
    $sql_results = "CREATE TABLE {$wpdb->prefix}wpvp_results (
        id INT NOT NULL AUTO_INCREMENT,
        vote_id INT NOT NULL,
        
        -- Summary data
        total_votes INT DEFAULT 0,
        total_voters INT DEFAULT 0,
        
        -- Results (JSON for flexibility)
        final_results LONGTEXT,
        winner_data LONGTEXT,
        
        -- Round-by-round data for IRV/STV (JSON)
        rounds_data LONGTEXT,
        
        -- Vote distribution and statistics (JSON)
        statistics LONGTEXT,
        
        -- Processing metadata
        calculated_at DATETIME NOT NULL,
        calculation_time FLOAT DEFAULT 0,
        validation_status VARCHAR(50) DEFAULT 'valid',
        validation_notes TEXT,
        
        PRIMARY KEY (id),
        UNIQUE KEY vote_id (vote_id),
        KEY calculated_at (calculated_at)
    ) $charset_collate;";

    // Execute table creation
    dbDelta($sql_votes);
    dbDelta($sql_ballots);
    dbDelta($sql_results);

    // Store version for future migrations
    update_option('wpvp_db_version', '2.0.0');
}

/**
 * Sample data structures for JSON fields
 */
class WPVP_Data_Structures
{

    /**
     * voting_options structure in wpvp_votes
     */
    public static function voting_options_sample()
    {
        return [
            ['id' => 1, 'text' => 'Option A', 'description' => 'Description for A'],
            ['id' => 2, 'text' => 'Option B', 'description' => 'Description for B'],
            ['id' => 3, 'text' => 'Option C', 'description' => 'Description for C']
        ];
    }

    /**
     * ballot_data structure in wpvp_ballots
     */
    public static function ballot_data_samples()
    {
        return [
            // Single choice
            'single' => 'Option A',

            // Multiple choice
            'multiple' => ['Option A', 'Option C'],

            // Ranked choice (IRV/STV)
            'ranked' => ['Option B', 'Option A', 'Option C'],

            // Condorcet pairs
            'condorcet' => [
                'A_vs_B' => 'A',
                'A_vs_C' => 'C',
                'B_vs_C' => 'B'
            ]
        ];
    }

    /**
     * final_results structure in wpvp_results
     */
    public static function final_results_sample()
    {
        return [
            'vote_counts' => [
                'Option A' => 45,
                'Option B' => 32,
                'Option C' => 23
            ],
            'percentages' => [
                'Option A' => 45.0,
                'Option B' => 32.0,
                'Option C' => 23.0
            ],
            'rankings' => [
                1 => 'Option A',
                2 => 'Option B',
                3 => 'Option C'
            ]
        ];
    }

    /**
     * rounds_data structure for IRV/STV
     */
    public static function rounds_data_sample()
    {
        return [
            [
                'round' => 1,
                'counts' => ['A' => 40, 'B' => 35, 'C' => 25],
                'eliminated' => [],
                'transfers' => []
            ],
            [
                'round' => 2,
                'counts' => ['A' => 48, 'B' => 52],
                'eliminated' => ['C'],
                'transfers' => [
                    ['from' => 'C', 'to' => 'A', 'count' => 8],
                    ['from' => 'C', 'to' => 'B', 'count' => 17]
                ]
            ]
        ];
    }
}

/**
 * Helper functions for working with the schema
 */

/**
 * Save a vote configuration
 */
function wpvp_save_vote($data)
{
    global $wpdb;

    $insert_data = [
        'proposal_name' => $data['proposal_name'],
        'proposal_description' => $data['proposal_description'] ?? '',
        'voting_type' => $data['voting_type'],
        'voting_options' => wp_json_encode($data['voting_options']),
        'number_of_winners' => $data['number_of_winners'] ?? 1,
        'allowed_roles' => wp_json_encode($data['allowed_roles'] ?? []),
        'visibility' => $data['visibility'] ?? 'private',
        'voting_stage' => 'draft',
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql'),
        'opening_date' => $data['opening_date'] ?? null,
        'closing_date' => $data['closing_date'] ?? null,
        'settings' => wp_json_encode($data['settings'] ?? [])
    ];

    return $wpdb->insert($wpdb->prefix . 'wpvp_votes', $insert_data);
}

/**
 * Cast a ballot
 */
function wpvp_cast_ballot($vote_id, $ballot_data)
{
    global $wpdb;

    $insert_data = [
        'vote_id' => $vote_id,
        'user_id' => get_current_user_id(),
        'ballot_data' => wp_json_encode($ballot_data),
        'voted_at' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];

    return $wpdb->insert($wpdb->prefix . 'wpvp_ballots', $insert_data);
}

/**
 * Save calculated results
 */
function wpvp_save_results($vote_id, $results)
{
    global $wpdb;

    $start_time = microtime(true);

    // Get vote counts
    $total_voters = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}wpvp_ballots WHERE vote_id = %d",
        $vote_id
    ));

    $insert_data = [
        'vote_id' => $vote_id,
        'total_votes' => $results['total_votes'] ?? 0,
        'total_voters' => $total_voters,
        'final_results' => wp_json_encode($results['final_results'] ?? []),
        'winner_data' => wp_json_encode([
            'winner' => $results['winner'] ?? null,
            'winner_votes' => $results['winner_votes'] ?? 0,
            'tie' => $results['tie'] ?? false,
            'tied_candidates' => $results['tied_candidates'] ?? []
        ]),
        'rounds_data' => wp_json_encode($results['rounds'] ?? []),
        'statistics' => wp_json_encode([
            'vote_distribution' => $results['vote_counts'] ?? [],
            'percentages' => $results['percentages'] ?? [],
            'eliminated_order' => $results['eliminated_candidates'] ?? [],
            'event_log' => $results['event_log'] ?? []
        ]),
        'calculated_at' => current_time('mysql'),
        'calculation_time' => microtime(true) - $start_time,
        'validation_status' => $results['validation']['is_valid'] ?? 'valid' ? 'valid' : 'invalid',
        'validation_notes' => wp_json_encode($results['validation'] ?? [])
    ];

    // Use INSERT ... ON DUPLICATE KEY UPDATE
    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}wpvp_results 
        (vote_id, total_votes, total_voters, final_results, winner_data, rounds_data, statistics, calculated_at, calculation_time, validation_status, validation_notes)
        VALUES (%d, %d, %d, %s, %s, %s, %s, %s, %f, %s, %s)
        ON DUPLICATE KEY UPDATE
        total_votes = VALUES(total_votes),
        total_voters = VALUES(total_voters),
        final_results = VALUES(final_results),
        winner_data = VALUES(winner_data),
        rounds_data = VALUES(rounds_data),
        statistics = VALUES(statistics),
        calculated_at = VALUES(calculated_at),
        calculation_time = VALUES(calculation_time),
        validation_status = VALUES(validation_status),
        validation_notes = VALUES(validation_notes)",
        $vote_id,
        $insert_data['total_votes'],
        $insert_data['total_voters'],
        $insert_data['final_results'],
        $insert_data['winner_data'],
        $insert_data['rounds_data'],
        $insert_data['statistics'],
        $insert_data['calculated_at'],
        $insert_data['calculation_time'],
        $insert_data['validation_status'],
        $insert_data['validation_notes']
    ));

    return !empty($wpdb->last_error) ? false : true;
}

/**
 * Get ballots for processing
 */
function wpvp_get_ballots_for_processing($vote_id)
{
    global $wpdb;

    $ballots = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, u.user_login, u.display_name 
        FROM {$wpdb->prefix}wpvp_ballots b
        JOIN {$wpdb->users} u ON b.user_id = u.ID
        WHERE b.vote_id = %d
        ORDER BY b.voted_at ASC",
        $vote_id
    ), ARRAY_A);

    // Decode ballot data
    foreach ($ballots as &$ballot) {
        $ballot['ballot_data'] = json_decode($ballot['ballot_data'], true);

        // Format for processing functions (backward compatible)
        $ballot['userName'] = $ballot['user_login'];
        $ballot['userVote'] = $ballot['ballot_data'];
    }

    return $ballots;
}

/**
 * Drop all plugin tables
 */
function wpvp_drop_tables()
{
    global $wpdb;

    $tables = ['wpvp_votes', 'wpvp_ballots', 'wpvp_results'];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
    }

    delete_option('wpvp_db_version');
}
