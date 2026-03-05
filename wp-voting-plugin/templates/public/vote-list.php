<?php
/**
 * Template: Public vote list.
 *
 * Variables available:
 *   $votes  array  List of vote objects from WPVP_Database::get_votes().
 *   $atts   array  Shortcode attributes.
 *
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpvp-vote-list">
	<?php if ( empty( $votes ) ) : ?>
		<p class="wpvp-vote-list__empty">
			<?php esc_html_e( 'No votes found.', 'wp-voting-plugin' ); ?>
		</p>
	<?php else : ?>
		<?php
		$pp              = isset( $per_page ) ? (int) $per_page : 10;
		$classifications = WPVP_Database::get_classifications();

		// Build unique sorted list of proposers from the current vote set.
		$proposers = array();
		foreach ( $votes as $v ) {
			if ( ! empty( $v->proposed_by ) && ! in_array( $v->proposed_by, $proposers, true ) ) {
				$proposers[] = $v->proposed_by;
			}
		}
		sort( $proposers );
		?>
		<div class="wpvp-vote-list__toolbar">
			<div class="wpvp-per-page">
				<label>
					<?php esc_html_e( 'Show', 'wp-voting-plugin' ); ?>
					<select class="wpvp-per-page-select">
						<?php foreach ( array( 10, 20, 50 ) as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>"<?php selected( $pp, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php esc_html_e( 'per page', 'wp-voting-plugin' ); ?>
				</label>
			</div>
		</div>
		<table class="wpvp-vote-table wpvp-sortable" data-per-page="<?php echo esc_attr( $pp ); ?>" data-default-sort-col="<?php echo esc_attr( isset( $sort_col_index ) ? $sort_col_index : 3 ); ?>" data-default-sort-dir="<?php echo esc_attr( isset( $sort_dir ) ? $sort_dir : 'asc' ); ?>">
			<thead>
				<tr>
					<th class="wpvp-sortable__col" data-col="0"><?php esc_html_e( 'Title', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="1"><?php esc_html_e( 'Proposed By', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="2"><?php esc_html_e( 'Type', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="3"><?php esc_html_e( 'Start', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="4"><?php esc_html_e( 'End', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="5"><?php esc_html_e( 'Votes', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="6"><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="7"><?php esc_html_e( 'Result', 'wp-voting-plugin' ); ?></th>
				</tr>
				<tr class="wpvp-filter-row">
					<td>
						<input type="text" class="wpvp-vote-search__input" placeholder="<?php esc_attr_e( 'Search...', 'wp-voting-plugin' ); ?>">
					</td>
					<td>
						<div class="wpvp-dropdown" data-filter="proposed_by">
							<button type="button" class="wpvp-dropdown__toggle"><?php esc_html_e( 'All', 'wp-voting-plugin' ); ?></button>
							<div class="wpvp-dropdown__menu">
								<?php foreach ( $proposers as $proposer ) : ?>
									<label class="wpvp-dropdown__item">
										<input type="checkbox" value="<?php echo esc_attr( $proposer ); ?>">
										<?php echo esc_html( $proposer ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</td>
					<td>
						<div class="wpvp-dropdown" data-filter="classification">
							<button type="button" class="wpvp-dropdown__toggle"><?php esc_html_e( 'All', 'wp-voting-plugin' ); ?></button>
							<div class="wpvp-dropdown__menu">
								<?php foreach ( $classifications as $c ) : ?>
									<label class="wpvp-dropdown__item">
										<input type="checkbox" value="<?php echo esc_attr( $c->classification_name ); ?>">
										<?php echo esc_html( $c->classification_name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</td>
					<td></td>
					<td></td>
					<td></td>
					<td>
						<button type="button" class="wpvp-clear-filters" style="display:none;"><?php esc_html_e( 'Clear', 'wp-voting-plugin' ); ?></button>
					</td>
					<td>
						<div class="wpvp-dropdown" data-filter="outcome">
							<button type="button" class="wpvp-dropdown__toggle"><?php esc_html_e( 'All', 'wp-voting-plugin' ); ?></button>
							<div class="wpvp-dropdown__menu">
								<label class="wpvp-dropdown__item"><input type="checkbox" value="winner"><?php esc_html_e( 'Winner(s)', 'wp-voting-plugin' ); ?></label>
								<label class="wpvp-dropdown__item"><input type="checkbox" value="passed"><?php esc_html_e( 'Passed', 'wp-voting-plugin' ); ?></label>
								<label class="wpvp-dropdown__item"><input type="checkbox" value="failed"><?php esc_html_e( 'Failed / Objected', 'wp-voting-plugin' ); ?></label>
								<label class="wpvp-dropdown__item"><input type="checkbox" value="tied"><?php esc_html_e( 'Tied', 'wp-voting-plugin' ); ?></label>
							</div>
						</div>
					</td>
				</tr>
			</thead>
			<tbody>
				<?php
				$short_fmt = 'j M Y H:i';
				$stages    = WPVP_Database::get_vote_stages();

				foreach ( $votes as $vote ) :
					$stage_label = $stages[ $vote->voting_stage ] ?? $vote->voting_stage;

					$page_ids        = get_option( 'wpvp_page_ids', array() );
					$detail_page     = ! empty( $page_ids['cast-vote'] ) ? $page_ids['cast-vote'] : 0;
					$results_page    = ! empty( $page_ids['vote-results'] ) ? $page_ids['vote-results'] : 0;
					$current_user_id = get_current_user_id();
					$is_results_context = isset( $is_results_context ) && $is_results_context;

					if ( $detail_page ) {
						$url = add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $detail_page ) );
					} else {
						$url = '#';
					}

					if ( $results_page ) {
						$results_url = add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $results_page ) );
					} else {
						$results_url = $url;
					}

					// Determine outcome + display text from stored results.
					$vote_outcome  = '';
					$result_text   = '';
					if ( in_array( $vote->voting_stage, array( 'closed', 'completed', 'withdrawn' ), true ) ) {
						$vote_results = WPVP_Database::get_results( (int) $vote->id );
						if ( $vote_results ) {
							$wd = $vote_results->winner_data ? $vote_results->winner_data : array();
							$fr = $vote_results->final_results ? $vote_results->final_results : array();
							if ( ! empty( $wd['tie'] ) ) {
								$vote_outcome = 'tied';
								$result_text  = __( 'Tied', 'wp-voting-plugin' );
							} elseif ( isset( $fr['passed'] ) ) {
								$vote_outcome = $fr['passed'] ? 'passed' : 'failed';
								$result_text  = $fr['passed'] ? __( 'Passed', 'wp-voting-plugin' ) : __( 'Objected', 'wp-voting-plugin' );
							} elseif ( ! empty( $wd['winners'] ) ) {
								$vote_outcome = 'winner';
								$result_text  = esc_html( implode( ', ', $wd['winners'] ) );
							} elseif ( ! empty( $wd['winner'] ) ) {
								$vote_outcome = 'winner';
								$result_text  = esc_html( $wd['winner'] );
							}
						}
					}

					$type_list    = json_decode( $vote->classification, true );
					$type_display = is_array( $type_list ) && ! empty( $type_list ) ? implode( ', ', $type_list ) : '';
					$ballot_count = WPVP_Database::get_ballot_count( (int) $vote->id );
					$open_ts      = $vote->opening_date ? WPVP_Database::local_timestamp( $vote->opening_date ) : 0;
					$close_ts     = $vote->closing_date ? WPVP_Database::local_timestamp( $vote->closing_date ) : 0;

					// URL for lightbox (results page for closed votes, detail page otherwise).
					$lightbox_url = in_array( $vote->voting_stage, array( 'closed', 'completed', 'withdrawn', 'archived' ), true )
						? $results_url
						: $url;

					// Direct link for "open in new tab" arrow.
					$direct_url = $lightbox_url;
					?>
					<tr class="wpvp-vote-row wpvp-vote-row--<?php echo esc_attr( $vote->voting_stage ); ?>"
						data-proposed-by="<?php echo esc_attr( strtolower( $vote->proposed_by ?? '' ) ); ?>"
						data-outcome="<?php echo esc_attr( $vote_outcome ); ?>">
						<td class="wpvp-vote-table__title" data-sort-value="<?php echo esc_attr( strtolower( $vote->proposal_name ) ); ?>">
							<a href="#" data-lightbox-url="<?php echo esc_url( $lightbox_url ); ?>" class="wpvp-vote-table__link">
								<?php echo esc_html( $vote->proposal_name ); ?>
							</a>
							<?php if ( $direct_url && '#' !== $direct_url ) : ?>
								<a href="<?php echo esc_url( $direct_url ); ?>" target="_blank" rel="noopener"
								   class="wpvp-vote-table__new-tab" title="<?php esc_attr_e( 'Open in new tab', 'wp-voting-plugin' ); ?>">&#8599;</a>
							<?php endif; ?>
						</td>
						<td class="wpvp-vote-table__proposed-by" data-label="<?php esc_attr_e( 'Proposed By', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( strtolower( $vote->proposed_by ?? '' ) ); ?>">
							<?php echo ! empty( $vote->proposed_by ) ? esc_html( $vote->proposed_by ) : '—'; ?>
						</td>
						<td class="wpvp-vote-table__type" data-label="<?php esc_attr_e( 'Type', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( strtolower( $type_display ) ); ?>">
							<?php echo $type_display ? esc_html( $type_display ) : '—'; ?>
						</td>
						<td class="wpvp-vote-table__date" data-label="<?php esc_attr_e( 'Start', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( $open_ts ); ?>">
							<?php echo $vote->opening_date ? esc_html( wp_date( $short_fmt, $open_ts ) ) : '—'; ?>
						</td>
						<td class="wpvp-vote-table__date" data-label="<?php esc_attr_e( 'End', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( $close_ts ); ?>">
							<?php echo $vote->closing_date ? esc_html( wp_date( $short_fmt, $close_ts ) ) : '—'; ?>
						</td>
						<td class="wpvp-vote-table__votes" data-label="<?php esc_attr_e( 'Votes', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( $ballot_count ); ?>">
							<?php echo esc_html( $ballot_count ); ?>
						</td>
						<td class="wpvp-vote-table__status" data-sort-value="<?php echo esc_attr( $vote->voting_stage ); ?>">
							<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
								<?php echo esc_html( $stage_label ); ?>
							</span>
						</td>
						<td class="wpvp-vote-table__result" data-label="<?php esc_attr_e( 'Result', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( strtolower( $result_text ) ); ?>">
							<?php if ( $result_text ) : ?>
								<span class="wpvp-result wpvp-result--<?php echo esc_attr( $vote_outcome ); ?>">
									<?php echo $result_text; ?>
								</span>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="wpvp-pagination"></div>
	<?php endif; ?>
</div>
