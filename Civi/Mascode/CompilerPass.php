<?php

/**
 * CompilerPass does not execute until all services (like action provider) have been registered.
 */

namespace Civi\Mascode;

use CRM_Mascode_ExtensionUtil as E;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container)
  {
    // This can be removed once we move service definitions to YAML.
    // Only proceed if FormProcessor (action_provider) is available
    if (!$container->hasDefinition('action_provider')) {
      return;
    }

    $actionProvider = $container->getDefinition('action_provider');

    // Define all your FormProcessor actions here
    $actions = [
      [
        'id' => 'ExampleFormAction',
        'class' => 'Civi\Mascode\FormProcessor\Action\ExampleFormAction',
        'label' => E::ts('mas: Example Form Action'),
        'options' => [],
      ],
      // Add more actions here easily later
      // [
      //   'id' => 'AnotherAction',
      //   'class' => 'Civi\Mascode\FormProcessor\Action\AnotherAction',
      //   'label' => E::ts('mas: Another Action'),
      //   'options' => [],
      // ],
    ];

    foreach ($actions as $action) {
      $actionProvider->addMethodCall('addAction', [
        $action['id'],
        $action['class'],
        $action['label'],
        $action['options'],
      ]);
    }
  }
}
