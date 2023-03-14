<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class All_Offers extends WP_List_Table {


	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Offer', 'sp' ), //singular name of the listed records
				'plural'   => __( 'Offers', 'sp' ), //plural name of the listed records
				'ajax'     => false, //should this table support ajax?
			)
		);

	}

	/**
	 * Retrieve All Offers from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_offers( $per_page = 15, $page_number = 1 ) {

		global $wpdb;
		$sql = "SELECT  p.product_id, wp.post_title as product_name, COUNT(p.product_id) as orders, SUM(p.quantity) as quantity FROM {$wpdb->prefix}dbargain_reports AS p JOIN {$wpdb->prefix}posts AS wp ON p.product_id = wp.ID GROUP BY p.product_id";
		if ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY %s';
			$sql .= isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( sanitize_text_field( $_REQUEST['order'] ) ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $wpdb->prepare( $sql, isset( $_REQUEST['orderby'] ) ? esc_sql( sanitize_text_field( $_REQUEST['orderby'] ) ) : '' ), 'ARRAY_A' );
		
		
		$dex = 0;
		foreach ( $result as $p ) {
			$result[ $dex ]['customers'] = $wpdb->get_var( $wpdb->prepare( "select count(distinct user_id) from {$wpdb->prefix}dbargain_reports where product_id = %d", $p['product_id'] ) );
			$dex++;
		}

		return $result;
	}

	/**
	 * Returns the count of reports in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = 'SELECT COUNT(DISTINCT product_id) FROM %sdbargain_reports';

		return $wpdb->get_var( $wpdb->prepare( $sql, $wpdb->prefix ) );
	}

	/**
	 * Text displayed when no data is available
	 */
	public function no_items() {
		esc_attr_e( 'No data.', 'sp' );
	}

	/**
	 * Method for view details
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_details( $item ) {

		$title = '<a href="javascript:;" class="show_detail populate_detail" data-nonce="' . wp_create_nonce( 'offer-details' ) . '" rel="' . $item['product_id'] . '">Show Details</a>';

		return $title;
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			//    'cb' => '<input type="checkbox" />',
			'product_id'   => __( 'Product ID', 'sp' ),
			'product_name' => __( 'Product Name', 'sp' ),
			'customers'    => __( 'Total Buyers', 'sp' ),
			'orders'       => __( 'Total No. of Orders', 'sp' ),
			'quantity'     => __( 'Total Quantity Sold', 'sp' ),
			'details'      => '',
		);

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'customers' => array( 'customers', true ),
			'orders'    => array( 'orders', true ),
			'quantity'  => array( 'quantity', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$perPage     = 15;
		$currentPage = $this->get_pagenum();
		$totalItems  = self::record_count();

		$data = self::get_offers( $perPage, $currentPage );

		$this->set_pagination_args(
			array(
				'total_items' => $totalItems,
				'per_page'    => $perPage,
			)
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}
}

