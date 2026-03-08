<?php
/**
 * Template: Public vote list with server-side sort + pagination.
 *
 * Variables available:
 *   $votes      array  Current page of vote objects (pre-sorted, pre-filtered).
 *   $pagination array  Pagination state (current_page, total_pages, total_votes, per_page, sort_col, sort_dir, search, proposer, type, outcome, proposers, types).
 *   $atts       array  Shortcode attributes.
 */

defined( 'ABSPATH' ) || exit;

// Use $pagination if set (server-side mode), otherwise fall back to legacy JS mode.
$server_side = isset( $pagination ) && is_array( $pagination );
?>

<div class="wpvp-vote-list">
	<?php if ( empty( $votes ) && ( ! $server_side || ( empty( $pagination['search'] ) && empty( $pagination['proposer'] ) && empty( $pagination['type'] ) && empty( $pagination['outcome'] ) ) ) ) : ?>
		<p class="wpvp-vote-list__empty">
			<?php esc_html_e( 'No votes found.', 'wp-voting-plugin' ); ?>
		</p>
	<?php else : ?>
		<?php
		$pp              = $server_side ? $pagination['per_page'] : ( isset( $per_page ) ? (int) $per_page : 10 );
		$classifications = WPVP_Database::get_classifications();
		$base_url        = remove_query_arg( array( 'wpvp_page', 'wpvp_sort', 'wpvp_dir', 'wpvp_pp', 'wpvp_s', 'wpvp_proposer', 'wpvp_type', 'wpvp_outcome' ) );

		if ( $server_side ) {
			$current_sort = $pagination['sort_col'];
			$current_dir  = $pagination['sort_dir'];
			$proposers    = $pagination['proposers'];
			$type_list    = $pagination['types'];
		} else {
			$current_sort = '';
			$current_dir  = 'asc';
			$proposers    = array();
			foreach ( $votes as $v ) {
				if ( ! empty( $v->proposed_by ) && ! in_array( $v->proposed_by, $proposers, true ) ) {
					$proposers[] = $v->proposed_by;
				}
			}
			sort( $proposers );
			$type_list = array();
		}

		/**
		 * Build a sort URL for a column header.
		 */
		$sort_url = function ( $col ) use ( $base_url, $current_sort, $current_dir, $pagination ) {
			$dir = ( $current_sort === $col && 'asc' === $current_dir ) ? 'desc' : 'asc';
			$params = array( 'wpvp_sort' => $col, 'wpvp_dir' => $dir, 'wpvp_page' => 1, 'wpvp_pp' => $pagination['per_page'] );
			if ( ! empty( $pagination['search'] ) )   $params['wpvp_s']        = $pagination['search'];
			if ( ! empty( $pagination['proposer'] ) )  $params['wpvp_proposer'] = $pagination['proposer'];
			if ( ! empty( $pagination['type'] ) )      $params['wpvp_type']     = $pagination['type'];
			if ( ! empty( $pagination['outcome'] ) )   $params['wpvp_outcome']  = $pagination['outcome'];
			return add_query_arg( $params, $base_url );
		};

		/**
		 * Build a page URL preserving current sort/filter state.
		 */
		$page_url = function ( $page ) use ( $base_url, $pagination ) {
			$params = array( 'wpvp_page' => $page, 'wpvp_pp' => $pagination['per_page'], 'wpvp_sort' => $pagination['sort_col'], 'wpvp_dir' => $pagination['sort_dir'] );
			if ( ! empty( $pagination['search'] ) )   $params['wpvp_s']        = $pagination['search'];
			if ( ! empty( $pagination['proposer'] ) )  $params['wpvp_proposer'] = $pagination['proposer'];
			if ( ! empty( $pagination['type'] ) )      $params['wpvp_type']     = $pagination['type'];
			if ( ! empty( $pagination['outcome'] ) )   $params['wpvp_outcome']  = $pagination['outcome'];
			return add_query_arg( $params, $base_url );
		};

		$sort_indicator = function ( $col ) use ( $current_sort, $current_dir ) {
			if ( $current_sort !== $col ) return '';
			return 'asc' === $current_dir ? ' &#9650;' : ' &#9660;';
		};

		$has_filters = $server_side && ( $pagination['search'] || $pagination['proposer'] || ! empty( $pagination['type'] ) || ! empty( $pagination['outcome'] ) );
		$active_types    = $server_side ? (array) $pagination['type'] : array();
		$active_outcomes = $server_side ? (array) $pagination['outcome'] : array();
		?>

		<?php if ( $server_side ) : ?>
		<form id="wpvp-filter-form" class="wpvp-vote-list__toolbar" method="get" action="<?php echo esc_url( $base_url ); ?>">
			<input type="hidden" name="wpvp_sort" value="<?php echo esc_attr( $pagination['sort_col'] ); ?>">
			<input type="hidden" name="wpvp_dir" value="<?php echo esc_attr( $pagination['sort_dir'] ); ?>">

			<div class="wpvp-toolbar__filters">
				<button type="submit" class="wpvp-filter-btn"><?php esc_html_e( 'Filter', 'wp-voting-plugin' ); ?></button>
				<?php if ( $has_filters ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'wpvp_sort' => $pagination['sort_col'], 'wpvp_dir' => $pagination['sort_dir'], 'wpvp_pp' => $pp ), $base_url ) ); ?>" class="wpvp-filter-clear"><?php esc_html_e( 'Clear All', 'wp-voting-plugin' ); ?></a>
				<?php endif; ?>
			</div>

			<div class="wpvp-per-page">
				<label>
					<?php esc_html_e( 'Show', 'wp-voting-plugin' ); ?>
					<select name="wpvp_pp" class="wpvp-per-page-select" onchange="this.form.submit()">
						<?php foreach ( array( 10, 20, 50, 100 ) as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>"<?php selected( $pp, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php esc_html_e( 'per page', 'wp-voting-plugin' ); ?>
				</label>
			</div>
		</form>
		<?php else : ?>
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
		<?php endif; ?>

		<table class="wpvp-vote-table<?php echo $server_side ? ' wpvp-server-paged' : ' wpvp-sortable'; ?>" data-per-page="<?php echo esc_attr( $pp ); ?>"<?php if ( ! $server_side ) : ?> data-default-sort-col="<?php echo esc_attr( isset( $sort_col_index ) ? $sort_col_index : 3 ); ?>" data-default-sort-dir="<?php echo esc_attr( isset( $sort_dir ) ? $sort_dir : 'asc' ); ?>"<?php endif; ?>>
			<thead>
				<tr>
					<?php if ( $server_side ) : ?>
						<th><a href="<?php echo esc_url( $sort_url( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'title' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'proposed_by' ) ); ?>"><?php esc_html_e( 'Proposed By', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'proposed_by' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'type' ) ); ?>"><?php esc_html_e( 'Type', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'type' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'start_date' ) ); ?>"><?php esc_html_e( 'Start', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'start_date' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'end_date' ) ); ?>"><?php esc_html_e( 'End', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'end_date' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'votes' ) ); ?>"><?php esc_html_e( 'Votes', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'votes' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'status' ) ); ?>"><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'status' ); ?></a></th>
						<th><a href="<?php echo esc_url( $sort_url( 'result' ) ); ?>"><?php esc_html_e( 'Result', 'wp-voting-plugin' ); ?><?php echo $sort_indicator( 'result' ); ?></a></th>
					<?php else : ?>
						<th class="wpvp-sortable__col" data-col="0"><?php esc_html_e( 'Title', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="1"><?php esc_html_e( 'Proposed By', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="2"><?php esc_html_e( 'Type', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="3"><?php esc_html_e( 'Start', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="4"><?php esc_html_e( 'End', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="5"><?php esc_html_e( 'Votes', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="6"><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?></th>
						<th class="wpvp-sortable__col" data-col="7"><?php esc_html_e( 'Result', 'wp-voting-plugin' ); ?></th>
					<?php endif; ?>
				</tr>
				<?php if ( $server_side ) : ?>
				<tr class="wpvp-filter-row">
					<td>
						<input type="text" name="wpvp_s" class="wpvp-vote-search__input" placeholder="<?php esc_attr_e( 'Search...', 'wp-voting-plugin' ); ?>" value="<?php echo esc_attr( $pagination['search'] ); ?>" form="wpvp-filter-form">
					</td>
					<td>
						<select name="wpvp_proposer" class="wpvp-filter-select" form="wpvp-filter-form" onchange="document.getElementById('wpvp-filter-form').submit()">
							<option value=""><?php esc_html_e( 'All', 'wp-voting-plugin' ); ?></option>
							<?php foreach ( $proposers as $p ) : ?>
								<option value="<?php echo esc_attr( $p ); ?>"<?php selected( $pagination['proposer'], $p ); ?>><?php echo esc_html( $p ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<div class="wpvp-dropdown" data-filter="type">
							<button type="button" class="wpvp-dropdown__toggle">
								<?php echo ! empty( $active_types ) ? sprintf( '%d selected', count( $active_types ) ) : esc_html__( 'All', 'wp-voting-plugin' ); ?>
							</button>
							<div class="wpvp-dropdown__menu">
								<?php foreach ( $type_list as $t ) : ?>
									<label class="wpvp-dropdown__item">
										<input type="checkbox" name="wpvp_type[]" value="<?php echo esc_attr( $t ); ?>" form="wpvp-filter-form"<?php checked( in_array( $t, $active_types, true ) ); ?>>
										<?php echo esc_html( $t ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</td>
					<td colspan="3"></td>
					<td>
						<button type="button" class="wpvp-clear-filters"<?php echo ! $has_filters ? ' style="display:none;"' : ''; ?>><?php esc_html_e( 'Clear', 'wp-voting-plugin' ); ?></button>
					</td>
					<td>
						<div class="wpvp-dropdown" data-filter="outcome">
							<button type="button" class="wpvp-dropdown__toggle">
								<?php echo ! empty( $active_outcomes ) ? sprintf( '%d selected', count( $active_outcomes ) ) : esc_html__( 'All', 'wp-voting-plugin' ); ?>
							</button>
							<div class="wpvp-dropdown__menu">
								<label class="wpvp-dropdown__item"><input type="checkbox" name="wpvp_outcome[]" value="winner" form="wpvp-filter-form"<?php checked( in_array( 'winner', $active_outcomes, true ) ); ?>><?php esc_html_e( 'Winner(s)', 'wp-voting-plugin' ); ?></label>
								<label class="wpvp-dropdown__item"><input type="checkbox" name="wpvp_outcome[]" value="passed" form="wpvp-filter-form"<?php checked( in_array( 'passed', $active_outcomes, true ) ); ?>><?php esc_html_e( 'Passed', 'wp-voting-plugin' ); ?></label>
								<label class="wpvp-dropdown__item"><input type="checkbox" name="wpvp_outcome[]" value="failed" form="wpvp-filter-form"<?php checked( in_array( 'failed', $active_outcomes, true ) ); ?>><?php esc_html_e( 'Failed / Objected', 'wp-voting-plugin' ); ?></label>
								<label class="wpvp-dropdown__item"><input type="checkbox" name="wpvp_outcome[]" value="tied" form="wpvp-filter-form"<?php checked( in_array( 'tied', $active_outcomes, true ) ); ?>><?php esc_html_e( 'Tied', 'wp-voting-plugin' ); ?></label>
							</div>
						</div>
					</td>
				</tr>
				<?php elseif ( ! $server_side ) : ?>
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
				<?php endif; ?>
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

					if ( $server_side ) {
						$vote_outcome = $vote->_outcome;
						$result_text  = $vote->_result_text;
						$type_display = $vote->_type_display;
						$ballot_count = $vote->_ballot_count;
					} else {
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

						$tl           = json_decode( $vote->classification, true );
						$type_display = is_array( $tl ) && ! empty( $tl ) ? implode( ', ', $tl ) : '';
						$ballot_count = WPVP_Database::get_ballot_count( (int) $vote->id );
					}

					$open_ts  = $vote->opening_date ? WPVP_Database::local_timestamp( $vote->opening_date ) : 0;
					$close_ts = $vote->closing_date ? WPVP_Database::local_timestamp( $vote->closing_date ) : 0;

					$lightbox_url = in_array( $vote->voting_stage, array( 'closed', 'completed', 'withdrawn', 'archived' ), true )
						? $results_url
						: $url;
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
							<?php if ( 'open' === $vote->voting_stage ) :
								$current_user_id = get_current_user_id();
								$can_vote  = $current_user_id && WPVP_Permissions::can_cast_vote( $current_user_id, (int) $vote->id );
								$has_voted = $current_user_id && WPVP_Database::user_has_voted( $current_user_id, (int) $vote->id );
							?>
								<?php if ( $can_vote && $has_voted ) : ?>
									<a href="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--primary wpvp-btn--small"><?php esc_html_e( 'Update', 'wp-voting-plugin' ); ?></a>
								<?php elseif ( $can_vote ) : ?>
									<a href="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--success wpvp-btn--small"><?php esc_html_e( 'Vote', 'wp-voting-plugin' ); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small"><?php esc_html_e( 'View', 'wp-voting-plugin' ); ?></a>
								<?php endif; ?>
							<?php elseif ( 'scheduled' === $vote->voting_stage ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small"><?php esc_html_e( 'View', 'wp-voting-plugin' ); ?></a>
							<?php elseif ( $result_text ) : ?>
								<span class="wpvp-result wpvp-result--<?php echo esc_attr( $vote_outcome ); ?>">
									<?php echo esc_html( $result_text ); ?>
								</span>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $server_side && $pagination['total_pages'] > 1 ) : ?>
		<div class="wpvp-pagination">
			<div class="wpvp-pagination__info">
				<?php
				$start = ( $pagination['current_page'] - 1 ) * $pagination['per_page'] + 1;
				$end   = min( $pagination['current_page'] * $pagination['per_page'], $pagination['total_votes'] );
				printf(
					/* translators: %1$d–%2$d of %3$d */
					esc_html__( 'Showing %1$d–%2$d of %3$d', 'wp-voting-plugin' ),
					$start, $end, $pagination['total_votes']
				);
				?>
			</div>
			<div class="wpvp-pagination__controls">
				<?php if ( $pagination['current_page'] > 1 ) : ?>
					<a class="wpvp-pagination__btn" href="<?php echo esc_url( $page_url( $pagination['current_page'] - 1 ) ); ?>">&lsaquo; <?php esc_html_e( 'Prev', 'wp-voting-plugin' ); ?></a>
				<?php else : ?>
					<span class="wpvp-pagination__btn wpvp-pagination__btn--disabled">&lsaquo; <?php esc_html_e( 'Prev', 'wp-voting-plugin' ); ?></span>
				<?php endif; ?>

				<?php
				$start_p = max( 1, $pagination['current_page'] - 2 );
				$end_p   = min( $pagination['total_pages'], $pagination['current_page'] + 2 );

				if ( $start_p > 1 ) :
					?>
					<a class="wpvp-pagination__btn" href="<?php echo esc_url( $page_url( 1 ) ); ?>">1</a>
					<?php if ( $start_p > 2 ) : ?><span class="wpvp-pagination__ellipsis">&hellip;</span><?php endif; ?>
				<?php endif; ?>

				<?php for ( $p = $start_p; $p <= $end_p; $p++ ) : ?>
					<?php if ( $p === $pagination['current_page'] ) : ?>
						<span class="wpvp-pagination__btn wpvp-pagination__btn--active"><?php echo esc_html( $p ); ?></span>
					<?php else : ?>
						<a class="wpvp-pagination__btn" href="<?php echo esc_url( $page_url( $p ) ); ?>"><?php echo esc_html( $p ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $end_p < $pagination['total_pages'] ) : ?>
					<?php if ( $end_p < $pagination['total_pages'] - 1 ) : ?><span class="wpvp-pagination__ellipsis">&hellip;</span><?php endif; ?>
					<a class="wpvp-pagination__btn" href="<?php echo esc_url( $page_url( $pagination['total_pages'] ) ); ?>"><?php echo esc_html( $pagination['total_pages'] ); ?></a>
				<?php endif; ?>

				<?php if ( $pagination['current_page'] < $pagination['total_pages'] ) : ?>
					<a class="wpvp-pagination__btn" href="<?php echo esc_url( $page_url( $pagination['current_page'] + 1 ) ); ?>"><?php esc_html_e( 'Next', 'wp-voting-plugin' ); ?> &rsaquo;</a>
				<?php else : ?>
					<span class="wpvp-pagination__btn wpvp-pagination__btn--disabled"><?php esc_html_e( 'Next', 'wp-voting-plugin' ); ?> &rsaquo;</span>
				<?php endif; ?>
			</div>
		</div>
		<?php elseif ( ! $server_side ) : ?>
		<div class="wpvp-pagination"></div>
		<?php endif; ?>

		<?php if ( $server_side && empty( $votes ) && $has_filters ) : ?>
			<p class="wpvp-vote-list__empty"><?php esc_html_e( 'No votes match your filters.', 'wp-voting-plugin' ); ?></p>
		<?php endif; ?>

	<?php endif; ?>
</div>
