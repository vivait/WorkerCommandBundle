# WorkerCommandBundle

Integrates [Pheanstalk](https://github.com/pda/pheanstalk) and [endless commands](https://github.com/mac-cain13/daemonizable-command) to allow easy creation of custom daemonizable beanstalk workers.

## Install


Add "vivait/worker-command-bundle": "dev-master" to your composer.json and run composer update
    
Update your AppKernel:

```php
public function registerBundles()
{
    $bundles = array(
        ...
        new Vivait\WorkerCommandBundle\VivaitWorkerCommandBundle(),
        new Leezy\PheanstalkBundle\LeezyPheanstalkBundle(),
}
```
    
Configure [LeezyPheanstalkBundle](https://github.com/armetiz/LeezyPheanstalkBundle/blob/master/Resources/doc/2-configuration.md).
    

## Basic Usage

Simply extend `Vivait\WorkerCommandBundle\Command\WorkerCommand` and implement its abstract methods.

```php
class EmailWorkerCommand extends WorkerCommand
{

    protected function performAction($payload, InputInterface $input, OutputInterface $output)
    {
        $output->writeln($payload);
    }

    protected function setCommandNamespace()
    {
        return "vivait:queue:worker:email";
    }

    protected function setTube()
    {
        return 'vivait.email';
    }
    
    protected function onFirstRun(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Watching the email tube")
    }
}
```
    
### Do some work

The `performAction()` method will be called whenever a new job is available. This method should be used to perform the
worker's task.

### Running the command

As long as this class resides in your application's `Command` directory, Symfony should autodetect it. Run `php app/console`
to see a list of available commands.

To run the command defined in the above class, run `php app/console vivait:queue:worker:email` in your terminal. 

### Options

By default, the worker will accept two optional arguments. 

1. `--timeout` will set the interval between running the command, with a
default setting of 5 seconds.

2. `--ignore` will set the tube name to ignore

E.g. `php app/console vivait:queue:worker:email -t 0.5`