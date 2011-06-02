<?php
/**
 *
 */
class TaskSchedulerApi
{
  protected $dispatcher;
  protected $configuration;
  protected $taskFiles;

  /**
   *
   * @param sfProjectConfiguration $cfg
   */
  public function __construct(sfProjectConfiguration $cfg)
  {
    $this->dispatcher = $cfg->getEventDispatcher();
    $this->configuration = $cfg;
  }

  /**
   * Return a list of all task class names including arguments and options that
   * are a sub class of AbstractSchedulableTask.
   * This can be used by a gui to discover all available task that can be
   * scheduled using the gui.
   * 
   * @return array
   */
  public function getAvailableTasks()
  {
    $tasks = array();
    $path = sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'task';
    $this->loadTasks($this->configuration);

    foreach (get_declared_classes() as $class)
    {
      $r = new ReflectionClass($class);

      if ($r->isSubclassOf('AbstractSchedulableTask') && !$r->isAbstract()/* && $r->hasProperty('taskSchedulerDescription')*/)
      {
        if (false !== strpos($r->getFileName(), $path))
        {
          $task = new $class($this->dispatcher, new sfFormatter());
          $commandOptions = $task->getOptions();
          $arguments = $task->getArguments();
          $options = array();

          foreach ($commandOptions as $opt)
          {
            $options[] = array(
              'name'     => $opt->getName(),
              'shortcut' => $opt->getShortcut(),
              'required' => $opt->isParameterRequired(),
              'default'  => $opt->getDefault(),
              'help'     => $opt->getHelp());
          }

          $tasks[] = array(
            'name' => $class,
            'brief-description' => $task->getBriefDescription(),
            'detailed-description' => $task->getDetailedDescription(),
            'options' => $options,
            'arguments' => $arguments);
        }
      }
    }

    return $tasks;
  }

  /**
   *
   * @param <type> $className
   * @return <type>
   */
  public function getAvailableTask($className)
  {
    $tasks = $this->getAvailableTasks();

    foreach ($tasks as $task)
    {
      if ($task['name'] == $className)
      {
        return $task;
      }
    }

    return array();
  }

  /**
   * Return a list of all tasks that have been configured to run at a specified
   * time.
   */
  public function getScheduledTasks()
  {
    $tasks = TSchedTaskTable::getInstance()->findAll(Doctrine_Core::HYDRATE_ARRAY);

    return $tasks;
  }

  /**
   *
   * @param <type> $id
   * @return <type>
   */
  public function getScheduledTask($id)
  {
    return TSchedTaskTable::getInstance()->find($id, Doctrine_Core::HYDRATE_ARRAY);
  }

  /**
   *
   * @param <type> $task
   * @param <type> $time
   */
  public function scheduleNewTask($task, $time)
  {
    $newTask = new TSchedTask();
    $newTask->setName($task['name']);
    $newTask->setBriefDescription($task['brief-description']);
    $newTask->setDetailedDescription($task['detailed-description']);
    $newTask->setClassName($task['name']);
    $newTask->save();
    $id = $newTask->getId();

    foreach ($task['arguments'] as $key => $val)
    {
      $arg = new TSchedTaskArgument();
      $arg->setName($key);
      $arg->setTschedTaskId($id);
      $arg->setValue($val);
      $arg->save();
    }

    foreach ($task['options'] as $key => $val)
    {
      $opt = new TSchedTaskOption();
      $opt->setName($key);
      $opt->setTschedTaskId($id);
      $opt->setValue($val);
      $opt->save();
    }
  }

  /**
   *
   * @param <type> $id
   * @param <type> $arguments
   * @param <type> $options
   * @param <type> $time
   */
  public function updateScheduledTask($id, $arguments, $options, $time)
  {

  }

  /**
   *
   * @param <type> $id
   */
  public function removeScheduledTask($id)
  {

  }
  /**
   * Loads all available tasks.
   *
   * Looks for tasks in the symfony core, the current project and all project plugins.
   *
   * @param sfProjectConfiguration $configuration The project configuration
   */
  protected function loadTasks(sfProjectConfiguration $configuration)
  {
    // Symfony core tasks
    $dirs = array(sfConfig::get('sf_symfony_lib_dir').'/task');

    // Plugin tasks
    foreach ($configuration->getPluginPaths() as $path)
    {
      if (is_dir($taskPath = $path.'/lib/task'))
      {
        $dirs[] = $taskPath;
      }
    }

    // project tasks
    $dirs[] = sfConfig::get('sf_lib_dir').'/task';

    $finder = sfFinder::type('file')->name('*Task.class.php');
    foreach ($finder->in($dirs) as $file)
    {
      $this->taskFiles[basename($file, '.class.php')] = $file;
    }

    // register local autoloader for tasks
    spl_autoload_register(array($this, 'autoloadTask'));

    // require tasks
    foreach ($this->taskFiles as $task => $file)
    {
      // forces autoloading of each task class
      class_exists($task, true);
    }

    // unregister local autoloader
    spl_autoload_unregister(array($this, 'autoloadTask'));
  }

  /**
   * Autoloads a task class
   *
   * @param  string  $class  The task class name
   *
   * @return Boolean
   */
  protected function autoloadTask($class)
  {
    if (isset($this->taskFiles[$class]))
    {
      require_once $this->taskFiles[$class];

      return true;
    }

    return false;
  }
}