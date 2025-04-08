<?php

namespace Civi\Mascode\Hook;

use CRM_Core_Resources;

class BuildFormHook
{
  public static function handle($formName, &$form)
  {
    if (in_array($formName, ['CRM_Contact_Form_Edit_Organization'])) {
      CRM_Core_Resources::singleton()->addScript("
      cj(function($) {
        $('form').on('submit', function() {
          var urlField = $('input[name=\"url\"]');
          var url = urlField.val().trim();
          if (url && !/^https?:\\/\\//i.test(url)) {
            urlField.val('http://' + url);
          }
        });
      });
    ");
    }
  }
}
