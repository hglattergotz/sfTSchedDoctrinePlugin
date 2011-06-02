<?php
/**
 * TaskSchedulerApi tests.
 */
include dirname(__FILE__).'/../../../../test/bootstrap/unit.php';

$t = new lime_test(3);

reload();

$task = array(
  'name' => 'testTask',
  'brief-description' => 'testTask',
  'detailed-description' => 'This is a test');
$time = null;

$api = new TaskSchedulerApi($configuration);
$api->scheduleNewTask($task, $time);

$newTask = TSchedTaskTable::getInstance()->find(1);
$t->is($newTask->getName(), 'testTask', '->getName() returns previously saved name');
$t->is($newTask->getBriefDescription(), 'testTask', '->getBriefDescription() returns previously saved value');
$t->is($newTask->getDetailedDescription(), 'This is a test', '->getDetailedDescription() returns previously saved vale');
