<?php

namespace Civi\Mascode\Utils;

class CodeGenerator
{
    public static function generate($caseType)
    {
        $year = date('y');
        $seq = self::getSequence($year);
        $returnValue = $year . str_pad($seq, 3, '0', STR_PAD_LEFT);
        if ($caseType == 'Service Request') {
            $returnValue = 'R' . $returnValue;
        }
        return $returnValue;
    }

    private function getSequence($year)
    {
        $groupName = 'mascode_year_sequence';

        try {
            // Get the OptionValue record using APIv4
            $row = \Civi\Api4\OptionValue::get()
                ->addWhere('option_group_id:name', '=', $groupName)
                ->addWhere('name', '=', $year)
                ->setLimit(1)
                ->execute()
                ->first();

            // Update the value
            \Civi\Api4\OptionValue::update()
                ->addValue('value', $row['value'] + 1)
                ->addWhere('id', '=', $row['id'])
                ->execute();

            return $row['value'] + 1;
        } catch (\Exception $e) {
            // Create the OptionValue if it doesn't exist
            \Civi\Api4\OptionValue::create()
                ->addValue('option_group_id:name', $groupName)
                ->addValue('name', $year)
                ->addValue('label', "Sequence for $year")
                ->addValue('value', 1)
                ->execute();
            return 1;
        }
    }


    public static function getFieldId($groupName, $label)
    {
        try {
            // Get the CustomGroup by name
            $group = \Civi\Api4\CustomGroup::get()
                ->addWhere('name', '=', $groupName)
                ->setLimit(1)
                ->execute()
                ->first();

            if (!$group) {
                return null;
            }

            // Get the CustomField by label within that group
            $field = \Civi\Api4\CustomField::get()
                ->addWhere('custom_group_id', '=', $group['id'])
                ->addWhere('label', '=', $label)
                ->setLimit(1)
                ->execute()
                ->first();

            return $field ? $field['id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
