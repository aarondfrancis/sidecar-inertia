<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Inertia;

use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Sidecar;
use Symfony\Component\Process\Process;

class SSR extends LambdaFunction
{
    public function name()
    {
        return 'Inertia-SSR';
    }

    public function memory()
    {
        // The more memory you give your function, the faster it will
        // run, but the more expensive it will be. You will need to
        // find the settings that are right for your application.
        return 1024;
    }

    public function handler()
    {
        // This format meets AWS requirements. The compiled SSR file
        // is "ssr.js", built by Laravel Mix. The function that
        // handles the incoming event is called "handler."
        return 'public/js/ssr.handler';
    }

    public function package()
    {

        // Since webpack compiles everything down to a single
        // file, it's the only thing we need to ship.
        return [
            'public/js/ssr.js',
        ];
    }

    public function beforeDeployment()
    {
        Sidecar::log('Executing beforeDeployment hooks');

        // Compile the SSR bundle before deploying.
        $this->compileJavascript();
    }

    protected function compileJavascript()
    {
        Sidecar::log('Compiling Inertia SSR bundle.');

        $command = ['npx', 'mix', '--mix-config=webpack.ssr.mix.js'];

        if (Sidecar::getEnvironment() === 'production') {
            $command[] = '--production';
        }

        Sidecar::log('Running ' . implode(' ', $command));

        $process = new Process($command, $cwd = base_path(), $env = []);

        // mustRun will throw an exception if it fails, which is what we want.
        $process->setTimeout(60)->disableOutput()->mustRun();

        Sidecar::log('JavaScript SSR bundle compiled!');
    }
}
