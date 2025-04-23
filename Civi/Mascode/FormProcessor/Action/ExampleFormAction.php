<?php
namespace Civi\Mascode\FormProcessor\Action;

use \Civi\ActionProvider\Action\AbstractAction;
use \Civi\ActionProvider\Exception\InvalidParameterException;
use \Civi\ActionProvider\Parameter\ParameterBagInterface;
use \Civi\ActionProvider\Parameter\SpecificationBag;
use \Civi\ActionProvider\Parameter\Specification;

use Civi\Core\Lock\NullLock;
use Civi\FormProcessor\API\Exception;
use CRM_Mascode_ExtensionUtil as E;

/**
 * Class Example - example Form Processor Action
 *
 * @package Civi\Mascode\FormProcessor\Action
 * @author Brian Flett <brian.g.flett@gmail.com>
 * @date 23 Apr 2025
 * @license AGPL-3.0
 * For more info see
 *    https://docs.civicrm.org/formprocessor/en/latest/email-preferences/
 *    https://lab.civicrm.org/extensions/action-provider/-/blob/master/docs/howto_create_an_action_in_an_extension.md 
 *    https://lab.civicrm.org/partners/civicoop/myemailprefs 
 */
class ExampleFormAction extends AbstractAction {

  /**
   * @return SpecificationBag
   */
  public function getParameterSpecification() {
    return new SpecificationBag([
      new Specification('contact_id', 'Integer', E::ts('Contact ID'), TRUE, NULL),
      new Specification('name', 'dataType', E::ts('Title'), $required, $defaultValue, $fkEntity, $options, $multiple),
      // Add more output fields as needed`
    ]);
  }

  /**
   * @return SpecificationBag
   */
  public function getConfigurationSpecification() {
    return new SpecificationBag([
      new Specification('configuration_field', 'Integer', E::ts('mas: example config field'), FALSE),
      // Add more output fields as needed`
    ]);
  }

/**
 * @return SpecificationBag
 */
public function getOutputSpecification() {
  return new SpecificationBag([
    new Specification('output_field', 'Boolean', E::ts('mas: example output field'), FALSE),
    // Add more output fields as needed`
  ]);
}

  /**
   * @param ParameterBagInterface $parameters
   * @param ParameterBagInterface $output
   * @throws InvalidParameterException
   */
  public function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {
    $contactId = (int) $parameters->getParameter('contact_id');
    if ($contactId) {
      // do stuff
    }
    else {
      throw new InvalidParameterException(E::ts("Could not find mandatory parameter contact_id"));
    }
  }
}
