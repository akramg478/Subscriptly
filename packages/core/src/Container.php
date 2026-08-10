<?php
/**
 * Lightweight service container.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly;

/**
 * Simple PSR-11 inspired service container.
 */
final class Container {

	/**
	 * Registered services.
	 *
	 * @var array<string, mixed>
	 */
	private array $services = array();

	/**
	 * Register a service.
	 *
	 * @param string $id    Service identifier.
	 * @param mixed  $value Service instance or factory.
	 * @return void
	 */
	public function set( string $id, $value ): void {
		$this->services[ $id ] = $value;
	}

	/**
	 * Retrieve a service.
	 *
	 * @param string $id Service identifier.
	 * @return mixed
	 */
	public function get( string $id ) {
		if ( ! isset( $this->services[ $id ] ) ) {
			throw new \RuntimeException(
				sprintf(
					'Service "%s" is not registered in the Subscriptly container.',
					esc_html( (string) $id )
				)
			);
		}

		return $this->services[ $id ];
	}

	/**
	 * Determine whether a service exists.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->services[ $id ] );
	}
}
