<?php

namespace Vivait\WorkerCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wrep\Daemonizable\Command\EndlessContainerAwareCommand;

abstract class WorkerCommand extends EndlessContainerAwareCommand
{
    private $tube;
    private $ignore;

    protected function configure()
    {
        $this->setArguments();
        $this->setName($this->setCommandNamespace())
            ->addArgument('tube', InputArgument::REQUIRED)
            ->addArgument('ignore', InputArgument::OPTIONAL)
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, '', self::DEFAULT_TIMEOUT);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Wrep\Daemonizable\Exception\ShutdownEndlessCommandException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Get arguments and options
        $tube = $input->getArgument('tube');
        $ignore = $input->getArgument('ignore');

        //Set timeout
        $this->setTimeout($input->getOption('timeout'));

        //Get beanstalk
        $container = $this->getContainer();
        $pheanstalk = $container->get("leezy.pheanstalk"); //TODO abstract out for different queues.

        //Watch tube
        $job = $pheanstalk
            ->watch($tube)
            ->ignore($ignore)
            ->reserve();

        //Do work
        try {
            $output->writeln(
                sprintf("[%s] <info>Performing job</info>", $this->getName())
            );

            $this->performAction($job->getData(), $input, $output);
            $pheanstalk->delete($job);
            $output->writeln(
                sprintf("[%s] <info>Job finished successfully and removed</info>", $this->getName())
            );

        } catch (\Exception $e) {
            $pheanstalk->bury($job);
            $output->writeln(
                sprintf("[%s] <error>Job buried: %s (%d)</error>", $this->getName(), $e->getMessage(), $e->getCode())
            );

            $this->handleException($e, $input, $output);
        }
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                "[%s] <comment>watching tube: %s</comment>",
                $this->getName(),
                $input->getArgument('tube')
            )
        );
    }

    /**
     * Set the namespace of the command, e.g. vivait:queue:worker:email
     *
     * @return string
     */
    abstract protected function setCommandNamespace();

    /**
     * Set any extra arguments for the worker. This does not include the tube or ignore arguments required by
     * beanstalk.
     *
     * @return array|null
     */
    abstract protected function setArguments();

    /**
     * @param $payload
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    abstract protected function performAction($payload, InputInterface $input, OutputInterface $output);

    /**
     * @param \Exception $e
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    abstract protected function handleException(\Exception $e, InputInterface $input, OutputInterface $output);
} 