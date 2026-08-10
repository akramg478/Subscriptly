<?php
/**
 * Service provider contract.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Contracts;

use Subscriptly\Container;

/**
 * Interface for modular service providers.
 */
interface ServiceProviderInterface {

	/**
	 * Register services and hooks.
	 *
	 * @return void
	 */
	public function register(): void;
}
