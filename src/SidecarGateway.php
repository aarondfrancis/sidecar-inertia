<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Inertia;

use Exception;
use Hammerstone\Sidecar\LambdaFunction;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\Response;
use Throwable;

class SidecarGateway implements Gateway
{

    public function dispatch(array $page): ?Response
    {
        if (!Config::get('inertia.ssr.enabled', false)) {
            return null;
        }

        if (!$handler = Config::get('inertia.ssr.sidecar.handler')) {
            return null;
        }

        try {
            return $this->execute($handler, $page);
        } catch (Throwable $e) {
            if (Config::get('inertia.ssr.sidecar.debug')) {
                throw $e;
            }

            return null;
        }
    }

    protected function execute($handler, array $page): ?Response
    {
        $handler = app($handler);

        if (!$handler instanceof LambdaFunction) {
            throw new Exception('The configured Sidecar SSR Handler is not a Sidecar function.');
        }

        $result = $handler::execute($page)->throw();

        if (Config::get('inertia.ssr.sidecar.timings')) {
            Log::info('Sending SSR request to Lambda', $result->report());
        }

        $response = $result->body();

        return new Response(
            implode("\n", $response['head']),
            $response['body']
        );
    }
}
