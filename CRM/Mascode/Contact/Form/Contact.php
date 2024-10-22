<?php

// For contacts, remove legal name, nickname, sic code, bulk mailings, instant messanger, IM location, IM type add formerly known as

class CRM_Mascode_Contact_Form_Contact
{
    public static function buildForm(&$form)
    {
        $hide = ['nick_name', 'im[1]'];

        foreach ($hide as $field_id) {
            if ($form->elementExists($field_id)) {
                $form->removeElement($field_id);
            }
        }
        // if it is a group, should i do $form->getElement(xxx)->freeze() instead??
    }
}
