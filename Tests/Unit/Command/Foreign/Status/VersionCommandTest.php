<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use In2code\In2publishCore\Command\Foreign\Status\VersionCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use In2code\In2publishCore\Utility\ExtensionUtility;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use const PHP_EOL;

class VersionCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     */
    public function testCommandCanBeExecuted(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new VersionCommand();

        unset($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly']);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = 'truthy';

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame(
            'Version: ' . ExtensionUtility::getExtensionVersion('in2publish_core') . PHP_EOL,
            $output->fetch()
        );
    }
}
