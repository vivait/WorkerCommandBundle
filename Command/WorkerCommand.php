<?php

namespace Vivait\WorkerCommandBundle\Command;


use Leezy\PheanstalkBundle\Proxy\PheanstalkProxyInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wrep\Daemonizable\Command\EndlessCommand;
use Wrep\Daemonizable\Command\EndlessContainerAwareCommand;

abstract class WorkerCommand extends EndlessContainerAwareCommand
{
    private $first_run = true;

    protected function configure()
    {
        $this->setName($this->setCommandNamespace())
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, '', self::DEFAULT_TIMEOUT)
            ->addOption('ignore', 'i', InputOption::VALUE_OPTIONAL);

        //TODO allow extra arguments

//        if(is_array($this->setArguments())){
//            foreach($this->setArguments() as $argument){
//                $this->addArgument($argument['name'], $argument['mode'], $argument['description']);
//            }
//        }

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Wrep\Daemonizable\Exception\ShutdownEndlessCommandException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->throwExceptionOnShutdown();

        $tube = $this->setTube();
        $ignore = $input->getOption('ignore');

        //Set timeout
        $this->setTimeout($input->getOption('timeout'));

        $container = $this->getContainer();
        //TODO abstract out for different queues.
        $pheanstalk = $container->get("leezy.pheanstalk");

        $job = $pheanstalk
            ->watch($tube)
            ->ignore($ignore)
            ->reserve();

        $this->performAction($job->getData(), $input, $output);

        //Remove job
        $pheanstalk->delete($job);
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output){
        $output->writeln(sprintf("<info>Worker %s:</info> <comment>watching tube \"%s\"</comment>", $this->setCommandNamespace(), $this->setTube()));
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
    //abstract protected function setArguments();

    /**
     * @param $payload
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    abstract protected function performAction($payload, InputInterface $input, OutputInterface $output);

    /**
     * Set the tube to watch
     *
     * @return string
     */
    abstract protected function setTube();

} 