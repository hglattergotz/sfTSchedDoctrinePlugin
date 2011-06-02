<?php
/**
 * Query the database for any scheduled tasks that match the dateTime parameter.
 * Any tasks that are returned are put on the work queue to be executed the next
 * time the taskRunner executes.
 */
class TaskScheduler
{
  protected $appLogger;
  protected $eventLogger;

  /**
   * Set the dic.
   *
   * @param ApplicationFileLogger $appLogger
   * @param EVentLogger           $eventLogger
   */
  public function __construct($appLogger = null, $eventLogger = null)
  {
    $this->appLogger = $appLogger;
    $this->eventLogger = $eventLogger;
  }

  /**
   * Use either the passed datetime stamp or the current timestamp to lookup in
   * sy_tsched_schedule if any tasks are scheduled to be run at this time. If
   * so, put them on the queue.
   *
   * @param <type> $dateTime Optional date time to use for lookup. This can be
   *                         used for debuging and development purposes. If no
   *                         parameter is passed then the current time is used.
   */
  public function run($dateTime = null)
  {
    try
    {
      if ($dateTime == null)
      {
        $dateTime = uDateTime::now();
      }

      $timeStamp = uDateTime::strtotime($dateTime);
      $minute = intval(date("i", $timeStamp));
      $hour = date("G", $timeStamp);
      $day = date("j", $timeStamp);
      $tasks = TschedScheduleTable::getScheduledTasks($minute, $hour, $day);

      foreach ($tasks as $task)
      {
        $queue = $this->newTaskQueue();
        $queue->setScheduleId($task->getId());
        $queue->save();

        $msg = 'TASK SCHEDULER|Queued task: '.$task->getDescription().' (id='.$task->getId().')';
        $this->logNotice($msg);
      }
    }
    catch (Exception $e)
    {
      $msg = 'TASK SCHEDULER|'.__METHOD__.':'.__LINE__.'|'.$e->getMessage();
      $this->logError($msg);
    }
  }

  /**
   *
   * @return LrcAutoReportSendQueue
   */
  protected function newTaskQueue()
  {
    return new PluginTschedQueue();
  }

  protected function logNotice($msg)
  {
    if ($this->appLogger !== null)
    {
      $this->appLogger->notice($msg);
    }

    if ($this->eventLogger !== null)
    {
      $this->eventLogger->notice($msg);
    }
  }

  protected function logError($msg)
  {
    if ($this->appLogger !== null)
    {
      $this->appLogger->err($msg);
    }

    if ($this->eventLogger !== null)
    {
      $this->eventLogger->err($msg);
    }
  }
}