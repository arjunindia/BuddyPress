<?php

/**
 * BuddyPress Notifications Template Functions
 *
 * @package BuddyPress
 * @subpackage TonificationsTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the notifications component slug.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_slug() {
	echo bp_get_notifications_slug();
}
	/**
	 * Return the notifications component slug.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return string Slug of the Notifications component.
	 */
	function bp_get_notifications_slug() {
		return apply_filters( 'bp_get_notifications_slug', buddypress()->notifications->slug );
	}

/** Main Loop *****************************************************************/

/**
 * The main notifications template loop class.
 *
 * Responsible for loading a group of notifications into a loop for display.
 *
 * @since BuddyPress (1.9.0)
 */
class BP_Notifications_Template {
	/**
	 * The loop iterator.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var int
	 */
	var $current_notification = -1;

	/**
	 * The number of notifications returned by the paged query.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var int
	 */
	var $current_notification_count;

	/**
	 * Total number of notifications matching the query.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var int
	 */
	var $total_notification_count;

	/**
	 * Array of notifications located by the query.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var array
	 */
	var $notifications;

	/**
	 * The notification object currently being iterated on.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var object
	 */
	var $notification;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var bool
	 */
	var $in_the_loop;

	/**
	 * The ID of the user to whom the displayed notifications belong.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var int
	 */
	var $user_id;

	/**
	 * The page number being requested.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var int
	 */
	var $pag_page;

	/**
	 * The number of items to display per page of results.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var int
	 */
	var $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var string
	 */
	var $pag_links;

	/**
	 * A string to match against.
	 *
	 * @since BuddyPress (1.9.0)
	 * @access public
	 * @var string
	 */
	var $search_terms;

