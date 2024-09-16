<?php

namespace SchoolOfTraditionalSkills\PayumLaravelPackage\Controller;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Redirector;
use Payum\Core\Bridge\Symfony\ReplyToSymfonyResponseConverter as ReplyConverter;
use Payum\Core\Payum;
use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\Capture;
use Symfony\Component\HttpFoundation\Request;

class CaptureController
{
	/**
	 * The Application Container instance.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * The Reply Converter instance.
	 *
	 * @var ReplyConverter
	 */
	private ReplyConverter $converter;

	/**
	 * The Payum instance.
	 *
	 * @var Payum
	 */
	private Payum $payum;

	/**
	 * The Redirector instance.
	 *
	 * @var Redirector
	 */
	private Redirector $redirect;

	/**
	 * Payment Capture action constructor.
	 *
	 * @param  Payum  $payum The Payum instance.
	 */
	public function __construct(Container $container, ReplyConverter $converter, Payum $payum, Redirector $redirect)
	{
		$this->container = $container;
		$this->converter = $converter;
		$this->payum = $payum;
		$this->redirect = $redirect;
	}

	/**
	 * Handles Payment Capture action.
	 *
	 * @param   string  $payumToken
	 * @return  mixed
	 */
	public function __invoke(string $payumToken)
	{
		/** @var Request $request */
		$request = $this->container->make(Request::class);
		$request->attributes->set('payum_token', $payumToken);

		$token = $this->payum->getHttpRequestVerifier()->verify($request);
		$gateway = $this->payum->getGateway($token->getGatewayName());

		try {
			$gateway->execute(new Capture($token));
		} catch (ReplyInterface $reply) {
			return $this->converter->convert($reply);
		}

		$this->payum->getHttpRequestVerifier()->invalidate($token);

		return $this->redirect->to($token->getAfterUrl());
	}
}
