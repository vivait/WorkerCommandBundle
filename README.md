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
}
```
    
### Do some work

The `performAction()` method will be called whenever a new job is available. This method should be used to perform the
worker's task.

Set the name of the command using `setCommandNamespace()`.

### Running the command

As long as this class resides in your application's `Command` directory, Symfony should autodetect it. Run `php app/console`
to see a list of available commands.

To run the command defined in the above class, run `php app/console vivait:queue:worker:email` in your terminal.

#### Arguments
The command above must be provided with the `tube` argument, for example, `php app/console vivait:queue:worker:email "vivait.myapp.email"`

Optionally, an `ignore` argument can be set to specify an ignored tube.

#### Options
`--timeout` will set the interval between running the command, with a default setting of 5 seconds.

E.g. `php app/console vivait:queue:worker:email -t 0.5`

### Exception handling
WorkerCommand catches any `\Exception`. Internally, WorkerCommand prints the error message and code to the console, but
by implementing `handleException()`, it's possible to further interact with the exception.
