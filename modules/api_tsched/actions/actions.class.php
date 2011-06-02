<?php
/**
 * api actions.
 *
 * @package    lsistats
 * @subpackage api
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class api_tschedActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
//    $this->forward('default', 'module');
  }

  public function executeGetavailabletasks(sfWebRequest $request)
  {
    $cfg = $this->context->getConfiguration();
    $api = new TaskSchedulerApi($this->dispatcher, $cfg);
    $this->objects = $api->getAvailableTasks();
  }

  public function executeGetavailabletask(sfWebRequest $request)
  {
    $className = $request->getParameter('taskname');
    $cfg = $this->context->getConfiguration();
    $api = new TaskSchedulerApi($this->dispatcher, $cfg);
    $this->objects = $api->getAvailableTaskByClassName($className);
  }

  public function executeGetscheduledtasks(sfWebRequest $request)
  {
    $cfg = $this->context->getConfiguration();
    $api = new TaskSchedulerApi($this->dispatcher, $cfg);
    $this->objects = $api->getScheduledTasks();
  }

  public function executeGetscheduledtask(sfWebRequest $request)
  {
    $id = $request->getParameter('id');
    $cfg = $this->context->getConfiguration();
    $api = new TaskSchedulerApi($this->dispatcher, $cfg);
    $this->objects = $api->getScheduledTask($id);
  }
}
