<?php
/**
 * Subscription domain model.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Models;

/**
 * Represents a subscription entity.
 */
final class Subscription {

	/**
	 * Subscription ID.
	 *
	 * @var int
	 */
	private int $id = 0;

	/**
	 * Parent order ID.
	 *
	 * @var int
	 */
	private int $parent_order_id = 0;

	/**
	 * Customer user ID.
	 *
	 * @var int
	 */
	private int $customer_id = 0;

	/**
	 * Subscription status.
	 *
	 * @var string
	 */
	private string $status = SubscriptionStatus::PENDING;

	/**
	 * Currency code.
	 *
	 * @var string
	 */
	private string $currency = '';

	/**
	 * Billing period.
	 *
	 * @var string
	 */
	private string $billing_period = 'month';

	/**
	 * Billing interval.
	 *
	 * @var int
	 */
	private int $billing_interval = 1;

	/**
	 * Recurring total.
	 *
	 * @var string
	 */
	private string $recurring_total = '0.00';

	/**
	 * Sign-up fee.
	 *
	 * @var string
	 */
	private string $sign_up_fee = '0.00';

	/**
	 * Trial length in days.
	 *
	 * @var int
	 */
	private int $trial_length = 0;

	/**
	 * Trial end datetime (UTC mysql format).
	 *
	 * @var string|null
	 */
	private ?string $trial_end = null;

	/**
	 * Next payment datetime (UTC mysql format).
	 *
	 * @var string|null
	 */
	private ?string $next_payment_date = null;

	/**
	 * Start datetime (UTC mysql format).
	 *
	 * @var string|null
	 */
	private ?string $start_date = null;

	/**
	 * End datetime (UTC mysql format).
	 *
	 * @var string|null
	 */
	private ?string $end_date = null;

	/**
	 * Created datetime (UTC mysql format).
	 *
	 * @var string|null
	 */
	private ?string $date_created = null;

	/**
	 * Modified datetime (UTC mysql format).
	 *
	 * @var string|null
	 */
	private ?string $date_modified = null;

	/**
	 * Subscription meta values.
	 *
	 * @var array<string, mixed>
	 */
	private array $meta = array();

	/**
	 * Hydrate from a database row.
	 *
	 * @param object $row Database row object.
	 * @return self
	 */
	public static function from_row( object $row ): self {
		$subscription = new self();

		$subscription->id                = (int) $row->id;
		$subscription->parent_order_id   = (int) $row->parent_order_id;
		$subscription->customer_id       = (int) $row->customer_id;
		$subscription->status            = (string) $row->status;
		$subscription->currency          = (string) $row->currency;
		$subscription->billing_period    = (string) $row->billing_period;
		$subscription->billing_interval  = (int) $row->billing_interval;
		$subscription->recurring_total   = (string) $row->recurring_total;
		$subscription->sign_up_fee       = (string) $row->sign_up_fee;
		$subscription->trial_length      = (int) $row->trial_length;
		$subscription->trial_end         = $row->trial_end ? (string) $row->trial_end : null;
		$subscription->next_payment_date = $row->next_payment_date ? (string) $row->next_payment_date : null;
		$subscription->start_date        = $row->start_date ? (string) $row->start_date : null;
		$subscription->end_date          = $row->end_date ? (string) $row->end_date : null;
		$subscription->date_created      = $row->date_created ? (string) $row->date_created : null;
		$subscription->date_modified     = $row->date_modified ? (string) $row->date_modified : null;

		return $subscription;
	}

	/**
	 * Convert to array for persistence.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'parent_order_id'   => $this->parent_order_id,
			'customer_id'       => $this->customer_id,
			'status'            => $this->status,
			'currency'          => $this->currency,
			'billing_period'    => $this->billing_period,
			'billing_interval'  => $this->billing_interval,
			'recurring_total'   => $this->recurring_total,
			'sign_up_fee'       => $this->sign_up_fee,
			'trial_length'      => $this->trial_length,
			'trial_end'         => $this->trial_end,
			'next_payment_date' => $this->next_payment_date,
			'start_date'        => $this->start_date,
			'end_date'          => $this->end_date,
			'date_created'      => $this->date_created,
			'date_modified'     => $this->date_modified,
			'meta'              => $this->meta,
		);
	}

	public function get_id(): int {
		return $this->id;
	}

	public function set_id( int $id ): void {
		$this->id = $id;
	}

	public function get_parent_order_id(): int {
		return $this->parent_order_id;
	}

	public function set_parent_order_id( int $parent_order_id ): void {
		$this->parent_order_id = $parent_order_id;
	}

	public function get_customer_id(): int {
		return $this->customer_id;
	}

	public function set_customer_id( int $customer_id ): void {
		$this->customer_id = $customer_id;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function set_status( string $status ): void {
		if ( ! SubscriptionStatus::is_valid( $status ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Invalid subscription status: %s',
					sanitize_key( $status )
				)
			);
		}

		$this->status = $status;
	}

	public function get_currency(): string {
		return $this->currency;
	}

	public function set_currency( string $currency ): void {
		$this->currency = $currency;
	}

	public function get_billing_period(): string {
		return $this->billing_period;
	}

	public function set_billing_period( string $billing_period ): void {
		$this->billing_period = $billing_period;
	}

	public function get_billing_interval(): int {
		return $this->billing_interval;
	}

	public function set_billing_interval( int $billing_interval ): void {
		$this->billing_interval = max( 1, $billing_interval );
	}

	public function get_recurring_total(): string {
		return $this->recurring_total;
	}

	public function set_recurring_total( string $recurring_total ): void {
		$this->recurring_total = $recurring_total;
	}

	public function get_sign_up_fee(): string {
		return $this->sign_up_fee;
	}

	public function set_sign_up_fee( string $sign_up_fee ): void {
		$this->sign_up_fee = $sign_up_fee;
	}

	public function get_trial_length(): int {
		return $this->trial_length;
	}

	public function set_trial_length( int $trial_length ): void {
		$this->trial_length = max( 0, $trial_length );
	}

	public function get_trial_end(): ?string {
		return $this->trial_end;
	}

	public function set_trial_end( ?string $trial_end ): void {
		$this->trial_end = $trial_end;
	}

	public function get_next_payment_date(): ?string {
		return $this->next_payment_date;
	}

	public function set_next_payment_date( ?string $next_payment_date ): void {
		$this->next_payment_date = $next_payment_date;
	}

	public function get_start_date(): ?string {
		return $this->start_date;
	}

	public function set_start_date( ?string $start_date ): void {
		$this->start_date = $start_date;
	}

	public function get_end_date(): ?string {
		return $this->end_date;
	}

	public function set_end_date( ?string $end_date ): void {
		$this->end_date = $end_date;
	}

	public function get_date_created(): ?string {
		return $this->date_created;
	}

	public function set_date_created( ?string $date_created ): void {
		$this->date_created = $date_created;
	}

	public function get_date_modified(): ?string {
		return $this->date_modified;
	}

	public function set_date_modified( ?string $date_modified ): void {
		$this->date_modified = $date_modified;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_meta(): array {
		return $this->meta;
	}

	/**
	 * @param array<string, mixed> $meta Meta values.
	 * @return void
	 */
	public function set_meta( array $meta ): void {
		$this->meta = $meta;
	}

	/**
	 * Get a meta value.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_meta_value( string $key, $default = null ) {
		return $this->meta[ $key ] ?? $default;
	}

	/**
	 * Set a meta value.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return void
	 */
	public function set_meta_value( string $key, $value ): void {
		$this->meta[ $key ] = $value;
	}
}
