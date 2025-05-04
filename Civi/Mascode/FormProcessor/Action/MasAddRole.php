<?php

  /**
   * This is a clone of ActionProvider\Action\CiviCase\AddRole.php
   * removing the authorization check.  If we got this far, we are authorized.
   */

namespace Civi\Mascode\FormProcessor\Action;

use \Civi\ActionProvider\Action\AbstractAction;
use Civi\ActionProvider\Parameter\OptionGroupSpecification;
use \Civi\ActionProvider\Parameter\ParameterBagInterface;
use \Civi\ActionProvider\Parameter\SpecificationBag;
use \Civi\ActionProvider\Parameter\Specification;

use Civi\ActionProvider\Utils\CiviCase;
use CRM_Mascode_ExtensionUtil as E;

class MasAddRole extends AbstractAction {

  private function relationshipTypes(){
    $options = [];
    $result = civicrm_api3('RelationshipType', 'get', [
      'sequential' => 1,
      'return' => ["id", "label_a_b", "label_b_a"],
      'options' => ['limit' => 0]
    ]);
    foreach($result['values'] as $value) {
      $options["${value['id']}_a_b"]="{$value['label_a_b']} (a->b)";
      $options["${value['id']}_b_a"]="{$value['label_b_a']} (b->a)";
    }
    uasort($options, function($a, $b) {
      return $a>=$b;
    });
    return $options;
  }

  /**
   * Returns the specification of the configuration options for the action.
   *
   * @return SpecificationBag
   */
  public function getConfigurationSpecification() {
    /**
     * The parameters given to the Specification object are:
     *
     * @param string $name
     * @param string $dataType
     * @param string $title
     * @param bool $required
     * @param mixed $defaultValue
     * @param string|null $fkEntity
     * @param array $options
     * @param bool $multiple
     */
    return new SpecificationBag(
      [
        new Specification('relationship_type', 'String', E::ts('mas: RelationShip'), TRUE, NULL, NULL,$this->relationshipTypes(), FALSE),
      ]
    );
  }

  /**
   * Returns the specification of the configuration options for the action.
   *
   * @return SpecificationBag
   */
  public function getParameterSpecification() {
    return new SpecificationBag([
      new Specification('contact_id', 'Integer', E::ts('Contact ID'), TRUE, NULL, NULL, NULL, FALSE),
      new Specification('case_id', 'Integer', E::ts('Case ID'), TRUE, NULL, 'Contact', NULL, FALSE),
    ]);
  }

  /**
   * Returns the specification of the output parameters of this action.
   *
   * @return SpecificationBag
   */
  public function getOutputSpecification() {
    return new SpecificationBag(
      [new Specification('relation_ship_id', 'Integer', E::ts('Relationship ID'), FALSE)]
    );
  }

  /**
   * Run the action
   *
   * @param ParameterInterface $parameters
   *   The parameters to this action.
   * @param ParameterBagInterface $output
   *   The parameters this action can send back
   *
   * @return void
   */
  protected function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {
    // Get the contact.
    $contact_id = $parameters->getParameter('contact_id');
    $case_id = $parameters->getParameter('case_id');
    $relationshipType = $this->configuration->getParameter('relationship_type');
    CiviCase::relationship($relationshipType,$contact_id,$case_id);
  }
}