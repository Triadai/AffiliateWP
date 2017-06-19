<?php
namespace AffWP\Payout\Database;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_Payouts_DB class
 *
 * @covers Affiliate_WP_Payouts_DB
 * @group database
 * @group payouts
 */
class Tests extends UnitTestCase {

	/**
	 * User fixture.
	 *
	 * @access protected
	 * @var int
	 */
	protected static $user_id = 0;

	/**
	 * Affiliate fixture.
	 *
	 * @access protected
	 * @var int
	 */
	protected static $affiliate_id = 0;

	/**
	 * Referrals fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $referrals = array();

	/**
	 * Payout fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $payouts = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$user_id = parent::affwp()->user->create();

		self::$affiliate_id = parent::affwp()->affiliate->create( array(
			'user_id' => self::$user_id
		) );

		self::$referrals = parent::affwp()->referral->create_many( 4, array(
			'affiliate_id' => self::$affiliate_id,
			'status'       => 'paid',
		) );

		self::$payouts = parent::affwp()->payout->create_many( 4, array(
			'affiliate_id' => self::$affiliate_id,
			'referrals'    => self::$referrals,
		) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::add()
	 */
	public function test_add_should_return_false_if_affiliate_id_undefined() {
		$this->assertFalse( affiliate_wp()->affiliates->payouts->add() );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::add()
	 */
	public function test_add_should_return_false_if_invalid_affiliate_id() {
		$this->assertFalse( affiliate_wp()->affiliates->payouts->add( array(
			'affiliate_id' => rand( 500, 5000 )
		) ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::add()
	 */
	public function test_add_should_return_false_if_no_referrals_defined() {
		$this->assertFalse( $payout = $this->factory->payout->create( array(
			'referrals'    => range( 1, 4 ),
		) ) );

		// Clean up.
		affwp_delete_payout( $payout );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::add()
	 */
	public function test_add_should_convert_array_of_referral_ids_to_comma_separated_string() {
		$referrals = implode( ',', self::$referrals );

		$this->assertSame( $referrals, affiliate_wp()->affiliates->payouts->get_column( 'referrals', self::$payouts[0] ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::payout_exists()
	 */
	public function test_payout_exists_should_return_false_if_payout_does_not_exist() {
		$this->assertFalse( affiliate_wp()->affiliates->payouts->payout_exists( 0 ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::payout_exists()
	 */
	public function test_payout_exists_should_return_true_if_payout_exists() {
		$this->assertTrue( affiliate_wp()->affiliates->payouts->payout_exists( self::$payouts[0] ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::payout_exists()
	 */
	public function test_column_defaults_should_return_paid_status() {
		$defaults = affiliate_wp()->affiliates->payouts->get_column_defaults();

		$this->assertSame( 'paid', $defaults['status'] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::payout_exists()
	 */
	public function test_column_defaults_should_return_the_current_date_for_date() {
		$defaults = affiliate_wp()->affiliates->payouts->get_column_defaults();

		$this->assertSame( date( 'Y-m-d H:i:s' ), $defaults['date'] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_columns()
	 */
	public function test_get_columns_should_return_all_columns() {
		$columns = affiliate_wp()->affiliates->payouts->get_columns();

		$expected = array(
			'payout_id'     => '%d',
			'affiliate_id'  => '%d',
			'referrals'     => '%s',
			'amount'        => '%s',
			'owner'         => '%d',
			'payout_method' => '%s',
			'status'        => '%s',
			'date'          => '%s',
		);

		$this->assertEqualSets( $expected, $columns );
	}

	/**
	 * @covers \Affiliate_WP_Payouts_DB::get_column_defaults()
	 */
	public function test_get_column_defaults_should_return_defaults() {
		$expected = array(
			'affiliate_id' => 0,
			'owner'        => 0,
			'status'       => 'paid',
			'date'         => date( 'Y-m-d H:i:s' ),
		);

		$this->assertEqualSets( $expected, affiliate_wp()->affiliates->payouts->get_column_defaults() );
	}

	/**
	 * @covers \Affiliate_WP_Payouts_DB::get_column_defaults()
	 */
	public function test_get_column_defaults_should_not_set_a_default_for_the_primary_key() {
		$defaults = affiliate_wp()->affiliates->payouts->get_column_defaults();

		$this->assertArrayNotHasKey( affiliate_wp()->affiliates->payouts->primary_key, $defaults );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_object()
	 */
	public function test_get_object_should_return_false_if_invalid_payout_id() {
		$this->assertFalse( affiliate_wp()->affiliates->payouts->get_object( 0 ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_object()
	 */
	public function test_get_object_should_return_payout_object_if_valid_payout_id() {
		$this->assertInstanceOf( 'AffWP\Affiliate\Payout', affiliate_wp()->affiliates->payouts->get_object( self::$payouts[0] ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_referral_ids()
	 */
	public function test_get_referral_ids_should_return_empty_array_if_invalid_payout_id() {
		$this->assertSame( array(), affiliate_wp()->affiliates->payouts->get_referral_ids( 0 ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_referral_ids()
	 */
	public function test_get_referral_ids_should_return_empty_array_if_invalid_payout_object() {
		$this->assertSame( array(), affiliate_wp()->affiliates->payouts->get_referral_ids( new \stdClass() ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_referral_ids()
	 */
	public function test_get_referral_ids_should_return_an_array_of_referral_ids() {
		$this->assertEqualSets( self::$referrals, affiliate_wp()->affiliates->payouts->get_referral_ids( self::$payouts[0] ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_number_should_return_number_if_available() {
		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'number' => 3
		) );

		$this->assertSame( 3, count( $payouts ) );
		$this->assertTrue( count( $payouts ) <= 3 );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_offset_should_offset_number_given() {
		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'number' => 3,
			'offset' => 1,
			'fields' => 'ids',
		) );

		$this->assertEqualSets( $payouts, array_slice( self::$payouts, 0, 3 ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_single_payout_id_should_return_that_payout() {
		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'payout_id' => self::$payouts[3],
			'fields'    => 'ids',
		) );

		$this->assertCount( 1, $payouts );
		$this->assertSame( self::$payouts[3], $payouts[0] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_multiple_payout_ids_should_return_those_payouts() {
		$to_query = array( self::$payouts[0], self::$payouts[2] );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'payout_id' => $to_query,
			'order'     => 'ASC', // Default descending.
			'fields'    => 'ids',
		) );

		$this->assertCount( 2, $results );
		$this->assertEqualSets( $to_query, $results );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_single_affiliate_id_should_return_payouts_for_that_affiliate_only() {
		// Total of 5 payouts, two different affiliates.
		$payout = $this->factory->payout->create( array(
			'affiliate_id' => $affiliate_id = $this->factory->affiliate->create()
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'affiliate_id' => $affiliate_id,
			'fields'       => 'ids',
		) );

		$this->assertSame( 1, count( $results ) );
		$this->assertSame( $payout, $results[0] );

		// Clean up.
		affwp_delete_payout( $payout );
		affwp_delete_affiliate( $affiliate_id );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_multiple_affiliate_ids_should_return_payouts_for_multiple_affiliates() {
		// Total of 6 payouts, two different affiliates.
		$payouts = $this->factory->payout->create_many( 2, array(
			'affiliate_id' => $affiliate_id = $this->factory->affiliate->create(),
			'referrals'    => $referrals = $this->factory->referral->create( array(
				'affiliate_id' => $affiliate_id
			) )
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'affiliate_id' => array( $affiliate_id, self::$affiliate_id ),
		) );

		$affiliates = array_unique( wp_list_pluck( $results, 'affiliate_id' ) );

		$this->assertTrue(
			in_array( $affiliate_id, $affiliates, true )
			&& in_array( self::$affiliate_id, $affiliates, true )
		);

		// Clean up.
		affwp_delete_payout( $payouts[0] );
		affwp_delete_payout( $payouts[1] );
		affwp_delete_affiliate( $affiliate_id );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_single_paid_referral_id_should_return_the_payout_for_that_referral() {
		$payout = $this->factory->payout->create( array(
			'affiliate_id' => self::$affiliate_id,
			'referrals'    => $referral = $this->factory->referral->create( array(
				'affiliate_id' => self::$affiliate_id
			) )
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'referrals' => $referral
		) );

		$this->assertCount( 1, $results );

		$payout_referrals = affiliate_wp()->affiliates->payouts->get_referral_ids( $results[0] );

		$this->assertSame( array( $referral ), $payout_referrals );

		// Clean up.
		affwp_delete_payout( $payout );
		affwp_delete_referral( $referral );
	}

	public function test_get_payouts_with_multiple_paid_referrals_should_return_the_payouts_for_those_referrals() {
		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'referrals' => self::$referrals,
			'fields'    => 'ids',
		) );

		$this->assertCount( 1, $results );

		$payout_referrals = affiliate_wp()->affiliates->payouts->get_referral_ids( $results[0] );

		$this->assertEqualSets( self::$referrals, $payout_referrals );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_multiple_unpaid_referrals_should_ignore_referrals_arg() {
		$payout = $this->factory->payout->create( array(
			'affiliate_id' => self::$affiliate_id,
			'referalls'    => $referrals = $this->factory->referral->create_many( 2, array(
				'affiliate_id' => self::$affiliate_id,
				'statis'       => 'unpaid',
			) )
		) );

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'referrals' => $referrals,
			'fields'    => 'ids',
		) );

		$this->assertCount( 5, $payouts );

		// Clean up.
		affwp_delete_payout( $payout );
		affwp_delete_referral( $referrals[0] );
		affwp_delete_referral( $referrals[1] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_should_default_to_all_statuses() {
		$payout_ids = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'fields' => 'ids'
		) );

		$this->assertEqualSets( self::$payouts, $payout_ids );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_paid_status_should_return_only_paid_status_payouts() {
		$failed_payouts = $this->factory->payout->create_many( 2, array(
			'status' => 'failed'
		) );

		$payout_ids = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'status' => 'paid',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$payouts, $payout_ids );

		// Clean up.
		affwp_delete_payout( $failed_payouts[0] );
		affwp_delete_payout( $failed_payouts[1] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_failed_status_should_return_only_failed_status_payouts() {
		$paid_payouts   = $this->factory->payout->create_many( 2, array(
			'status' => 'failed'
		) );

		$payout_ids = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'status' => 'failed',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( $paid_payouts, $payout_ids );

		// Clean up.
		affwp_delete_payout( $paid_payouts[0] );
		affwp_delete_payout( $paid_payouts[1] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_invalid_status_should_default_to_paid_status() {
		$failed = $this->factory->payout->create_many( 2, array( 'status' => 'failed' ) );

		$payout_ids = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'status' => 'foo',
			'fields' => 'ids',
		) );

		$this->assertEqualSets( self::$payouts, $payout_ids );

		// Clean up.
		affwp_delete_payout( $failed[0] );
		affwp_delete_payout( $failed[1] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_only_should_return_payouts_matching_that_amount() {
		$payout_id = $this->factory->payout->create( array(
			'amount' => '5.00'
		) );

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount' => 5,
			'fields' => 'ids',
		) );

		$this->assertCount( 1, $payouts );
		$this->assertSame( $payout_id, $payouts[0] );

		// Clean up.
		affwp_delete_payout( $payout_id );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_min_and_max_should_return_payouts_between_them() {
		$one   = $this->factory->payout->create( array( 'amount' => '1.00' ) );
		$three = $this->factory->payout->create( array( 'amount' => '3.00' ) );
		$five  = $this->factory->payout->create( array( 'amount' => '5.00' ) );

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount' => array(
				'min' => 2,
				'max' => 4
			),
			'fields' => 'ids',
		) );

		$this->assertSame( $three, $payouts[0] );

		// Clean up.
		affwp_delete_payout( $one );
		affwp_delete_payout( $three );
		affwp_delete_payout( $five );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_with_greater_than_compare_should_return_payouts_greater_than_amount() {
		// Default payout is 3.00 (3 referrals x 1.00 each).
		$five = $this->factory->payout->create( array(
			'amount' => '4.00'
		) );

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount'         => 3,
			'amount_compare' => '>',
			'fields'         => 'ids',
		) );

		$this->assertSame( $five, $payouts[0] );

		// Clean up.
		affwp_delete_payout( $five );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_with_less_than_compare_should_return_payouts_less_than_amount() {
		// Default payout is 3.00 (3 referrals x 1.00 each).
		$four = $this->factory->payout->create( array(
			'amount' => '4.00'
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount'         => 4,
			'amount_compare' => '<',
			'fields'         => 'ids',
			'order'          => 'ASC', // Default 'DESC'
		) );

		$this->assertSame( self::$payouts, $results );

		// Clean up.
		affwp_delete_payout( $four );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_with_greater_than_equals_should_return_payouts_greater_than_or_equal() {
		// Default payout is 3.00 (3 referrals x 1.00 each).
		$five = $this->factory->payout->create( array(
			'amount' => '5.00'
		) );

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount'         => 4,
			'amount_compare' => '>=',
			'fields'         => 'ids',
		) );

		$this->assertSame( $five, $payouts[0] );

		// Clean up.
		affwp_delete_payout( $five );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_with_less_than_equal_should_return_payouts_less_than_or_equal() {
		// Default payout is 3.00 (3 referrals x 1.00 each).
		$five = $this->factory->payout->create( array(
			'amount' => '5.00'
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount'         => 4,
			'amount_compare' => '<=',
			'fields'         => 'ids',
		) );

		$this->assertEqualSets( self::$payouts, $results );

		// Cleaup.
		affwp_delete_payout( $five );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_amount_with_not_equal_should_return_payouts_not_equal() {
		// Default payout is 3.00 (3 referrals x 1.00 each).
		$five = $this->factory->payout->create( array(
			'amount' => '5.00'
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'amount'         => 5,
			'amount_compare' => '!=',
			'fields'         => 'ids',
			'order'          => 'ASC', // Default 'DESC'
		) );

		$this->assertSame( self::$payouts, $results );

		// Clean up.
		affwp_delete_payout( $five );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_owner_with_single_owner_should_return_payouts_only_for_that_owner() {
		$user_id = $this->factory->user->create();

		wp_set_current_user( $user_id );

		$payouts = $this->factory->payout->create_many( 2, array(
			'affiliate_id' => self::$affiliate_id,
			'referrals'    => self::$referrals
		) );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'owner'  => $user_id,
			'fields' => 'ids',
		) );

		$this->assertEqualSets( $payouts, $results );

		// Clean up.
		foreach ( $payouts as $payout ) {
			affwp_delete_payout( $payout );
		}
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_owner_with_multiple_owners_should_return_payouts_only_for_those_owners() {
		wp_set_current_user( self::$user_id );

		$payouts1 = $this->factory->payout->create_many( 2, array(
			'affiliate_id' => self::$affiliate_id,
			'referrals'    => self::$referrals
		) );

		$user_id2 = $this->factory->user->create();

		wp_set_current_user( $user_id2 );

		$payouts2 = $this->factory->payout->create_many( 2, array(
			'affiliate_id' => self::$affiliate_id,
			'referrals'    => self::$referrals
		) );

		$combined_payouts = array_merge( $payouts1, $payouts2 );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'owner'  => array( self::$user_id, $user_id2 ),
			'fields' => 'ids',
		) );

		$this->assertEqualSets( $combined_payouts, $results );

		// Clean up.
		foreach ( $combined_payouts as $payout ) {
			affwp_delete_payout( $payout );
		}
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_with_count_true_should_return_a_count_only() {
		$this->assertSame( 4, affiliate_wp()->affiliates->payouts->get_payouts( array(), true ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_fields_ids_should_return_an_array_of_ids_only() {
		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'fields' => 'ids',
			'order'  => 'ASC', // Default 'DESC'
		) );

		$this->assertEqualSets( self::$payouts, $results );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payouts()
	 */
	public function test_get_payouts_invalid_fields_arg_should_return_regular_Payout_object_results() {
		$payouts = array_map( 'affwp_get_payout', self::$payouts );

		$results = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'fields' => 'foo'
		) );

		$this->assertEqualSets( $payouts, $results );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_affiliate_ids_by_referrals()
	 */
	public function test_get_affiliate_ids_by_referrals_should_reject_invalid_referrals() {
		$this->assertEmpty( affiliate_wp()->affiliates->payouts->get_affiliate_ids_by_referrals( range( 1, 5 ) ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_affiliate_ids_by_referrals()
	 */
	public function test_get_affiliate_ids_by_referrals_should_reject_non_paid_referrals_by_default() {
		$pending = $this->factory->referral->create_many( 2, array(
			'affiliate_id' => $affiliate_id = $this->factory->affiliate->create(),
			'status'       => 'pending',
		) );

		$referrals = array_merge( self::$referrals, $pending );
		$results   = affiliate_wp()->affiliates->payouts->get_affiliate_ids_by_referrals( $referrals );

		$this->assertSame( self::$referrals, $results[ self::$affiliate_id ] );

		// Clean up.
		affwp_delete_affiliate( $affiliate_id );
		affwp_delete_referral( $pending[0] );
		affwp_delete_referral( $pending[1] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_affiliate_ids_by_referrals()
	 */
	public function test_get_affiliate_ids_by_referrals_should_only_accept_referrals_by_non_default_status() {
		$unpaid = (array) $this->factory->referral->create_many( 2, array(
			'status'       => 'unpaid',
			'affiliate_id' => $affiliate_id = $this->factory->affiliate->create(),
		) );

		$referrals = array_merge( $unpaid, self::$referrals );
		$results   = affiliate_wp()->affiliates->payouts->get_affiliate_ids_by_referrals( $referrals, 'unpaid' );

		$this->assertNotSame( self::$referrals, $results[ $affiliate_id ] );
		$this->assertSame( $unpaid, $results[ $affiliate_id ] );

		// Clean up.
		affwp_delete_affiliate( $affiliate_id );
		affwp_delete_referral( $unpaid[0] );
		affwp_delete_referral( $unpaid[1] );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payout_ids_by_affiliates()
	 */
	public function test_get_payout_ids_by_affiliates_should_return_an_empty_array_if_affiliates_is_empty() {
		$this->assertEmpty( affiliate_wp()->affiliates->payouts->get_payout_ids_by_affiliates( array() ) );
	}

	/**
	 * @covers Affiliate_WP_Payouts_DB::get_payout_ids_by_affiliates()
	 */
	public function test_get_payout_ids_by_affiliates_should_retrieve_payout_ids_for_all_given_referrals() {
		$affiliates = affiliate_wp()->affiliates->payouts->get_affiliate_ids_by_referrals( self::$referrals );
		$results    = affiliate_wp()->affiliates->payouts->get_payout_ids_by_affiliates( $affiliates );

		$this->assertEqualSets( array( self::$payouts[3] ), $results );
	}
}
