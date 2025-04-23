<?php

namespace Civi\Mascode;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface {
  public function process(ContainerBuilder $container) {
    // No custom logic needed — services.yml handles everything
  }
}
