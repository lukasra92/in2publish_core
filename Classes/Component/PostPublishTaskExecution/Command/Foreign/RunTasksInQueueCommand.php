<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Command\Foreign;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function json_encode;

class RunTasksInQueueCommand extends Command
{
    public const IDENTIFIER = 'in2publish_core:publishtasksrunner:runtasksinqueue';
    protected ContextService $contextService;
    protected TaskRepository $taskRepository;

    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }

    public function injectTaskRepository(TaskRepository $taskRepository): void
    {
        $this->taskRepository = $taskRepository;
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isForeign();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = [];
        // Tasks which should get executed do not have an execution begin
        $tasksToExecute = $this->taskRepository->findByExecutionBegin();
        foreach ($tasksToExecute as $task) {
            try {
                $success = $task->execute();
                $result[] = 'Task ' . $task->getUid() . ($success ? ' was executed successfully' : ' failed');
                $result[] = $task->getMessages();
            } catch (Throwable $e) {
                $result[] = $e->getMessage();
            }
            $this->taskRepository->update($task);
        }
        if (empty($result)) {
            $result[] = 'There was nothing to execute';
        }
        $output->write(json_encode($result, JSON_THROW_ON_ERROR));
        return Command::SUCCESS;
    }
}
