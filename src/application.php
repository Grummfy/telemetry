<?php

namespace atoum\telemetry;

use atoum\telemetry\controllers\error;
use atoum\telemetry\controllers\hook;
use atoum\telemetry\controllers\telemetry;
use atoum\telemetry\providers\influxdb;
use atoum\telemetry\providers\log;
use atoum\telemetry\providers\resque;
use JDesrosiers\Silex\Provider\SwaggerServiceProvider;
use Knp\Provider\ConsoleServiceProvider;
use Silex;
use Silex\Provider\ValidatorServiceProvider;
use SwaggerUI\Silex\Provider\SwaggerUIServiceProvider;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SWG\Info(
 *     title="atoum telemetry",
 *     termsOfServiceUrl="http://helloreverb.com/terms/",
 *     contact="team@atoum.org",
 *     license="BSD-3-Clause",
 *     licenseUrl="https://raw.githubusercontent.com/atoum/telemetry/master/LICENSE"
 * )
 */
class application extends Silex\Application
{
	public function boot()
	{
		$this
			->register(
				new SwaggerServiceProvider(),
				[
					'swagger.apiVersion' => 1,
					'swagger.apiDocPath' => '/docs',
					'swagger.srcDir' => __DIR__ . '/../vendor/zircote/swagger-php/library',
					'swagger.servicePath' => __DIR__ . '/controllers',
				]
			)
			->register(
				new SwaggerUIServiceProvider(),
				[
					'swaggerui.path'       => '/swagger',
					'swaggerui.apiDocPath' => '/docs'
				]
			)
			->register(new ValidatorServiceProvider())
			->register(new log())
			->register(new influxdb())
			->register(new resque())
			->register(new ConsoleServiceProvider())
		;

		parent::boot();
	}

	public function run(Request $request = null)
	{
		$this->post('/hook/{token}', new hook($this['auth_token'], $this['broker'], $this['validator'], $this['logger']));
		$this->post('/', new telemetry($this['broker'], $this['validator'], $this['logger']));
		$this->error(new error());

		parent::run($request);
	}
}
