<?php

namespace Vivait\WorkerCommandBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wrep\Daemonizable\Command\EndlessCommand;
use Wrep\Daemonizable\Command\EndlessContainerAwareCommand;

abstract class WorkerCommand extends EndlessContainerAwareCommand
{

    public function __construct(){
        parent::__construct();
    }
    protected function configure()
    {
        $this->setName($this->setCommandNamespace())
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 5)
            ->addOption('ignore', 'i', InputOption::VALUE_OPTIONAL);

        //TODO allow extra arguments
        /**
        if(is_array($this->setArguments())){
            foreach($this->setArguments() as $argument){
                $this->addArgument($argument['name'], $argument['mode'], $argument['description']);
            }
        }**/

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

        $output->writeln(sprintf("Worker: watching tube \"%s\"", $tube));

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
     * Set the namespace of the command, e.g. vivait:queue:worker:email
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