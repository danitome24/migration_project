<?php
/**
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/**
 * @author Daniel Tome <danieltomefer@gmail.com>
 */
namespace AppBundle\Routing\Matcher\Dumper;

use LogicException;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Matcher\Dumper\MatcherDumper;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DirectMatcherDumper extends MatcherDumper
{

    private $env;

    public function __construct($env, RouteCollection $routes)
    {
        $this->env = $env;

        parent::__construct($routes);
    }

    /**
     * Dumps a set of routes to a string representation of executable code
     * that can then be used to match a request against these routes.
     *
     * @param array $options An array of options
     *
     * @return string Executable code
     */
    public function dump(array $options = [])
    {
        if ($this->getRoutes()->count() == 0) {
            return '';
        }
        $scriptSymfonyName = $this->env == 'dev' ? 'symfony/web/app_dev.php' : 'symfony/web/app.php';

        $options = array_merge([
            'script_name' => $scriptSymfonyName,
            'base_uri' => '',
        ], $options);

        $options['script_name'] = self::escape($options['script_name'], ' ', '\\');

        $rules = [];
        $processedRoutes = [];

        $rules[] = '<IfModule mod_rewrite.c>
RewriteEngine On';
        foreach ($this->getRoutes()->all() as $name => $route) {
            if ($this->isAlreadyDumped($route, $processedRoutes)) {
                continue;
            }
            if ($route->hasOption('exclude-env')) {
                $excludedEnvironments = array_map('trim', explode(',', $route->getOption('exclude-env')));

                if (in_array($this->env, $excludedEnvironments)) {
                    continue;
                }
            }

            if ($route->getCondition()) {
                throw new LogicException(
                    sprintf('Unable to dump the routes for Apache as route "%s" has a condition.', $name)
                );
            }

            $rules[] = $this->dumpRoute($name, $route, $options, $processedRoutes);
            $processedRoutes[] = $route->getPath();
        }
        $rules[] = '# Here some of your custom rewrites...
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ page.php [QSA,L]';
        $rules[] = '</IfModule>';

        return implode("\n\n", $rules) . "\n";
    }

    /**
     * Dumps a single route
     *
     * @param string $name Route name
     * @param Route $route The route
     * @param array $options Options
     *
     * @return string The compiled route
     */
    private function dumpRoute($name, $route, array $options, array &$processedRoutes)
    {
        $compiledRoute = $route->compile();

        // prepare the apache regex
        $regex = $this->routeRegexp($compiledRoute);
        $regex = '^' . self::escape(preg_quote($options['base_uri']) . substr($regex, 1), ' ', '\\');

        $hasTrailingSlash = '/$' === substr($regex, -2) && '^/$' !== $regex;

        $rule = ["# $name"];

        $hostRegex = $compiledRoute->getHostRegex();

        // redirect with trailing slash appended
        if ($hasTrailingSlash) {

            if (null !== $hostRegex) {
                $rule[] = $this->buildHostRewriteCond($hostRegex);
            }

            $rule[] = 'RewriteCond %{REQUEST_URI} ' . substr($regex, 0, -2) . '$';
            $rule[] = 'RewriteRule .* $0/ [QSA,L,R=301]';
        }

        if (null !== $hostRegex) {
            $rule[] = $this->buildHostRewriteCond($hostRegex);
        }

        // the main rule
        $rule[] = "RewriteCond %{REQUEST_URI} $regex";
        $rule[] = "RewriteRule .* {$options['script_name']} [QSA,L]";

        $processedRoutes[$hostRegex][] = $regex;

        return implode("\n", $rule);
    }

    /**
     * Converts a regex to make it suitable for mod_rewrite
     *
     * @param string $regex The regex
     *
     * @return string The converted regex
     */
    private function regexToApacheRegex($regex)
    {
        $regexPatternEnd = strrpos($regex, $regex[0]);

        return preg_replace('/\?P<.+?>/', '', substr($regex, 1, $regexPatternEnd - 1));
    }

    /**
     * Escapes a string.
     *
     * @param string $string The string to be escaped
     * @param string $char The character to be escaped
     * @param string $with The character to be used for escaping
     *
     * @return string The escaped string
     */
    private static function escape($string, $char, $with)
    {
        $escaped = false;
        $output = '';
        foreach (str_split($string) as $symbol) {
            if ($escaped) {
                $output .= $symbol;
                $escaped = false;
                continue;
            }
            if ($symbol === $char) {
                $output .= $with . $char;
                continue;
            }
            if ($symbol === $with) {
                $escaped = true;
            }
            $output .= $symbol;
        }

        return $output;
    }

    private function buildHostRewriteCond($hostRegex)
    {
        $apacheHostRegex = $this->regexToApacheRegex($hostRegex);
        $apacheHostRegex = self::escape($apacheHostRegex, ' ', '\\');

        return sprintf('RewriteCond %%{HTTP_HOST} %s', $apacheHostRegex);
    }

    public function isAlreadyDumped(Route $route, array &$processedPaths)
    {
        $compiledRoute = $route->compile();

        $hostRegexp = $compiledRoute->getHostRegex();

        if (!isset($processedPaths[$hostRegexp])) {
            $processedPaths[$hostRegexp] = [];

            return false;
        }

        return in_array($this->routeRegexp($compiledRoute), $processedPaths[$hostRegexp], true);
    }

    private function routeRegexp(CompiledRoute $compiledRoute)
    {
        return $this->regexToApacheRegex($compiledRoute->getRegex());
    }
}
