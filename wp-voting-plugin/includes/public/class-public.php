<?php
/**
 * Frontend bootstrap: shortcodes, Elementor widgets, asset enqueue.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Public {

	/** @var bool Track whether assets need enqueuing. */
	private $enqueue = false;

	public function __construct() {
		add_shortcode( 'wpvp_votes', array( $this, 'shortcode_votes' ) );
		add_shortcode( 'wpvp_vote', array( $this, 'shortcode_vote' ) );
		add_shortcode( 'wpvp_results', array( $this, 'shortcode_results' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_footer', array( $this, 'maybe_enqueue_assets' ) );

		// Elementor widget registration.
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
	}

	/*
	------------------------------------------------------------------
	 *  Asset registration.
	 * ----------------------------------------------------------------*/

	/**
	 * Register (but don't enqueue yet) CSS and JS.
	 * Assets are only enqueued when a shortcode is present.
	 */
	public function register_assets(): void {
		wp_register_style(
			'wpvp-public',
			WPVP_PLUGIN_URL . 'assets/css/public.css',
			array(),
			WPVP_VERSION
		);

		wp_register_script(
			'wpvp-public',
			WPVP_PLUGIN_URL . 'assets/js/public.js',
			array( 'jquery' ),
			WPVP_VERSION,
			true
		);

		wp_localize_script(
			'wpvp-public',
			'wpvp_public',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wpvp_public' ),
				'i18n'     => array(
					'submitting'     => __( 'Submitting...', 'wp-voting-plugin' ),
					'success'        => __( 'Your vote has been recorded.', 'wp-voting-plugin' ),
					'error'          => __( 'An error occurred. Please try again.', 'wp-voting-plugin' ),
					'confirm_submit' => __( 'Submit your vote? This cannot be changed.', 'wp-voting-plugin' ),
					'confirm_revote' => __( 'Update your vote?', 'wp-voting-plugin' ),
					'rank_required'  => __( 'Please rank at least one option.', 'wp-voting-plugin' ),
					'select_option'  => __( 'Please select an option.', 'wp-voting-plugin' ),
					'login_required' => __( 'You must be logged in to vote.', 'wp-voting-plugin' ),
				),
			)
		);
	}

	/**
	 * Enqueue assets in footer if a shortcode was rendered.
	 */
	public function maybe_enqueue_assets(): void {
		if ( $this->enqueue ) {
			wp_enqueue_style( 'wpvp-public' );
			wp_enqueue_script( 'wpvp-public' );
		}
	}

	/**
	 * Mark that frontend assets should be enqueued.
	 */
	private function flag_enqueue(): void {
		$this->enqueue = true;
		// If called late (after wp_enqueue_scripts), enqueue directly.
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			wp_enqueue_style( 'wpvp-public' );
			wp_enqueue_script( 'wpvp-public' );
		}
	}

	/*
	------------------------------------------------------------------
	 *  Shortcodes.
	 * ----------------------------------------------------------------*/

	/**
	 * [wpvp_votes] — List of votes.
	 *
	 * Attributes:
	 *  - status: open|closed|completed|all (default: open)
	 *  - limit:  number of votes to show (default: 20)
	 */
	public function shortcode_votes( $atts ): string {
		$this->flag_enqueue();

		$atts = shortcode_atts(
			array(
				'status' => 'open',
				'limit'  => 20,
			),
			$atts,
			'wpvp_votes'
		);

		$limit = max( 1, intval( $atts['limit'] ) );

		$args = array(
			'per_page' => $limit * 3, // Over-fetch to account for permission filtering.
			'page'     => 1,
		);

		if ( 'all' !== $atts['status'] ) {
			// Support comma-separated status values (e.g., "closed,completed,archived").
			if ( strpos( $atts['status'], ',' ) !== false ) {
				$statuses         = array_map( 'sanitize_key', explode( ',', $atts['status'] ) );
				$args['status']   = array_filter( $statuses );
			} else {
				$args['status'] = sanitize_key( $atts['status'] );
			}
		}

		$all_votes = WPVP_Database::get_votes( $args );
		$user_id   = get_current_user_id();

		// Filter to only votes the current user can view.
		$votes = array();
		foreach ( $all_votes as $vote ) {
			if ( WPVP_Permissions::can_view_vote( $user_id, $vote ) ) {
				$votes[] = $vote;
			}
			if ( count( $votes ) >= $limit ) {
				break;
			}
		}

		ob_start();
		include WPVP_PLUGIN_DIR . 'templates/public/vote-list.php';
		return ob_get_clean();
	}

	/**
	 * [wpvp_vote id="123"] — Single vote detail + ballot form.
	 */
	public function shortcode_vote( $atts ): string {
		$this->flag_enqueue();

		// Check URL parameter first, then shortcode attribute.
		$vote_id_from_url = isset( $_GET['wpvp_vote'] ) ? absint( $_GET['wpvp_vote'] ) : 0;

		$atts = shortcode_atts(
			array(
				'id' => $vote_id_from_url,
			),
			$atts,
			'wpvp_vote'
		);

		$vote_id = absint( $atts['id'] );
		if ( ! $vote_id ) {
			return '<div class="wpvp-vote-detail"><p class="wpvp-error">' . esc_html__( 'Invalid vote ID.', 'wp-voting-plugin' ) . '</p></div>';
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			return '<div class="wpvp-vote-detail"><p class="wpvp-error">' . esc_html__( 'Vote not found.', 'wp-voting-plugin' ) . '</p></div>';
		}

		$user_id = get_current_user_id();

		ob_start();
		include WPVP_PLUGIN_DIR . 'templates/public/vote-detail.php';
		return ob_get_clean();
	}

	/**
	 * [wpvp_results id="123"] — Results display.
	 * If no ID provided, shows a list of votes with available results.
	 */
	public function shortcode_results( $atts ): string {
		$this->flag_enqueue();

		// Check URL parameter first, then shortcode attribute.
		$vote_id_from_url = isset( $_GET['wpvp_vote'] ) ? absint( $_GET['wpvp_vote'] ) : 0;

		$atts = shortcode_atts(
			array(
				'id' => $vote_id_from_url,
			),
			$atts,
			'wpvp_results'
		);

		$vote_id = absint( $atts['id'] );

		// If no vote ID, show list of votes with available results.
		if ( ! $vote_id ) {
			return $this->render_results_list();
		}

		$user_id = get_current_user_id();
		if ( ! WPVP_Permissions::can_view_results( $user_id, $vote_id ) ) {
			return '<div class="wpvp-results-wrap"><p class="wpvp-error">' . esc_html__( 'You do not have permission to view these results.', 'wp-voting-plugin' ) . '</p></div>';
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			return '<div class="wpvp-results-wrap"><p class="wpvp-error">' . esc_html__( 'Vote not found.', 'wp-voting-plugin' ) . '</p></div>';
		}

		$results = WPVP_Database::get_results( $vote_id );

		// If no saved results, check if we should calculate live results for open votes.
		if ( ! $results && 'open' === $vote->voting_stage ) {
			$decoded_settings = json_decode( $vote->settings, true );
			$settings         = $decoded_settings ? $decoded_settings : array();
			$show_live        = ! empty( $settings['show_results_before_closing'] );

			if ( $show_live ) {
				try {
					// Calculate results on-the-fly WITHOUT saving (to avoid modifying the vote).
					$algo = WPVP_Processor::get_algorithm( $vote->voting_type );

					if ( ! $algo ) {
						error_log( sprintf( 'WPVP live results: No algorithm found for voting type "%s" (vote ID: %d)', $vote->voting_type, $vote_id ) );
					}

					if ( $algo ) {
						// Load ballots.
						$ballots = WPVP_Database::get_ballots( $vote_id );

						// Only calculate if there are ballots.
						if ( ! empty( $ballots ) ) {
							// Build the flat list of valid option strings.
							$raw_options = json_decode( $vote->voting_options, true );
							if ( is_array( $raw_options ) && ! empty( $raw_options ) ) {
								$options = array_column( $raw_options, 'text' );

								// Algorithm config.
								$config = array(
									'num_seats' => max( 1, intval( $vote->number_of_winners ) ),
								);

								// Run the algorithm (calculation only, no saving).
								$calculated = $algo->process( $ballots, $options, $config );

								// Create temporary results object with validated data.
								if ( ! $calculated || ! is_array( $calculated ) ) {
									error_log( sprintf( 'WPVP live results: Algorithm returned invalid data for vote ID %d', $vote_id ) );
								}

								if ( $calculated && is_array( $calculated ) ) {
									// Ensure all expected keys exist and are arrays.
									$final_results = isset( $calculated['final'] ) && is_array( $calculated['final'] )
										? $calculated['final']
										: array();
									$winner_data   = isset( $calculated['winner'] ) && is_array( $calculated['winner'] )
										? $calculated['winner']
										: array();
									$rounds_data   = isset( $calculated['rounds'] ) && is_array( $calculated['rounds'] )
										? $calculated['rounds']
										: array();
									$statistics    = isset( $calculated['stats'] ) && is_array( $calculated['stats'] )
										? $calculated['stats']
										: array();

									$results = (object) array(
										'vote_id'       => $vote_id,
										'total_votes'   => count( $ballots ),
										'total_voters'  => count( $ballots ),
										'final_results' => $final_results,
										'winner_data'   => $winner_data,
										'rounds_data'   => $rounds_data,
										'statistics'    => $statistics,
										'calculated_at' => current_time( 'mysql' ),
										'is_live'       => true,
									);
								}
							}
						}
					}
				} catch ( Exception $e ) {
					// Log the error but don't crash the page.
					error_log( 'WPVP live results calculation error: ' . $e->getMessage() );
				}
			}
		}

		if ( ! $results ) {
			return '<div class="wpvp-results-wrap"><p class="wpvp-error">' . esc_html__( 'Results are not yet available.', 'wp-voting-plugin' ) . '</p></div>';
		}

		// Wrap template rendering in try-catch to prevent uncaught errors.
		try {
			ob_start();
			include WPVP_PLUGIN_DIR . 'templates/public/results.php';
			return ob_get_clean();
		} catch ( Exception $e ) {
			error_log( 'WPVP results template error: ' . $e->getMessage() );
			return '<div class="wpvp-results-wrap"><p class="wpvp-error">' . esc_html__( 'An error occurred while displaying results.', 'wp-voting-plugin' ) . '</p></div>';
		}
	}

	/**
	 * Render a list of votes with available results.
	 */
	private function render_results_list(): string {
		$user_id = get_current_user_id();

		// Fetch completed and closed votes.
		$args = array(
			'per_page' => 100,
			'page'     => 1,
		);

		$all_votes = WPVP_Database::get_votes( $args );

		// Filter to votes with results that user can view.
		$votes_with_results = array();
		foreach ( $all_votes as $vote ) {
			// Include completed, closed, or open votes with "show results before closing" enabled.
			$is_completed_or_closed = in_array( $vote->voting_stage, array( 'completed', 'closed' ), true );

			$show_open_results = false;
			if ( 'open' === $vote->voting_stage ) {
				$settings = json_decode( $vote->settings, true );
				$settings = $settings ? $settings : array();
				$show_open_results = ! empty( $settings['show_results_before_closing'] );
			}

			if ( ! $is_completed_or_closed && ! $show_open_results ) {
				continue;
			}

			// Check if user can view this vote.
			if ( ! WPVP_Permissions::can_view_vote( $user_id, $vote ) ) {
				continue;
			}

			// Check if results exist (not required for open votes with "show results before closing").
			if ( ! $show_open_results ) {
				$results = WPVP_Database::get_results( $vote->id );
				if ( ! $results ) {
					continue;
				}
			}

			// Check if user can view results.
			if ( ! WPVP_Permissions::can_view_results( $user_id, $vote->id ) ) {
				continue;
			}

			$votes_with_results[] = $vote;
		}

		// Render the list.
		ob_start();
		$votes = $votes_with_results; // Template expects $votes variable.
		$is_results_context = true; // Flag for template to know this is results list context.
		include WPVP_PLUGIN_DIR . 'templates/public/vote-list.php';
		return ob_get_clean();
	}

	/*
	------------------------------------------------------------------
	 *  Elementor integration.
	 * ----------------------------------------------------------------*/

	/**
	 * Register Elementor widgets (when Elementor is active).
	 */
	public function register_elementor_widgets( $widgets_manager ): void {
		// Only register if Elementor's base widget class exists.
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once WPVP_PLUGIN_DIR . 'includes/public/class-elementor-widgets.php';

		if ( class_exists( 'WPVP_Elementor_Vote_List_Widget' ) ) {
			$widgets_manager->register( new WPVP_Elementor_Vote_List_Widget() );
		}
		if ( class_exists( 'WPVP_Elementor_Vote_Widget' ) ) {
			$widgets_manager->register( new WPVP_Elementor_Vote_Widget() );
		}
		if ( class_exists( 'WPVP_Elementor_Results_Widget' ) ) {
			$widgets_manager->register( new WPVP_Elementor_Results_Widget() );
		}
	}
}
