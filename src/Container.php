<?php
/**
 * App          : Pyramid PHP Fremework
 * Author       : Nihat Doğan
 * Email        : info@pyramid.com
 * Website      : https://www.pyramid.com
 * Created Date : 01/01/2025
 * License GPL
 *
 */

namespace Pyramid;

use ReflectionException;

class Container {
	protected static $bindings = [];
	protected static $instances = [];
	protected static $aliases = [];


	/**
	 * @param $abstract
	 * @param $concrete
	 * Bir servis sınıfını container'a bind et
	 *
	 * @return void
	 */
	public static function bind( $abstract, $concrete = null ): void {
		if ( is_null( $concrete ) ) {
			$concrete = $abstract;
		}

		/** Servisin hangi sınıf tarafından sağlanacağını kaydet */
		self::$bindings[ $abstract ] = $concrete;
	}


	/**
	 * @param $abstract
	 * @param $concrete
	 * Singleton olarak bir servis kaydet
	 *
	 * @return void
	 */
	public static function singleton( $abstract, $concrete = null ): void {
		if ( is_null( $concrete ) ) {
			$concrete = $abstract;
		}

		/** Singleton olarak kaydediyoruz */
		self::$bindings[ $abstract ]  = $concrete;
		self::$instances[ $abstract ] = null; // İlk başta instance yok
	}


	/**
	 * @param $abstract
	 * Bir servise erişmek için container'dan al
	 *
	 * @return mixed|object|null
	 * @throws \Exception
	 */
	public static function make( $abstract ) {

		/** Eğer servisin daha önce bir örneği oluşturulduysa onu döndür */
		if ( isset( self::$instances[ $abstract ] ) ) {
			if ( self::$instances[ $abstract ] === null ) {

				/** Eğer singleton ve örnek henüz oluşturulmamışsa, build ile oluşturuyoruz */
				self::$instances[ $abstract ] = Container::build( self::$bindings[ $abstract ] );
			}

			return self::$instances[ $abstract ];
		}

		/** Eğer servis bir sınıf ise, yeni bir örnek oluştur ve döndür */
		if ( isset( self::$bindings[ $abstract ] ) ) {
			return Container::build( self::$bindings[ $abstract ] );
		}

		/** Eğer servis bulunamazsa hata döndür */
		throw new \Exception( lang( "Service not found:" ) . $abstract );
	}


	/**
	 * @param $concrete
	 * Bağımlılıkları çözümlemek için sınıf örneğini oluştur
	 *
	 * @return mixed|object|null
	 * @throws ReflectionException
	 */
	protected static function build( $concrete ) {
		if ( $concrete instanceof \Closure ) {
			return $concrete( new self() );
		}

		$reflector = new \ReflectionClass( $concrete );

		if ( $constructor = $reflector->getConstructor() ) {
			$parameters   = $constructor->getParameters();
			$dependencies = [];

			foreach ( $parameters as $parameter ) {
				$dependency = $parameter->getClass();
				if ( $dependency ) {
					$dependencies[] = Container::make( $dependency->name );
				}
			}

			return $reflector->newInstanceArgs( $dependencies );
		}

		return new $concrete;
	}


	/**
	 * @param $name
	 * @param $class
	 * Alias'ları kaydediyoruz
	 *
	 * @return void
	 */
	public static function setAlias( $name, $class ): void {
		self::$aliases[ $name ] = $class;
	}


	/**
	 * @param $name
	 * Alias ile sınıfı çözümleyip döndürüyoruz
	 *
	 * @return mixed
	 */
	public static function resolveAlias( $name ): mixed {
		if ( isset( self::$aliases[ $name ] ) ) {
			return self::$aliases[ $name ];
		}

		/** Eğer alias bulunmazsa null döner */
		return null;
	}


}
