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

namespace AppBundle\Command;

use AppBundle\Routing\Matcher\Dumper\DirectMatcherDumper;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class DumpMigratedRoutesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('router')) {
            return false;
        }
        $router = $this->getContainer()->get('router');
        if (!$router instanceof RouterInterface) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('router:dump:migrated')
            ->setDefinition([
                new InputArgument(
                    'script-name',
                    InputArgument::OPTIONAL,
                    'The script name of the application\'s front controller.'
                ),
                new InputArgument(
                    'output-file',
                    InputArgument::OPTIONAL,
                    'The path to the output file where the routes will be dumped.',
                    getcwd() . '/../.htaccess'
                ),
                new InputOption('base-uri', null, InputOption::VALUE_REQUIRED, 'The base URI'),
            ])
            ->setDescription('Dumps all migrated routes as Apache rewrite rules')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> dumps all routes as Apache rewrite rules.
 
  <info>php %command.full_name%</info>
 
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $router = $this->getContainer()->get('router');

        $dumpOptions = [];

        if ($input->getArgument('script-name')) {
            $dumpOptions['script_name'] = $input->getArgument('script-name');
        }

        if ($input->getOption('base-uri')) {
            $dumpOptions['base_uri'] = $input->getOption('base-uri');
        }

        $outputFilePath = $input->getArgument('output-file');

        if (!is_writable(dirname($outputFilePath))) {
            throw new InvalidArgumentException(
                sprintf('The path "%s" is not writable!', dirname($outputFilePath))
            );
        }

        $dumper = new DirectMatcherDumper(
            $input->getParameterOption(['--env', '-e'], 'dev'),
            $router->getRouteCollection()
        );

        $output->writeln(
            sprintf('<info>Dumping routes to <comment>%s</comment>', $outputFilePath)
        );

        file_put_contents(
            $outputFilePath,
            $dumper->dump($dumpOptions)
        );
    }
}
