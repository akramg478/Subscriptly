<?php
/**
 * Payment gateway contract for Pro extensions.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Contracts;

use Subscriptly\Models\Subscription;

/**
 * Interface for recurring payment gateway implementations.
 */
interface GatewayInterface {

	/**
	 * Gateway identifier.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Attempt to process an automatic renewal payment.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return bool True when payment succeeded.
	 */
	public function process_renewal( Subscription $subscription ): bool;
}
