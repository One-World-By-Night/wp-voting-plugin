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
				'default' => 'open',
				'options' => array(
					'all'       => __( 'All', 'wp-voting-plugin' ),
					'open'      => __( 'Open', 'wp-voting-plugin' ),
					'closed'    => __( 'Closed', 'wp-voting-plugin' ),
					'completed' => __( 'Completed', 'wp-voting-plugin' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Number of Votes', 'wp-voting-plugin' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 20,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		echo do_shortcode(
			sprintf(
				'[wpvp_votes status="%s" limit="%d"]',
				esc_attr( $settings['status'] ),
				intval( $settings['limit'] )
			)
		);
	}
}

/**
 * Elementor widget: Single Vote ([wpvp_vote])
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
				'description' => __( 'Enter the ID of the vote to display.', 'wp-voting-plugin' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$id       = intval( $settings['vote_id'] );

		if ( ! $id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="wpvp-error">' . esc_html__( 'Please enter a Vote ID in the widget settings.', 'wp-voting-plugin' ) . '</p>';
			}
			return;
		}

		echo do_shortcode( sprintf( '[wpvp_vote id="%d"]', $id ) );
	}
}

/**
 * Elementor widget: Vote Results ([wpvp_results])
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
				'description' => __( 'Enter the ID of the vote whose results you want to display.', 'wp-voting-plugin' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$id       = intval( $settings['vote_id'] );

		if ( ! $id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="wpvp-error">' . esc_html__( 'Please enter a Vote ID in the widget settings.', 'wp-voting-plugin' ) . '</p>';
			}
			return;
		}

		echo do_shortcode( sprintf( '[wpvp_results id="%d"]', $id ) );
	}
}
