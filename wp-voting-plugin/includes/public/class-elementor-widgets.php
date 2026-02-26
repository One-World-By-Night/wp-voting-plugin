<?php
/**
 * Elementor widget wrappers for WP Voting Plugin shortcodes.
 *
 * Each widget wraps a shortcode so the plugin works in both
 * standard WordPress and Elementor page-builder contexts.
 *
 * Only loaded when Elementor is active (checked in class-public.php).
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Elementor widget: Vote List ([wpvp_votes])
 */
class WPVP_Elementor_Vote_List_Widget extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'wpvp_vote_list';
	}

	public function get_title(): string {
		return __( 'WP Voting — Vote List', 'wp-voting-plugin' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	public function get_categories(): array {
		return array( 'general' );
	}

	public function get_keywords(): array {
		return array( 'vote', 'voting', 'poll', 'list', 'wpvp' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Settings', 'wp-voting-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'status',
			array(
				'label'   => __( 'Status Filter', 'wp-voting-plugin' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'scheduled,open',
				'options' => array(
					'scheduled,open'            => __( 'Scheduled & Open Votes', 'wp-voting-plugin' ),
					'open'                      => __( 'Open Votes Only', 'wp-voting-plugin' ),
					'closed,completed,archived' => __( 'Vote Results (closed / completed / withdrawn / archived)', 'wp-voting-plugin' ),
					'all'                       => __( 'All Votes', 'wp-voting-plugin' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Number of Votes', 'wp-voting-plugin' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'sort_col',
			array(
				'label'   => __( 'Default Sort Column', 'wp-voting-plugin' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'start_date',
				'options' => array(
					'title'      => __( 'Title', 'wp-voting-plugin' ),
					'type'       => __( 'Proposal Type', 'wp-voting-plugin' ),
					'votes'      => __( 'Votes', 'wp-voting-plugin' ),
					'start_date' => __( 'Start Date', 'wp-voting-plugin' ),
					'end_date'   => __( 'End Date', 'wp-voting-plugin' ),
					'status'     => __( 'Status', 'wp-voting-plugin' ),
				),
			)
		);

		$this->add_control(
			'sort_dir',
			array(
				'label'   => __( 'Default Sort Direction', 'wp-voting-plugin' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'asc',
				'options' => array(
					'asc'  => __( 'Ascending', 'wp-voting-plugin' ),
					'desc' => __( 'Descending', 'wp-voting-plugin' ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo do_shortcode(
			sprintf(
				'[wpvp_votes status="%s" limit="%d" sort_col="%s" sort_dir="%s"]',
				esc_attr( $settings['status'] ),
				intval( $settings['limit'] ),
				esc_attr( $settings['sort_col'] ),
				esc_attr( $settings['sort_dir'] )
			)
		);
	}
}

/**
 * Elementor widget: Single Vote ([wpvp_vote])
 *
 * When Vote ID is left at 0 (the default), the widget reads the vote ID
 * from the URL parameter ?wpvp_vote=X — this is the standard "dynamic"
 * mode used on the Cast Vote page. Set a specific ID to embed a
 * particular vote on any page.
 */
class WPVP_Elementor_Vote_Widget extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'wpvp_vote';
	}

	public function get_title(): string {
		return __( 'WP Voting — Vote Detail', 'wp-voting-plugin' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	public function get_categories(): array {
		return array( 'general' );
	}

	public function get_keywords(): array {
		return array( 'vote', 'voting', 'ballot', 'poll', 'wpvp' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Settings', 'wp-voting-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'vote_id',
			array(
				'label'       => __( 'Vote ID', 'wp-voting-plugin' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 0,
				'description' => __( 'Enter a specific vote ID, or leave at 0 to read from the URL parameter (?wpvp_vote=X).', 'wp-voting-plugin' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$id       = intval( $settings['vote_id'] );

		if ( $id > 0 ) {
			echo do_shortcode( sprintf( '[wpvp_vote id="%d"]', $id ) );
			return;
		}

		// Dynamic mode: let the shortcode read from the URL parameter.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div class="wpvp-elementor-placeholder" style="padding:20px;background:#f0f0f0;border:1px dashed #ccc;text-align:center;">';
			echo '<p><strong>' . esc_html__( 'WP Voting — Vote Detail', 'wp-voting-plugin' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'Dynamic mode: vote ID will be read from the URL parameter (?wpvp_vote=X) on the front-end.', 'wp-voting-plugin' ) . '</p>';
			echo '</div>';
			return;
		}

		echo do_shortcode( '[wpvp_vote]' );
	}
}

/**
 * Elementor widget: Vote Results ([wpvp_results])
 *
 * When Vote ID is left at 0 (the default), the widget reads the vote ID
 * from the URL parameter ?wpvp_vote=X — this is the standard "dynamic"
 * mode used on the Vote Results page. When no URL parameter is present
 * either, it renders a list of votes with available results. Set a
 * specific ID to embed a particular vote's results on any page.
 */
class WPVP_Elementor_Results_Widget extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'wpvp_results';
	}

	public function get_title(): string {
		return __( 'WP Voting — Results', 'wp-voting-plugin' );
	}

	public function get_icon(): string {
		return 'eicon-bar-chart';
	}

	public function get_categories(): array {
		return array( 'general' );
	}

	public function get_keywords(): array {
		return array( 'vote', 'results', 'chart', 'poll', 'wpvp' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Settings', 'wp-voting-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'vote_id',
			array(
				'label'       => __( 'Vote ID', 'wp-voting-plugin' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 0,
				'description' => __( 'Enter a specific vote ID, or leave at 0 to read from the URL parameter (?wpvp_vote=X). When no ID is available, a list of votes with results is shown.', 'wp-voting-plugin' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$id       = intval( $settings['vote_id'] );

		if ( $id > 0 ) {
			echo do_shortcode( sprintf( '[wpvp_results id="%d"]', $id ) );
			return;
		}

		// Dynamic mode: let the shortcode read from the URL parameter.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div class="wpvp-elementor-placeholder" style="padding:20px;background:#f0f0f0;border:1px dashed #ccc;text-align:center;">';
			echo '<p><strong>' . esc_html__( 'WP Voting — Results', 'wp-voting-plugin' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'Dynamic mode: vote ID will be read from the URL parameter (?wpvp_vote=X) on the front-end. If no ID is present, a results list is shown.', 'wp-voting-plugin' ) . '</p>';
			echo '</div>';
			return;
		}

		echo do_shortcode( '[wpvp_results]' );
	}
}
