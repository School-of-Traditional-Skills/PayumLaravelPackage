<?php

namespace SchoolOfTraditionalSkills\PayumLaravelPackage;

use SchoolOfTraditionalSkills\PayumLaravelPackage\Action\GetHttpRequestAction;
use SchoolOfTraditionalSkills\PayumLaravelPackage\Action\ObtainCreditCardAction;
use SchoolOfTraditionalSkills\PayumLaravelPackage\Security\TokenFactory;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Payum\Core\Bridge\Symfony\ReplyToSymfonyResponseConverter;
use Payum\Core\Bridge\Symfony\Security\HttpRequestVerifier;
use Payum\Core\Payum;
use Payum\Core\PayumBuilder;
use Payum\Core\Registry\StorageRegistryInterface;
use Payum\Core\Storage\StorageInterface;

class PayumServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerBuilder();
		$this->registerActions();
		$this->registerPayum();
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->loadRoutesFrom(__DIR__ . '/../routes/payum.php');
	}

	/**
	 * Registers Payum Builder.
	 *
	 * @return  void
	 */
	protected function registerBuilder(): void
	{
		$this->app->bind(PayumBuilder::class, function ($app) {
			$builder = new PayumBuilder();

			$builder
				->setTokenFactory(
					fn(
						StorageInterface $tokenStorage,
						StorageRegistryInterface $registry
					) => new TokenFactory($tokenStorage, $registry)
				)
				->setHttpRequestVerifier(
					fn(StorageInterface $tokenStorage) => new HttpRequestVerifier($tokenStorage)
				)
				->setCoreGatewayFactory(function (array $defaultConfig) {
					$factory = new CoreGatewayFactory($defaultConfig);
					$factory->setContainer($this->app);

					return $factory;
				})
				->setCoreGatewayFactoryConfig([
					'payum.action.get_http_request' => 'payum.action.get_http_request',
					'payum.action.obtain_credit_card' => 'payum.action.obtain_credit_card',
				])
				->setGenericTokenFactoryPaths([ // @phpstan-ignore-line
					'capture' => 'payum.capture',
					'notify' => 'payum.notify',
					'authorize' => 'payum.authorize',
					'refund' => 'payum.refund',
				])
			;

			return $builder;
		});
	}

	/**
	 * Registers Payum actions.
	 *
	 * @return  void
	 */
	private function registerActions(): void
	{
		$this->app->singleton('payum.action.get_http_request', function ($app) {
			return new GetHttpRequestAction();
		});

		$this->app->singleton('payum.action.obtain_credit_card', function ($app) {
			return new ObtainCreditCardAction();
		});
	}

	/**
	 * Registers Payum instance.
	 *
	 * @return  void
	 */
	private function registerPayum(): void
	{
		$this->app->singleton(Payum::class, function ($app) {
			return $app->make(PayumBuilder::class)->getPayum();
		});
	}
}