	/**
	 * Constructor method.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @param array $args {
	 *     @type int $user_id ID of the user to whom the displayed
	 *           notifications belong.
	 *     @type bool $is_new Whether to limit the query to unread
	 *           notifications. Default: true.
	 *     @type int $page Number of the page of results to return.
	 *           Will be overridden by URL parameter. Default: 1.
	 *     @type int $per_page Number of results to return per page.
	 *           Will be overridden by URL parameter. Default: 25.
	 *     @type int $max Optional. Max results to display.
	 *     @type string $search_terms Optional. Term to match against
	 *           component_name and component_action.
	 *     @type string $page_arg URL argument to use for pagination.
	 *           Default: 'npage'.
	 * }
	 */
	public function __construct( $args = array() ) {

		$r = wp_parse_args( $args, array(
			'user_id'      => 0,
			'is_new'       => true,
			'page'         => 1,
			'per_page'     => 25,
			'max'          => null,
			'search_terms' => '',
			'page_arg'     => 'npage',
		) );

		// Setup variables
		$this->pag_page     = isset( $_GET[ $r['page_arg'] ] ) ? intval( $_GET[ $r['page_arg'] ] ) : $r['page'];
		$this->pag_num      = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $r['per_page'];
		$this->user_id      = $r['user_id'];
		$this->is_new       = $r['is_new'];
		$this->search_terms = $r['search_terms'];
		$this->page_arg     = $r['page_arg'];

		// Get the notifications
		$notifications      = BP_Notifications_Notification::get_current_notifications_for_user( array(
			'user_id'      => $this->user_id,
			'is_new'       => $this->is_new,
			'page'         => $this->pag_page,
			'per_page'     => $this->pag_num,
			'search_terms' => $this->search_terms
		) );

		// Setup the notifications to loop through
		$this->notifications            = $notifications['notifications'];
		$this->total_notification_count = $notifications['total'];

		if ( empty( $this->notifications ) ) {
			$this->notification_count       = 0;
			$this->total_notification_count = 0;

		} else {
			if ( ! empty( $max ) ) {
				if ( $max >= count( $this->notifications ) ) {
					$this->notification_count = count( $this->notifications );
				} else {
					$this->notification_count = (int) $max;
				}
			} else {
				$this->notification_count = count( $this->notifications );
			}
		}

		if ( (int) $this->total_notification_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $this->page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_notification_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Notifications pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Notifications pagination next text',     'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	/**
	 * Whether there are notifications available in the loop.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @see bp_has_notifications()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_notifications() {
		if ( $this->notification_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next notification and iterate index.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return object The next notification to iterate over.
	 */
	public function next_notification() {

		$this->current_notification++;

		$this->notification = $this->notifications[ $this->current_notification ];

		return $this->notification;
	}

	/**
	 * Rewind the blogs and reset blog index.
	 *
	 * @since BuddyPress (1.9.0)
	 */
	public function rewind_notifications() {

		$this->current_notification = -1;

		if ( $this->notification_count > 0 ) {
			$this->notification = $this->notifications[0];
		}
	}

	/**
	 * Whether there are notifications left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_notifications()} as part of the
	 * while loop that controls iteration inside the notifications loop, eg:
	 *     while ( bp_notifications() ) { ...
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @see bp_notifications()
	 *
	 * @return bool True if there are more notifications to show,
	 *         otherwise false.
	 */
	public function notifications() {

		if ( $this->current_notification + 1 < $this->notification_count ) {
			return true;

		} elseif ( $this->current_notification + 1 == $this->notification_count ) {
			do_action( 'notifications_loop_end');

			$this->rewind_notifications();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current notification inside the loop.
	 *
	 * Used by {@link bp_the_notification()} to set up the current
	 * notification data while looping, so that template tags used during
	 * that iteration make reference to the current notification.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @see bp_the_notification()
	 */
	public function the_notification() {
		$this->in_the_loop  = true;
		$this->notification = $this->next_notification();

		// loop has just started
		if ( 0 === $this->current_notification ) {
			do_action( 'notifications_loop_start' );
		}
	}
}

/**
 * Initialize the notifications loop.
 *
 * Based on the $args passed, bp_has_notifications() populates
 * buddypress()->notifications->query_loop global, enabling the use of BP
 * templates and template functions to display a list of notifications.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param array $args {
 *     Arguments for limiting the contents of the notifications loop. Can be
 *     passed as an associative array, or as a URL query string.
 *     @type int $user_id ID of the user to whom notifications belong. Default:
 *           ID of the logged-in user.
 *     @type bool $is_new Whether to limit query to unread notifications.
 *           Default: when viewing the 'unread' tab, defaults to true; when
 *           viewing the 'read' tab, defaults to false.
 *     @type int $page The page of notifications being fetched. Default: 1.
 *     @type int $per_page Number of items to display on a page. Default: 25.
 *     @type int $max Optional. Max items to display. Default: false.
 *     @type string $search_terms Optional. Term to match against
 *           component_name and component_action.
 *     @type string $page_arg URL argument to use for pagination.
 *           Default: 'npage'.
 * }
 */
function bp_has_notifications( $args = '' ) {

	// Get the default is_new argument
	if ( bp_is_current_action( 'unread' ) ) {
		$is_new = 1;
	} elseif ( bp_is_current_action( 'read' ) ) {
		$is_new = 0;
	}

	// Parse the args
	$r = wp_parse_args( $args, array(
		'user_id'      => bp_loggedin_user_id(),
		'is_new'       => $is_new,
		'page'         => 1,
		'per_page'     => 25,
		'max'          => false,
		'search_terms' => isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '',
		'page_arg'     => 'npage'
	) );

	// Get the notifications
	$query_loop = new BP_Notifications_Template( $r );

	// Setup the global query loop
	buddypress()->notifications->query_loop = $query_loop;

	return apply_filters( 'bp_has_notificationss', $query_loop->has_notifications(), $query_loop );
}

/**
 * Get the notifications returned by the template loop.
 *
 * @since BuddyPress (1.9.0)
 *
 * @return array List of notifications.
 */
function bp_the_notifications() {
	return buddypress()->notifications->query_loop->notifications();
}

/**
 * Get the current notification object in the loop.
 *
 * @since BuddyPress (1.9.0)
 *
 * @return object The current notification within the loop.
 */
function bp_the_notification() {
	return buddypress()->notifications->query_loop->the_notification();
}

/**
 * Output the ID of the notification currently being iterated on.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_notification_id() {
	echo bp_get_the_notification_id();
}
	/**
	 * Return the ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return int ID of the current notification.
	 */
	function bp_get_the_notification_id() {
		return apply_filters( 'bp_get_the_notification_id', buddypress()->notifications->query_loop->notification->id );
	}

/**
 * Output the associated item ID of the notification currently being iterated on.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_notification_item_id() {
	echo bp_get_the_notification_item_id();
}
	/**
	 * Return the associated item ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return int ID of the item associated with the current notification.
	 */
	function bp_get_the_notification_item_id() {
		return apply_filters( 'bp_get_the_notification_item_id', stripslashes_deep( buddypress()->notifications->query_loop->notification->item_id ) );
	}

/**
 * Output the secondary associated item ID of the notification currently being iterated on.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_notification_secondary_item_id() {
	echo bp_get_the_notification_secondary_item_id();
}
	/**
	 * Return the secondary associated item ID of the notification currently being iterated on.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return int ID of the secondary item associated with the current notification.
	 */
	function bp_get_the_notification_secondary_item_id() {
		return apply_filters( 'bp_get_the_notification_secondary_item_id', stripslashes_deep( buddypress()->notifications->query_loop->notification->secondary_item_id ) );
	}

/**
 * Output the name of the component associated with the notification currently being iterated on.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_notification_component_name() {
	echo bp_get_the_notification_component_name();
}
	/**
	 * Return the name of the component associated with the notification currently being iterated on.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return int Name of the component associated with the current notification.
	 */
	function bp_get_the_notification_component_name() {
		return apply_filters( 'bp_get_the_notification_component_name', stripslashes_deep( buddypress()->notifications->query_loop->notification->component_name ) );
	}

/**
 * Output the name of the action associated with the notification currently being iterated on.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_notification_component_action() {
	echo bp_get_the_notification_component_action();
}
	/**
	 * Return the name of the action associated with the notification currently being iterated on.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return int Name of the action associated with the current notification.
	 */
	function bp_get_the_notification_component_action() {
		return apply_filters( 'bp_get_the_notification_component_action', stripslashes_deep( buddypress()->notifications->query_loop->notification->component_action ) );
	}

/**
 * Output the timestamp of the current notification.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_notification_date_notified() {
	echo bp_get_the_notification_date_notified();
}
	/**
	 * Return the timestamp of the current notification.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return string Timestamp of the current notification.
	 */
	function bp_get_the_notification_date_notified() {
		return apply_filters( 'bp_get_the_notification_date_notified', stripslashes_deep( buddypress()->notifications->query_loop->notification->date_notified ) );
	}

/**
 * Output the pagination count for the current notification loop.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_pagination_count() {
	echo bp_get_notifications_pagination_count();
}
	/**
	 * Return the pagination count for the current notification loop.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return string HTML for the pagination count.
	 */
	function bp_get_notifications_pagination_count() {
		$query_loop = buddypress()->notifications->query_loop;
		$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
		$from_num   = bp_core_number_format( $start_num );
		$to_num     = bp_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_notification_count ) ? $query_loop->total_notification_count : $start_num + ( $query_loop->pag_num - 1 ) );
		$total      = bp_core_number_format( $query_loop->total_notification_count );
		$pag        = sprintf( _n( 'Viewing %1$s to %2$s (of %3$s notification)', 'Viewing %1$s to %2$s (of %3$s notifications)', $total, 'buddypress' ), $from_num, $to_num, $total );

		return apply_filters( 'bp_notifications_pagination_count', $pag );
	}

/**
 * Output the pagination links for the current notification loop.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_pagination_links() {
	echo bp_get_notifications_pagination_links();
}
	/**
	 * Return the pagination links for the current notification loop.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return string HTML for the pagination links.
	 */
	function bp_get_notifications_pagination_links() {
		return apply_filters( 'bp_get_notifications_pagination_links', buddypress()->notifications->query_loop->pag_links );
	}